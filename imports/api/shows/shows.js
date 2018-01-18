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
      Streamers.doSearch(query, Schemas.Show, (result) => {
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
Shows.querySearch = function(query) {
  Schemas.animeSearch.validate({query});

  // TODO: Improve search query

  if (Meteor.isServer && query) {
    return Shows.find({
        $text: {
          $search: query
        }
      }, {
        fields: {
          score: {
            $meta: 'textScore'
          }
        }
      }
    );
  }

  else {
    return Shows.find({}, {
      sort: {
        textScore: -1,
        name: 1
      }
    });
  }
};

Shows.queryMatchingAlts = function(names) {
  new SimpleSchema({
    names: {
      type: Array,
      minCount: 1
    },
    'names.$': {
      type: String
    }
  }).validate({names});

  // TODO: Proper fuzzy matching
  return Shows.find({
    altNames: {
      $in: names
    }
  });
};
