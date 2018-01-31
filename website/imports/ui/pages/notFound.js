import './notFound.html';

Template.pages_notFound.onCreated(function() {
  Session.set('PageTitle', 'Page Not Found');
});

Template.pages_notFound.helpers({
  breadCrumbsWithUrl() {
    return JSON.parse(Session.get('BreadCrumbs')).filter((breadcrumb) => {
      return breadcrumb.url;
    });
  }
});
