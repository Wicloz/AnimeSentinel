import './image.html';

Template.components_image.helpers({
  showImage() {
    return Template.findState(this).get('appeared') && Template.currentData().src !== '/media/spinner.svg';
  },

  showLoading() {
    return !Template.findState(this).get('loaded') || Template.currentData().src === '/media/spinner.svg';
  },

  getId() {
    return Template.findState(this).get('id');
  }
});

Template.components_image.events({
  'appear .img-detector'(event) {
    if ($(event.target).attr('id') === 'img-detector-' + Template.findState(this).get('id')) {
      Template.findState(this).set('appeared', true);
    }
  },

  'load .img-lazy'(event) {
    if ($(event.target).attr('id') === Template.findState(this).get('id')) {
      Template.findState(this).set('loaded', true);
    }
  }
});

Template.components_image.onCreated(function () {
  // Local variables
  Template.makeState({
    appeared: false,
    loaded: false,
    id: undefined
  });

  // When the id changes
  this.autorun(() => {
    if (Template.currentData().id) {
      Template.findState(this).set('id', Template.currentData().id);
    } else if (!Template.findState(this).get('id')) {
      Template.findState(this).set('id', createUniqueId());
    }
  });
});

Template.components_image.onRendered(function () {
  // When the src changes
  this.srcOld = undefined;
  this.autorun(() => {
    if (this.srcOld !== Template.currentData().src) {
      Template.findState(this).set('appeared', false);
      Template.findState(this).set('loaded', false);
      Tracker.afterFlush(() => {
        $('#img-detector-' + Template.findState(this).get('id')).appear();
        $.force_appear();
      });
      this.srcOld = Template.currentData().src;
    }
  });
});
