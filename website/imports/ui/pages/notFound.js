import './notFound.html';

Template.pages_notFound.onCreated(function() {
  Session.set('PageTitle', 'Page Not Found');
});
