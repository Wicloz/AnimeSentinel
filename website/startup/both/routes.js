FlowRouter.notFound = {
  name: 'notFound',
  action() {
    if (Meteor.isClient) {
      require('/imports/ui/layouts/main.js');
      require('/imports/ui/pages/notFound.js');
    }
    BlazeLayout.render('layouts_main', {content: 'pages_notFound'});
  }
};

FlowRouter.route('/', {
  name: 'welcome',
  action() {
    if (Meteor.isClient) {
      require('/imports/ui/layouts/main.js');
      require('/imports/ui/pages/welcome.js');
    }
    BlazeLayout.render('layouts_main', {content: 'pages_welcome'});
  }
});

FlowRouter.route('/anime', {
  name: 'search',
  action() {
    if (Meteor.isClient) {
      require('/imports/ui/layouts/main.js');
      require('/imports/ui/pages/search.js');
    }
    BlazeLayout.render('layouts_main', {content: 'pages_search'});
  }
});

FlowRouter.route('/anime/overview', {
  name: 'animeOverview',
  triggersEnter: [AccountsTemplates.ensureSignedIn],
  action() {
    if (Meteor.isClient) {
      require('/imports/ui/layouts/main.js');
      require('/imports/ui/pages/overview.js');
    }
    BlazeLayout.render('layouts_main', {content: 'pages_overview'});
  }
});

FlowRouter.route('/anime/:showId', {
  name: 'show',
  action() {
    if (Meteor.isClient) {
      require('/imports/ui/layouts/main.js');
      require('/imports/ui/pages/show.js');
    }
    BlazeLayout.render('layouts_main', {content: 'pages_show'});
  }
});

FlowRouter.route('/anime/:showId/episodes/:translationType/:episodeNumStart-:episodeNumEnd-:notes', {
  name: 'episode',
  action() {
    if (Meteor.isClient) {
      require('/imports/ui/layouts/main.js');
      require('/imports/ui/pages/episode.js');
    }
    BlazeLayout.render('layouts_main', {content: 'pages_episode'});
  }
});

FlowRouter.route('/manage-profile', {
  name: 'manageProfile',
  triggersEnter: [AccountsTemplates.ensureSignedIn],
  action() {
    if (Meteor.isClient) {
      require('/imports/ui/layouts/main.js');
      require('/imports/ui/pages/profile.js');
    }
    BlazeLayout.render('layouts_main', {content: 'pages_profile'});
  },
});

FlowRouter.route('/sign-out', {
  name: 'logOut',
  triggersEnter: [AccountsTemplates.ensureSignedIn],
  action() {
    AccountsTemplates.logout();
  }
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
