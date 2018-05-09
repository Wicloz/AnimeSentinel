import './overview.html';
import {WatchStates} from '../../api/watchstates/watchstates';
import {Shows} from '../../api/shows/shows';
import {Episodes} from '../../api/episodes/episodes';
import moment from 'moment-timezone';

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

  this.memoizedShowSort = _.memoize((a, b) => {
    let nextA = a.nextEpisodeDate('sub');
    let nextB = b.nextEpisodeDate('sub');
    let diff = undefined;

    if (a.airingState.sub === 'Completed' && b.airingState.sub === 'Completed') {
      diff = 0;
    }
    else if (a.airingState.sub === 'Completed') {
      diff = 1;
    }
    else if (b.airingState.sub === 'Completed') {
      diff = -1;
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

    if (diff === 0) {
      diff = (b.availableEpisodes.sub - b.watchedEpisodes()) - (a.availableEpisodes.sub - a.watchedEpisodes());
    }

    if (diff === 0) {
      diff = moment.duration(moment.utc(a.airedStart) - moment.utc(b.airedStart)).asMinutes();
    }

    if (diff === 0) {
      diff = a.name.localeCompare(b.name);
    }

    return diff;
  },

  (a, b) => {
    return [[
      a.airingState.sub,
      a.nextEpisodeDate('sub'),
      a.availableEpisodes.sub,
      a.watchedEpisodes(),
      a.airedStart,
      a.name,
    ], [
      b.airingState.sub,
      b.nextEpisodeDate('sub'),
      b.availableEpisodes.sub,
      b.watchedEpisodes(),
      b.airedStart,
      b.name,
    ]];
  });

  this.currentDisplayShows = function() {
    return Shows.queryWithMalIds(this.getMalIds()).fetch().sort(this.memoizedShowSort).slice(0, this.state.get('displayLimit'));
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
