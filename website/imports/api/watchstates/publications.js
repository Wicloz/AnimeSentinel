import { WatchStates } from './watchstates.js';

Meteor.publish('watchStates.currentUserShow', function(malId) {
  return WatchStates.queryUnique(this.userId, malId);
});

Meteor.publish('watchStates.currentUserStatuses', function(statuses) {
  return WatchStates.queryWithStatuses(this.userId, statuses);
});
