import './main.html';

Template.layouts_main.onCreated(function() {
  // TODO: Use a more general SEO solution
  Session.set('BreadCrumbs', JSON.stringify([]));
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

AutoForm.hooks({
  translationTypeSelectionForm: {
    onSubmit(insertDoc) {
      setStorageItem('SelectedTranslationType', insertDoc.translationType);
      this.done();
      return false;
    }
  }
});
