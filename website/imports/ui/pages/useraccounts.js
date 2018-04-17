import './useraccounts.html';

Template.pages_useraccounts.onCreated(function() {
  // Set page variables
  Session.set('BreadCrumbs', JSON.stringify([]));

  // Set page title from the route name
  this.autorun(() => {
    let routeName = FlowRouter.getRouteName();
    if (routeName) {
      routeName = routeName.replace(/([A-Z])/g, ' $1');
      routeName = routeName.charAt(0).toUpperCase() + routeName.slice(1);
      Session.set('PageTitle', routeName);
    }
  });
});
