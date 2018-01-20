import './show.html';
import {Shows} from '/imports/api/shows/shows.js';

Template.pages_show.onCreated(function() {
  this.autorun(() => {
    this.subscribe('shows.withId', FlowRouter.getParam('showId'));
  });
});

Template.pages_show.helpers({
  show() {
    return Shows.findOne(FlowRouter.getParam('showId'));
  }
});
