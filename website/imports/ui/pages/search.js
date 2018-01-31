import './search.html';
import { Shows } from '/imports/api/shows/shows.js';
import {Searches} from "../../api/searches/searches";
import '/imports/ui/components/loadingIndicatorBackground.js';

Template.pages_search.onCreated(function() {
  // Set page variables
  Session.set('BreadCrumbs', JSON.stringify([]));
  Session.set('PageTitle', 'Browse Anime');

  // Local variables
  this.searchQuery = new ReactiveVar(undefined);
  this.searchLimit = new ReactiveVar(10);
  this.noMoreShows = new ReactiveVar(false);
  this.lastShowCount = 0;

  // Local functions
  this.isSearching = function() {
    let currentSearch = Searches.queryWithQuery(this.searchQuery.get()).fetch()[0];
    return currentSearch && currentSearch.busy();
  };

  // Subscribe to searches based on search options
  this.autorun(() => {
    this.lastShowCount = 0;
    this.searchLimit.set(10);
    this.noMoreShows.set(false);
    this.subscribe('searches.withQuery', this.searchQuery.get());
  });

  // Subscribe to shows based on search options and limit
  this.autorun(() => {
    this.subscribe('shows.search', this.searchQuery.get(), this.searchLimit.get());
  });

  // Check if more shows were added
  this.autorun(() => {
    if (this.subscriptionsReady()) {
      let currentShowCount = Shows.querySearch(this.searchQuery.get(), this.searchLimit.get()).count();

      console.log(this.lastShowCount, currentShowCount);

      if (this.lastShowCount === currentShowCount || currentShowCount < this.searchLimit.get()) {
        this.noMoreShows.set(true);
      } else {
        this.noMoreShows.set(false);
      }

      this.lastShowCount = currentShowCount;
    }
  });

  // Search when the subscription is ready
  this.autorun(() => {
    if (this.subscriptionsReady() && this.searchQuery.get()) {
      Meteor.call('searches.startSearch', this.searchQuery.get());
    }
  });
});

Template.pages_search.onRendered(function() {
  $('#load-more-results').appear();
});

Template.pages_search.helpers({
  shows() {
    return Shows.querySearch(Template.instance().searchQuery.get(), Template.instance().searchLimit.get());
  },

  searching() {
    return Template.instance().isSearching();
  },

  showsLoading() {
    return !Template.instance().noMoreShows.get() || Template.instance().isSearching() || !Template.instance().subscriptionsReady();
  }
});

Template.pages_search.events({
  'appear #load-more-results'(event) {
    Template.instance().searchLimit.set(Template.instance().searchLimit.get() + 10);
  }
});

AutoForm.hooks({
  animeSearchForm: {
    onSubmit(insertDoc) {
      if (insertDoc.query) {
        insertDoc.query = insertDoc.query.cleanQuery();
      }
      this.template.view.parentView.parentView._templateInstance.searchQuery.set(insertDoc.query);
      this.done();
      return false;
    }
  }
});
