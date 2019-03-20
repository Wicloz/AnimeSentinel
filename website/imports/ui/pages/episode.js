import './episode.html';
import {Episodes} from "../../api/episodes/episodes";
import Streamers from "../../streamers/streamers";
import {Shows} from "../../api/shows/shows";
import '/imports/ui/components/image.js';
import moment from 'moment-timezone';
import '/imports/ui/components/tooltippedIcon';

Template.pages_episode.onCreated(function() {
  // Getters for the episode numbers
  this.getEpisodeNumStart = function() {
    return Number(FlowRouter.getParam('episodeNumStart'));
  };
  this.getEpisodeNumEnd = function() {
    return Number(FlowRouter.getParam('episodeNumEnd'));
  };
  this.getNotes = function() {
    if (FlowRouter.getParam('notes') === 'none') {
      return undefined;
    } else {
      return decodeBase64(FlowRouter.getParam('notes'));
    }
  };

  // Other functions
  this.setIframeErrors = function() {
    let problemFlags = Episodes.queryUnique(
      FlowRouter.getParam('showId'),
      FlowRouter.getParam('translationType'),
      this.getEpisodeNumStart(),
      this.getEpisodeNumEnd(),
      this.getNotes(),
      Template.findState(this).get('selectedStreamerId'),
      Template.findState(this).get('selectedSourceName')
    ).fetch()[0].flags.filter((flag) => {
      return Episodes.isFlagProblematic(flag);
    });

    if (problemFlags.empty()) {
      problemFlags.push('unknown');
    }

    if (!Session.get('AddOnInstalled')) {
      problemFlags.push('add-on');
    }

    Template.findState(this).set('iframeErrors', problemFlags);
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
    Template.findState(this).set('selectedStreamerId', streamerId);
    Template.findState(this).set('selectedSourceName', sourceName);
    if (manual) {
      setStorageItem(['SelectedSourceLastTime', streamerId, sourceName], moment.fromUtc().valueOf());
    }

    Template.findState(this).set('iframeErrors', []);
    this.startErrorsDelay();
  };

  this.goToEpisode = function(episodeNumStart, episodeNumEnd, notes) {
    FlowRouter.go('episode', {
      showId: FlowRouter.getParam('showId'),
      translationType: FlowRouter.getParam('translationType'),
      episodeNumStart: episodeNumStart,
      episodeNumEnd: episodeNumEnd,
      notes: notes
    });
    this.selectSource(undefined, undefined, false);
  };

  // Create local variables
  Template.makeState({
    selectedStreamerId: undefined,
    selectedSourceName: undefined,
    iframeErrors: []
  });
  this.iframeErrorsTimeout = undefined;

  // Enable frame sandboxing initially
  this.autorun(() => {
    if (typeof getStorageItem('FrameSandboxingEnabled') === 'undefined') {
      setStorageItem('FrameSandboxingEnabled', true);
    }
  });

  // Set page title based on the episode numbers and translation type
  this.autorun(() => {
    if (Session.get('BreadCrumbs') === '[]') {
      Session.set('BreadCrumbs', JSON.stringify([{
        name: FlowRouter.getParam('translationType').capitalize()
      }]));
    }
    Session.set('PageTitle', 'Episode ' + this.getEpisodeNumStart()
      + (this.getEpisodeNumStart() !== this.getEpisodeNumEnd() ? ' - ' + this.getEpisodeNumEnd() : '')
      + (this.getNotes() ? ' - ' + this.getNotes() : ''));
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
    let show = Shows.findOne(FlowRouter.getParam('showId'));
    if (show) {
      Session.set('BreadCrumbs', JSON.stringify([{
        name: 'Anime',
        url: FlowRouter.path('search')
      }, {
        name: Shows.findOne(FlowRouter.getParam('showId')).name,
        url: FlowRouter.path('show', {
          showId: FlowRouter.getParam('showId')
        })
      }, {
        name: FlowRouter.getParam('translationType').capitalize()
      }]));
      if (show.canHaveWatchState()) {
        this.subscribe('watchStates.currentUserUnique', show.malId);
      }
    }
  });

  // Subscribe based on the showId and translationType
  this.autorun(() => {
    this.subscribe('episodes.forTranslationType', FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'));
  });

  // Check if the episodes exists
  this.autorun(() => {
    if (isNaN(this.getEpisodeNumStart()) || isNaN(this.getEpisodeNumEnd()) || (this.subscriptionsReady() && !Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd(), this.getNotes()).count())) {
      FlowRouter.go('notFound');
    }
  });

  // When the episodes are found and the selection needs to change
  this.autorun(() => {
    if (Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd(), this.getNotes()).count() && (!Template.findState(this).get('selectedStreamerId') || !Template.findState(this).get('selectedSourceName'))) {
      let selectedSource = Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd(), this.getNotes()).fetch().reduce((total, episode) => {
        let thisTime = getStorageItem(['SelectedSourceLastTime', episode.streamerId, episode.sourceName]);
        if (thisTime && (!total || thisTime > total.time)) {
          total = {
            streamerId: episode.streamerId,
            sourceName: episode.sourceName,
            time: thisTime
          };
        }
        return total;
      }, undefined);

      if (selectedSource) {
        this.selectSource(selectedSource.streamerId, selectedSource.sourceName, false);
      }

      else {
        let flagsPreference = Episodes.flagsWithoutAddOnPreference;
        let flagsNever = Episodes.flagsWithoutAddOnNever;
        if (Session.get('AddOnInstalled')) {
          flagsPreference = Episodes.flagsWithAddOnPreference;
          flagsNever = Episodes.flagsWithAddOnNever;
        }
        if (getStorageItem('FrameSandboxingEnabled') && BrowserDetect.browser === 'Chrome') {
          flagsNever = flagsNever.concat(Episodes.flagsWithSandboxingNever);
        }

        for (let i = flagsPreference.length; i >= 0; i--) {
          if (!Template.findState(this).get('selectedStreamerId') || !Template.findState(this).get('selectedSourceName')) {
            Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd(), this.getNotes()).forEach((episode) => {
              if ((!Template.findState(this).get('selectedStreamerId') || !Template.findState(this).get('selectedSourceName')) && episode.flags.every((flag) => {
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
});

Template.pages_episode.helpers({
  selectedStreamerId() {
    return Template.findState(this).get('selectedStreamerId');
  },

  selectedStreamerHomepage() {
    return tryGetProperty(Streamers.getSimpleStreamerById(Template.findState(this).get('selectedStreamerId')), 'homepage');
  },

  selectedStreamerName() {
    return tryGetProperty(Streamers.getSimpleStreamerById(Template.findState(this).get('selectedStreamerId')), 'name');
  },

  selectedSourceName() {
    return Template.findState(this).get('selectedSourceName');
  },

  selectedSourceUrl() {
    if (!Template.findState(this).get('selectedStreamerId') || !Template.findState(this).get('selectedSourceName') || !Template.findState(this).get('iframeErrors').empty()) {
      return 'about:blank';
    }
    return Episodes.queryUnique(
      FlowRouter.getParam('showId'),
      FlowRouter.getParam('translationType'),
      Template.instance().getEpisodeNumStart(),
      Template.instance().getEpisodeNumEnd(),
      Template.instance().getNotes(),
      Template.findState(this).get('selectedStreamerId'),
      Template.findState(this).get('selectedSourceName')
    ).fetch()[0].sourceUrl;
  },

  episodesByStreamer() {
    let results = [];

    Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Template.instance().getEpisodeNumStart(), Template.instance().getEpisodeNumEnd(), Template.instance().getNotes()).forEach((episode) => {
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
    return Template.findState(this).get('iframeErrors');
  },

  episodeSelectionOptions() {
    let options = [];

    Episodes.queryForTranslationType(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType')).forEach((episode) => {
      let option = {
        label: episode.fancyKey(),
        value: episode.encodedKey()
      };
      if (!options.hasPartialObjects(option)) {
        options.push(option);
      }
    });

    return options;
  },

  episodeSelectionDefaultValue() {
    return encodeURIComponent(JSON.stringify({
      episodeNumStart: Template.instance().getEpisodeNumStart(),
      episodeNumEnd: Template.instance().getEpisodeNumEnd(),
      notes: FlowRouter.getParam('notes')
    }));
  },

  frameSandboxingEnabled() {
    return getStorageItem('FrameSandboxingEnabled');
  },

  previousEpisode() {
    return Episodes.getPreviousEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Template.instance().getEpisodeNumStart(), Template.instance().getEpisodeNumEnd(), Template.instance().getNotes());
  },

  nextEpisode() {
    return Episodes.getNextEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Template.instance().getEpisodeNumStart(), Template.instance().getEpisodeNumEnd(), Template.instance().getNotes());
  },

  show() {
    return Shows.findOne(FlowRouter.getParam('showId'));
  },

  isCompletionEpisode() {
    let show = Shows.findOne(FlowRouter.getParam('showId'));
    let watchState = show.watchState();
    return Template.instance().getEpisodeNumEnd() >= show.episodeCount && (!watchState || watchState.status !== 'completed');
  },

  episodeWatched() {
    let watchState = Shows.findOne(FlowRouter.getParam('showId')).watchState();
    return watchState && Template.instance().getEpisodeNumEnd() <= watchState.episodesWatched;
  }
});

Template.pages_episode.events({
  'click .btn-source'(event) {
    if (event.target.tagName === 'I') {
      event.target = event.target.parentElement;
    }
    Template.instance().selectSource(event.target.dataset.streamerid, event.target.dataset.sourcename, true);
  },

  'load #episode-frame'(event) {
    Template.instance().stopErrorsDelay();
  },

  'error #episode-frame'(event) {
    Template.instance().stopErrorsDelay();
    Template.instance().setIframeErrors();
  },

  'click .btn-not-working'(event) {
    Template.instance().stopErrorsDelay();
    Template.instance().setIframeErrors();
  },

  'click .btn-select-prev'(event) {
    let episode = Episodes.getPreviousEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Template.instance().getEpisodeNumStart(), Template.instance().getEpisodeNumEnd(), Template.instance().getNotes());
    Template.instance().goToEpisode(episode.episodeNumStart, episode.episodeNumEnd, episode.notesEncoded());
  },

  'click .btn-select-next'(event) {
    let episode = Episodes.getNextEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Template.instance().getEpisodeNumStart(), Template.instance().getEpisodeNumEnd(), Template.instance().getNotes());
    Template.instance().goToEpisode(episode.episodeNumStart, episode.episodeNumEnd, episode.notesEncoded());
  },

  'change #sandboxing-checkbox'(event) {
    setStorageItem('FrameSandboxingEnabled', event.target.checked);
  },

  'click .btn-watched'(event) {
    let show = Shows.findOne(FlowRouter.getParam('showId'));
    let watchState = show.watchState();

    if (typeof watchState === 'undefined') {
      watchState = {
        malId: show.malId,
        userId: Meteor.userId(),
      };
    }

    watchState.episodesWatched = Template.instance().getEpisodeNumEnd();
    if (watchState.episodesWatched >= show.episodeCount) {
      watchState.status = 'completed';
      watchState.rewatching = false;
    }

    Meteor.call('watchStates.changeWatchState', watchState);
  }
});

AutoForm.hooks({
  episodeSelectionForm: {
    onSubmit(insertDoc) {
      let info = JSON.parse(decodeURIComponent(insertDoc.episodeNumber));
      this.template.view.parentView.parentView._templateInstance.goToEpisode(info.episodeNumStart, info.episodeNumEnd, info.notes);
      this.done();
      return false;
    }
  },

  scoreForm: {
    formToDoc(doc) {
      // Get current watch state
      let watchState = Shows.findOne(FlowRouter.getParam('showId')).watchState();
      // Remove score if empty
      if (!doc.hasOwnProperty('score')) {
        doc.score = null;
      }
      // Copy missing values
      Object.keys(watchState).forEach((key) => {
        if (!doc.hasOwnProperty(key)) {
          doc[key] = watchState[key];
        }
      });
      // Return
      return doc;
    },

    onSubmit(insertDoc) {
      Meteor.call('watchStates.changeWatchState', insertDoc, (error) => {
        this.done(error);
      });
      return false;
    }
  }
});
