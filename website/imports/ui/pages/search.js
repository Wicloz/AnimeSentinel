import './search.html';
import {Searches} from "../../api/searches/searches";
import { Shows } from '/imports/api/shows/shows.js';
import '/imports/ui/components/image.js';
import moment from 'moment-timezone';

Template.pages_search.onCreated(function() {
  // Set page variables
  Session.set('BreadCrumbs', JSON.stringify([]));
  Session.set('PageTitle', 'Browse Anime');

  // Local variables
  this.limitIncrement = 20;
  Template.makeState({
    searchLimit: this.limitIncrement
  });

  // Local functions
  this.getSearchOptions = function() {
    FlowRouter.watchPathChange();
    return FlowRouter.current().queryParams;
  };

  this.isSearching = function() {
    let currentSearch = Searches.queryWithSearch(this.getSearchOptions()).fetch()[0];
    return currentSearch && currentSearch.locked();
  };

  this.canLoadMoreShows = function() {
    return Shows.querySearch(this.getSearchOptions(), Template.findState(this).get('searchLimit'), getStorageItem('SelectedTranslationType')).count() >= Template.findState(this).get('searchLimit');
  };

  this.moveSeasonOption = function(offset) {
    let season = this.getSearchOptions().season;
    let year = Number(this.getSearchOptions().year);

    if (!season && isNaN(year)) {
      season = Shows.validQuarters[moment.fromUtc().quarter() - 1];
      year = moment.fromUtc().year();
    }

    else if (!season) {
      year += offset;
    }

    else {
      let seasonIndex = Shows.validQuarters.indexOf(season) + offset;
      if (!isNaN(year)) {
        year += Math.floor(seasonIndex / 4);
      }
      seasonIndex = seasonIndex.mod(4);
      season = Shows.validQuarters[seasonIndex];
    }

    FlowRouter.withReplaceState(() => {
      FlowRouter.setQueryParams({
        season: season,
        year: isNaN(year) ? null : year
      });
    });
  };

  // Subscribe to searches based on search options
  this.autorun(() => {
    this.subscribe('searches.withSearch', this.getSearchOptions());
    Template.findState(this).set('searchLimit', this.limitIncrement);
  });

  // Search when the subscription is ready
  this.autorun(() => {
    if (this.subscriptionsReady() || Searches.queryWithSearch(this.getSearchOptions()).count()) {
      Tracker.nonreactive(() => {
        Meteor.call('searches.startSearch', this.getSearchOptions());
      });
    }
  });

  // Subscribe to shows based on search options and limit
  this.autorun(() => {
    this.subscribe('shows.search', this.getSearchOptions(), Template.findState(this).get('searchLimit'), getStorageItem('SelectedTranslationType'));
  });

  // Subscribe to thumbnails, episodes, and watchStates for all shows
  this.autorun(() => {
    let thumbnailHashes = [];
    let malIds = [];

    Shows.querySearch(this.getSearchOptions(), Template.findState(this).get('searchLimit'), getStorageItem('SelectedTranslationType')).forEach((show) => {
      thumbnailHashes = thumbnailHashes.concat(show.thumbnails);
      if (typeof show.malId !== 'undefined') {
        malIds.push(show.malId);
      }
      this.subscribe('episodes.forTranslationType', show._id, getStorageItem('SelectedTranslationType'), 1);
    });

    this.subscribe('thumbnails.withHashes', thumbnailHashes);
    if (Meteor.userId()) {
      this.subscribe('watchStates.currentUserUniqueMultiple', malIds);
    }
  });

  // Set 'LoadingBackground' parameter
  this.autorun(() => {
    Session.set('LoadingBackground', this.isSearching());
  });
});

Template.pages_search.onRendered(function() {
  $('#load-more-results').appear();
});

Template.pages_search.onDestroyed(function() {
  Session.set('LoadingBackground', false);
});

