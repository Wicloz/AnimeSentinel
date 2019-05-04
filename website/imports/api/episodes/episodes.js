import SimpleSchema from 'simpl-schema';
import Streamers, {TempShow} from '../../streamers/streamers';
import {Shows} from '../shows/shows';
import {WatchStates} from '../watchstates/watchstates';
import ScrapingHelpers from '../../streamers/scrapingHelpers';

// Collection
export const Episodes = new Mongo.Collection('episodes');

// Constants
Episodes.validTranslationTypes = ['sub', 'dub', 'raw'];

// Schema
Schemas.Episode = new SimpleSchema({
  _id: {
    type: String,
    optional: true
  },

  showId: {
    type: String,
    index: true
  },
  episodeNumStart: {
    type: Number,
    index: true
  },
  episodeNumEnd: {
    type: Number,
    index: true
  },
  notes: {
    type: String,
    index: true,
    optional: true
  },
  translationType: {
    type: String,
    allowedValues: Episodes.validTranslationTypes,
    index: true
  },
  streamerId: {
    type: String,
    index: true
  },
  sourceName: {
    type: String,
    index: true
  },

  sourceUrl: {
    type: String
  },
  flags: {
    type: Array,
    autoValue: function() {
      if (!this.value && (!this.isUpdate || this.isSet)) {
        return [];
      } else if (!this.isSet) {
        return undefined;
      }
      return this.value.reduce((total, value) => {
        value = value.trim();
        if (value && !total.includes(value)) {
          total.push(value);
        }
        return total;
      }, []);
    }
  },
  'flags.$': {
    type: String,
    allowedValues: ['flash', 'cloudflare', 'x-frame-options', 'mixed-content', 'requires-plugins']
  },
  uploadDate: {
    type: Object,
    index: true,
    optional: true,
    defaultValue: {}
  },
  'uploadDate.year': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'uploadDate.month': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'uploadDate.date': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'uploadDate.hour': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'uploadDate.minute': {
    type: SimpleSchema.Integer,
    optional: true
  }
}, { tracker: Tracker });

Episodes.attachSchema(Schemas.Episode);

// Constants
Episodes.flagsWithoutAddOnPreference = ['cloudflare', 'mixed-content', 'flash'];
Episodes.flagsWithoutAddOnNever = ['x-frame-options'];
Episodes.flagsWithAddOnPreference = ['flash', 'mixed-content'];
Episodes.flagsWithAddOnNever = [];
Episodes.flagsWithSandboxingNever = ['requires-plugins'];
Episodes.objectKeys = Schemas.Episode._schemaKeys.filter((key) => {
  return !key.includes('.') && Schemas.Episode._schema[key].type.definitions[0].type.toString().includes('Object()');
});
Episodes.informationKeys = ['sourceUrl', 'flags', 'uploadDate'];

// Helpers
Episodes.helpers({
  translationTypeExpanded() {
    return ScrapingHelpers.makeTranslationTypeFancy(this.translationType);
  },

  safeEpisodeNumStart() {
    return this.episodeNumStart.toString();
  },

  safeEpisodeNumEnd() {
    return this.episodeNumEnd.toString();
  },

  notesEncoded() {
    if (this.notes) {
      return encodeBase64(this.notes);
    } else {
      return 'none';
    }
  },

  encodedKey() {
    return encodeURIComponent(JSON.stringify({
      episodeNumStart: this.episodeNumStart,
      episodeNumEnd: this.episodeNumEnd,
      notes: this.notesEncoded()
    }));
  },

  fancyKey() {
    return this.episodeNumStart
      + (this.episodeNumStart !== this.episodeNumEnd ? ' - ' + this.episodeNumEnd : '')
      + (this.notes ? ' - ' + this.notes : '');
  },

  watched() {
    if (Meteor.userId()) {
      let show = Shows.findOne(this.showId);
      if (show && typeof show.malId !== 'undefined') {
        let watchState = WatchStates.queryUnique(Meteor.userId(), show.malId).fetch()[0];
        return watchState && watchState.episodesWatched >= this.episodeNumEnd;
      }
    }
    return false;
  },

  mergeEpisode(other) {
    // Copy and merge attributes
    Object.keys(other).forEach((key) => {
      if (Episodes.informationKeys.includes(key) && (!Episodes.objectKeys.includes(key) || Object.countNonEmptyValues(other[key]) > Object.countNonEmptyValues(this[key]))) {
        this[key] = other[key];
      }
    });

    // Update database
    Episodes.update(this._id, {
      $set: Schemas.Episode.clean(this, {
        mutate: true
      })
    });

    // Remove other from database
    if (other._id && other._id !== this._id) {
      Episodes.remove(other._id);
    }
  },

  moveToShow(showId) {
    this.showId = showId;
    Episodes.remove(this._id);
    Episodes.addEpisode(this);
  }
});

