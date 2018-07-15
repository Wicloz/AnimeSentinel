import './search.html';
import {Searches} from "../../api/searches/searches";
import { Shows } from '/imports/api/shows/shows.js';
import '/imports/ui/components/image.js';

Template.pages_search.onCreated(function() {
  // Set page variables
  Session.set('BreadCrumbs', JSON.stringify([]));
  Session.set('PageTitle', 'Browse Anime');

  // Local variables
  this.limitIncrement = 20;

  this.state = new ReactiveDict();
  this.state.setDefault({
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
    return Shows.querySearch(this.getSearchOptions(), this.state.get('searchLimit')).count() >= this.state.get('searchLimit');
  };

  // Subscribe to searches based on search options
  this.autorun(() => {
    this.subscribe('searches.withSearch', this.getSearchOptions());
    this.state.set('searchLimit', this.limitIncrement);
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
    this.subscribe('shows.search', this.getSearchOptions(), this.state.get('searchLimit'));
  });

  // Subscribe to thumbnails for all shows
  this.autorun(() => {
    this.subscribe('thumbnails.withHashes', Shows.querySearch(this.getSearchOptions(), this.state.get('searchLimit')).fetch().reduce((total, show) => {
      return total.concat(show.thumbnails);
    }, []));
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
    return Shows.querySearch(Template.instance().getSearchOptions(), Template.instance().state.get('searchLimit'));
  },

  showsLoading() {
    return !Template.instance().subscriptionsReady() || Template.instance().isSearching() || Template.instance().canLoadMoreShows();
  },

  searchOptions() {
    return Template.instance().getSearchOptions();
  }
});

Template.pages_search.events({
  'appear #load-more-results'(event) {
    if (Template.instance().subscriptionsReady() && Template.instance().canLoadMoreShows()) {
      Template.instance().state.set('searchLimit', Template.instance().state.get('searchLimit') + Template.instance().limitIncrement);
    }
  },

  'click #animeSearchFormOptionsReset'(event) {
    let reset = {};
    Object.keys(FlowRouter.current().queryParams).forEach((key) => {
      if (key !== 'query') {
        reset[key] = null;
      }
    });
    FlowRouter.withReplaceState(() => {
      FlowRouter.setQueryParams(reset);
    });
  }
});

AutoForm.hooks({
  animeSearchFormQuery: {
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

  animeSearchFormOptions: {
    onSubmit(insertDoc) {
      // Remove missing parameters
      Object.keys(FlowRouter.current().queryParams).forEach((key) => {
        if (key !== 'query' && !insertDoc.hasOwnProperty(key)) {
          insertDoc[key] = null;
        }
      });
      // Remove default parameters
      Object.keys(insertDoc).forEach((key) => {
        if (Schemas.Search._schema[key].defaultValue === insertDoc[key]) {
          insertDoc[key] = null;
        }
      });

      FlowRouter.withReplaceState(() => {
        FlowRouter.setQueryParams(insertDoc);
      });
      this.done();
      return false;
    }
  }
});
