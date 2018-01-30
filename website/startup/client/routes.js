import '/imports/ui/layout/main.js';
import '/imports/ui/pages/welcome.js';
import '/imports/ui/pages/search.js';
import '/imports/ui/pages/notFound.js';
import '/imports/ui/pages/show.js';
import '/imports/ui/pages/episode.js';

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

FlowRouter.route('/anime/:showId', {
  name: 'show',
  action() {
    BlazeLayout.render('layouts_main', {content: 'pages_show'});
  }
});

FlowRouter.route('/anime/:showId/episodes/:translationType/:episodeNumStart-:episodeNumEnd', {
  name: 'episodeDouble',
  action() {
    BlazeLayout.render('layouts_main', {content: 'pages_episode'});
  }
});

FlowRouter.route('/anime/:showId/episodes/:translationType/:episodeNumBoth', {
  name: 'episodeSingle',
  action() {
    BlazeLayout.render('layouts_main', {content: 'pages_episode'});
  }
});
