import './episode.html';
import {Episodes} from "../../api/episodes/episodes";
import Streamers from "../../streamers/streamers";
import {Shows} from "../../api/shows/shows";
import '/imports/ui/components/loadingIndicatorBackground.js';

Template.pages_episode.onCreated(function() {
  // Getters for the episode numbers
  this.hasMultipleEpisodeNumbers = function() {
    return FlowRouter.getRouteName() === 'episodeDouble';
  };
  this.getEpisodeNumStart = function() {
    return Number(this.hasMultipleEpisodeNumbers() ? FlowRouter.getParam('episodeNumStart') : FlowRouter.getParam('episodeNumBoth'));
  };
  this.getEpisodeNumEnd = function() {
    return Number(this.hasMultipleEpisodeNumbers() ? FlowRouter.getParam('episodeNumEnd') : FlowRouter.getParam('episodeNumBoth'));
  };

  // Other functions
  this.getSelectedSource = function() {
    return Episodes.findOne(this.selectedEpisode.get()).sources.getPartialObjects({
      name: this.selectedSource.get()
    })[0];
  };

  this.setIframeErrors = function() {
    let problemFlags = this.getSelectedSource().flags.filter((flag) => {
      return Episodes.isFlagProblematic(flag);
    });

    if (problemFlags.empty()) {
      problemFlags.push('unknown');
    }

    problemFlags.push('add-on');

    this.iframeErrors.set(problemFlags);
  };

  // Set page variables
  if (this.hasMultipleEpisodeNumbers()) {
    Session.set('PageTitle', 'Episode ' + this.getEpisodeNumStart() + ' - ' + this.getEpisodeNumEnd());
  } else {
    Session.set('PageTitle', 'Episode ' + this.getEpisodeNumStart());
  }

  // Create local variables
  this.selectedEpisode = new ReactiveVar(undefined);
  this.selectedSource = new ReactiveVar(undefined);
  this.iframeErrors = new ReactiveVar([]);

  // Subscribe based on the show id
  this.autorun(() => {
    this.subscribe('shows.withId', FlowRouter.getParam('showId'));
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
      Session.set('BreadCrumbs', JSON.stringify([{
        name: 'Anime',
        url: FlowRouter.path('search')
      }, {
        name: Shows.findOne(FlowRouter.getParam('showId')).name,
        url: FlowRouter.path('show', {
          showId: FlowRouter.getParam('showId')
        })
      }, {
        name: 'Episodes (' + FlowRouter.getParam('translationType').capitalize() + ')'
      }]));
    }
  });

  // Subscribe based on all parameters
  this.autorun(() => {
    this.subscribe('episodes.forEpisode', FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd());
  });

  // Check if the episodes exists
  this.autorun(() => {
    if (this.subscriptionsReady() && !Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd()).count()) {
      FlowRouter.go('notFound');
    }
  });

  // When the episodes are found
  this.autorun(() => {
    if (Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd()).count()) {
      Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd()).forEach((episode) => {
        Meteor.call('episodes.attemptUpdate', episode._id);
      });
    }
  });

  // When the episodes are found and the selection needs to change
  this.autorun(() => {
    if (Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd()).count() && (!this.selectedEpisode.get() || !this.selectedSource.get())) {
      let flagsPreference = Episodes.flagsWithoutAddOnPreference;
      let flagsNever = Episodes.flagsWithoutAddOnNever;
      if (Session.get('AddOnInstalled')) {
        flagsPreference = Episodes.flagsWithAddOnPreference;
        flagsNever = Episodes.flagsWithAddOnNever;
      }

      for (let i = flagsPreference.length; i >= 0; i--) {
        if (!this.selectedEpisode.get() || !this.selectedSource.get()) {
          Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd()).forEach((episode) => {
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
    let episode = Episodes.findOne(Template.instance().selectedEpisode.get());
    episode.streamer = Streamers.getSimpleStreamerById(episode.streamerId);
    return episode;
  },

  selectedSource() {
    if (!Template.instance().selectedEpisode.get() || !Template.instance().selectedSource.get()) {
      return undefined;
    }
    return Template.instance().getSelectedSource();
  },

  episodes() {
    return Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Template.instance().getEpisodeNumStart(), Template.instance().getEpisodeNumEnd()).fetch().map((episode) =>{
      episode.streamer = Streamers.getSimpleStreamerById(episode.streamerId);
      return episode;
    });
  },

  updating() {
    return Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Template.instance().getEpisodeNumStart(), Template.instance().getEpisodeNumEnd()).fetch().reduce((total, episode) => {
      return total || episode.locked();
    }, false);
  },

  showIcon(flag) {
    return Episodes.isFlagProblematic(flag);
  },

  flagsDisabled(flags) {
    return flags.reduce((total, flag) => {
      return total || (!Session.get('AddOnInstalled') && Episodes.flagsWithoutAddOnNever.includes(flag)) || (Session.get('AddOnInstalled') && Episodes.flagsWithAddOnNever.includes(flag));
    }, false);
  },

  iframeErrors() {
    return Template.instance().iframeErrors.get();
  }
});

Template.pages_episode.events({
  'click a.source-btn'(event) {
    if (event.target.tagName === 'I') {
      event.target = event.target.parentElement.parentElement;
    }

    Template.instance().selectedEpisode.set(event.target.dataset.episode);
    Template.instance().selectedSource.set(event.target.dataset.source);
    Template.instance().iframeErrors.set([]);
  },

  'error #episode-frame'(event) {
    Template.instance().setIframeErrors();
  },

  'click a.not-working-btn'(event) {
    Template.instance().setIframeErrors();
  },
});
