if (Meteor.isServer) {
  require('elastic-apm-node').start({
    serviceName: 'AnimeSentinel',
  })
}
