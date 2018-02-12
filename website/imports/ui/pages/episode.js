import './episode.html';
import {Episodes} from "../../api/episodes/episodes";
import Streamers from "../../streamers/streamers";
import {Shows} from "../../api/shows/shows";
import '/imports/ui/components/image.js';

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
    return Episodes.findOne(this.state.get('selectedEpisode')).sources.getPartialObjects({
      name: this.state.get('selectedSource')
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

    this.state.set('iframeErrors', problemFlags);
  };

  // Create local variables
  this.state = new ReactiveDict();
  this.state.setDefault({
    selectedEpisode: undefined,
    selectedSource: undefined,
    iframeErrors: []
  });

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

  // When the episodes are found and the selection needs to change
  this.autorun(() => {
    if (Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd()).count() && (!this.state.get('selectedEpisode') || !this.state.get('selectedSource'))) {
      let flagsPreference = Episodes.flagsWithoutAddOnPreference;
      let flagsNever = Episodes.flagsWithoutAddOnNever;
      if (Session.get('AddOnInstalled')) {
        flagsPreference = Episodes.flagsWithAddOnPreference;
        flagsNever = Episodes.flagsWithAddOnNever;
      }

      for (let i = flagsPreference.length; i >= 0; i--) {
        if (!this.state.get('selectedEpisode') || !this.state.get('selectedSource')) {
          Episodes.queryForEpisode(FlowRouter.getParam('showId'), FlowRouter.getParam('translationType'), this.getEpisodeNumStart(), this.getEpisodeNumEnd()).forEach((episode) => {
            episode.sources.forEach((source) => {
              if ((!this.state.get('selectedEpisode') || !this.state.get('selectedSource')) && source.flags.every((flag) => {
                  return !flagsNever.includes(flag) && !flagsPreference.slice(0, i).includes(flag);
                })) {
                this.state.set('selectedEpisode', episode._id);
                this.state.set('selectedSource', source.name);
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
    if (!Template.instance().state.get('selectedEpisode')) {
      return undefined;
    }
    let episode = Episodes.findOne(Template.instance().state.get('selectedEpisode'));
    episode.streamer = Streamers.getSimpleStreamerById(episode.streamerId);
    return episode;
  },

  selectedSource() {
    if (!Template.instance().state.get('selectedEpisode') || !Template.instance().state.get('selectedSource')) {
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

  showIcon(flag) {
    return Episodes.isFlagProblematic(flag);
  },

  flagsDisabled(flags) {
    return flags.some((flag) => {
      return (!Session.get('AddOnInstalled') && Episodes.flagsWithoutAddOnNever.includes(flag)) || (Session.get('AddOnInstalled') && Episodes.flagsWithAddOnNever.includes(flag));
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
  }
});

Template.pages_episode.events({
  'click a.btn-source'(event) {
    if (event.target.tagName === 'I') {
      event.target = event.target.parentElement.parentElement;
    }

    Template.instance().state.set('selectedEpisode', event.target.dataset.episode);
    Template.instance().state.set('selectedSource', event.target.dataset.source);
    Template.instance().state.set('iframeErrors', []);
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

      this.template.view.parentView.parentView._templateInstance.state.set('selectedEpisode', undefined);
      this.template.view.parentView.parentView._templateInstance.state.set('selectedSource', undefined);

      this.done();
      return false;
    }
  }
});
