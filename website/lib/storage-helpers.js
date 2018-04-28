import * as RLocalStorage from 'meteor/simply:reactive-local-storage';

const localStoragePrefix = '_AS.';

setStorageItem = function(key, value, localOnly=false) {
  if (_.isArray(key)) {
    key = key.join(' - ');
  }

  if (Meteor.user() && !localOnly) {
    Meteor.call('users.setCurrentUserStorageItem', key, value);
  } else {
    RLocalStorage.setItem(localStoragePrefix + key, value);
  }
};

removeStorageItem = function(key, localOnly=false) {
  if (_.isArray(key)) {
    key = key.join(' - ');
  }

  if (Meteor.user() && !localOnly) {
    Meteor.call('users.removeCurrentUserStorageItem', key);
  } else {
    RLocalStorage.removeItem(localStoragePrefix + key);
  }
};

getStorageItem = function(key, localOnly=false) {
  if (_.isArray(key)) {
    key = key.join(' - ');
  }

  if (Meteor.user() && !localOnly) {
    return Meteor.user().getStorageItem(key);
  } else {
    let value = RLocalStorage.getItem(localStoragePrefix + key);
    return value === null ? undefined : value;
  }
};

localStorageCopy = function() {
  let index = 0;
  let allItems = {};

  while (true) {
    let key = RLocalStorage.key(index);
    if (key === null) {
      break;
    }

    else if (key.startsWith(localStoragePrefix)) {
      key = key.replace(localStoragePrefix, '');
      allItems[key] = getStorageItem(key);
    }

    index++;
  }

  return allItems;
};
