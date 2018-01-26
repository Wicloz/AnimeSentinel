import './main.html';
import '/imports/ui/components/navMain.js';

Template.layouts_main.onRendered(function() {
  $('.appear').appear();
});
