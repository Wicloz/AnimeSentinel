import './show.html';
import {Shows} from '/imports/api/shows/shows.js';
import '/imports/ui/components/loadingIndicatorBackground.js';
import {Episodes} from "../../api/episodes/episodes";

Template.pages_show.onCreated(function() {
  this.autorun(() => {
    this.subscribe('shows.withId', FlowRouter.getParam('showId'));
    if (this.subscriptionsReady()) {
      if (!Shows.findOne(FlowRouter.getParam('showId'))) {
        FlowRouter.go('notFound');
      }
      Session.set('PageTitle', Shows.findOne(FlowRouter.getParam('showId')).name);
      Meteor.call('shows.attemptUpdate', FlowRouter.getParam('showId'));
    }
  });

  this.autorun(() => {
    this.subscribe('episodes.forShow', FlowRouter.getParam('showId'));
    if (this.subscriptionsReady()) {
      Episodes.queryForShow(FlowRouter.getParam('showId')).forEach((episode) => {
        Meteor.call('episodes.attemptUpdate', episode._id);
      });
    }
  });
});

Template.pages_show.helpers({
  show() {
    return Shows.findOne(FlowRouter.getParam('showId'));
  },

  updating() {
    let show = Shows.findOne(FlowRouter.getParam('showId'));
    let busy = show && show.locked();

    Episodes.queryForShow(FlowRouter.getParam('showId')).forEach((episode) => {
      busy = busy || episode.locked();
    });

    return busy;
  },

  episodes() {
    return Episodes.queryForShow(FlowRouter.getParam('showId'));
  }
});
