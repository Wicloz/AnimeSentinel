import './image.html';

Template.components_image.helpers({
  showImage() {
    return Template.instance().state.get('appeared') && Template.currentData().src !== '/media/spinner.gif';
  },

  showLoading() {
    return !Template.instance().state.get('loaded') || Template.currentData().src === '/media/spinner.gif';
  },

  getId() {
    return Template.instance().state.get('id');
  },

  getSrc() {
    return Template.instance().state.get('src');
  }
});

Template.components_image.events({
  'appear .img-detector'(event) {
    if ($(event.target).attr('id') === 'img-detector-' + Template.instance().state.get('id')) {
      Template.instance().state.set('appeared', true);
    }
  },

  'load .img-lazy'(event) {
    if ($(event.target).attr('id') === Template.instance().state.get('id')) {
      Template.instance().state.set('loaded', true);
    }
  }
});

Template.components_image.onCreated(function () {
  // Local variables
  this.state = new ReactiveDict();
  this.state.setDefault({
    appeared: false,
    loaded: false,
    id: undefined,
    src: Template.currentData().src
  });

  // When the id changes
  this.autorun(() => {
    if (Template.currentData().id) {
      this.state.set('id', Template.currentData().id);
    } else if (!this.state.get('id')) {
      this.state.set('id', createUniqueId());
    }
  });
});

Template.components_image.onRendered(function () {
  // When the id changes
  this.autorun(() => {
    $('#img-detector-' + this.state.get('id')).appear();
  });

  // When the src changes
  this.autorun(() => {
    if (Template.currentData().src !== this.state.get('src')) {
      this.state.set('appeared', false);
      this.state.set('loaded', false);
      this.state.set('src', Template.currentData().src);
      Tracker.nonreactive(() => {
        $.force_appear();
      })
    }
  });
});
