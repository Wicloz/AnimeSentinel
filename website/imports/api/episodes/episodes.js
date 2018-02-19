import SimpleSchema from 'simpl-schema';

// Collection
export const Episodes = new Mongo.Collection('episodes');

// Schema
Schemas.Episode = new SimpleSchema({
  _id: {
    type: String,
    optional: true
  },

  showId: {
    type: String,
    index: true
  },
  episodeNumStart: {
    type: Number,
    index: true
  },
  episodeNumEnd: {
    type: Number,
    index: true
  },
  translationType: {
    type: String,
    allowedValues: ['sub', 'dub', 'raw'],
    index: true
  },
  streamerId: {
    type: String,
    index: true
  },
  sourceName: {
    type: String,
    index: true
  },

  sourceUrl: {
    type: String
  },
  flags: {
    type: Array,
    optional: true,
    autoValue: function() {
      if ((!this.isSet || !this.value) && !this.isUpdate) {
        return [];
      }
      if (!this.isSet) {
        return undefined;
      }
      return this.value.reduce((total, value) => {
        value = value.trim();
        if (value && !total.includes(value)) {
          total.push(value);
        }
        return total;
      }, []);
    }
  },
  'flags.$': {
    type: String,
    allowedValues: ['flash', 'cloudflare', 'x-frame-options', 'mixed-content']
  },
  uploadDate: {
    type: Object,
    index: true,
    optional: true,
    defaultValue: {}
  },
  'uploadDate.year': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'uploadDate.month': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'uploadDate.date': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'uploadDate.hour': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'uploadDate.minute': {
    type: SimpleSchema.Integer,
    optional: true
  }
}, { tracker: Tracker });

Episodes.attachSchema(Schemas.Episode);

// Constants
Episodes.flagsWithoutAddOnPreference = ['cloudflare', 'mixed-content', 'flash'];
Episodes.flagsWithoutAddOnNever = ['x-frame-options'];
Episodes.flagsWithAddOnPreference = ['flash', 'mixed-content'];
Episodes.flagsWithAddOnNever = [];
Episodes.informationKeys = ['sourceUrl', 'flags'];

// Helpers
Episodes.helpers({
  remove() {
    Episodes.remove(this._id);
  },

  translationTypeExpanded() {
    return this.translationType.replace('dub', 'dubbed').replace('sub', 'subbed').capitalize();
  },

  mergeEpisode(other) {
    // Copy and merge attributes
    Object.keys(other).forEach((key) => {
      if (Episodes.informationKeys.includes(key) || (key === 'uploadDate' && Object.countNonEmptyValues(other[key]) >= Object.countNonEmptyValues(this[key]))) {
        this[key] = other[key];
      }
    });

    // Update database
    Episodes.update(this._id, {
      $set: Schemas.Episode.clean(this, {
        mutate: true
      })
    });

    // Remove other from database
    if (other._id) {
      other.remove();
    }
  },

  moveToShow(showId) {
    this.showId = showId;
    Episodes.remove(this._id);
    Episodes.addEpisode(this);
  }
});

Episodes.addEpisode = function(episode) {
  let id = undefined;

  // Get episodes which are the same
  let others = Episodes.queryUnique(episode.showId, episode.translationType, episode.episodeNumStart, episode.episodeNumEnd, episode.streamerId, episode.sourceName);

  // Merge episodes if found
  if (others.count()) {
    let firstOther = undefined;

    others.forEach((other) => {
      if (!firstOther) {
        firstOther = other;
      } else {
        firstOther.mergeEpisode(other);
      }
    });

    if (firstOther) {
      firstOther.mergeEpisode(episode);
      id = firstOther._id;
    }
  }

  // Insert otherwise
  else {
    id = Episodes.insert(episode);
  }

  // Return id
  return id;
};

Episodes.moveEpisodes = function(fromId, toId) {
  Episodes.queryForShow(fromId).forEach((episode) => {
    episode.moveToShow(toId);
  });
};

Episodes.isFlagProblematic = function(flag) {
  return !Session.get('AddOnInstalled') || Episodes.flagsWithAddOnPreference.includes(flag) || Episodes.flagsWithAddOnNever.includes(flag);
};

// Queries
Episodes.queryForShow = function(showId) {
  // Validate
  Schemas.Episode.validate({
    showId: showId,
  }, {
    keys: ['showId']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId
  }, {
    sort: {
      episodeNumEnd: -1,
      episodeNumStart: 1
    }
  });
};

Episodes.queryForTranslationType = function(showId, translationType) {
  // Validate
  Schemas.Episode.validate({
    showId: showId,
    translationType: translationType
  }, {
    keys: ['showId', 'translationType']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId,
    translationType: translationType
  }, {
    sort: {
      episodeNumEnd: -1,
      episodeNumStart: 1
    }
  });
};

Episodes.queryForEpisode = function (showId, translationType, episodeNumStart, episodeNumEnd) {
  // Validate
  Schemas.Episode.validate({
    showId: showId,
    translationType: translationType,
    episodeNumStart: episodeNumStart,
    episodeNumEnd: episodeNumEnd
  }, {
    keys: ['showId', 'translationType', 'episodeNumStart', 'episodeNumEnd']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId,
    episodeNumStart: episodeNumStart,
    episodeNumEnd: episodeNumEnd,
    translationType: translationType
  });
};

Episodes.queryForStreamer = function (showId, translationType, episodeNumStart, episodeNumEnd, streamerId) {
  // Validate
  Schemas.Episode.validate({
    showId: showId,
    translationType: translationType,
    episodeNumStart: episodeNumStart,
    episodeNumEnd: episodeNumEnd,
    streamerId: streamerId
  }, {
    keys: ['showId', 'translationType', 'episodeNumStart', 'episodeNumEnd', 'streamerId']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId,
    episodeNumStart: episodeNumStart,
    episodeNumEnd: episodeNumEnd,
    translationType: translationType,
    streamerId: streamerId
  });
};

Episodes.queryUnique = function (showId, translationType, episodeNumStart, episodeNumEnd, streamerId, sourceName) {
  // Validate
  Schemas.Episode.validate({
    showId: showId,
    translationType: translationType,
    episodeNumStart: episodeNumStart,
    episodeNumEnd: episodeNumEnd,
    streamerId: streamerId,
    sourceName: sourceName
  }, {
    keys: ['showId', 'translationType', 'episodeNumStart', 'episodeNumEnd', 'streamerId', 'sourceName']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId,
    episodeNumStart: episodeNumStart,
    episodeNumEnd: episodeNumEnd,
    translationType: translationType,
    streamerId: streamerId,
    sourceName: sourceName
  });
};

Episodes.queryLatest = function(limit) {
  // Validate
  new SimpleSchema({
    limit: {
      type: Number
    }
  }).validate({limit});

  // Return results cursor
  return Episodes.find({}, {
    limit: limit,
    sort: {
      'uploadDate.year': -1,
      'uploadDate.month': -1,
      'uploadDate.date': -1,
      'uploadDate.hour': -1,
      'uploadDate.minute': -1
    }
  })
};
