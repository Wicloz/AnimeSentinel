import SimpleSchema from 'simpl-schema';
import {Searches} from '../searches/searches';

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

// Helpers
Meteor.users.helpers({
  changeInfo(newInfo) {
    let oldEmailAddresses = this.emails.pluck('address');

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
  }
});
