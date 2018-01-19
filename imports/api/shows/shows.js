import Cheerio from 'cheerio';
import SimpleSchema from 'simpl-schema';

// Schema
Schemas.Show = new SimpleSchema({
  lastFullUpdate: {
    type: Date,
    optional: true
  },
  lockFullUpdate: {
    type: Date,
    optional: true
  },
  streamerUrls: {
    type: Array,
    defaultValue: [],
    optional: true
  },
  'streamerUrls.$': {
    type: Object
  },
  'streamerUrls.$.id': {
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
    minCount: 1
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
  }
}, { tracker: Tracker });

// Collection
export const Shows = new Mongo.Collection('shows');
Shows.attachSchema(Schemas.Show);

if (Meteor.isServer) {
  Shows._ensureIndex({
    altNames: 'text'
  });
}

// Constants
Shows.descriptionCutoff = '&hellip; (read more)';

// Helpers
Shows.helpers({
  remove() {
    Shows.remove(this._id);
  },

  mergePartialShow(other) {
    // Create and clean a clone
    let otherForUpdate = JSON.parse(JSON.stringify(other));
    delete otherForUpdate.streamerUrls;
    delete otherForUpdate.altNames;

    Object.keys(otherForUpdate).forEach((key) => {
      if (this[key]) {
        delete otherForUpdate[key];
      } else {
        this[key] = other[key];
      }
    });

    // Define initial query
    let query = {
      $set: otherForUpdate,
      $addToSet: {
        streamerUrls: {$each: other.streamerUrls},
        altNames: {$each: other.altNames}
      }
    };

    // Determine if the description should be replaced
    if (this.description && other.description && this.description.endsWith(Shows.descriptionCutoff) && other.description.length > this.description.length) {
      delete otherForUpdate.description;
      query.$set = {
        description: other.description
      };
    }

    try {
      // Execute query
      Shows.update(this._id, query);

      // Remove other from database
      if (other._id) {
        other.remove();
      }
    }

    catch(err) {
      console.log(err);
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
      if (other.lastFullUpdate || other.lockFullUpdate) {
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
        $search: '"' + query.replace('-', ' ') + '"',
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
