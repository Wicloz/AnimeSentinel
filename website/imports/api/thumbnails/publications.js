import {Thumbnails} from './thumbnails';

Meteor.publish('thumbnails.withHashes', function(hashes) {
  return Thumbnails.queryWithHashes(hashes);
});
