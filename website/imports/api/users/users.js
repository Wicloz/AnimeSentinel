import SimpleSchema from 'simpl-schema';
import {Searches} from '../searches/searches'; // REQUIRED TO FIX IMPORT CHAINS
import {WatchStates} from '../watchstates/watchstates';
import {Shows} from '../shows/shows';
import Streamers from '../../streamers/streamers';
import request from 'request';
import Cheerio from 'cheerio';
import moment from 'moment-timezone';

// Constants
const badLoginError = 'Your username or password is incorrect.';

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
  },
  lastMalUpdateStart: {
    type: Date,
    optional: true
  },
  lastMalUpdateEnd: {
    type: Date,
    optional: true
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

// Constants
Meteor.users.maxMalUpdateTime = 600000; // 10 minutes
if (Meteor.isDevelopment) {
  Meteor.users.maxMalUpdateTime = 60000; // 60 seconds
}

// Helpers
Meteor.users.helpers({
  malListUpdating() {
    if (Meteor.isClient) {
      invalidateSecond.depend();
    }
    return this.lastMalUpdateStart
      && (!this.lastMalUpdateEnd || this.lastMalUpdateStart > this.lastMalUpdateEnd)
      && moment.fromUtc(this.lastMalUpdateStart).add(Meteor.users.maxMalUpdateTime).isAfter();
  },

  changeInfo(newInfo) {
    let oldEmailAddresses = this.emails.pluck('address');
    let oldMalUsername = this.profile.malUsername;
    let oldMalPassword = this.profile.malPassword;

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

    // Update MAL connection if login info changed
    if (oldMalUsername !== newInfo.profile.malUsername || oldMalPassword !== newInfo.profile.malPassword) {
      this.updateMalConnection();
    }
  },

  async updateMalConnection() {
    this.setMalCanReadWrite(false, false);
    this.setMalCanReadWrite(true, true);

    if (this.profile.malUsername && this.profile.malPassword) {
      try {
        await this.updateMalSession();
      } catch (e) {}
    } else {
      this.setMalCanReadWrite(undefined, false);
    }

    if (this.profile.malUsername) {
      this.updateWatchStates();
    } else {
      this.setMalCanReadWrite(false, undefined);
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
    // Update statuses
    if (typeof canRead !== 'undefined') {
      this.malCanRead = canRead;
    }
    if (typeof canWrite !== 'undefined') {
      this.malCanWrite = canWrite;
    }

    // Send to database
    Meteor.users.update(this._id, {
      $set: {
        malCanRead: this.malCanRead,
        malCanWrite: this.malCanWrite,
      }
    });

    // Clear watch states if they don't work
    if (canRead === false) {
      WatchStates.remove({
        userId: this._id
      });
    }

    // Clear tokens if they aren't needed
    if (canWrite === false) {
      delete this.malSessionId1;
      delete this.malSessionId2;
      delete this.malTokenCSRF;
      Meteor.users.update(this._id, {
        $unset: {
          malSessionId1: true,
          malSessionId2: true,
          malTokenCSRF: true,
        }
      });
    }
  },

  getMalCookieJar() {
    let jar = request.jar();
    jar.setCookie(request.cookie('MALSESSIONID=' + this.malSessionId1), 'https://myanimelist.net');
    jar.setCookie(request.cookie('MALHLOGSESSID=' + this.malSessionId2), 'https://myanimelist.net');
    jar.setCookie(request.cookie('is_logged_in=1'), 'https://myanimelist.net');
    return jar;
  },

  updateMalSession() {
    return new Promise((resolve, reject) => {
      // Make empty cookie jar and CSRF token
      let jar = request.jar();
      let tokenCSRF = '';

      // Get CSRF token
      rp('GET', {
        url: 'https://myanimelist.net/login.php',
        jar: jar,
      })

      // Send login request
      .then((body) => {
        tokenCSRF = Cheerio.load(body)('meta[name=csrf_token]').attr('content');
        return rp('POST', {
          url: 'https://myanimelist.net/login.php',
          jar: jar,
          form: {
            'user_name': this.profile.malUsername,
            'password': this.profile.malPassword,
            'csrf_token': tokenCSRF,
            'submit': 1,
          },
        }, [badLoginError, 'Too many failed login attempts.']);
      })

      // On successful login
      .then(() => {
        jar.getCookies('https://myanimelist.net').forEach((cookie) => {
          if (cookie.key === 'MALSESSIONID') {
            this.malSessionId1 = cookie.value;
          } else if (cookie.key === 'MALHLOGSESSID') {
            this.malSessionId2 = cookie.value;
          }
        });
        this.malTokenCSRF = tokenCSRF;
        Meteor.users.update(this._id, {
          $set: {
            malSessionId1: this.malSessionId1,
            malSessionId2: this.malSessionId2,
            malTokenCSRF: this.malTokenCSRF,
          }
        });
        this.setMalCanReadWrite(undefined, true);
        resolve();
      })

      // When something goes wrong
      .catch((error) => {
        if (error === badLoginError) {
          this.setMalCanReadWrite(undefined, false);
        } else if (error) {
          console.error(error);
        }
        reject(error);
      });
    });
  },

  updateWatchStates() {
    if (!this.malListUpdating()) {
      // Mark update as started
      this.lastMalUpdateStart = moment.fromUtc().toDate();
      Meteor.users.update(this._id, {
        $set: {
          lastMalUpdateStart: this.lastMalUpdateStart
        }
      });

      let baseUrl = 'https://myanimelist.net/animelist/' + encodeURIComponent(this.profile.malUsername) + '/load.json?offset=';
      let offset = 0;
      let entries = [];

      let finishCallback = () => {
        // Mark update as done
        this.lastMalUpdateEnd = moment.fromUtc().toDate();
        Meteor.users.update(this._id, {
          $set: {
            lastMalUpdateEnd: this.lastMalUpdateEnd
          }
        });
      };

      let doneCallback = () => {
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

            priority: ['Low', 'Medium', 'High'].indexOf(entry.priority_string),
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
          WatchStates.addWatchState(watchState, true);
        });

        // Remove missing watch states
        WatchStates.remove({
          userId: this._id,
          malId: {
            $nin: malIds
          }
        });

        // Finish
        finishCallback();
      };

      let repeatCallback = () => {
        rp('GET', {
          url: baseUrl + offset,
          jar: this.getMalCookieJar(),
        })
          .then((json) => {
            entries = entries.concat(JSON.parse(json));
            if (entries.length > offset) {
              offset = entries.length;
              repeatCallback();
            } else {
              doneCallback();
            }
          })
          .catch((error) => {
            if (error === 400) {
              if (this.malCanWrite) {
                this.updateMalSession().then(repeatCallback).catch((error) => {
                  if (error === badLoginError) {
                    this.setMalCanReadWrite(false, undefined);
                  }
                  finishCallback();
                });
              } else {
                this.setMalCanReadWrite(false, undefined);
                finishCallback();
              }
            } else {
              if (error) {
                console.error(error);
              }
              finishCallback();
            }
          });
      };

      repeatCallback();
    }
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
