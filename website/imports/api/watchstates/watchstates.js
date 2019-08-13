import SimpleSchema from 'simpl-schema';

// Collection
export const WatchStates = new Mongo.Collection('watchstates');

// Constants
WatchStates.validStatuses  = ['watching', 'completed', 'held', 'dropped', 'planned'];

// Helpers
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

  status: {
    type: String,
    allowedValues: WatchStates.validStatuses,
    index: true,
    defaultValue: 'watching',
    autoform: {
      options: WatchStates.validStatuses.map((statusId) => {
        return {
          label: WatchStates.makeFancyStatus(statusId),
          value: statusId
        };
      }),
      firstOption: false
    }
  },
  rewatching: {
    type: Boolean,
    autoValue: function() {
      if (!this.value && (!this.isUpdate || this.isSet)) {
        return false;
      } else if (!this.isSet) {
        return undefined;
      }
      if (this.field('status').value !== 'watching') {
        return false;
      } else {
        return this.value;
      }
    }
  },
  episodesWatched: {
    type: Number,
    defaultValue: 0,
    min: 0
  },
  score: {
    type: SimpleSchema.Integer,
    optional: true,
    min: 1,
    max: 10
  },

  priority: {
    type: SimpleSchema.Integer,
    defaultValue: 0
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
  mergeWatchState(other, fromMal) {
    // Update this
    Object.keys(other).forEach((key) => {
      if (!fromMal || key !== 'episodesWatched' || Math.floor(this[key]) !== Math.floor(other[key])) {
        this[key] = other[key];
      }
    });

    // Update database
    WatchStates.update(this._id, {
      $set: Schemas.WatchState.clean(this, {
        mutate: true
      })
    });

    // Remove other from database
    if (other._id && other._id !== this._id) {
      WatchStates.remove(other._id);
    }
  },

  fancyStatus() {
    if (this.rewatching) {
      return 'Re-watching';
    } else {
      return WatchStates.makeFancyStatus(this.status);
    }
  },

  shortStatus() {
    if (this.rewatching) {
      return 'RW';
    } else {
      switch (this.status) {
        case 'watching':
          return 'CW';
        case 'completed':
          return 'CMPL';
        case 'held':
          return 'HOLD';
        case 'dropped':
          return 'DROP';
        case 'planned':
          return 'PTW';
      }
    }
  }
});

WatchStates.addWatchState = function(watchState, fromMal) {
  // Find existing watch states
  let others = WatchStates.queryUnique(watchState.userId, watchState.malId);

  // Update existing watch states
  if (others.count()) {
    let firstOther = undefined;

    others.forEach((other) => {
      if (!firstOther) {
        firstOther = other;
      } else {
        firstOther.mergeWatchState(other, fromMal);
      }
    });

    firstOther.mergeWatchState(watchState, fromMal);
  }

  // Add new watch state otherwise
  else {
    WatchStates.insert(watchState);
  }
};

WatchStates.removeWatchState = function(watchState) {
  // Find existing watch states
  let others = WatchStates.queryUnique(watchState.userId, watchState.malId);

  // Remove existing watch states
  others.forEach((other) => {
    WatchStates.remove(other._id);
  });
};

WatchStates.sendWatchStateToMAL = function(watchState, remove=false) {
  return new Promise((resolve, reject) => {
    // Get related user
    let user = Meteor.users.findOne(watchState.userId);

    // Determine the url to use (add/edit)
    let url = '';
    if (remove) {
      url = 'https://myanimelist.net/ownlist/anime/' + watchState.malId + '/delete';
    } else if (WatchStates.queryUnique(watchState.userId, watchState.malId).count() > 0) {
      url = 'https://myanimelist.net/ownlist/anime/' + watchState.malId + '/edit';
    } else {
      url = 'https://myanimelist.net/ownlist/anime/add?selected_series_id=' + watchState.malId;
    }

    // Create form data for the watching state
    let states = {};
    if (watchState.rewatching) {
      states = {
        'add_anime[status]': 2,
        'add_anime[is_rewatching]': 1,
      };
    } else {
      states = {
        'add_anime[status]': watchState.status === WatchStates.validStatuses[4] ? 6 : WatchStates.validStatuses.indexOf(watchState.status) + 1,
      };
    }

    // Make POST request
    rp('POST', {
      url: url,
      jar: user.getMalCookieJar(),
      form: Object.assign({
        'anime_id': watchState.malId,
        'csrf_token': user.malTokenCSRF,
        'add_anime[priority]': watchState.priority,
        'add_anime[is_asked_to_discuss]': 0,
        'add_anime[sns_post_type]': 0,
        'add_anime[num_watched_episodes]': Math.floor(watchState.episodesWatched),
        'add_anime[score]': watchState.score,
      }, states),
    }, ['Invalid submission.', 'Failed to edit the series.', 'Failed to add the series.'])

    .then(() => {
      // On successful update
      resolve();
    })

    .catch((error) => {
      // Handle authentication errors
      if (error === 400) {
        if (user.malCanWrite) {
          user.updateMalSession().then(() => {
            return WatchStates.sendWatchStateToMAL(watchState);
          }).then(resolve).catch(reject);
        } else {
          reject();
        }
      }
      // Handle generic errors
      else {
        if (error) {
          console.error(error);
        }
        reject();
      }
    });
  });
};

// Methods
Meteor.methods({
  async 'watchStates.changeWatchState'(watchState) {
    // Clean received data
    Schemas.WatchState.clean(watchState, {
      mutate: true
    });

    // Validate received data
    Schemas.WatchState.validate(watchState);
    new SimpleSchema({
      userId: {
        type: String,
        allowedValues: [this.userId]
      },
      malCanWrite: {
        type: Boolean,
        allowedValues: [true]
      }
    }).validate({
      userId: watchState.userId,
      malCanWrite: Meteor.users.findOne(watchState.userId).malCanWrite
    });

    // Send data to MAL and add to DB on success
    if (this.isSimulation) {
      WatchStates.addWatchState(watchState, false);
    } else {
      await WatchStates.sendWatchStateToMAL(watchState).then(() => {
        WatchStates.addWatchState(watchState, false);
      }).catch(() => {});
    }
  },

  async 'watchStates.removeWatchState'(watchState) {
    // Clean received data
    Schemas.WatchState.clean(watchState, {
      mutate: true
    });

    // Validate received data
    Schemas.WatchState.validate(watchState);
    new SimpleSchema({
      userId: {
        type: String,
        allowedValues: [this.userId]
      },
      malCanWrite: {
        type: Boolean,
        allowedValues: [true]
      }
    }).validate({
      userId: watchState.userId,
      malCanWrite: Meteor.users.findOne(watchState.userId).malCanWrite
    });

    // Send data to MAL and add to DB on success
    if (this.isSimulation) {
      WatchStates.removeWatchState(watchState);
    } else {
      await WatchStates.sendWatchStateToMAL(watchState, true).then(() => {
        WatchStates.removeWatchState(watchState);
      }).catch(() => {});
    }
  }
});

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

WatchStates.queryUniqueMultiple = function(userId, malIds) {
  // Validate
  Schemas.WatchState.validate({
    userId: userId
  }, {
    keys: ['userId']
  });
  malIds.forEach((malId) => {
    Schemas.WatchState.validate({
      malId: malId
    }, {
      keys: ['malId']
    });
  });

  // Return results cursor
  return WatchStates.find({
    userId: userId,
    malId: {
      $in: malIds
    }
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
      status: status
    }, {
      keys: ['status']
    });
  });

  // Return results cursor
  return WatchStates.find({
    userId: userId,
    status: {
      $in: statuses
    }
  });
};
