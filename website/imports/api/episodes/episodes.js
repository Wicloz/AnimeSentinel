import SimpleSchema from 'simpl-schema';
import Streamers from "../../streamers/streamers";

// Collection
export const Episodes = new Mongo.Collection('episodes');

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
    type: String
  },
  sourceUrl: {
    type: String
  },
  sources: {
    type: Array,
    optional: true,
    autoValue: function() {
      if (!this.isSet || !this.value) {
        return [];
      }
      return this.value.reduce((total, value) => {
        if (value && !total.hasPartialObjects({
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
  'sources.$.flags': {
    type: Array,
    optional: true,
    autoValue: function() {
      if (!this.isSet || !this.value) {
        return [];
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
  'sources.$.flags.$': {
    type: String,
    allowedValues: ['flash', 'cloudflare', 'x-frame-options', 'mixed-content']
  }
}, { tracker: Tracker });

Episodes.attachSchema(Schemas.Episode);

// Constants
Episodes.timeUntilRecache = 86400000; // 1 day
Episodes.maxUpdateTime = 60000; // 1 minute
Episodes.flagsWithoutAddOnPreference = ['cloudflare', 'mixed-content', 'flash'];
Episodes.flagsWithoutAddOnNever = ['x-frame-options'];
Episodes.flagsWithAddOnPreference = ['flash', 'mixed-content'];
Episodes.flagsWithAddOnNever = [];

if (Meteor.isDevelopment) {
  Episodes.timeUntilRecache = 10000;
  Episodes.maxUpdateTime = 30000;
}

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
      // Mark update as started
      this.lastUpdateStart = moment().toDate();
      Episodes.update(this._id, {
        $set: {
          lastUpdateStart: this.lastUpdateStart
        }
      });

      Streamers.getEpisodeResults(this.sourceUrl, Streamers.getStreamerById(this.streamerId), this._id, (sources) => {

        // Replace existing sources
        if (!sources.empty()) {
          this.sources = sources;
        }

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
  },

  moveToShow(showId) {
    this.showId = showId;
    Episodes.update(this._id, {
      $set: {
        showId: this.showId
      }
    });
  }
});

Episodes.addEpisode = function(episode) {
  let id = undefined;

  // Get episodes which are the same
  let others = Episodes.queryUnique(episode.showId, episode.translationType, episode.episodeNumStart, episode.episodeNumEnd, episode.streamerId);

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
      episodeNumEnd: -1,
      episodeNumStart: 1
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
      episodeNumEnd: -1,
      episodeNumStart: 1
    }
  });
};

Episodes.queryForEpisode = function (showId, translationType, episodeNumStart, episodeNumEnd) {
  // Validate
  Episodes.simpleSchema().validate({
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

Episodes.queryUnique = function (showId, translationType, episodeNumStart, episodeNumEnd, streamerId) {
  // Validate
  Episodes.simpleSchema().validate({
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
