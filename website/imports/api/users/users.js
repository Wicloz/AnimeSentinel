import SimpleSchema from 'simpl-schema';
import {Searches} from '../searches/searches'; // REQUIRED TO FIX IMPORT CHAINS
import {WatchStates} from '../watchstates/watchstates';
import {Shows} from '../shows/shows';
import Streamers from '../../streamers/streamers';

// Schema
Schemas.User = new SimpleSchema({
  _id: {
    type: String,
    optional: true
  },
  createdAt: {
    type: Date
  },
  services: {
    type: Object,
    blackbox: true
  },

  storage: {
    type: Object,
    blackbox: true,
    defaultValue: {}
  },

  emails: {
    type: Array,
    minCount: 1
  },
  'emails.$': {
    type: Object
  },
  'emails.$.address': {
    type: String,
    regEx: SimpleSchema.RegEx.EmailWithTLD,
    index: true,
    unique: true,
    sparse: true
  },
  'emails.$.verified': {
    type: Boolean
  },

  username: {
    type: String,
    index: true,
    unique: true,
    sparse: true
  },

  profile: {
    type: Object,
    defaultValue: {}
  },
  'profile.malUsername': {
    type: String,
    optional: true,
    label: 'MAL Username'
  },
  'profile.malPassword': {
    type: String,
    optional: true,
    label: 'MAL Password',
    autoform: {
      type: 'password'
    }
  },

  malSessionId1: {
    type: String,
    optional: true
  },
  malSessionId2: {
    type: String,
    optional: true
  },
  malTokenCSRF: {
    type: String,
    optional: true
  },
  malCanRead: {
    type: Boolean,
    defaultValue: false
  },
  malCanWrite: {
    type: Boolean,
    defaultValue: false
  }
}, { tracker: Tracker });

Schemas.UserClient = new SimpleSchema({
  'emails': Schemas.User._schema['emails'],
  'emails.$': Schemas.User._schema['emails.$'],
  'emails.$.address': Schemas.User._schema['emails.$.address'],
  'username': Schemas.User._schema['username'],
  'profile': Schemas.User._schema['profile'],
  'profile.malUsername': Schemas.User._schema['profile.malUsername'],
  'profile.malPassword': Schemas.User._schema['profile.malPassword'],
}, { tracker: Tracker });

Meteor.users.attachSchema(Schemas.User);
Meteor.users.deny({
  update() { return true; }
});

// Helpers
Meteor.users.helpers({
  changeInfo(newInfo) {
    let oldEmailAddresses = this.emails.pluck('address');
    let oldMalUsername = this.profile.malUsername;

    // Mark changed email addresses as unverified
    newInfo.emails = newInfo.emails.map((email) => {
      if (oldEmailAddresses.includes(email.address)) {
        email.verified = this.emails.getPartialObjects({
          address: email.address
        })[0].verified;
      } else {
        email.verified = false;
      }
      return email;
    });

    // Update this
    Object.keys(newInfo).forEach((key) => {
      this[key] = newInfo[key];
    });

    // Update database
    Meteor.users.update(this._id, {
      $set: Schemas.User.clean(this, {
        mutate: true
      })
    });

    // Send verification mails
    newInfo.emails.forEach((email) => {
      if (Meteor.isServer && !oldEmailAddresses.includes(email.address)) {
        Accounts.sendVerificationEmail(this._id, email.address);
      }
    });

    // Update watch states if MAL name changed
    if (oldMalUsername !== newInfo.profile.malUsername) {
      this.updateWatchStates(true);
    }
  },

  setStorageItem(key, value) {
    this.storage[key] = value;
    Meteor.users.update(this._id, {
      $set: {
        storage: this.storage
      }
    });
  },

  removeStorageItem(key) {
    delete this.storage[key];
    Meteor.users.update(this._id, {
      $set: {
        storage: this.storage
      }
    });
  },

  getStorageItem(key) {
    return this.storage[key];
  },

  setMalCanReadWrite(canRead, canWrite) {
    // Modify this
    if (typeof canRead !== 'undefined') {
      this.malCanRead = canRead;
    }
    if (typeof canWrite !== 'undefined') {
      this.malCanWrite = canWrite;
    }

    // Modify database
    Meteor.users.update(this._id, {
      $set: {
        malCanRead: this.malCanRead,
        malCanWrite: this.malCanWrite,
      }
    });
  },

  updateWatchStates(userNameChanged=false) {
    if (!userNameChanged && !this.malCanRead) {
      // Can't get MAL list
      return;
    }
    if (userNameChanged) {
      // User name has changed
      WatchStates.remove({
        userId: this._id
      });
    }
    if (!this.profile.malUsername) {
      // No MAL username
      this.setMalCanReadWrite(false, false);
      return;
    }

    let baseUrl = 'https://myanimelist.net/animelist/' + encodeURIComponent(this.profile.malUsername) + '/load.json?offset=';
    let offset = 0;
    let entries = [];

    let temp = () => {
      startDownloadWithCallback(baseUrl + offset, (json) => {
        if (json) {
          json = JSON.parse(json);
          // Get all entries
          entries = entries.concat(json);
          if (entries.length > offset) {
            offset = entries.length;
            temp();
          } else {

            this.setMalCanReadWrite(true, undefined);
            let malIds = [];

            entries.forEach((entry, index) => {
              // Add the show
              try {
                let show = Streamers.convertCheerioToShow(entry, entries, Streamers.getStreamerById('myanimelist'), 'showApi');
                if (show) {
                  Shows.addPartialShow(show);
                }
              } catch (e) {
                console.error('Failed to process show api page for user: \'' + this.profile.malUsername + '\' and streamer: \'myanimelist\'.');
                console.error('Failed to process entry number ' + index + '.');
                console.error(e);
              }

              // Add the watch state
              let watchState = {
                userId: this._id,
                malId: entry.anime_id,

                status: entry.status,
                episodesWatched: entry.num_watched_episodes,
                rewatching: entry.is_rewatching === 1,
                score: entry.score === 0 ? undefined : entry.score,
              };

              if (watchState.rewatching) {
                watchState.status = 'watching'
              } else if (watchState.status === 6) {
                watchState.status = WatchStates.validStatuses[4];
              } else {
                watchState.status = WatchStates.validStatuses[watchState.status - 1];
              }

              Schemas.WatchState.clean(watchState, {
                mutate: true
              });
              Schemas.WatchState.validate(watchState);

              malIds.push(watchState.malId);
              WatchStates.addWatchState(watchState);
            });

            // Remove missing watch states
            WatchStates.remove({
              userId: this._id,
              malId: {
                $nin: malIds
              }
            });

          }
        } else {
          // Invalid MAL username
          this.setMalCanReadWrite(false, false);
        }
      });
    };
    temp();
  }
});

// Methods
Meteor.methods({
  'users.changeCurrentUserInfo'(newInfo) {
    Schemas.UserClient.validate(newInfo);
    Meteor.users.findOne(this.userId).changeInfo(newInfo);
  },

  'users.setCurrentUserStorageItem'(key, value) {
    new SimpleSchema({
      key: String
    }).validate({key});
    Meteor.users.findOne(this.userId).setStorageItem(key, value);
  },

  'users.removeCurrentUserStorageItem'(key) {
    new SimpleSchema({
      key: String
    }).validate({key});
    Meteor.users.findOne(this.userId).removeStorageItem(key);
  },

  'users.updateCurrentUserWatchStates'() {
    Meteor.users.findOne(this.userId).updateWatchStates();
  }
});
