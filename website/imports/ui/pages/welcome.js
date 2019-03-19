import './welcome.html';

Template.pages_welcome.onCreated(function() {
  Session.set('BreadCrumbs', JSON.stringify([]));
  Session.set('PageTitle', 'AnimeSentinel');
});

Template.pages_welcome.helpers({
  queryRecent() {
    return {
      sortBy: 'Latest Update',
      sortDirection: -1
    };
  }
});
