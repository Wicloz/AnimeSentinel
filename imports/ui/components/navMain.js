import './navMain.html';

Template.components_nav_main.onRendered(function() {
  $('.navMainSide').sideNav({
    closeOnClick: true
  });
});

Template.components_nav_main.helpers({
  pageTitle() {
    return Session.get('PageTitle');
  }
});
