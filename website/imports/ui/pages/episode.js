import './episode.html';
import {Episodes} from "../../api/episodes/episodes";
import Streamers from "../../streamers/_streamers";
import {Shows} from "../../api/shows/shows";
import '/imports/ui/components/loadingIndicatorBackground.js';

Template.pages_episode.onCreated(function() {
  this.selectedEpisode = new ReactiveVar(undefined);
  this.selectedSource = new ReactiveVar(undefined);

  this.autorun(() => {
    this.subscribe('shows.withId', FlowRouter.getParam('showId'));
    if (this.subscriptionsReady()) {
      if (!Shows.findOne(FlowRouter.getParam('showId'))) {
        FlowRouter.go('notFound');
      } else {
        Session.set('PageTitle', Shows.findOne(FlowRouter.getParam('showId')).name + ' - Episode ' + FlowRouter.getParam('episodeNum') + ' (' + FlowRouter.getParam('translationType').capitalize() + ')');
      }
    }
  });

  this.autorun(() => {
    this.subscribe('episodes.forEpisode', FlowRouter.getParam('showId'), Number(FlowRouter.getParam('episodeNum')), FlowRouter.getParam('translationType'));
    if (this.subscriptionsReady()) {
      if (!Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Number(FlowRouter.getParam('episodeNum'))).count()) {
        FlowRouter.go('notFound');
      } else {
        Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Number(FlowRouter.getParam('episodeNum'))).forEach((episode) => {
          Meteor.call('episodes.attemptUpdate', episode._id);
          if (episode.lastUpdateEnd && (!this.selectedEpisode.get() || !this.selectedSource.get())) {
            this.selectedEpisode.set(Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Number(FlowRouter.getParam('episodeNum'))).fetch()[0]._id);
            this.selectedSource.set(Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Number(FlowRouter.getParam('episodeNum'))).fetch()[0].sources[0].name);
          }
        });
      }
    }
  });
});

Template.pages_episode.helpers({
  selectedEpisode() {
    if (!Template.instance().selectedEpisode.get()) {
      return undefined;
    }
    return Episodes.findOne(Template.instance().selectedEpisode.get());
  },

  selectedSource() {
    if (!Template.instance().selectedEpisode.get() || !Template.instance().selectedSource.get()) {
      return undefined;
    }
    return Episodes.findOne(Template.instance().selectedEpisode.get()).sources.getPartialObjects({
      name: Template.instance().selectedSource.get()
    })[0];
  },

  episodes() {
    return Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Number(FlowRouter.getParam('episodeNum'))).fetch().map((episode) =>{
      episode.streamer = Streamers.getSimpleStreamerById(episode.streamerId);
      return episode;
    });
  },

  updating() {
    return Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Number(FlowRouter.getParam('episodeNum'))).fetch().reduce((total, episode) => {
      return total || episode.locked();
    }, false);
  }
});

Template.pages_episode.events({
  'click a.btn'(event) {
    Template.instance().selectedEpisode.set(event.target.dataset.episode);
    Template.instance().selectedSource.set(event.target.dataset.source);
  }
});