Episodes.addEpisode = function(episode) {
  // Get episodes which are the same
  let others = Episodes.queryUnique(episode.showId, episode.translationType, episode.episodeNumStart, episode.episodeNumEnd, episode.notes, episode.streamerId, episode.sourceName);

  // Merge episodes if found
  if (others.count()) {
    let firstOther = undefined;

    others.forEach((other) => {
      if (!firstOther) {
        firstOther = other;
      } else {
        firstOther.mergeEpisode(other);
      }
    });

    firstOther.mergeEpisode(episode);
    episode = firstOther;
  }

  // Insert otherwise
  else {
    Episodes.insert(episode);
  }

  // Find related show
  episode = Episodes._transform(episode);
  let show = Shows.findOne(episode.showId);

  if (show) {
    // Recalculate certain attributes
    show.afterNewEpisode(episode.translationType);
  }

  // Return id
  return episode._id;
};

Episodes.moveEpisodes = function(fromId, toId) {
  Episodes.queryForShow(fromId).forEach((episode) => {
    episode.moveToShow(toId);
  });
};

Episodes.isFlagProblematic = function(flag) {
  return (getStorageItem('FrameSandboxingEnabled') && Episodes.flagsWithSandboxingNever.includes(flag) && BrowserDetect.browser === 'Chrome') ||
    (Session.get('AddOnInstalled') && (Episodes.flagsWithAddOnPreference.includes(flag) || Episodes.flagsWithAddOnNever.includes(flag))) ||
    (!Session.get('AddOnInstalled') && (Episodes.flagsWithoutAddOnPreference.includes(flag) || Episodes.flagsWithoutAddOnNever.includes(flag)));
};

Episodes.isFlagDisabled = function(flag) {
  return (getStorageItem('FrameSandboxingEnabled') && Episodes.flagsWithSandboxingNever.includes(flag) && BrowserDetect.browser === 'Chrome') ||
    (Session.get('AddOnInstalled') && Episodes.flagsWithAddOnNever.includes(flag)) ||
    (!Session.get('AddOnInstalled') && Episodes.flagsWithoutAddOnNever.includes(flag));
};

Episodes.findRecentEpisodes = function(doneCallback) {
  let missingResultCount = 0;

  // For each streamer
  Streamers.getStreamers().forEach((streamer) => {
    // Download and process recent page
    Streamers.getRecentResults(streamer.recentPage, streamer, undefined, (results) => {
      // For each result
      results.forEach((result) => {

        // Add as partial
        Shows.addPartialShow(result.show);

        // Process show in simple mode when episodes are missing
        if (result.missing) {
          missingResultCount++;
          let tempShow = new TempShow(result.show, (partial, episodes) => {

            Shows.addPartialShow(partial, episodes);
            missingResultCount--;
            if (missingResultCount === 0) {
              doneCallback();
            }

          }, (partial, episodes) => {

            return Shows.addPartialShow(partial, episodes);

          }, (partial) => {

            result.show = partial;
            Shows.addPartialShow(result.show);
            delete partial._id;

          }, (episode) => {

            Shows.queryMatchingShows(result.show).forEach((show) => {
              episode.showId = show._id;
              Episodes.addEpisode(episode);
              delete episode._id;
            });

          }, true);
          tempShow.start();
        }

      });
    });
  });
};

Episodes.getPreviousEpisode = function(showId, translationType, episodeNumStart, episodeNumEnd, notes) {
  let episodes = Episodes.queryForTranslationType(showId, translationType).fetch();

  for (let i = episodes.length - 1; i >= 0; i--) {
    if (episodes[i].episodeNumStart === episodeNumStart && episodes[i].episodeNumEnd === episodeNumEnd && episodes[i].notes === notes) {
      return episodes[i + 1];
    }
  }
};

