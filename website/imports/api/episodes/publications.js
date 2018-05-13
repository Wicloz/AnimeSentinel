import {Episodes} from "./episodes";

Meteor.publish('episodes.forShow', function(showId) {
  return Episodes.queryForShow(showId);
});

Meteor.publish('episodes.forTranslationType', function(showId, translationType) {
  return Episodes.queryForTranslationType(showId, translationType);
});

Meteor.publish('episodes.recent', function(limit) {
  return Episodes.queryRecent(limit);
});

Meteor.publish('episodes.toWatch', function(showId, translationType, lastWatched) {
  return Episodes.queryToWatch(showId, translationType, lastWatched);
});
