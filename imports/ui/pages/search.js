import './search.html';
import { Shows } from '/imports/api/shows/shows.js';
import {Searches} from "../../api/searches/searches";

Template.pages_search.onCreated(function() {
  Session.set('PageTitle', 'Browse Anime');

  this.searchQuery = new ReactiveVar(undefined);
  this.searchLimit = new ReactiveVar(100);

  this.remoteSearchDebounced = _.debounce(function(query) {
    if (query) {
      Meteor.call('searches.startSearch', query);
    }
  }, 1000);

  this.autorun(() => {
    this.subscribe('shows.search', this.searchQuery.get(), this.searchLimit.get());
  });
  this.autorun(() => {
    this.remoteSearchDebounced(this.searchQuery.get());
    this.subscribe('searches.withQuery', this.searchQuery.get());
  });
});

Template.pages_search.helpers({
  shows() {
    return Shows.querySearch(Template.instance().searchQuery.get(), Template.instance().searchLimit.get());
  },

  searching() {
    let currentSearch = Searches.queryWithQuery(Template.instance().searchQuery.get()).fetch()[0];
    return currentSearch && currentSearch.busy();
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
