import {Shows} from '../shows/shows';

// Collection
let options = {};
if (Meteor.isProduction && Meteor.isServer) {
  options.path = '~/as-thumbnails';
}

export const Thumbnails = new FS.Collection('thumbnails', {
  stores: [new FS.Store.FileSystem('thumbnails', options)]
});

Thumbnails.allow({
  download() {
    return true;
  },
  insert() {
    return false;
  },
  update() {
    return false;
  },
  remove() {
    return false;
  }
});

// Helpers
Thumbnails.addThumbnail = function(url) {
  let hash = createWeakHash(url);

  if (!Thumbnails.queryWithHashes([hash]).count()) {
    downloadToStream(url, (readStream, contentType) => {
      if (readStream) {
        let newFile = new FS.File();
        newFile.attachData(readStream, {type: contentType});
        newFile.name(hash);
        Thumbnails.insert(newFile);
      }
    });
  }

  return hash;
};

// Queries
Thumbnails.queryWithHashes = function(hashes) {
  Shows.simpleSchema().validate({
    thumbnails: hashes
  }, {
    keys: ['thumbnails']
  });

  return Thumbnails.find({
    'original.name': {
      $in: hashes
    }
  });
};
