import './main.html';
import '/imports/ui/components/navMain.js';

Template.layouts_main.helpers({
  loadingBackground() {
    return Session.get('LoadingBackground');
  }
});
