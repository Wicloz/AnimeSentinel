Meteor.autorun(() => {
  if (Meteor.user() && typeof Meteor.user().storage !== 'undefined') {
    let stack = [[[], Meteor.user().storage]];

    while (!stack.empty()) {
      let item = stack.pop();

      Object.keys(item[1]).forEach((key) => {
        let value = item[1][key];
        let fullKey = item[0].concat(key);

        if (_.isObject(value)) {
          stack.push([fullKey, value])
        } else {
          setStorageItem(fullKey, value);
        }
      });
    }
  }
});
