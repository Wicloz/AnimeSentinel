import Cheerio from 'cheerio';
import SimpleSchema from 'simpl-schema';
import Streamers from "../../streamers/_streamers";

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
    index: true,
    unique: true,
    sparse: true
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
            id: value.id,
            hasShowInfo: value.hasShowInfo,
            hasEpisodeInfo: value.hasEpisodeInfo
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
  'streamerUrls.$.id': {
    type: String
  },
  'streamerUrls.$.hasShowInfo': {
    type: Boolean
  },
  'streamerUrls.$.hasEpisodeInfo': {
    type: String,
    optional: true,
    allowedValues: ['multi', 'sub', 'dub', 'raw']
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
Shows.timeUntilRecache = 86400000; // 1 day
Shows.maxUpdateTime = 600000; // 10 minutes

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
      if (typeof this[key] === 'undefined') {
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
  },

  doUpdate() {
    this.lastUpdateStart = moment().toDate();
    Shows.update(this._id, {
      $set: {
        lastUpdateStart: this.lastUpdateStart
      }
    });

    if (Meteor.isServer) { // TODO: remove when downloads are fixed
      Streamers.createFullShow(this.altNames, this.streamerUrls, (show) => {

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

      }, (partial) => {
        // Insert any partial results found in the process
        Shows.addPartialShow(partial);
      });
    }
  }
});

Shows.addPartialShow = function(show) {
  // Grab matching shows from database
  let others = Shows.queryMatchingAlts(show.altNames);

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

Shows.attemptUpdate = function(id) {
  Schemas.id.validate({id});

  let show = Shows.findOne(id);
  if (show.expired()) {
    show.doUpdate();
  }
};

// Methods
Meteor.methods({
  'shows.attemptUpdate'(id) {
    Shows.attemptUpdate(id);
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

Shows.queryMatchingAlts = function(names) {
  // Validate
  new SimpleSchema({
    names: {
      type: Array,
      minCount: 1
    },
    'names.$': {
      type: String
    }
  }).validate({names});

  // Split names on commas
  names.forEach((name) => {
    if (name.includes(', ')) {
      names = names.concat(name.split(', '));
    }
  });

  // Process names to regex
  names = names.map((name) => {
    // allow matching of ' and ', ' to ', ' und ' and '&' to each other
    // allow matching of ': ' to ' '
    let regex = '^' + RegExp.escape(name).replace(/: | /g, '(: | )').replace(/ and | und | to |&/g, '( and | und | to |&)') + '$';
    // allow case insensitive matching
    return new RegExp(regex, 'i');
  });

  // Return results cursor
  return Shows.find({
    altNames: {
      $in: names
    }
  });
};
