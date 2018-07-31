import { WatchStates } from './watchstates.js';

Meteor.publish('watchStates.currentUserUnique', function(malId) {
  if (!this.userId) {
    return this.ready();
  }

  return WatchStates.queryUnique(this.userId, malId);
});

Meteor.publish('watchStates.currentUserUniqueMultiple', function(malIds) {
  if (!this.userId) {
    return this.ready();
  }

  return WatchStates.queryUniqueMultiple(this.userId, malIds);
});

Meteor.publish('watchStates.currentUserWithStatuses', function(statuses) {
  if (!this.userId) {
    return this.ready();
  }

  return WatchStates.queryWithStatuses(this.userId, statuses);
});
