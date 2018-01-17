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

Template.components_nav_main.events({
  'submit #navMainSearchForm'(event) {
    event.preventDefault();
    FlowRouter.go('search');
    document.getElementById('searchFormInput').value = event.target.navMainSearchFormInput.value;
    event.target.navMainSearchFormInput.value = '';
    document.getElementById('searchFormInput').focus();
  }
});
