import {Episodes} from "./episodes";

Meteor.publish('episodes.forShow', function(showId) {
  return Episodes.queryForShow(showId);
});

Meteor.publish('episodes.forTranslationType', function(showId, translationType, limit) {
  return Episodes.queryForTranslationType(showId, translationType, limit);
});

Meteor.publish('episodes.forEpisode', function(showId, translationType, episodeNumStart, episodeNumEnd) {
  return Episodes.queryForEpisode(showId, translationType, episodeNumStart, episodeNumEnd);
});

Meteor.publish('episodes.recent', function(limit) {
  return Episodes.queryRecent(limit);
});
