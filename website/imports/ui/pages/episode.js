import './episode.html';
import {Episodes} from "../../api/episodes/episodes";
import Streamers from "../../streamers/streamers";
import {Shows} from "../../api/shows/shows";
import '/imports/ui/components/image.js';
import * as RLocalStorage from 'meteor/simply:reactive-local-storage';
import moment from 'moment-timezone';

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
  this.getEpisodeNumBoth = function() {
    if (this.hasMultipleEpisodeNumbers()) {
      return this.getEpisodeNumStart() + ' - ' + this.getEpisodeNumEnd();
    } else {
      return this.getEpisodeNumStart();
    }
  };

  // Other functions
  this.setIframeErrors = function() {
    let problemFlags = Episodes.queryUnique(
      FlowRouter.getParam('showId'),
      FlowRouter.getParam('translationType'),
      this.getEpisodeNumStart(),
      this.getEpisodeNumEnd(),
      this.state.get('selectedStreamerId'),
      this.state.get('selectedSourceName')
    ).fetch()[0].flags.filter((flag) => {
      return Episodes.isFlagProblematic(flag);
    });

    if (problemFlags.empty()) {
      problemFlags.push('unknown');
    }

    if (!Session.get('AddOnInstalled')) {
      problemFlags.push('add-on');
    }

    this.state.set('iframeErrors', problemFlags);
  };

  this.startErrorsDelay = function() {
    this.stopErrorsDelay();
    this.iframeErrorsTimeout = setTimeout(() => {
      this.setIframeErrors();
    }, 10000);
  };

  this.stopErrorsDelay = function() {
    clearTimeout(this.iframeErrorsTimeout);
  };

  this.selectSource = function(streamerId, sourceName, manual) {
    this.state.set('selectedStreamerId', streamerId);
    this.state.set('selectedSourceName', sourceName);
    if (manual) {
      RLocalStorage.setItem('SelectedSourceLastTime.' + streamerId + '.' + sourceName, moment().valueOf());
    }
  };

  this.goToEpisode = function(episodeNumStart, episodeNumEnd) {
    if (episodeNumStart === episodeNumEnd) {
      FlowRouter.go('episodeSingle', {
        showId: FlowRouter.getParam('showId'),
        translationType: FlowRouter.getParam('translationType'),
        episodeNumBoth: episodeNumStart
      });
    } else {
      FlowRouter.go('episodeDouble', {
        showId: FlowRouter.getParam('showId'),
        translationType: FlowRouter.getParam('translationType'),
        episodeNumStart: episodeNumStart,
        episodeNumEnd: episodeNumEnd
      });
    }

    this.selectSource(undefined, undefined, false);
    this.state.set('iframeErrors', []);
    this.startErrorsDelay();
  };

  // Create local variables
  this.state = new ReactiveDict();
  this.state.setDefault({
    selectedStreamerId: undefined,
    selectedSourceName: undefined,
    iframeErrors: []
  });
  this.iframeErrorsTimeout = undefined;

  // Enable frame sandboxing initially
  if (RLocalStorage.getItem('FrameSandboxingEnabled') === null) {
    RLocalStorage.setItem('FrameSandboxingEnabled', true);
  }

  // Set page title based on the episode numbers and translation type
  this.autorun(() => {
    Session.set('PageTitle', 'Episode ' + this.getEpisodeNumBoth() + ' (' + FlowRouter.getParam('translationType').capitalize() + ')');
  });

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
      }]));
    }
  });

  // Subscribe based on the showId and translationType
  this.autorun(() => {
    this.subscribe('episodes.forTranslationType', FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'));
  });

  // Check if the episodes exists
  this.autorun(() => {
    if (isNaN(this.getEpisodeNumStart()) || isNaN(this.getEpisodeNumEnd()) || (this.subscriptionsReady() && !Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd()).count())) {
      FlowRouter.go('notFound');
    }
  });

  // When the episodes are found and the selection needs to change
  this.autorun(() => {
    if (Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd()).count() && (!this.state.get('selectedStreamerId') || !this.state.get('selectedSourceName'))) {
      let selectSource = Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd()).fetch().reduce((total, episode) => {
        let thisTime = RLocalStorage.getItem('SelectedSourceLastTime.' + episode.streamerId + '.' + episode.sourceName);
        if (thisTime && (!total || thisTime > total.time)) {
          total = {
            streamerId: episode.streamerId,
            sourceName: episode.sourceName,
            time: thisTime
          };
        }
        return total;
      }, undefined);

      if (selectSource) {
        this.selectSource(selectSource.streamerId, selectSource.sourceName, false);
      }

      else {
        let flagsPreference = Episodes.flagsWithoutAddOnPreference;
        let flagsNever = Episodes.flagsWithoutAddOnNever;
        if (Session.get('AddOnInstalled')) {
          flagsPreference = Episodes.flagsWithAddOnPreference;
          flagsNever = Episodes.flagsWithAddOnNever;
        }
        if (RLocalStorage.getItem('FrameSandboxingEnabled') && BrowserDetect.browser === 'Chrome') {
          flagsNever = flagsNever.concat(Episodes.flagsWithSandboxingNever);
        }

        for (let i = flagsPreference.length; i >= 0; i--) {
          if (!this.state.get('selectedStreamerId') || !this.state.get('selectedSourceName')) {
            Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd()).forEach((episode) => {
              if ((!this.state.get('selectedStreamerId') || !this.state.get('selectedSourceName')) && episode.flags.every((flag) => {
                  return !flagsNever.includes(flag) && !flagsPreference.slice(0, i).includes(flag);
                })) {
                this.selectSource(episode.streamerId, episode.sourceName, false);
              }
            });
          }
        }
      }
    }
  });

  // Start the error delay now that everything is ready
  this.startErrorsDelay();
});

