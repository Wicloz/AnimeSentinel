import './search.html';
import { Shows } from '/imports/api/shows/shows.js';

Template.pages_search.onCreated(function() {
  Session.set('PageTitle', 'Browse Anime');

  this.remoteSearchDebounced = _.debounce(function(query) {
    Shows.remoteSearch(query);
  }, 1000);

  this.searchQuery = new ReactiveVar('');
  this.searchLimit = new ReactiveVar(100);
  this.autorun(() => {
    this.subscribe('shows.search', this.searchQuery.get(), this.searchLimit.get());
  });
});

Template.pages_search.helpers({
  shows() {
    return Shows.querySearch(Template.instance().searchQuery.get(), Template.instance().searchLimit.get());
  }
});

AutoForm.hooks({
  animeSearchForm: {
    onSubmit(insertDoc) {
      this.template.view.parentView.parentView._templateInstance.searchQuery.set(insertDoc.query);
      this.template.view.parentView.parentView._templateInstance.remoteSearchDebounced(insertDoc.query);
      this.done();
      return false;
    }
  }
});
