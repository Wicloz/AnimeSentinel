import './image.html';

Template.components_image.helpers({
  getLoaded() {
    return Template.instance().state.get('loaded');
  },

  getId() {
    return Template.instance().state.get('id');
  },
});

Template.components_image.events({
  'appear .image-detector'(event) {
    if ($(event.target).attr('id') === 'image-detector-' + Template.instance().state.get('id')) {
      Template.instance().state.set('loaded', true);
    }
  }
});

Template.components_image.onCreated(function () {
  this.state = new ReactiveDict();
  this.state.setDefault({
    loaded: false,
    id: undefined
  });

  this.autorun(() => {
    if (Template.currentData().id) {
      this.state.set('id', Template.currentData().id);
    } else {
      this.state.set('id', createUniqueId());
    }
  });

  this.autorun(() => {
    if (this.state.get('loaded') && Template.currentData().class.split(' ').includes('materialboxed')) {
      let temp = Template.currentData().caption;
      Tracker.afterFlush(() => {
        $('#' + this.state.get('id')).materialbox();
      });
    }
  });
});

Template.components_image.onRendered(function () {
  $('#image-detector-' + this.state.get('id')).appear();
  $.force_appear();
});
