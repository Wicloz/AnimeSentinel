import './navMain.html';
let ResizeSensor = require('css-element-queries/src/ResizeSensor');

Template.components_nav_main.onRendered(function() {
  $('.navMainSide').sideNav({
    closeOnClick: true
  });

  let breadcrumbsElement = $('#navbar-breadcrumbs');

  let resizeBreadcrumbsDebounced = _.debounce(function() {
    breadcrumbsElement.find('.breadcrumb').css({
      fontSize: '2.1rem'
    });
    breadcrumbsElement.css({
      fontSize: '2.1rem'
    });

    let fontSize = Number(breadcrumbsElement.css('fontSize').replace('px', ''));

    while (breadcrumbsElement.height() > 64 && fontSize > 1) {
      fontSize -= 0.5;
      breadcrumbsElement.find('.breadcrumb').css({
        fontSize: fontSize + 'px'
      });
      breadcrumbsElement.css({
        fontSize: fontSize + 'px'
      });
    }
  }, 100, true);

  new ResizeSensor(breadcrumbsElement, function() {
    resizeBreadcrumbsDebounced();
  });
  resizeBreadcrumbsDebounced();
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
