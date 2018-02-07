import { Searches } from './searches.js';

Meteor.publish('searches.withSearch', function(search) {
  return Searches.queryWithSearch(search);
});
