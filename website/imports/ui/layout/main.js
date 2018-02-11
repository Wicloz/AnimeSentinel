import './main.html';
import '/imports/ui/components/navMain.js';
import * as RLocalStorage from 'meteor/simply:reactive-local-storage';

Template.layouts_main.onCreated(function() {
  // TODO: Use a more general SEO solution
  Session.set('BreadCrumbs', JSON.stringify([]));
  this.autorun(() => {
    document.title = JSON.parse(Session.get('BreadCrumbs')).reduce((total, breadCrumb) => {
      return total + breadCrumb.name + ' > ';
    }, '') + Session.get('PageTitle')
  });
});

Template.layouts_main.onRendered(function() {
  window.addEventListener('message', (event) => {
    if (event.source === window && event.data && event.data.direction === 'from-content-script' && event.data.message === 'ready') {
      Session.set('AddOnInstalled', true)
    }
  });
  window.postMessage({
    direction: 'from-page-script',
    message: 'ready'
  }, '*');
});

Template.layouts_main.helpers({
  superNoteDismissed() {
    return RLocalStorage.getItem('SuperNoteDismissed');
  }
});

Template.layouts_main.events({
  'click .btn-dismiss'(event) {
    RLocalStorage.setItem('SuperNoteDismissed', true);
  },

  'click .btn-oldsite-desktop'(event) {
    $('.tap-target-desktop').tapTarget('open');
  },

  'click .btn-oldsite-mobile'(event) {
    $('.button-collapse').sideNav('show');
    setTimeout(function() {
      $('.tap-target-mobile').tapTarget('open');
    }, 300);
  },

  'click #fabSearchFormButton'(event) {
    setTimeout(() => {
      $('#fabSearchFormQuery').focus();
    }, 100);
  },

  'submit #fabSearchForm'(event) {
    event.preventDefault();
    FlowRouter.go('search');

    let animeSearchFormQueryField = $('#animeSearchFormQuery').find('input[name="query"]');
    let fabSearchFormField = $('#fabSearchFormQuery');

    animeSearchFormQueryField.val(fabSearchFormField.val());
    fabSearchFormField.val('');

    animeSearchFormQueryField.focus();
    animeSearchFormQueryField.submit();
    $('#fabSearchFormButton.toolbar').closeToolbar();
  }
});
