import { Shows } from './shows.js';

Meteor.publish('shows.search', function(search, limit, translationType) {
  return Shows.querySearch(search, limit, translationType);
});

Meteor.publish('shows.withId', function(id) {
  Schemas.id.validate({id});
  return Shows.find(id);
});

Meteor.publish('shows.withIds', function(ids) {
  return Shows.queryWithIds(ids);
});

Meteor.publish('shows.forOverview', function(malIds, limit) {
  return Shows.queryForOverview(malIds, limit);
});
