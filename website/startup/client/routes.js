import '/imports/ui/layouts/main.js';
import '/imports/ui/pages/welcome.js';
import '/imports/ui/pages/search.js';
import '/imports/ui/pages/notFound.js';
import '/imports/ui/pages/show.js';
import '/imports/ui/pages/episode.js';
import '/imports/ui/pages/recent.js';
import '/imports/ui/pages/profile.js';

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

FlowRouter.route('/recent', {
  name: 'recent',
  action() {
    BlazeLayout.render('layouts_main', {content: 'pages_recent'});
  }
});

FlowRouter.route('/sign-out', {
  name: 'logOut',
  triggersEnter: [AccountsTemplates.ensureSignedIn],
  action() {
    AccountsTemplates.logout();
  }
});

FlowRouter.route('/manage-profile', {
  name: 'manageProfile',
  triggersEnter: [AccountsTemplates.ensureSignedIn],
  action() {
    BlazeLayout.render('layouts_main', {content: 'pages_profile'});
  },
});

AccountsTemplates.configureRoute('signIn', {
  name: 'logIn',
  redirect: 'manageProfile'
});

AccountsTemplates.configureRoute('signUp', {
  name: 'register',
  redirect: 'manageProfile'
});

AccountsTemplates.configureRoute('forgotPwd', {
  name: 'forgotPassword',
  redirect: 'forgotPassword'
});

AccountsTemplates.configureRoute('resetPwd', {
  name: 'resetPassword',
  redirect: function() {
    if (Meteor.user()) {
      FlowRouter.go('manageProfile');
    } else {
      FlowRouter.go('logIn');
    }
  }
});

AccountsTemplates.configureRoute('resendVerificationEmail', {
  name: 'resendVerificationEmail',
  redirect: 'resendVerificationEmail'
});

AccountsTemplates.configureRoute('verifyEmail', {
  name: 'verifyEmail',
  redirect: function() {
    if (Meteor.user()) {
      FlowRouter.go('manageProfile');
    } else {
      FlowRouter.go('logIn');
    }
  }
});
