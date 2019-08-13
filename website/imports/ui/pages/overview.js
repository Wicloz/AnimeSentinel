import './overview.html';
import {WatchStates} from '../../api/watchstates/watchstates';
import {Shows} from '../../api/shows/shows';
import {Episodes} from '../../api/episodes/episodes';
import '/imports/ui/components/image.js';

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
    Shows.queryForOverview(Template.instance().getMalIds()).forEach((show) => {
      Meteor.call('shows.attemptUpdate', show._id);
    });
  },

  'appear #load-more-items'(event) {
    if (Template.instance().subscriptionsReady()) {
      Template.findState(this).set('displayLimit', Template.findState(this).get('displayLimit') + 10);
    }
  }
});

Template.pages_overview.helpers({
  displayShows() {
    return Shows.queryForOverview(Template.instance().getMalIds(), Template.findState(this).get('displayLimit'));
  },

  episodesToWatch(show) {
    let episodes = [];

    Episodes.queryToWatch(show._id, getStorageItem('SelectedTranslationType'), show.watchedEpisodes()).forEach((episode) => {
      if (!episodes.hasPartialObjects({
        episodeNumStart: episode.episodeNumStart,
        episodeNumEnd: episode.episodeNumEnd,
        notes: episode.notes
      })) {
        episodes.push(episode);
      }
    });

    return episodes;
  },

  loading() {
    return !Template.instance().subscriptionsReady() ||
      Shows.queryForOverview(Template.instance().getMalIds(), Template.findState(this).get('displayLimit')).count() >= Template.findState(this).get('displayLimit');
  },

  anyShowWorking() {
    return Shows.queryForOverview(Template.instance().getMalIds()).fetch().some((show) => {
      return show.locked();
    });
  },

  allShowsCached() {
    return Shows.queryForOverview(Template.instance().getMalIds()).fetch().every((show) => {
      return !show.expired();
    });
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

  // Local variables
  Template.makeState({
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
    this.subscribe('shows.forOverview', this.getMalIds())
  });

  // Subscribe to thumbnails and episodes based on displayed shows
  this.autorun(() => {
    let thumbnails = [];
    Shows.queryForOverview(Template.instance().getMalIds(), Template.findState(this).get('displayLimit')).forEach((show) => {
      thumbnails = thumbnails.concat(show.thumbnails);
      this.subscribe('episodes.toWatch', show._id, getStorageItem('SelectedTranslationType'), show.watchedEpisodes());
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
