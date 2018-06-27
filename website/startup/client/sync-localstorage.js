Tracker.autorun(() => {
  if (Meteor.user() && typeof Meteor.user().storage !== 'undefined') {
    let userStorageKeys = Object.keys(Meteor.user().storage);

    Object.keys(localStorageCopy()).forEach((key) => {
      if (!userStorageKeys.includes(key)) {
        removeStorageItem(key, true);
      }
    });

    userStorageKeys.forEach((key) => {
      setStorageItem(key, Meteor.user().storage[key], true);
    });
  }
});
