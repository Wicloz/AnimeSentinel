import SimpleSchema from 'simpl-schema';

// Collection
export const WatchStates = new Mongo.Collection('watchstates');

// Constants
WatchStates.validStatuses  = ['watching', 'completed', 'held', 'dropped', 'planned'];

// Schema
Schemas.WatchState = new SimpleSchema({
  _id: {
    type: String,
    optional: true
  },

  malId: {
    type: SimpleSchema.Integer,
    index: true
  },
  userId: {
    type: String,
    index: true
  },

  malStatus: {
    type: String,
    allowedValues: WatchStates.validStatuses,
    index: true
  },
  malWatchedEpisodes: {
    type: SimpleSchema.Integer
  },
  malRewatching: {
    type: Boolean
  },
  malScore: {
    type: SimpleSchema.Integer,
    optional: true
  }
}, { tracker: Tracker });

WatchStates.attachSchema(Schemas.WatchState);

if (Meteor.isServer) {
  WatchStates._ensureIndex({
    malId: 1,
    userId: 1
  }, {
    unique: true,
    sparse: true
  });
}

// Helpers
WatchStates.helpers({
  mergeWatchState(other) {
    // Update this
    Object.keys(other).forEach((key) => {
      this[key] = other[key];
    });

    // Update database
    WatchStates.update(this._id, {
      $set: Schemas.WatchState.clean(this, {
        mutate: true
      })
    });

    // Remove other from database
    if (other._id) {
      WatchStates.remove(other._id);
    }
  },

  fancyStatus() {
    if (this.malRewatching) {
      return 'Re-watching';
    } else {
      return WatchStates.makeFancyStatus(this.malStatus);
    }
  }
});

WatchStates.makeFancyStatus = function(statusId) {
  switch (statusId) {
    case 'watching':
      return 'Currently Watching';
    case 'completed':
      return 'Completed';
    case 'held':
      return 'On Hold';
    case 'dropped':
      return 'Dropped';
    case 'planned':
      return 'Plan to Watch';
  }
};

WatchStates.addWatchState = function(watchState) {
  let others = WatchStates.queryUnique(watchState.userId, watchState.malId);

  // Update existing watch state
  if (others.count()) {
    others.forEach((other) => {
      other.mergeWatchState(watchState);
    });
  }

  // Add new watch state
  else {
    WatchStates.insert(watchState);
  }
};

// Queries
WatchStates.queryUnique = function(userId, malId) {
  // Validate
  Schemas.WatchState.validate({
    userId: userId,
    malId: malId
  }, {
    keys: ['userId', 'malId']
  });

  // Return results cursor
  return WatchStates.find({
    userId: userId,
    malId: malId
  });
};

WatchStates.queryWithStatuses = function(userId, statuses) {
  // Validate
  Schemas.WatchState.validate({
    userId: userId
  }, {
    keys: ['userId']
  });
  statuses.forEach((status) => {
    Schemas.WatchState.validate({
      malStatus: status
    }, {
      keys: ['malStatus']
    });
  });

  // Return results cursor
  return WatchStates.find({
    userId: userId,
    malStatus: {
      $in: statuses
    }
  });
};
