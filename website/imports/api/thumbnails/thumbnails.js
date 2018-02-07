import {Shows} from '../shows/shows';

// Stores
let thumbnailStore = new FS.Store.GridFS('thumbnails', {});

// Collection
export const Thumbnails = new FS.Collection('thumbnails', {
  stores: [
    thumbnailStore
  ],
  filter: {
    allow: {
      contentTypes: ['image/*']
    },
    onInvalid(message) {
      if (Meteor.isClient) {
        alert(message);
      } else {
        console.error(message);
      }
    }
  }
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
    downloadToStream(url, (readStream, contentType, contentLength) => {
      if (readStream && contentType && contentLength) {
        let newFile = new FS.File();
        newFile.attachData(readStream, {type: contentType});
        newFile.size(contentLength);
        newFile.name(hash);
        newFile.extension('pict');
        Thumbnails.insert(newFile);
      }
    });
  }

  return hash;
};

Thumbnails.removeWithHashes = function(hashes) {
  hashes = hashes.map((hash) => {
    return hash + '.pict';
  });

  Thumbnails.remove({
    'original.name': {
      $in: hashes
    }
  });
};

// Queries
Thumbnails.queryWithHashes = function(hashes) {
  Shows.simpleSchema().validate({
    thumbnails: hashes
  }, {
    keys: ['thumbnails']
  });

  hashes = hashes.map((hash) => {
    return hash + '.pict';
  });

  return Thumbnails.find({
    'original.name': {
      $in: hashes
    }
  });
};
