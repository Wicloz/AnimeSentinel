import './overview.html';
import {WatchStates} from '../../api/watchstates/watchstates';
import {Shows} from '../../api/shows/shows';
import {Episodes} from '../../api/episodes/episodes';
import moment from 'moment-timezone';
import ScrapingHelpers from '../../streamers/scrapingHelpers';

Template.pages_overview.onRendered(function() {
  $('#load-more-items').appear();
});

Template.pages_overview.events({
  'click .reload-mal-list-btn'(event) {
    Meteor.call('users.updateCurrentUserWatchStates');
  },

  'click .reload-show-btn'(event) {
    Meteor.call('shows.attemptUpdate', event.target.dataset.showid);
  },

  'click .reload-all-shows-btn'(event) {
    Shows.queryWithMalIds(Template.instance().getMalIds()).forEach((show) => {
      Meteor.call('shows.attemptUpdate', show._id);
    });
  },

  'appear #load-more-items'(event) {
    if (Template.instance().subscriptionsReady()) {
      Template.instance().state.set('displayLimit', Template.instance().state.get('displayLimit') + 10);
    }
  }
});

Template.pages_overview.helpers({
  displayShows() {
    return Template.instance().currentDisplayShows();
  },

  episodesToWatch(show) {
    let episodes = [];

    Episodes.queryToWatch(show._id, 'sub', show.watchedEpisodes()).forEach((episode) => {
      if (!episodes.hasPartialObjects({
        episodeNumStart: episode.episodeNumStart,
        episodeNumEnd: episode.episodeNumEnd
      })) {
        episodes.push(episode);
      }
    });

    return episodes;
  },

  loading() {
    return !Template.instance().subscriptionsReady() || Template.instance().currentDisplayShows().length >= Template.instance().state.get('displayLimit');
  }
});

Template.pages_overview.onCreated(function() {
  // Set page variables
  Session.set('BreadCrumbs', JSON.stringify([]));
  Session.set('PageTitle', 'Anime Overview');

  // Local functions
  this.getMalIds = function() {
    return WatchStates.queryWithStatuses(Meteor.userId(), getStorageItem('SelectedStatuses')).fetch().reduce((total, watchState) => {
      return total.concat([watchState.malId]);
    }, []);
  };

  this.currentDisplayShows = function() {
    return Shows.queryWithMalIds(this.getMalIds()).fetch().sort((a, b) => {
      let nextA = a.nextEpisodeDate('sub');
      let nextB = b.nextEpisodeDate('sub');
      let stateA = a.airingState('sub');
      let stateB = b.airingState('sub');
      let diff = undefined;

      if (stateA === 'Completed' && stateB === 'Completed') {
        diff = 0;
      }
      else if (stateA === 'Completed') {
        diff = -1;
      }
      else if (stateB === 'Completed') {
        diff = 1;
      }

      else if (typeof nextA === 'undefined' && typeof nextB === 'undefined') {
        diff = 0;
      }
      else if (typeof nextA === 'undefined') {
        diff = -1;
      }
      else if (typeof nextB === 'undefined') {
        diff = 1;
      }

      else {
        diff = moment.duration(moment.utc(nextA) - moment.utc(nextB)).asMinutes();
      }

      return diff;
    }).slice(0, this.state.get('displayLimit'));
  };

  // Local variables
  this.state = new ReactiveDict();
  this.state.setDefault({
    displayLimit: 10
  });

  // Set the initially selected statuses
  this.autorun(() => {
    if (typeof getStorageItem('SelectedStatuses') === 'undefined') {
      setStorageItem('SelectedStatuses', ['watching']);
    }
  });

  // Subscribe to required watch states
  this.autorun(() => {
    this.subscribe('watchStates.currentUserWithStatuses', getStorageItem('SelectedStatuses'));
  });

  // Subscribe to shows based on watch states
  this.autorun(() => {
    this.subscribe('shows.withMalIds', this.getMalIds())
  });

  // Subscribe to episodes based on all shows
  this.autorun(() => {
    Shows.queryWithMalIds(this.getMalIds()).forEach((show) => {
      this.subscribe('episodes.forTranslationType', show._id, 'sub', 1);
      Episodes.queryForTranslationType(show._id, 'sub', 1).forEach((episode) => {
        this.subscribe('episodes.forEpisode', episode.showId, episode.translationType, episode.episodeNumStart, episode.episodeNumEnd);
      });
    });
  });

  // Subscribe to thumbnails and episodes based on displayed shows
  this.autorun(() => {
    let thumbnails = [];
    this.currentDisplayShows().forEach((show) => {
      thumbnails = thumbnails.concat(show.thumbnails);
      this.subscribe('episodes.toWatch', show._id, 'sub', show.watchedEpisodes());
    });
    this.subscribe('thumbnails.withHashes', thumbnails);
  });
});

AutoForm.hooks({
  statusesSelectionForm: {
    onSubmit(insertDoc) {
      setStorageItem('SelectedStatuses', insertDoc.statuses || []);
      this.done();
      return false;
    }
  }
});
