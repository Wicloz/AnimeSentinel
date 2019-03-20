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

Template.findState = function(scope) {
  let instance = Template.instance() || scope;
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