Template.pages_episode.helpers({
  selectedStreamerId() {
    return Template.instance().state.get('selectedStreamerId');
  },

  selectedStreamerHomepage() {
    return Streamers.getSimpleStreamerById(Template.instance().state.get('selectedStreamerId')).homepage;
  },

  selectedSourceName() {
    return Template.instance().state.get('selectedSourceName');
  },

  selectedSourceUrl() {
    if (!Template.instance().state.get('selectedStreamerId') || !Template.instance().state.get('selectedSourceName') || !Template.instance().state.get('iframeErrors').empty()) {
      return undefined;
    }
    return Episodes.queryUnique(
      FlowRouter.getParam('showId'),
      FlowRouter.getParam('translationType'),
      Template.instance().getEpisodeNumStart(),
      Template.instance().getEpisodeNumEnd(),
      Template.instance().state.get('selectedStreamerId'),
      Template.instance().state.get('selectedSourceName')
    ).fetch()[0].sourceUrl;
  },

  episodesByStreamer() {
    let results = [];

    Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Template.instance().getEpisodeNumStart(), Template.instance().getEpisodeNumEnd()).forEach((episode) => {
      let done = false;
      results = results.map((result) => {
        if (result.streamer.id === episode.streamerId) {
          result.episodes.push(episode);
          done = true;
        }
        return result;
      });
      if (!done) {
        results.push({
          streamer: Streamers.getSimpleStreamerById(episode.streamerId),
          episodes: [episode]
        });
      }
    });

    return results;
  },

  showIcon(flag) {
    return Episodes.isFlagProblematic(flag);
  },

  flagsDisabled(flags) {
    return flags.some((flag) => {
      return Episodes.isFlagDisabled(flag);
    });
  },

  iframeErrors() {
    return Template.instance().state.get('iframeErrors');
  },

  episodeSelectionOptions() {
    let options = [];

    Episodes.queryForTranslationType(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType')).forEach((episode) => {
      let label = episode.episodeNumStart;
      if (episode.episodeNumStart !== episode.episodeNumEnd) {
        label += ' - ' + episode.episodeNumEnd;
      }

      if (!options.hasPartialObjects({label})) {
        options.push({
          label: label,
          value: label
        });
      }
    });

    return options;
  },

  episodeSelectionDefaultValue() {
    return Template.instance().getEpisodeNumBoth();
  },

  frameSandboxingEnabled() {
    return RLocalStorage.getItem('FrameSandboxingEnabled');
  },

  previousEpisode() {
    return Episodes.getPreviousEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Template.instance().getEpisodeNumStart(), Template.instance().getEpisodeNumEnd());
  },

  nextEpisode() {
    return Episodes.getNextEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Template.instance().getEpisodeNumStart(), Template.instance().getEpisodeNumEnd());
  }
});

Template.pages_episode.events({
  'click a.btn-source'(event) {
    if (event.target.tagName === 'I') {
      event.target = event.target.parentElement.parentElement;
    }

    Template.instance().selectSource(event.target.dataset.streamerid, event.target.dataset.sourcename, true);
    Template.instance().state.set('iframeErrors', []);

    Template.instance().startErrorsDelay();
  },

  'error #episode-frame'(event) {
    Template.instance().stopErrorsDelay();
    Template.instance().setIframeErrors();
  },

  'load #episode-frame'(event) {
    Template.instance().stopErrorsDelay();
    Template.instance().state.set('iframeErrors', []);
  },

  'click a.btn-not-working'(event) {
    Template.instance().setIframeErrors();
  },

  'click button.btn-select-prev'(event) {
    let episode = Episodes.getPreviousEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Template.instance().getEpisodeNumStart(), Template.instance().getEpisodeNumEnd());
    Template.instance().goToEpisode(episode.episodeNumStart, episode.episodeNumEnd);
  },
  'click button.btn-select-next'(event) {
    let episode = Episodes.getNextEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Template.instance().getEpisodeNumStart(), Template.instance().getEpisodeNumEnd());
    Template.instance().goToEpisode(episode.episodeNumStart, episode.episodeNumEnd);
  },

  'change #sandboxing-checkbox'(event) {
    RLocalStorage.setItem('FrameSandboxingEnabled', event.target.checked);
  }
});

AutoForm.hooks({
  episodeSelectionForm: {
    onSubmit(insertDoc) {
      let split = insertDoc.episodeNumber.split(' - ');
      this.template.view.parentView.parentView._templateInstance.goToEpisode(split[0], split.length === 2 ? split[1] : split[0]);
      this.done();
      return false;
    }
  }
});
