import SimpleSchema from 'simpl-schema';
import Streamers from '/imports/streamers/_streamers';

// Schema
Schemas.Show = new SimpleSchema({
  isSearchResult: {
    type: Boolean,
    defaultValue: false,
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
    defaultValue: '',
    optional: true
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

// Helpers
Shows.helpers({
  remove() {
    Shows.remove(this._id);
  }
});

Shows.mergeSearchResult = function(id, other) {
  let otherForUpdate = JSON.parse(JSON.stringify(other));
  delete otherForUpdate.streamerUrls;
  delete otherForUpdate.altNames;

  Shows.update(id, {
    $max: otherForUpdate,
    $addToSet: {
      streamerUrls: {$each: other.streamerUrls},
      altNames: {$each: other.altNames}
    }
  });

  // Remove other from database
  if (other._id) {
    other.remove();
  }
};

Shows.addSearchResult = function(show) {
  let others = Shows.queryMatchingAlts(show.altNames);

  if (others.count()) {
    let firstId;
    others.forEach((other) => {
      if (other.isSearchResult) {
        if (!firstId) {
          firstId = other._id;
        } else {
          Shows.mergeSearchResult(firstId, other);
        }
      }
    });
    if (firstId) {
      Shows.mergeSearchResult(firstId, show);
    }
  }

  else {
    Shows.insert(show);
  }
};

Shows.remoteSearch = function(query, fromMethod=false) {
  if (Meteor.isServer || fromMethod) {
    Schemas.animeSearch.validate({query});
    if (query) {
      Streamers.getSearchResults(query, (result) => {
        Shows.addSearchResult(result);
      });
    }
  }

  else {
    Meteor.call('shows.remoteSearch', query);
  }
};

// Methods
Meteor.methods({
  'shows.remoteSearch'(query) {
    Shows.remoteSearch(query, true);
  }
});

// Queries
Shows.querySearch = function(query, limit) {
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
    return new RegExp(regex, 'i');
  });

  // Return results cursor
  return Shows.find({
    altNames: {
      $in: names
    }
  });
};