Template.pages_search.helpers({
  shows() {
    return Shows.querySearch(Template.instance().getSearchOptions(), Template.findState(this).get('searchLimit'), getStorageItem('SelectedTranslationType'));
  },

  showsLoading() {
    return !Template.instance().subscriptionsReady() || Template.instance().isSearching() || Template.instance().canLoadMoreShows();
  },

  searchOptions() {
    return Template.instance().getSearchOptions();
  },

  hasLatestEpisode(show) {
    return typeof show.latestEpisode(getStorageItem('SelectedTranslationType')) !== 'undefined';
  },

  latestEpisodeNumbers(show) {
    let latestEpisode = show.latestEpisode(getStorageItem('SelectedTranslationType'));
    return latestEpisode.episodeNumStart
      + (latestEpisode.episodeNumStart !== latestEpisode.episodeNumEnd ? ' - ' + latestEpisode.episodeNumEnd : '');
  },

  latestEpisodeNotes(show) {
    return show.latestEpisode(getStorageItem('SelectedTranslationType')).notes;
  },

  latestEpisodeLink(show) {
    let latestEpisode = show.latestEpisode(getStorageItem('SelectedTranslationType'));
    return FlowRouter.path('episode', {
      showId: latestEpisode.showId,
      translationType: latestEpisode.translationType,
      episodeNumStart: latestEpisode.episodeNumStart,
      episodeNumEnd: latestEpisode.episodeNumEnd,
      notes: latestEpisode.notesEncoded()
    });
  },

  latestEpisodeWatched(show) {
    return show.latestEpisode(getStorageItem('SelectedTranslationType')).watched();
  },

  sortDirectionDisabled() {
    return !Template.instance().getSearchOptions().sortBy;
  }
});

Template.pages_search.events({
  'appear #load-more-results'(event) {
    if (Template.instance().subscriptionsReady() && Template.instance().canLoadMoreShows()) {
      Template.findState(this).set('searchLimit', Template.findState(this).get('searchLimit') + Template.instance().limitIncrement);
    }
  },

  'click #animeSearchFormOptionsReset'(event) {
    let reset = {};
    Object.keys(FlowRouter.current().queryParams).forEach((key) => {
      if (!['query', 'sortBy', 'sortDirection'].includes(key)) {
        reset[key] = null;
      }
    });
    FlowRouter.withReplaceState(() => {
      FlowRouter.setQueryParams(reset);
    });
  },

  'click .btn-prev-season'(event) {
    Template.instance().moveSeasonOption(-1);
  },

  'click .btn-next-season'(event) {
    Template.instance().moveSeasonOption(1);
  },

  'click .btn-preset-recent'(event) {
    FlowRouter.setQueryParams({
      sortBy: 'Latest Update',
      sortDirection: -1
    });
  },

  'click .btn-preset-season'(event) {
    FlowRouter.setQueryParams({
      season: Shows.validQuarters[moment.fromUtc().quarter() - 1],
      year: moment.fromUtc().year()
    });
  }
});

AutoForm.hooks({
  animeSearchFormQuery: {
    formToDoc: function(doc) {
      // Clean up query
      if (typeof doc.query !== 'undefined') {
        doc.query = doc.query.cleanWhitespace(true);
        if (doc.query === '') {
          doc.query = null;
        }
      }
      return doc;
    },

    onSubmit(insertDoc) {
      FlowRouter.withReplaceState(() => {
        FlowRouter.setQueryParams({
          query: insertDoc.query
        });
      });
      this.done();
      return false;
    }
  },

  animeSearchFormSorting: {
    formToDoc: function(doc) {
      // Remove default parameters
      Object.keys(doc).forEach((key) => {
        if (Schemas.Search._schema[key].defaultValue === doc[key]) {
          doc[key] = null;
        }
      });
      // Remove sort direction for the default sort order
      if (!doc.sortBy) {
        doc.sortDirection = null;
      }
      return doc;
    },

    onSubmit(insertDoc) {
      FlowRouter.withReplaceState(() => {
        FlowRouter.setQueryParams({
          sortBy: insertDoc.sortBy,
          sortDirection: insertDoc.sortDirection
        });
      });
      this.done();
      return false;
    }
  },

  animeSearchFormOptions: {
    formToDoc: function(doc) {
      // Remove missing parameters
      Object.keys(FlowRouter.current().queryParams).forEach((key) => {
        if (!['query', 'sortBy', 'sortDirection'].includes(key) && !doc.hasOwnProperty(key)) {
          doc[key] = null;
        }
      });
      // Remove default parameters
      Object.keys(doc).forEach((key) => {
        if (Schemas.Search._schema[key].defaultValue === doc[key]) {
          doc[key] = null;
        }
      });
      return doc;
    },

    onSubmit(insertDoc) {
      FlowRouter.withReplaceState(() => {
        FlowRouter.setQueryParams(insertDoc);
      });
      this.done();
      return false;
    }
  }
});
