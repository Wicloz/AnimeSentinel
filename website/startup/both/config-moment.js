import moment from 'moment-timezone';

if (Meteor.isServer) {
  moment.tz.setDefault('UTC');
}

moment.fromUtc = function(...args) {
  if (Meteor.isClient) {
    return moment.utc(...args).local();
  } else {
    return moment.utc(...args);
  }
};