Episodes.getNextEpisode = function(showId, translationType, episodeNumStart, episodeNumEnd, notes) {
  let episodes = Episodes.queryForTranslationType(showId, translationType).fetch();

  for (let i = 0; i < episodes.length; i++) {
    if (episodes[i].episodeNumStart === episodeNumStart && episodes[i].episodeNumEnd === episodeNumEnd && episodes[i].notes === notes) {
      return episodes[i - 1];
    }
  }
};

// Queries
Episodes.queryForShow = function(showId) {
  // Validate
  Schemas.Episode.validate({
    showId: showId,
  }, {
    keys: ['showId']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId
  });
};

Episodes.queryForTranslationType = function(showId, translationType, limit) {
  // Validate
  Schemas.Episode.validate({
    showId: showId,
    translationType: translationType
  }, {
    keys: ['showId', 'translationType']
  });
  new SimpleSchema({
    limit: {
      type: SimpleSchema.Integer,
      optional: true
    }
  }).validate({limit});

  // Return results cursor
  return Episodes.find({
    showId: showId,
    translationType: translationType
  }, {
    limit: limit,
    sort: {
      episodeNumEnd: -1,
      episodeNumStart: -1,
      notes: -1,
      'uploadDate.year': 1,
      'uploadDate.month': 1,
      'uploadDate.date': 1,
      'uploadDate.hour': 1,
      'uploadDate.minute': 1
    }
  });
};

Episodes.queryForEpisode = function (showId, translationType, episodeNumStart, episodeNumEnd, notes) {
  if (!notes) {
    notes = null;
  }

  // Validate
  Schemas.Episode.validate({
    showId: showId,
    translationType: translationType,
    episodeNumStart: episodeNumStart,
    episodeNumEnd: episodeNumEnd,
    notes: notes
  }, {
    keys: ['showId', 'translationType', 'episodeNumStart', 'episodeNumEnd', 'notes']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId,
    episodeNumStart: episodeNumStart,
    episodeNumEnd: episodeNumEnd,
    notes: notes,
    translationType: translationType
  });
};

Episodes.queryForStreamer = function (showId, translationType, episodeNumStart, episodeNumEnd, notes, streamerId) {
  if (!notes) {
    notes = null;
  }

  // Validate
  Schemas.Episode.validate({
    showId: showId,
    translationType: translationType,
    episodeNumStart: episodeNumStart,
    episodeNumEnd: episodeNumEnd,
    notes: notes,
    streamerId: streamerId
  }, {
    keys: ['showId', 'translationType', 'episodeNumStart', 'episodeNumEnd', 'streamerId', 'notes']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId,
    episodeNumStart: episodeNumStart,
    episodeNumEnd: episodeNumEnd,
    notes: notes,
    translationType: translationType,
    streamerId: streamerId
  });
};

Episodes.queryUnique = function (showId, translationType, episodeNumStart, episodeNumEnd, notes, streamerId, sourceName) {
  if (!notes) {
    notes = null;
  }

  // Validate
  Schemas.Episode.validate({
    showId: showId,
    translationType: translationType,
    episodeNumStart: episodeNumStart,
    episodeNumEnd: episodeNumEnd,
    notes: notes,
    streamerId: streamerId,
    sourceName: sourceName
  }, {
    keys: ['showId', 'translationType', 'episodeNumStart', 'episodeNumEnd', 'streamerId', 'sourceName', 'notes']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId,
    episodeNumStart: episodeNumStart,
    episodeNumEnd: episodeNumEnd,
    notes: notes,
    translationType: translationType,
    streamerId: streamerId,
    sourceName: sourceName
  });
};

Episodes.queryToWatch = function(showId, translationType, lastWatched) {
  // Validate
  Schemas.Episode.validate({
    showId: showId,
    translationType: translationType,
    episodeNumEnd: lastWatched
  }, {
    keys: ['showId', 'translationType', 'episodeNumEnd']
  });

  // Return results cursor
  return Episodes.find({
    showId: showId,
    translationType: translationType,
    episodeNumEnd: {
      $gt: lastWatched
    }
  }, {
    sort: {
      episodeNumStart: 1,
      episodeNumEnd: 1,
      notes: -1
    }
  });
};
