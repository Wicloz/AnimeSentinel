import '/imports/ui/layout/main.js';
import '/imports/ui/pages/welcome.js';
import '/imports/ui/pages/search.js';
import '/imports/ui/pages/notFound.js';

FlowRouter.notFound = {
  name: 'notFound',
  action() {
    BlazeLayout.render('layouts_main', {content: 'pages_notFound'});
  }
};

FlowRouter.route('/', {
  name: 'welcome',
  action() {
    BlazeLayout.render('layouts_main', {content: 'pages_welcome'});
  }
});

FlowRouter.route('/anime', {
  name: 'search',
  action() {
    BlazeLayout.render('layouts_main', {content: 'pages_search'});
  }
});
