import SimpleSchema from 'simpl-schema';
import Streamers from "../../streamers/streamers";
import {Shows} from "../shows/shows";
import moment from 'moment-timezone';

// Collection
export const Searches = new Mongo.Collection('searches');

// Schema
Schemas.Search = new SimpleSchema({
  _id: {
    type: String,
    optional: true
  },
  lastSearchStart: {
    type: Date,
    optional: true
  },
  lastSearchEnd: {
    type: Date,
    optional: true
  },

  query: {
    type: String,
    index: true,
    optional: true,
    autoValue: function() {
      if (!this.isSet || !this.value) {
        this.unset();
        return undefined;
      }
      return this.value.cleanQuery();
    },
    autoform: {
      icon: 'search',
      label: 'Search Anime',
      autocomplete: 'off',
      type: 'search'
    }
  },

  types: {
    type: Array,
    index: true,
    optional: true,
    defaultValue: [],
    autoform: {
      options: 'allowed',
      type: 'select-checkbox',
      class: 'filled-in'
    }
  },
  'types.$': {
    type: String,
    allowedValues: Shows.validTypes.sort().concat(['Unknown'])
  },
  includeTypes: {
    type: Boolean,
    index: true,
    optional: true,
    defaultValue: true,
    autoform: {
      type: 'switch',
      trueLabel: 'Include',
      falseLabel: 'Exclude'
    }
  },

  genres: {
    type: Array,
    index: true,
    optional: true,
    defaultValue: [],
    autoform: {
      options: 'allowed',
      type: 'select-checkbox',
      class: 'filled-in'
    }
  },
  'genres.$': {
    type: String,
    allowedValues: Shows.validGenres.sort().concat(['Unknown'])
  },
  includeGenres: {
    type: Boolean,
    index: true,
    optional: true,
    defaultValue: true,
    autoform: {
      type: 'switch',
      trueLabel: 'Include',
      falseLabel: 'Exclude'
    }
  }
}, { tracker: Tracker });

Searches.attachSchema(Schemas.Search);

// Constants
Searches.timeUntilRecache = 86400000; // 1 day
Searches.maxSearchTime = 60000; // 1 minute

if (Meteor.isDevelopment) {
  Searches.timeUntilRecache = 10000;
  Searches.maxSearchTime = 30000;
}

// Helpers
Searches.helpers({
  expired() {
    let now = moment();
    return (!this.busy() && (!this.lastSearchStart || moment(this.lastSearchEnd).add(Searches.timeUntilRecache) < now)) ||
            (this.busy() && moment(this.lastSearchStart).add(Searches.maxSearchTime) < now);
  },

  busy() {
    return this.lastSearchStart && (!this.lastSearchEnd || this.lastSearchStart > this.lastSearchEnd);
  },

  completeQuery(length, string) {
    let filler = this.query.length < length ? string.repeat(length - this.query.length) : '';
    return this.query + filler;
  },

  getTypesAsIncludes(validTypes) {
    if (this.includeTypes) {
      if (this.types.includes('Unknown')) {
        return undefined;
      }
      return validTypes.filter((type) => {
        return this.types.includes(type);
      });
    }
    else {
      if (!this.types.includes('Unknown')) {
        return undefined;
      }
      return validTypes.filter((type) => {
        return !this.types.includes(type);
      });
    }
  },

  getGenresAsIncludes(validGenres) {
    if (this.includeGenres) {
      if (this.genres.includes('Unknown')) {
        return undefined;
      }
      return validGenres.filter((genre) => {
        return this.genres.includes(genre);
      });
    }
    else {
      if (!this.genres.includes('Unknown')) {
        return undefined;
      }
      return validGenres.filter((genre) => {
        return !this.genres.includes(genre);
      });
    }
  },

  getSingleType(validTypes) {
    if (this.includeTypes && this.types.length === 1 && validTypes.includes(this.types[0])) {
      return this.types[0];
    }
    if (!this.includeTypes && this.types.length === Shows.validTypes.length) {
      return validTypes.find((type) => {
        return !this.types.includes(type);
      });
    }
    return undefined;
  },

  getSingleGenre(validGenres) {
    if (this.includeGenres && this.genres.length === 1 && validGenres.includes(this.genres[0])) {
      return this.genres[0];
    }
    if (!this.includeGenres && this.genres.length === Shows.validGenres.length) {
      return validGenres.find((genre) => {
        return !this.genres.includes(genre);
      });
    }
    return undefined;
  },

  doSearch() {
    // Mark search as started
    this.lastSearchStart = moment().toDate();
    Searches.update(this._id, {
      $set: {
        lastSearchStart: this.lastSearchStart
      }
    });

    Streamers.doSearch(this, () => {

      // When done
      this.lastSearchEnd = moment().toDate();
      Searches.update(this._id, {
        $set: {
          lastSearchEnd: this.lastSearchEnd
        }
      });

    }, (partial, episodes) => {

      // For each search result
      Shows.addPartialShow(partial, episodes);

    });
  }
});

Searches.getOrInsertSearch = function(search) {
  let result = Searches.findOne(search);
  if (!result) {
    result = Searches.findOne(
      Searches.insert(search)
    );
  }
  return result;
};

Searches.startSearch = function(search) {
  Schemas.Search.clean(search, {
    mutate: true
  });
  Schemas.Search.validate(search);

  let result = Searches.getOrInsertSearch(search);

  if (result.expired()) {
    result.doSearch();
  }
};

// Methods
Meteor.methods({
  'searches.startSearch'(search) {
    Searches.startSearch(search);
  }
});

// Queries
Searches.queryWithSearch = function(search) {
  // Clean
  Schemas.Search.clean(search, {
    mutate: true
  });

  // Validate
  Schemas.Search.validate(search);

  // Return results cursor
  return Searches.find(search);
};
