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
  this.getEpisodeNumBoth = function() {
    if (this.hasMultipleEpisodeNumbers()) {
      return this.getEpisodeNumStart() + ' - ' + this.getEpisodeNumEnd();
    } else {
      return this.getEpisodeNumStart();
    }
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

    if (!Session.get('AddOnInstalled')) {
      problemFlags.push('add-on');
    }

    this.iframeErrors.set(problemFlags);
  };

  // Create local variables
  this.selectedEpisode = new ReactiveVar(undefined);
  this.selectedSource = new ReactiveVar(undefined);
  this.iframeErrors = new ReactiveVar([]);

  // Set page title based on getEpisodeNumBoth()
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
    if (this.subscriptionsReady() && (isNaN(this.getEpisodeNumStart()) || isNaN(this.getEpisodeNumEnd()) || !Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd()).count())) {
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
              if ((!this.selectedEpisode.get() || !this.selectedSource.get()) && source.flags.every((flag) => {
                  return !flagsNever.includes(flag) && !flagsPreference.slice(0, i).includes(flag);
                })) {
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
    return Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), Template.instance().getEpisodeNumStart(), Template.instance().getEpisodeNumEnd()).fetch().some((episode) => {
      return episode.locked();
    });
  },

  showIcon(flag) {
    return Episodes.isFlagProblematic(flag);
  },

  flagsDisabled(flags) {
    return flags.some((flag) => {
      return (!Session.get('AddOnInstalled') && Episodes.flagsWithoutAddOnNever.includes(flag)) || (Session.get('AddOnInstalled') && Episodes.flagsWithAddOnNever.includes(flag));
    });
  },

  iframeErrors() {
    return Template.instance().iframeErrors.get();
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
  }
});

Template.pages_episode.events({
  'click a.btn-source'(event) {
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

  'click a.btn-not-working'(event) {
    Template.instance().setIframeErrors();
  },

  'click button.btn-select-prev'(event) {
    $('#episodeNumberSelection').find('option:selected').next().attr('selected', 'selected');
  },
  'click button.btn-select-next'(event) {
    $('#episodeNumberSelection').find('option:selected').prev().attr('selected', 'selected');
  },
});

AutoForm.hooks({
  episodeSelectionForm: {
    onSubmit(insertDoc) {
      let split = insertDoc.episodeNumber.split(' - ');

      if (split.length === 2) {
        FlowRouter.go('episodeDouble', {
          showId: FlowRouter.getParam('showId'),
          translationType: FlowRouter.getParam('translationType'),
          episodeNumStart: split[0],
          episodeNumEnd: split[1]
        });
      } else {
        FlowRouter.go('episodeSingle', {
          showId: FlowRouter.getParam('showId'),
          translationType: FlowRouter.getParam('translationType'),
          episodeNumBoth: insertDoc.episodeNumber
        });
      }

      this.template.view.parentView.parentView._templateInstance.selectedEpisode.set(undefined);
      this.template.view.parentView.parentView._templateInstance.selectedSource.set(undefined);

      this.done();
      return false;
    }
  }
});
