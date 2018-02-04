import './carousel.html';

Template.components_carousel.helpers({
  getLoaded() {
    return Template.instance().state.get('loaded');
  },

  getId() {
    return Template.instance().state.get('id');
  },
});

Template.components_carousel.events({
  'appear .carousel-detector'(event) {
    if ($(event.target).attr('id') === 'carousel-detector-' + Template.instance().state.get('id')) {
      Template.instance().state.set('loaded', true);
    }
  }
});

Template.components_carousel.onCreated(function () {
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
    if (this.state.get('loaded')) {
      let temp = Template.currentData().images;
      Tracker.afterFlush(() => {
        let carouselElement = $('#' + this.state.get('id'));
        carouselElement.carousel('destroy');
        carouselElement.carousel();
      });
    }
  });
});

Template.components_carousel.onRendered(function () {
  $('#carousel-detector-' + this.state.get('id')).appear();
  $.force_appear();
});
