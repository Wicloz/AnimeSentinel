import './search.html';

Template.pages_search.onCreated(function() {
  Session.set('PageTitle', 'Browse Anime');
});

AutoForm.hooks({
  animeSearchForm: {
    onSubmit(insertDoc) {
      console.log(insertDoc.query);
      this.done();
      return false;
    }
  }
});
