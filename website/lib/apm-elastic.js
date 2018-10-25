if (Meteor.isServer && Meteor.isProduction) {
  require('elastic-apm-node').start({
    serviceName: 'AnimeSentinel',
  });
}
