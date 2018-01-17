import './search.html';

Template.pages_search.onCreated(function() {
  Session.set('PageTitle', 'Browse Anime');
});

Template.pages_search.helpers({
  schemaSearch() {
    return Schemas.search;
  }
});
