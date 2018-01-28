import { Searches } from './searches.js';

Meteor.publish('searches.withQuery', function(query) {
  return Searches.queryWithQuery(query);
});
