import {Episodes} from "./episodes";

Meteor.publish('episodes.forShow', function(showId) {
  return Episodes.queryForShow(showId);
});

Meteor.publish('episodes.forTranslationType', function(showId, translationType, limit) {
  return Episodes.queryForTranslationType(showId, translationType, limit);
});

Meteor.publish('episodes.toWatch', function(showId, translationType, lastWatched) {
  return Episodes.queryToWatch(showId, translationType, lastWatched);
});
