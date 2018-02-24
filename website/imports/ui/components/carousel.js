import './carousel.html';

Template.components_carousel.helpers({
  getAppeared() {
    return Template.instance().state.get('appeared');
  },

  getId() {
    return Template.instance().state.get('id');
  },
});

Template.components_carousel.events({
  'appear .carousel-detector'(event) {
    if ($(event.target).attr('id') === 'carousel-detector-' + Template.instance().state.get('id')) {
      Template.instance().state.set('appeared', true);
    }
  }
});

Template.components_carousel.onCreated(function () {
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
});

Template.components_carousel.onRendered(function () {
  this.autorun(() => {
    if (this.state.get('appeared')) {
      let temp = Template.currentData().images;
      Tracker.afterFlush(() => {
        let carouselElement = $('#' + this.state.get('id'));
        carouselElement.carousel('destroy');
        carouselElement.carousel();
      });
    }
  });

  $('#carousel-detector-' + this.state.get('id')).appear();
  $.force_appear();
});
