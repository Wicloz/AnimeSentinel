import SimpleSchema from 'simpl-schema';
import Streamers from "../../streamers/streamers";
import {Shows} from "../shows/shows";
import moment from 'moment-timezone';
import {Episodes} from '../episodes/episodes';

// Collection
export const Searches = new Mongo.Collection('searches');

// Schema
Schemas.Search = new SimpleSchema({
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

  sortBy: {
    type: String,
    index: true,
    optional: true,
    allowedValues: ['Latest Update', 'Type'],
    autoform: {
      label: 'Sort By:',
      options: 'allowed'
    }
  },
  sortDirection: {
    type: SimpleSchema.Integer,
    index: true,
    optional: true,
    defaultValue: 1,
    allowedValues: [-1, 1],
    autoform: {
      type: 'select-radio-inline',
      options: [{
        label: 'Ascending', value: '1'
      }, {
        label: 'Descending', value: '-1'
      }],
      defaultValue: '1',
      label: false
    }
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
      label: 'Search Anime:',
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
      type: 'select-checkbox-inline'
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
      type: 'select-radio-inline',
      options: [{
        label: 'Include', value: 'true'
      }, {
        label: 'Exclude', value: 'false'
      }],
      defaultValue: 'true',
      label: false
    }
  },

  genres: {
    type: Array,
    index: true,
    optional: true,
    defaultValue: [],
    autoform: {
      options: 'allowed',
      type: 'select-checkbox-inline'
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
      type: 'select-radio-inline',
      options: [{
        label: 'Include', value: 'true'
      }, {
        label: 'Exclude', value: 'false'
      }],
      defaultValue: 'true',
      label: false
    }
  },

  season: {
    type: String,
    allowedValues: Shows.validQuarters,
    index: true,
    optional: true,
    autoform: {
      options: 'allowed'
    }
  },
  year: {
    type: SimpleSchema.Integer,
    index: true,
    optional: true
  }
}, { tracker: Tracker });

Searches.attachSchema(Schemas.Search);

// Constants
Searches.systemKeys = ['_id', 'lastUpdateStart', 'lastUpdateEnd'];
Searches.timeUntilRecache = 86400000; // 1 day
Searches.maxUpdateTime = 60000; // 1 minute
Searches.timeUntilRecacheRecent = 1800000; // 30 minutes
Searches.maxUpdateTimeRecent = 600000; // 10 minutes

if (Meteor.isDevelopment) {
  Searches.timeUntilRecache = 60000; // 60 seconds
  Searches.maxUpdateTime = 60000; // 60 seconds
  Searches.timeUntilRecacheRecent = 60000; // 60 seconds
  Searches.maxUpdateTimeRecent = 60000; // 60 seconds
}

// Helpers
Searches.helpers({
  expired() {
    if (Meteor.isClient) {
      invalidateSecond.depend();
    }
    return !this.lastUpdateStart ||
      (!this.locked() && moment.fromUtc(this.lastUpdateEnd).add(
        this.isRecentSearch() ? Searches.timeUntilRecacheRecent : Searches.timeUntilRecache
      ).isBefore()) ||
      (this.locked() && moment.fromUtc(this.lastUpdateStart).add(
        this.isRecentSearch() ? Searches.maxUpdateTimeRecent : Searches.maxUpdateTime
      ).isBefore());
  },

  locked() {
    return this.lastUpdateStart && (!this.lastUpdateEnd || this.lastUpdateStart > this.lastUpdateEnd);
  },

  isRecentSearch() {
    return this.sortBy === 'Latest Update' && this.sortDirection === -1;
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
    this.lastUpdateStart = moment.fromUtc().toDate();
    Searches.update(this._id, {
      $set: {
        lastUpdateStart: this.lastUpdateStart
      }
    });

    // Track when both searches are done
    let firstDone = false;
    let doneFunction = () => {
      if (firstDone) {
        this.lastUpdateEnd = moment.fromUtc().toDate();
        Searches.update(this._id, {
          $set: {
            lastUpdateEnd: this.lastUpdateEnd
          }
        });
      } else {
        firstDone = true;
      }
    };

    // Search for recent episodes when needed
    if (this.isRecentSearch()) {
      Episodes.findRecentEpisodes(doneFunction);
    } else {
      firstDone = true;
    }

    // Do normal search
    Streamers.doSearch(this, doneFunction, (partial, episodes) => {
      return Shows.addPartialShow(partial, episodes);
    });
  }
});

Searches.getOrInsertSearch = function(search) {
  let result = Searches.queryWithSearch(search).fetch()[0];
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
  if (!search.sortBy) {
    new SimpleSchema({
      sortDirection: {
        type: SimpleSchema.Integer,
        allowedValues: [1],
      },
    }).validate({
      sortDirection: search.sortDirection
    });
  }

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

  // Properly query for missing values
  Schemas.Search._schemaKeys.forEach((key) => {
    if (Searches.systemKeys.includes(key)) {
      delete search[key];
    } else if (!search[key]) {
      search[key] = {
        $exists: false
      };
    }
  });

  // Return results cursor
  return Searches.find(search);
};
