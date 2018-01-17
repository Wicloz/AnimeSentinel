import './search.html';
import { Shows } from '/imports/api/shows/shows.js';

Template.pages_search.onCreated(function() {
  Session.set('PageTitle', 'Browse Anime');

  this.searchQuery = new ReactiveVar('');
  this.autorun(() => {
    this.subscribe('shows.search', this.searchQuery.get());
  });
});

Template.pages_search.helpers({
  shows() {
    return Shows.querySearch(Template.instance().searchQuery.get());
  }
});

AutoForm.hooks({
  animeSearchForm: {
    onSubmit(insertDoc) {
      this.template.view.parentView.parentView._templateInstance.searchQuery.set(insertDoc.query);
      Session.set('LoadingBackground', true);
      Meteor.call('shows.startSearch', insertDoc.query, (err) => {
        Session.set('LoadingBackground', false);
        if (err) {
          alert(err);
        }
      });
      this.done();
      return false;
    }
  }
});
