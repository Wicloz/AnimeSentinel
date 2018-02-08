import './image.html';

Template.components_image.helpers({
  getAppeared() {
    return Template.instance().state.get('appeared');
  },

  getId() {
    return Template.instance().state.get('id');
  },
});

Template.components_image.events({
  'appear .image-detector'(event) {
    if ($(event.target).attr('id') === 'image-detector-' + Template.instance().state.get('id')) {
      Template.instance().state.set('appeared', true);
    }
  }
});

Template.components_image.onCreated(function () {
  this.state = new ReactiveDict();
  this.state.setDefault({
    appeared: false,
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
    if (this.state.get('appeared') && Template.currentData().class.split(' ').includes('materialboxed')) {
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
