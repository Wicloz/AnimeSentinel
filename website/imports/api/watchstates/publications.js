import { WatchStates } from './watchstates.js';

Meteor.publish('watchStates.currentUserUnique', function(malId) {
  return WatchStates.queryUnique(this.userId, malId);
});

Meteor.publish('watchStates.currentUserWithStatuses', function(statuses) {
  return WatchStates.queryWithStatuses(this.userId, statuses);
});
