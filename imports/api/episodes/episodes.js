import SimpleSchema from 'simpl-schema';
import {Shows} from "../shows/shows";

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
  episode: {
    type: Number,
    index: true
  },
  streamerId: {
    type: String
  },
  sourceUrl: {
    type: String
  },
  videos: {
    type: Array,
    optional: true,
    defaultValue: []
  },
  'videos.$': {
    type: Object
  },
  'videos.$.url': {
    type: String
  },
  'videos.$.js': {
    type: String,
    optional: true
  }
}, { tracker: Tracker });

// Collection
export const Episodes = new Mongo.Collection('episodes');
Episodes.attachSchema(Schemas.Episode);

// Constants
Shows.timeUntilRecache = 86400000; // 1 day
Shows.maxUpdateTime = 600000; // 10 minutes

// Helpers
Episodes.helpers({

});

// Methods
Meteor.methods({

});

// Queries
