import SimpleSchema from 'simpl-schema';

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
  episodeNum: {
    type: Number,
    index: true
  },
  translationType: {
    type: String,
    allowedValues: ['sub', 'dub', 'raw'],
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
Episodes.timeUntilRecache = 86400000; // 1 day
Episodes.maxUpdateTime = 600000; // 10 minutes

// Helpers
Episodes.helpers({
  mergeEpisode(other) {
    // console.log(other);
  }
});

Episodes.addEpisode = function(episode) {
  let others = Episodes.queryUnique(episode.showId, episode.episodeNum, episode.translationType, episode.streamerId);

  if (others.count()) {
    others.forEach((other) => {
      other.mergeEpisode(episode);
    });
  }

  else {
    Episodes.insert(episode);
  }
};

// Methods
Meteor.methods({

});

// Queries
Episodes.queryUnique = function(showId, episodeNum, translationType, streamerId) {
  // Validate
  Episodes.simpleSchema().validate({
    showId: showId,
    episodeNum: episodeNum,
    translationType: translationType,
    streamerId: streamerId
  }, {
    keys: ['showId', 'episodeNum', 'translationType', 'streamerId']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId,
    episodeNum: episodeNum,
    translationType: translationType,
    streamerId: streamerId
  });
};

Episodes.queryForShow = function(showId) {
  // Validate
  Schemas.id.validate({
    id: showId
  });

  // Return results cursor
  return Episodes.find({
    showId: showId
  }, {
    sort: {
      episodeNum: -1
    }
  });
};
