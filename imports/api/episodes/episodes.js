import SimpleSchema from 'simpl-schema';

// Schema
Schemas.Episode = new SimpleSchema({
  _id: {
    type: String,
    optional: true
  },
  lastUpdateStart: {
    type: Date,
    optional: true
  },
  lastUpdateEnd: {
    type: Date,
    optional: true
  },
  showId: {
    type: String,
    index: true
  },
  episodeNum: {
    type: Number,
    index: true
  },
  translationType: {
    type: String,
    allowedValues: ['sub', 'dub', 'raw'],
    index: true
  },
  streamerId: {
    type: String
  },
  sourceUrl: {
    type: String
  },
  sources: {
    type: Array,
    optional: true,
    autoValue: function() {
      if (!this.isSet) {
        return [];
      }
      return this.value.reduce((total, value) => {
        if (!total.hasPartialObjects({
            id: value.id
          })) {
          total.push(value);
        }
        return total;
      }, []);
    }
  },
  'sources.$': {
    type: Object
  },
  'sources.$.id': {
    type: String
  },
  'sources.$.url': {
    type: String
  },
  'sources.$.js': {
    type: String,
    optional: true
  }
}, { tracker: Tracker });

// Collection
export const Episodes = new Mongo.Collection('episodes');
Episodes.attachSchema(Schemas.Episode);

// Constants
Episodes.timeUntilRecache = 86400000; // 1 day
Episodes.maxUpdateTime = 600000; // 10 minutes

// Helpers
Episodes.helpers({
  remove() {
    Episodes.remove(this._id);
  },

  mergeEpisode(other) {
    // Overwrite and merge attributes
    Object.keys(other).forEach((key) => {
      if (key === 'sources' && typeof this[key] !== 'undefined') {
        this[key] = this[key].concat(other[key]);
      } else if (!['_id', 'lastUpdateStart', 'lastUpdateEnd'].includes(key)) {
        this[key] = other[key];
      }
    });

    // Update database
    Episodes.update({
      _id: this._id,
      lastUpdateStart: this.lastUpdateStart
    }, {
      $set: Episodes.simpleSchema().clean(this, {
        mutate: true
      })
    });

    // Remove other from database
    if (other._id) {
      other.remove();
    }
  }
});

Episodes.addEpisode = function(episode) {
  // Get episodes which are the same
  let others = Episodes.queryUnique(episode.showId, episode.episodeNum, episode.translationType, episode.streamerId);

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
    }
  }

  // Insert otherwise
  else {
    Episodes.insert(episode);
  }
};

// Methods
Meteor.methods({

});

// Queries
Episodes.queryUnique = function(showId, episodeNum, translationType, streamerId) {
  // Validate
  Episodes.simpleSchema().validate({
    showId: showId,
    episodeNum: episodeNum,
    translationType: translationType,
    streamerId: streamerId
  }, {
    keys: ['showId', 'episodeNum', 'translationType', 'streamerId']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId,
    episodeNum: episodeNum,
    translationType: translationType,
    streamerId: streamerId
  });
};

Episodes.queryForShow = function(showId) {
  // Validate
  Schemas.id.validate({
    id: showId
  });

  // Return results cursor
  return Episodes.find({
    showId: showId
  }, {
    sort: {
      episodeNum: -1
    }
  });
};

Episodes.queryForEpisode = function(showId, episodeNum, translationType) {
  // Validate
  Episodes.simpleSchema().validate({
    showId: showId,
    episodeNum: episodeNum,
    translationType: translationType
  }, {
    keys: ['showId', 'episodeNum', 'translationType']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId,
    episodeNum: episodeNum,
    translationType: translationType
  });
};
