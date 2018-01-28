import './navMain.html';

Template.components_nav_main.onCreated(function() {
  Session.set('BreadCrumbs', JSON.stringify([]));
});

Template.components_nav_main.onRendered(function() {
  $(".button-collapse").sideNav({
    closeOnClick: true
  });
});

Template.components_nav_main.helpers({
  pageTitle() {
    return Session.get('PageTitle');
  },

  breadCrumbs() {
    return JSON.parse(Session.get('BreadCrumbs'));
  }
});

AutoForm.hooks({
  navMainSearchForm: {
    onSubmit(insertDoc) {
      FlowRouter.go('search');
      let animeSearchFormQueryField = $('#animeSearchForm').find('input[name="query"]');
      animeSearchFormQueryField.val(insertDoc.query);
      animeSearchFormQueryField.focus();
      animeSearchFormQueryField.submit();
      this.done();
      return false;
    }
  }
});
