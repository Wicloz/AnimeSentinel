// Stores
let thumbnailStoreJpeg = new FS.Store.GridFS('thumbnailsJPEG', {
  beforeWrite(fileObj) {
    return {
      extension: 'jpeg',
      type: 'image/jpeg'
    };
  },
  transformWrite(fileObj, readStream, writeStream) {
    gm(readStream).stream('JPEG').pipe(writeStream);
  }
});

let thumbnailStoreWebp = new FS.Store.GridFS('thumbnailsWEBP', {
  beforeWrite(fileObj) {
    return {
      extension: 'webp',
      type: 'image/webp'
    };
  },
  transformWrite(fileObj, readStream, writeStream) {
    gm(readStream).stream('WEBP').pipe(writeStream);
  }
});

// Collection
export const Thumbnails = new FS.Collection('thumbnails', {
  stores: [
    thumbnailStoreJpeg,
    thumbnailStoreWebp
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
  let thumbnails = Thumbnails.queryWithHashes([hash]);

  if (!thumbnails.count()) {
    startDownloadToStream(url, (readStream, contentType, contentLength) => {
      if (readStream && contentType && contentLength) {
        let newFile = new FS.File();
        newFile.attachData(readStream, {type: contentType});
        newFile.size(contentLength);
        newFile.name(hash);
        newFile.extension('pict');
        Thumbnails.insert(newFile);
      }
      if (thumbnails.count() > 1) {
        Thumbnails.cleanForHash(hash);
      }
    });
  }

  else if (thumbnails.count() > 1) {
    Thumbnails.cleanForHash(hash);
  }

  return hash;
};

Thumbnails.cleanForHash = function(hash) {
  Thumbnails.queryWithHashes([hash]).forEach((thumbnail, index) => {
    if (index > 0) {
      Thumbnails.remove(thumbnail._id);
    }
  });
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
  Schemas.Show.validate({
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
