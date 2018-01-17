import { Shows } from './shows.js';

Meteor.publish('shows.search', function(query) {
  return Shows.querySearch(query);
});
