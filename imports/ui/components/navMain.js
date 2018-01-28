import './navMain.html';
let ResizeSensor = require('css-element-queries/src/ResizeSensor');

Template.components_nav_main.onRendered(function() {
  $('.navMainSide').sideNav({
    closeOnClick: true
  });

  let pageTitleElement = $('#page-title');

  let resizeTitleDebounced = _.debounce(function() {
    pageTitleElement.css({
      fontSize: '2.1rem'
    });
    while (pageTitleElement.height() > 64) {
      pageTitleElement.css({
        fontSize: (pageTitleElement.css('fontSize').replace('px', '') - 0.5) + 'px'
      });
    }
  }, 100, true);

  new ResizeSensor(pageTitleElement, function() {
    resizeTitleDebounced();
  });
  resizeTitleDebounced();
});

Template.components_nav_main.helpers({
  pageTitle() {
    return Session.get('PageTitle');
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
