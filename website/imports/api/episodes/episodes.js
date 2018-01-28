import SimpleSchema from 'simpl-schema';
import Streamers from "../../streamers/_streamers";

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
            name: value.name
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
  'sources.$.name': {
    type: String
  },
  'sources.$.url': {
    type: String
  },
  'sources.$.js': {
    type: String,
    optional: true
  },
  'sources.$.flags': {
    type: Array,
    optional: true,
    autoValue: function() {
      if (!this.isSet) {
        return [];
      }
      return this.value.reduce((total, value) => {
        value = value.trim();
        if (!total.includes(value)) {
          total.push(value);
        }
        return total;
      }, []);
    }
  },
  'sources.$.flags.$': {
    type: String,
    allowedValues: ['flash', 'cloudflare', 'x-frame-options', 'mixed-content']
  }
}, { tracker: Tracker });

// Collection
export const Episodes = new Mongo.Collection('episodes');
Episodes.attachSchema(Schemas.Episode);

// Constants
Episodes.timeUntilRecache = 8000; // 1 day
Episodes.maxUpdateTime = 8000; // 10 minutes

// Helpers
Episodes.helpers({
  remove() {
    Episodes.remove(this._id);
  },

  expired() {
    let now = moment();
    return (!this.locked() && (!this.lastUpdateStart || moment(this.lastUpdateEnd).add(Episodes.timeUntilRecache) < now)) ||
           (this.locked() && moment(this.lastUpdateStart).add(Episodes.maxUpdateTime) < now);
  },

  locked() {
    return this.lastUpdateStart && (!this.lastUpdateEnd || this.lastUpdateStart > this.lastUpdateEnd);
  },

  attemptUpdate() {
    if (this.expired()) {
      this.lastUpdateStart = moment().toDate();
      Episodes.update(this._id, {
        $set: {
          lastUpdateStart: this.lastUpdateStart
        }
      });

      Streamers.getEpisodeResults(this.sourceUrl, Streamers.getStreamerById(this.streamerId), this._id, (sources) => {

        // Replace existing sources
        this.sources = sources;

        // Update result
        this.lastUpdateEnd = moment().toDate();
        Episodes.update({
          _id: this._id,
          lastUpdateStart: this.lastUpdateStart
        }, {
          $set: Episodes.simpleSchema().clean(this, {
            mutate: true
          })
        });

      });
    }
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
  let others = Episodes.queryUnique(episode.showId, episode.translationType, episode.episodeNum, episode.streamerId);

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
  'episodes.attemptUpdate'(id) {
    Schemas.id.validate({id});
    Episodes.findOne(id).attemptUpdate();
  }
});

// Queries
Episodes.queryForShow = function(showId) {
  // Validate
  Episodes.simpleSchema().validate({
    showId: showId,
  }, {
    keys: ['showId']
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

Episodes.queryForTranslationType = function(showId, translationType) {
  // Validate
  Episodes.simpleSchema().validate({
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
      episodeNum: -1
    }
  });
};

Episodes.queryForEpisode = function (showId, translationType, episodeNum) {
  // Validate
  Episodes.simpleSchema().validate({
    showId: showId,
    translationType: translationType,
    episodeNum: episodeNum
  }, {
    keys: ['showId', 'translationType', 'episodeNum']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId,
    episodeNum: episodeNum,
    translationType: translationType
  });
};

Episodes.queryUnique = function (showId, translationType, episodeNum, streamerId) {
  // Validate
  Episodes.simpleSchema().validate({
    showId: showId,
    translationType: translationType,
    episodeNum: episodeNum,
    streamerId: streamerId
  }, {
    keys: ['showId', 'translationType', 'episodeNum', 'streamerId']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId,
    episodeNum: episodeNum,
    translationType: translationType,
    streamerId: streamerId
  });
};
