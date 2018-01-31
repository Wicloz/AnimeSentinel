import './main.html';
import '/imports/ui/components/navMain.js';
import * as RLocalStorage from 'meteor/simply:reactive-local-storage';

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
});
