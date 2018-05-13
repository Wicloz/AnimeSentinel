import './main.html';
import '/imports/ui/components/navMain.js';
import '/imports/ui/components/superNote.js';
import '/imports/ui/components/footer.js';

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
    if (event.data && event.data.direction === 'from-content-script' && event.data.message === 'ready') {
      Session.set('AddOnInstalled', true)
    }
  });
  window.postMessage({
    direction: 'from-page-script',
    message: 'ready'
  }, '*');
});
