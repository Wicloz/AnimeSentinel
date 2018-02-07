import { Shows } from './shows.js';

Meteor.publish('shows.search', function(search, limit) {
  return Shows.querySearch(search, limit);
});

Meteor.publish('shows.withId', function(id) {
  Schemas.id.validate({id});
  return Shows.find(id);
});
