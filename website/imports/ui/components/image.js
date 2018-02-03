import './image.html';

Template.components_image.helpers({
  loaded() {
    return Template.instance().loaded.get();
  }
});

Template.components_image.events({
  'appear .image-detector'(event) {
    if ($(event.target).attr('id') === 'image-detector-' + Template.instance().data.id) {
      Template.instance().loaded.set(true);
    }
  }
});

Template.components_image.onCreated(function () {
  this.loaded = new ReactiveVar(false);

  if (!this.data.id) {
    this.data.id = createUniqueId();
  }

  this.autorun(() => {
    if (this.loaded.get()) {
      Tracker.afterFlush(() => {
        if (this.data.class.includes('materialboxed')) {
          $('#' + this.data.id).materialbox();
        }
      });
    }
  });
});

Template.components_image.onRendered(function () {
  $('#image-detector-' + this.data.id).appear();
  $.force_appear();
});
