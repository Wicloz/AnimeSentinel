Blaze.TemplateInstance.prototype.parentTemplate = function(levels) {
  let view = this.view;
  if (typeof levels === 'undefined') {
    levels = 1;
  }
  while (view) {
    if (view.name.substring(0, 9) === 'Template.' && !(levels--)) {
      return view.templateInstance();
    }
    view = view.parentView;
  }
};

Template.parentInstance = function(levels) {
  return Template.instance().parentTemplate(levels);
};

Template.findState = function() {
  let instance = Template.instance();
  while (instance && !instance.hasOwnProperty('state')) {
    instance = instance.parentTemplate();
  }
  return instance.state;
};

Template.makeState = function(content) {
  let instance = Template.instance();
  instance.state = new ReactiveDict();
  instance.state.setDefault(content);
};

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
