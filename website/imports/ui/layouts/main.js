import './main.html';

Template.layouts_main.onCreated(function() {
  // TODO: Use a more general SEO solution
  Session.set('BreadCrumbs', JSON.stringify([]));
  Session.set('PageTitle', 'AnimeSentinel');
  this.autorun(() => {
    document.title = JSON.parse(Session.get('BreadCrumbs')).reduce((total, breadCrumb) => {
      return total + breadCrumb.name + ' / ';
    }, '') + Session.get('PageTitle')
  });

  // Set the initially selected translation type
  this.autorun(() => {
    if (typeof getStorageItem('SelectedTranslationType') === 'undefined') {
      setStorageItem('SelectedTranslationType', 'sub');
    }
  });
});

Template.layouts_main.onRendered(function() {
  // Communicate with the add on
  window.addEventListener('message', (event) => {
    if (event.data && event.data.direction === 'from-content-script' && event.data.message === 'ready') {
      Session.set('AddOnInstalled', true)
    }
  });
  window.postMessage({
    direction: 'from-page-script',
    message: 'ready'
  }, '*');
});

Template.layouts_main.events({
  'click #mainNav .nav-link'(event) {
    $('#mainNav').collapse('hide');
  },
});

Template.layouts_main.helpers({
  pageTitle() {
    return Session.get('PageTitle');
  },

  breadCrumbs() {
    return JSON.parse(Session.get('BreadCrumbs'));
  },

  loadingBackground() {
    return Session.get('LoadingBackground');
  }
});

AutoForm.hooks({
  translationTypeSelectionForm: {
    onSubmit(insertDoc) {
      setStorageItem('SelectedTranslationType', insertDoc.translationType);
      this.done();
      return false;
    }
  }
});
