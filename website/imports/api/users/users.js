import SimpleSchema from 'simpl-schema';

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
    regEx: SimpleSchema.RegEx.EmailWithTLD
  },
  'emails.$.verified': {
    type: Boolean
  },

  username: {
    type: String
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
    key.reduce((current, next, index) => {
      if (index === key.length - 1) {
        current[next] = value;
      } else if (typeof current[next] === 'undefined') {
        current[next] = {};
      }
      return current[next];
    }, this.storage);

    Meteor.users.update(this._id, {
      $set: {
        storage: this.storage
      }
    });
  },

  removeStorageItem(key) {
    while (!key.empty()) {
      key.reduce((current, next, index) => {
        if (typeof current === 'undefined') {
          return undefined;
        }
        if (index === key.length - 1 && (!_.isObject(current[next]) || _.isEmpty(current[next]))) {
          delete current[next];
        }
        return current[next];
      }, this.storage);
      key.pop();
    }

    Meteor.users.update(this._id, {
      $set: {
        storage: this.storage
      }
    });
  },

  getStorageItem(key) {
    return key.reduce((current, next) => {
      return typeof current === 'undefined' ? undefined : current[next];
    }, this.storage);
  }
});

Meteor.users.changeUserInfo = function(userId, newInfo) {
  Schemas.id.validate({
    id: userId
  });
  Schemas.UserClient.validate(newInfo);

  let user = Meteor.users.findOne(userId);
  user.changeInfo(newInfo);
};

// Methods
Meteor.methods({
  'users.changeCurrentUserInfo'(user) {
    Meteor.users.changeUserInfo(this.userId, user);
  },

  'users.setCurrentUserStorageItem'(key, value) {
    new SimpleSchema({
      key: Array,
      'key.$': String
    }).validate({key});
    Meteor.users.findOne(this.userId).setStorageItem(key, value);
  },

  'users.removeCurrentUserStorageItem'(key) {
    new SimpleSchema({
      key: Array,
      'key.$': String
    }).validate({key});
    Meteor.users.findOne(this.userId).removeStorageItem(key);
  }
});
