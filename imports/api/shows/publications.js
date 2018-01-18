import { Shows } from './shows.js';

Meteor.publish('shows.search', function(query, limit) {
  return Shows.querySearch(query, limit);
});
