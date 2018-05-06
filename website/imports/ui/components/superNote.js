import './superNote.html';

Template.components_superNote.helpers({
  superNoteDismissed() {
    return getStorageItem('SuperNoteDismissed');
  }
});

Template.components_superNote.events({
  'click .btn-dismiss'(event) {
    setStorageItem('SuperNoteDismissed', true);
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
});
