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
        });
      }
    }
  });

  this.autorun(() => {
    if (this.subscriptionsReady() && (!this.selectedEpisode.get() || !this.selectedSource.get())) {
      let flagsPreference = Episodes.flagsWithoutAddOnPreference;
      let flagsNever = Episodes.flagsWithoutAddOnNever;
      if (Session.get('AddOnInstalled')) {
        flagsPreference = Episodes.flagsWithAddOnPreference;
        flagsNever = Episodes.flagsWithAddOnNever;
      }

      for (let i = flagsPreference.length; i >= 0; i--) {
        if (!this.selectedEpisode.get() || !this.selectedSource.get()) {
          Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Number(FlowRouter.getParam('episodeNum'))).forEach((episode) => {
            episode.sources.forEach((source) => {
              if ((!this.selectedEpisode.get() || !this.selectedSource.get()) && source.flags.reduce((total, flag) => {
                  return total && !flagsNever.includes(flag) && !flagsPreference.slice(0, i).includes(flag);
                }, true)) {
                this.selectedEpisode.set(episode._id);
                this.selectedSource.set(source.name);
              }
            });
          });
        }
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
  },

  showIcon(flag) {
    return !Session.get('AddOnInstalled') || Episodes.flagsWithAddOnPreference.includes(flag) || Episodes.flagsWithAddOnNever.includes(flag);
  },

  flagsDisabled(flags) {
    return flags.reduce((total, flag) => {
      return total || (!Session.get('AddOnInstalled') && Episodes.flagsWithoutAddOnNever.includes(flag)) || (Session.get('AddOnInstalled') && Episodes.flagsWithAddOnNever.includes(flag));
    }, false);
  }
});

Template.pages_episode.events({
  'click a.btn'(event) {
    Template.instance().selectedEpisode.set(event.target.dataset.episode);
    Template.instance().selectedSource.set(event.target.dataset.source);
  }
});
