import {Episodes} from "./episodes";

Meteor.publish('episodes.forShow', function(showId) {
  return Episodes.queryForShow(showId);
});
