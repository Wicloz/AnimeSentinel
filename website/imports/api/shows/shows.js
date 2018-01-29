import Cheerio from 'cheerio';
import SimpleSchema from 'simpl-schema';
import Streamers from "../../streamers/_streamers";
import {Episodes} from "../episodes/episodes";

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
        if (!total.hasPartialObjects({
            streamer: value.streamer,
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
  'streamerUrls.$.streamer': {
    type: String
  },
  'streamerUrls.$.type': {
    type: String
  },
  'streamerUrls.$.url': {
    type: String
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
        if (!total.includes(value)) {
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
  type: {
    type: String,
    optional: true,
    allowedValues: ['TV', 'OVA', 'Movie', 'Special', 'ONA']
  }
}, { tracker: Tracker });

// Collection
export const Shows = new Mongo.Collection('shows');
Shows.attachSchema(Schemas.Show);

// Constants
Shows.arrayKeys = Shows.simpleSchema()._schemaKeys.filter((key) => {
  return !key.includes('.') && Shows.simpleSchema()._schema[key].type.definitions[0].type.toString().includes('Array()');
});
Shows.descriptionCutoff = '&#x2026; (read more)';
Shows.timeUntilRecache = 8000; // 1 day
Shows.maxUpdateTime = 8000; // 10 minutes

// Helpers
Shows.helpers({
  remove() {
    Shows.remove(this._id);
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

    // Remove other from database
    if (other._id) {
      other.remove();
    }

    // Move episodes
    Episodes.moveEpisodes(this._id, other._id);
  },

  attemptUpdate() {
    if (this.expired()) {
      this.lastUpdateStart = moment().toDate();
      Shows.update(this._id, {
        $set: {
          lastUpdateStart: this.lastUpdateStart
        }
      });

      Streamers.createFullShow(this, (show) => {

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

      }, (partial) => {
        // Insert any partial results found in the process
        Shows.addPartialShow(partial);
      }, (episode) => {
        // Add found episodes
        Episodes.addEpisode(episode);
      });
    }
  }
});

Shows.addPartialShow = function(show) {
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
    });
  }

  // Insert otherwise
  else {
    Shows.insert(show);
  }
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

  // Split names on commas
  names.forEach((name) => {
    if (name.includes(', ')) { // TODO: Remove when alts are fixed
      names = names.concat(name.split(', '));
    }
  });

  // Process names to regex
  names = names.map((name) => {
    // allow matching of 'and', 'to' and 'und' to each other
    // allow matching of '&' to synonymous words
    // allow matching of ': ' to ' '
    let regex = '^' + RegExp.escape(name).replace(/((?:\\:)? ?)\band\b((?:\\:)? ?)|((?:\\:)? ?)\bund\b((?:\\:)? ?)|((?:\\:)? ?)\bto\b((?:\\:)? ?)|((?:\\:)? ?)&((?:\\:)? ?)/g, '(?:$1$3$5$7 ?and ?$2$4$6$8|$1$3$5$7 ?und ?$2$4$6$8|$1$3$5$7 ?to ?$2$4$6$8|(?:$1$3$5$7)?&(?:$2$4$6$8)?)').replace(/\\: | /g, '(?:\\: | )') + '$';
    // allow case insensitive matching
    return new RegExp(regex, 'i');
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
