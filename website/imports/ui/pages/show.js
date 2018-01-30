import './show.html';
import {Shows} from '/imports/api/shows/shows.js';
import '/imports/ui/components/loadingIndicatorBackground.js';
import {Episodes} from "../../api/episodes/episodes";
import Streamers from "../../streamers/streamers";

Template.pages_show.onCreated(function() {
  // Set page variables
  Session.set('BreadCrumbs', JSON.stringify([{
    name: 'Anime',
    url: FlowRouter.path('search')
  }]));

  // Subscribe based on the show id
  this.autorun(() => {
    this.subscribe('shows.withId', FlowRouter.getParam('showId'));
    this.subscribe('episodes.forShow', FlowRouter.getParam('showId'));
  });

  // Check if the show exists
  this.autorun(() => {
    if (this.subscriptionsReady() && !Shows.findOne(FlowRouter.getParam('showId'))) {
      FlowRouter.go('notFound');
    }
  });

  // When a show is found
  this.autorun(() => {
    if (Shows.findOne(FlowRouter.getParam('showId'))) {
      Session.set('PageTitle', Shows.findOne(FlowRouter.getParam('showId')).name);
      Meteor.call('shows.attemptUpdate', FlowRouter.getParam('showId'));
    }
  });
});

Template.pages_show.helpers({
  show() {
    return Shows.findOne(FlowRouter.getParam('showId'));
  },

  updating() {
    let show = Shows.findOne(FlowRouter.getParam('showId'));
    return show && show.locked();
  },

  episodes(translationType) {
    let episodes = [];

    Episodes.queryForTranslationType(FlowRouter.getParam('showId'), translationType).forEach((episode) => {
      let selector = {
        showId: episode.showId,
        translationType: episode.translationType,
        episodeNum: episode.episodeNum
      };

      if (episodes.hasPartialObjects(selector)) {
        let other = episodes.getPartialObjects(selector)[0];
        other.streamers.push(Streamers.getSimpleStreamerById(episode.streamerId));
        episodes = episodes.removePartialObjects(selector);
        episodes.push(other);
      }

      else {
        episode.streamers = [Streamers.getSimpleStreamerById(episode.streamerId)];
        episodes.push(episode);
      }
    });

    return episodes;
  }
});
