import {Episodes} from "./episodes";

Meteor.publish('episodes.forShow', function(showId) {
  return Episodes.queryForShow(showId);
});

Meteor.publish('episodes.forEpisode', function(showId, episodeNum, translationType) {
  return Episodes.queryForEpisode(showId, translationType, episodeNum);
});
