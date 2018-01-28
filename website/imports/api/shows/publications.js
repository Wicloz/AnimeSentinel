import { Shows } from './shows.js';

Meteor.publish('shows.search', function(query, limit) {
  return Shows.querySearch(query, limit);
});

Meteor.publish('shows.withId', function(id) {
  Schemas.id.validate({id});
  return Shows.find(id);
});
