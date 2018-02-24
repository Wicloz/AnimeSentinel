import './image.html';

Template.components_image.helpers({
  getAppeared() {
    return Template.instance().state.get('appeared');
  },

  getNotLoaded() {
    return !Template.instance().state.get('loaded');
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
});

Template.components_image.onRendered(function () {
  this.autorun(() => {
    if (this.state.get('appeared')) {
      let temp = Template.currentData().src;
      Tracker.afterFlush(() => {
        let img = $('#' + this.state.get('id')).get(0);
        if (img) {
          if (img.complete) {
            this.state.set('loaded', true);
          } else {
            this.state.set('loaded', false);
            img.addEventListener('load', () => {
              this.state.set('loaded', true);
            });
          }
        }
      });
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

  $('#image-detector-' + this.state.get('id')).appear();
  $.force_appear();
});
