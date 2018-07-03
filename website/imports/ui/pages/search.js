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
    searchQuery: undefined,
    searchOptions: {},
    searchLimit: this.limitIncrement
  });

  // Local functions
  this.getCombinedSearchOptions = function() {
    let combinedSearchOptions = this.state.get('searchOptions');
    combinedSearchOptions.query = this.state.get('searchQuery');
    return combinedSearchOptions;
  };

  this.isSearching = function() {
    let currentSearch = Searches.queryWithSearch(this.getCombinedSearchOptions()).fetch()[0];
    return currentSearch && currentSearch.busy();
  };

  this.canLoadMoreShows = function() {
    return Shows.querySearch(this.getCombinedSearchOptions(), this.state.get('searchLimit')).count() >= this.state.get('searchLimit');
  };

  // Subscribe to searches based on search options
  this.autorun(() => {
    this.subscribe('searches.withSearch', this.getCombinedSearchOptions());
    this.state.set('searchLimit', this.limitIncrement);
  });

  // Search when the subscription is ready
  this.autorun(() => {
    if (this.subscriptionsReady() || Searches.queryWithSearch(this.getCombinedSearchOptions()).count()) {
      Meteor.call('searches.startSearch', this.getCombinedSearchOptions());
    }
  });

  // Subscribe to shows based on search options and limit
  this.autorun(() => {
    this.subscribe('shows.search', this.getCombinedSearchOptions(), this.state.get('searchLimit'));
  });

  // Subscribe to thumbnails for all shows
  this.autorun(() => {
    this.subscribe('thumbnails.withHashes', Shows.querySearch(this.getCombinedSearchOptions(), this.state.get('searchLimit')).fetch().reduce((total, show) => {
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
    return Shows.querySearch(Template.instance().getCombinedSearchOptions(), Template.instance().state.get('searchLimit'));
  },

  showsLoading() {
    return !Template.instance().subscriptionsReady() || Template.instance().isSearching() || Template.instance().canLoadMoreShows();
  }
});

Template.pages_search.events({
  'appear #load-more-results'(event) {
    if (Template.instance().subscriptionsReady() && Template.instance().canLoadMoreShows()) {
      Template.instance().state.set('searchLimit', Template.instance().state.get('searchLimit') + Template.instance().limitIncrement);
    }
  }
});

AutoForm.hooks({
  animeSearchFormQuery: {
    onSubmit(insertDoc) {
      this.template.view.parentView.parentView._templateInstance.state.set('searchQuery', insertDoc.query);
      this.done();
      return false;
    }
  },

  animeSearchFormOptions: {
    onSubmit(insertDoc) {
      console.log(insertDoc);
      this.template.view.parentView.parentView._templateInstance.state.set('searchOptions', insertDoc);
      this.done();
      return false;
    }
  }
});
