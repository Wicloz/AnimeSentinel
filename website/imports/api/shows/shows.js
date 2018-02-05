import Cheerio from 'cheerio';
import SimpleSchema from 'simpl-schema';
import Streamers from "../../streamers/streamers";
import {Episodes} from "../episodes/episodes";
import {Thumbnails} from '../thumbnails/thumbnails';

// Collection
export const Shows = new Mongo.Collection('shows');

// Schema
Schemas.Show = new SimpleSchema({
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
  malId: {
    type: SimpleSchema.Integer,
    optional: true,
    index: true
  },
  streamerUrls: {
    type: Array,
    minCount: 1,
    autoValue: function() {
      if (!this.isSet) {
        return undefined;
      }
      return this.value.reduce((total, value) => {
        if (value && !total.hasPartialObjects({
            streamerId: value.streamerId,
            type: value.type
          })) {
          total.push(value);
        }
        return total;
      }, []);
    }
  },
  'streamerUrls.$': {
    type: Object
  },
  'streamerUrls.$.streamerId': {
    type: String
  },
  'streamerUrls.$.type': {
    type: String
  },
  'streamerUrls.$.url': {
    type: String
  },
  'streamerUrls.$.lastDownloadFailed': {
    type: Boolean,
    optional: true,
    defaultValue: false
  },
  name: {
    type: String
  },
  altNames: {
    type: Array,
    index: 'text',
    minCount: 1,
    autoValue: function() {
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
  'altNames.$': {
    type: String
  },
  description: {
    type: String,
    optional: true,
    autoValue: function() {
      if (!this.isSet || !this.value) {
        this.unset();
        return undefined;
      }
      let desc = Cheerio.load(this.value);
      desc('script').remove();
      return desc('body').html();
    }
  },
  thumbnails: {
    type: Array,
    optional: true,
    autoValue: function() {
      if (!this.isSet) {
        return undefined;
      } else if (!this.value) {
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
  'thumbnails.$': {
    type: String
  },
  type: {
    type: String,
    optional: true,
    allowedValues: Shows.validTypes
  }
}, { tracker: Tracker });

Shows.attachSchema(Schemas.Show);

// Constants
Shows.arrayKeys = Shows.simpleSchema()._schemaKeys.filter((key) => {
  return !key.includes('.') && Shows.simpleSchema()._schema[key].type.definitions[0].type.toString().includes('Array()');
});
Shows.descriptionCutoff = '&#x2026; (read more)';
Shows.timeUntilRecache = 86400000; // 1 day
Shows.maxUpdateTime = 600000; // 10 minutes
Shows.validTypes = ['TV', 'OVA', 'Movie', 'Special', 'ONA'];

if (Meteor.isDevelopment) {
  Shows.timeUntilRecache = 10000;
  Shows.maxUpdateTime = 30000;
}

// Helpers
Shows.helpers({
  remove() {
    Shows.remove(this._id);
  },

  getThumbnailUrls() {
    if (this.thumbnails && !this.thumbnails.empty()) {
      let urls = Thumbnails.queryWithHashes(this.thumbnails).fetch().filterMap((thumbnail) => {
        return thumbnail.url();
      });
      if (!urls.empty()) {
        return urls;
      }
    }
    return ['/media/unknown.gif'];
  },

  expired() {
    let now = moment();
    return (!this.locked() && (!this.lastUpdateStart || moment(this.lastUpdateEnd).add(Shows.timeUntilRecache) < now)) ||
            (this.locked() && moment(this.lastUpdateStart).add(Shows.maxUpdateTime) < now);
  },

  locked() {
    return this.lastUpdateStart && (!this.lastUpdateEnd || this.lastUpdateStart > this.lastUpdateEnd);
  },

  mergePartialShow(other) {
    // Copy and merge attributes
    Object.keys(other).forEach((key) => {
      if (typeof this[key] === 'undefined' && !['_id', 'lastUpdateStart', 'lastUpdateEnd'].includes(key)) {
        this[key] = other[key];
      }
      else if (Shows.arrayKeys.includes(key)) {
        this[key] = this[key].concat(other[key]);
      }
    });

    // Determine if the description should be replaced
    if (this.description && other.description && this.description.endsWith(Shows.descriptionCutoff) && other.description.length > this.description.length) {
      this.description = other.description;
    }

    // Update database
    Shows.update({
      _id: this._id,
      lastUpdateStart: this.lastUpdateStart
    }, {
      $set: Shows.simpleSchema().clean(this, {
        mutate: true
      })
    });

    if (other._id) {
      // Remove other from database
      other.remove();
      // Move episodes
      Episodes.moveEpisodes(other._id, this._id);
    }
  },

  attemptUpdate() {
    if (this.expired()) {
      // Mark update as started
      this.lastUpdateStart = moment().toDate();
      Shows.update(this._id, {
        $set: {
          lastUpdateStart: this.lastUpdateStart
        }
      });

      // Track found episodes
      let episodeIds = [];

      Streamers.createFullShow(this, (show) => {

        // Determine which streamers failed to download
        let streamersIdsFailed = show.streamerUrls.filter((streamerUrl) => {
          return streamerUrl.lastDownloadFailed;
        }).map((streamerUrl) => {
          return streamerUrl.streamerId;
        });

        // Remove missing episodes
        Episodes.remove({
          showId: this._id,
          _id: {
            $nin: episodeIds
          },
          streamerId: {
            $nin: streamersIdsFailed
          }
        });

        // Replace existing fields
        Object.keys(show).forEach((key) => {
          this[key] = show[key];
        });

        // Update result
        this.lastUpdateEnd = moment().toDate();
        Shows.update({
          _id: this._id,
          lastUpdateStart: this.lastUpdateStart
        }, {
          $set: Shows.simpleSchema().clean(this, {
            mutate: true
          })
        });

        // TODO: Merge and remove duplicate shows
        // TODO: Move episodes

      }, (partial, episodes) => {

        // Insert any partial results found in the process
        Shows.addPartialShow(partial, episodes);

      }, (episode) => {

        // Add found episodes
        episodeIds.push(Episodes.addEpisode(episode));

      });
    }
  }
});

Shows.addPartialShow = function(show, episodes) {
  let ids = [];

  // Grab matching shows from database
  let others = Shows.queryMatchingAltsMalId(show.altNames, show.malId);

  // Merge if shows were found
  if (others.count()) {
    let othersFull = [];
    let othersPartial = [];

    others.forEach((other) => {
      if (other.lastUpdateStart) {
        othersFull.push(other);
      } else {
        othersPartial.push(other);
      }
    });

    if (othersFull.empty()) {
      othersFull.push(othersPartial.shift());
    }

    othersFull.forEach((otherFull) => {
      othersPartial.forEach((otherPartial) => {
        otherFull.mergePartialShow(otherPartial);
      });
      otherFull.mergePartialShow(show);
      ids.push(otherFull._id);
    });
  }

  // Insert otherwise
  else {
    ids.push(Shows.insert(show));
  }

  // Add any episodes
  if (episodes) {
    ids.forEach((id) => {
      episodes.forEach((episode) => {
        episode.showId = id;
        Episodes.addEpisode(episode);
      });
    });
  }

  // Return ids
  return ids;
};

Shows.prepareAltForMatching = function(altName) {
  // allow matching of 'and', 'to' and 'und' to each other
  // allow matching of '&' to synonymous words
  // allow matching of ': ' to ' '
  let regex = '^' + RegExp.escape(altName).replace(/((?:\\:)? ?)\band\b((?:\\:)? ?)|((?:\\:)? ?)\bund\b((?:\\:)? ?)|((?:\\:)? ?)\bto\b((?:\\:)? ?)|((?:\\:)? ?)&((?:\\:)? ?)/g, '(?:$1$3$5$7 ?and ?$2$4$6$8|$1$3$5$7 ?und ?$2$4$6$8|$1$3$5$7 ?to ?$2$4$6$8|(?:$1$3$5$7)?&(?:$2$4$6$8)?)').replace(/\\: | /g, '(?:\\: | )') + '$';
  // allow case insensitive matching
  return new RegExp(regex, 'i');
};

// Methods
Meteor.methods({
  'shows.attemptUpdate'(id) {
    Schemas.id.validate({id});
    Shows.findOne(id).attemptUpdate();
  }
});

// Queries
Shows.querySearch = function(query, limit) { // TODO: Improve searching
  // Validate
  Schemas.animeSearch.validate({query});
  new SimpleSchema({
    limit: {
      type: Number
    }
  }).validate({limit});

  // Setup initial options
  let selector = {};
  let options = {
    limit: limit
  };

  // Do text search if query is specified
  if (query) {
    if (Meteor.isServer) {
      selector.$text = {
        $search: '"' + query.replace(/\s-([^\s])/g, ' $1') + '"',
        $language: 'english',
      };
      options.fields = {
        score: {
          $meta: 'textScore'
        }
      };
    }
    options.sort = {
      textScore: -1
    };
  }

  // Otherwise sort by name
  else {
    options.sort = {
      name: 1
    };
  }

  // Return results cursor
  return Shows.find(selector, options);
};

Shows.queryMatchingAltsMalId = function(names, malId) {
  // Validate
  new SimpleSchema({
    names: {
      type: Array,
      minCount: 1
    },
    'names.$': {
      type: String
    },
    malId: {
      type: SimpleSchema.Integer,
      optional: true
    }
  }).validate({
    names: names,
    malId: malId
  });

  // Process names to regex
  names = names.map((name) => {
    return Shows.prepareAltForMatching(name);
  });

  // Return results cursor
  if (typeof malId === 'undefined') {
    return Shows.find({
      altNames: {
        $in: names
      }
    });
  } else {
    return Shows.find({
      $or: [{
        altNames: {
          $in: names
        },
        malId: undefined
      }, {
        malId: malId
      }]
    });
  }
};
