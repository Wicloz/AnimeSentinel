Template.registerHelper('$getStorageItem', (key) => {
  return getStorageItem(key);
});

Template.registerHelper('$dot', (object, key) => {
  return tryGetProperty(object, key);
});

Template.registerHelper('$in', (item, ...list) => {
  list.pop();
  if (list.length === 1 && _.isArray(list[0])) {
    list = list[0];
  }
  return list.includes(item);
});

Template.registerHelper('$nin', (item, ...list) => {
  list.pop();
  if (list.length === 1 && _.isArray(list[0])) {
    list = list[0];
  }
  return !list.includes(item);
});

Template.registerHelper('$firstActive', (index, start) => {
  if (!isNumeric(start)) {
    start = 0;
  }
  return index === start ? 'active' : '';
});

Template.registerHelper('$addOne', (value) => {
  return value + 1;
});

Template.registerHelper('$capitalize', (string) => {
  return string.capitalize();
});
