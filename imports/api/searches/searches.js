import SimpleSchema from 'simpl-schema';
import Streamers from "../../streamers/_streamers";
import {Shows} from "../shows/shows";

// Schema
Schemas.Search = new SimpleSchema({
  query: {
    type: String
  },
  lastStart: {
    type: Date,
    optional: true
  },
  lastEnd: {
    type: Date,
    optional: true
  }
}, { tracker: Tracker });

// Collection
export const Searches = new Mongo.Collection('searches');
Searches.attachSchema(Schemas.Search);

// Constants
Searches.timeUntilRecache = 86400000; // 1 day
Searches.maxSearchTime = 30000; // 30 seconds

// Helpers
Searches.helpers({
  expired() {
    let now = moment();
    return !this.lastStart || // Never started
      moment(this.lastEnd).add(Searches.timeUntilRecache) < now || // Cache expired
      (this.busy() && moment(this.lastStart).add(Searches.maxSearchTime) < now); // Busy but took too long (probably terminated)
  },

  busy() {
    return this.lastStart && (!this.lastEnd || this.lastStart > this.lastEnd);
  },

  doSearch(query) {
    Searches.update(this._id, {
      $set: {
        lastStart: moment().toDate()
      }
    });

    if (Meteor.isServer) { // TODO: remove when downloads are fixed
      Streamers.getSearchResults(query, (result) => {
        Shows.addPartialShow(result);
      }, () => {
        Searches.update(this._id, {
          $set: {
            lastEnd: moment().toDate()
          }
        });
      });
    }
  }
});

Searches.startSearch = function(query) {
  // Clean and validate query
  query = query.cleanQuery();
  Schemas.animeSearch.validate({query});

  if (query) {
    let search = Searches.findOne({
      query: query
    });

    if (!search) {
      search = Searches.findOne(
        Searches.insert({
          query: query
        })
      );
    }

    if (search.expired()) {
      search.doSearch(query);
    }
  }
};

// Methods
Meteor.methods({
  'searches.startSearch'(query) {
    Searches.startSearch(query);
  }
});

// Queries
Searches.queryWithQuery = function(query) {
  // Validate
  Schemas.animeSearch.validate({query});

  // Return results cursor
  return Searches.find({
    query: query
  });
};
