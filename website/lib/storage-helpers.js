import * as RLocalStorage from 'meteor/simply:reactive-local-storage';

const localStoragePrefix = '_AS';

setStorageItem = function(key, value) {
  if (!_.isArray(key)) {
    key = [key];
  }

  if (Meteor.user()) {
    Meteor.call('users.setCurrentUserStorageItem', key, value);
  }
  RLocalStorage.setItem(localStoragePrefix + '.' + key.join('.'), value);
};

removeStorageItem = function(key) {
  if (!_.isArray(key)) {
    key = [key];
  }

  if (Meteor.user()) {
    Meteor.call('users.removeCurrentUserStorageItem', key);
  }
  RLocalStorage.removeItem(localStoragePrefix + '.' + key.join('.'));
};

getStorageItem = function(key) {
  if (!_.isArray(key)) {
    key = [key];
  }

  if (Meteor.user()) {
    return Meteor.user().getStorageItem(key);
  } else {
    let value = RLocalStorage.getItem(localStoragePrefix + '.' + key.join('.'));
    return value === null ? undefined : value;
  }
};
