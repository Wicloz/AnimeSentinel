import Cheerio from 'cheerio';
import SimpleSchema from 'simpl-schema';
import Streamers from "../../streamers/streamers";
import {Episodes} from "../episodes/episodes";
import {Thumbnails} from '../thumbnails/thumbnails';
import ScrapingHelpers from '../../streamers/scrapingHelpers';
import moment from 'moment-timezone';
import {WatchStates} from '../watchstates/watchstates';
const score = require('string-score');

// Collection
export const Shows = new Mongo.Collection('shows');

// Constants
Shows.validTypes = ['TV', 'OVA', 'Movie', 'Special', 'ONA'];
Shows.validGenres = ['Action', 'Adventure', 'Cars', 'Comedy', 'Dementia', 'Demons', 'Mystery', 'Drama', 'Ecchi',
  'Fantasy', 'Game', 'Historical', 'Horror', 'Kids', 'Magic', 'Martial Arts', 'Mecha', 'Music', 'Parody', 'Samurai',
  'Romance', 'School', 'Sci-Fi', 'Shoujo', 'Shoujo Ai', 'Shounen', 'Shounen Ai', 'Space', 'Sports', 'Super Power',
  'Vampire', 'Yaoi', 'Yuri', 'Harem', 'Slice of Life', 'Supernatural', 'Military', 'Police', 'Psychological',
  'Thriller', 'Seinen', 'Josei'];
Shows.validQuarters = ['Winter', 'Spring', 'Summer', 'Fall'];

// Schema
Schemas.Show = new SimpleSchema({
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

  malId: {
    type: SimpleSchema.Integer,
    optional: true,
    index: true
  },

  streamerUrls: {
    type: Array,
    minCount: 1,
    autoValue: function() {
      if (!this.isSet) {
        return undefined;
      }
      return this.value.reduce((total, value) => {
        if (value && !total.hasPartialObjects({
            streamerId: value.streamerId,
            type: value.type
          })) {
          total.push(value);
        }
        return total;
      }, []);
    }
  },
  'streamerUrls.$': {
    type: Object
  },
  'streamerUrls.$.streamerId': {
    type: String
  },
  'streamerUrls.$.type': {
    type: String
  },
  'streamerUrls.$.url': {
    type: String
  },
  'streamerUrls.$.lastDownloadFailed': {
    type: Boolean,
    optional: true,
    defaultValue: false
  },

  name: {
    type: String
  },
  altNames: {
    type: Array,
    index: true,
    minCount: 1,
    autoValue: function() {
      if (!this.isSet) {
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
  'altNames.$': {
    type: String
  },
  description: {
    type: String,
    optional: true,
    autoValue: function() {
      if (!this.isSet || !this.value) {
        this.unset();
        return undefined;
      }
      let desc = Cheerio.load(this.value);
      desc('script').remove();
      return desc('body').html();
    }
  },
  thumbnails: {
    type: Array,
    optional: true,
    autoValue: function() {
      if ((!this.isSet || !this.value) && !this.isUpdate) {
        return [];
      }
      if (!this.isSet) {
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
  'thumbnails.$': {
    type: String
  },
  type: {
    type: String,
    index: true,
    optional: true,
    allowedValues: Shows.validTypes
  },
  genres: {
    type: Array,
    index: true,
    optional: true,
    autoValue: function() {
      if ((!this.isSet || !this.value) && !this.isUpdate) {
        return [];
      }
      if (!this.isSet) {
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
  'genres.$': {
    type: String,
    allowedValues: Shows.validGenres
  },
  airedStart: {
    type: Object,
    index: true,
    optional: true,
    defaultValue: {}
  },
  'airedStart.year': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'airedStart.month': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'airedStart.date': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'airedStart.hour': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'airedStart.minute': {
    type: SimpleSchema.Integer,
    optional: true
  },
  airedEnd: {
    type: Object,
    index: true,
    optional: true,
    defaultValue: {}
  },
  'airedEnd.year': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'airedEnd.month': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'airedEnd.date': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'airedEnd.hour': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'airedEnd.minute': {
    type: SimpleSchema.Integer,
    optional: true
  },
  season: {
    type: Object,
    index: true,
    optional: true
  },
  'season.quarter': {
    type: String,
    allowedValues: Shows.validQuarters
  },
  'season.year': {
    type: SimpleSchema.Integer
  },
  episodeCount: {
    type: SimpleSchema.Integer,
    min: 1,
    optional: true
  },

  broadcastIntervalMinutes: {
    type: SimpleSchema.Integer,
    min: 0,
    optional: true
  },
  determinedIntervalMinutes: {
    type: Object,
    optional: true
  },
  'determinedIntervalMinutes.sub': {
    type: SimpleSchema.Integer,
    min: 0,
    optional: true
  },
  'determinedIntervalMinutes.dub': {
    type: SimpleSchema.Integer,
    min: 0,
    optional: true
  },
  'determinedIntervalMinutes.raw': {
    type: SimpleSchema.Integer,
    min: 0,
    optional: true
  }
}, { tracker: Tracker });

Shows.attachSchema(Schemas.Show);

// Constants
Shows.arrayKeys = Schemas.Show._schemaKeys.filter((key) => {
  return !key.includes('.') && Schemas.Show._schema[key].type.definitions[0].type.toString().includes('Array()');
});
Shows.objectKeys = ['airedStart', 'airedEnd'];
Shows.descriptionCutoff = '&#x2026; (read more)';
Shows.timeUntilRecache = 86400000; // 1 day
Shows.maxUpdateTime = 600000; // 10 minutes

if (Meteor.isDevelopment) {
  Shows.timeUntilRecache = 10000;
  Shows.maxUpdateTime = 30000;
}

// Helpers
Shows.helpers({
  remove() {
    Shows.remove(this._id);
  },

  getThumbnailUrls() {
    if (!this.thumbnails || this.thumbnails.empty()) {
      return ['/media/unknown.png'];
    }

    let urls = Thumbnails.queryWithHashes(this.thumbnails).fetch().filterMap((thumbnail) => {
      if (thumbnail.uploadedAt) {
        return thumbnail.url({store: Session.get('FeatureSupportWebP') ? 'thumbnailsWEBP' : 'thumbnailsJPEG'});
      }
    });

    while (urls.length < this.thumbnails.length) {
      urls.push('/media/spinner.gif');
    }

    return urls;
  },

  airingState(translationType) {
    let lastEpisode = Episodes.queryForTranslationType(this._id, translationType, 1).fetch()[0];

    if (!lastEpisode || lastEpisode.episodeNumEnd <= 0) {
      return 'Not Yet Aired';
    }

    else if (lastEpisode && lastEpisode.episodeNumEnd >= this.episodeCount) {
      return 'Completed';
    }

    else {
      return 'Airing';
    }
  },

  relevantBroadcastInterval(translationType) {
    let interval = this.broadcastIntervalMinutes;

    if ((!interval || translationType === 'dub') && this.determinedIntervalMinutes && this.determinedIntervalMinutes[translationType]) {
      interval = this.determinedIntervalMinutes[translationType];
    }

    return interval;
  },

  nextEpisodeDate(translationType) {
    // Return start date if the show has not started
    if (this.airingState(translationType) === 'Not Yet Aired') {
      return translationType === 'dub' ? undefined : this.airedStart;
    }

    // Determine the interval to use for the calculation
    let intervalToUse = this.relevantBroadcastInterval(translationType);

    // Stop if the interval is not known
    if (!intervalToUse) {
      return undefined;
    }

    // Grab one of the last episodes
    let lastEpisode = Episodes.queryForTranslationType(this._id, translationType, 1).fetch()[0];

    // Determine earliest upload date
    let lastDate = {};
    Episodes.queryForEpisode(this._id, translationType, lastEpisode.episodeNumStart, lastEpisode.episodeNumEnd).forEach((episode) => {
      lastDate = ScrapingHelpers.determineEarliestAiringDate(lastDate, episode.uploadDate);
    });

    // Convert to moment and add the interval
    let lastDateMoment = moment(lastDate);
    lastDateMoment.add(intervalToUse, 'minutes');

    // Convert back to object
    Object.keys(lastDate).forEach((key) => {
      lastDate[key] = lastDateMoment.get(key);
    });

    // Return
    return lastDate;
  },

  nextEpisodeInterval(translationType) {
    invalidateMinute.depend();
    return Math.round(
      moment.duration(moment.utc(this.nextEpisodeDate(translationType)) - moment.utc()).asMinutes()
    );
  },

  watchedEpisodes() {
    let watchState = WatchStates.queryUnique(Meteor.userId(), this.malId).fetch()[0];
    return watchState ? watchState.malWatchedEpisodes : 0;
  },

  availableEpisodes(translationType) {
    let lastEpisode = Episodes.queryForTranslationType(this._id, translationType, 1).fetch()[0];
    return lastEpisode ? lastEpisode.episodeNumEnd : 0;
  },

  expired() {
    let now = moment();
    return (!this.locked() && (!this.lastUpdateStart || moment(this.lastUpdateEnd).add(Shows.timeUntilRecache) < now)) ||
            (this.locked() && moment(this.lastUpdateStart).add(Shows.maxUpdateTime) < now);
  },

  locked() {
    return this.lastUpdateStart && (!this.lastUpdateEnd || this.lastUpdateStart > this.lastUpdateEnd);
  },

  recalculateEpisodeInterval(translationType) {
    // Get the earliest episode for each number combination
    let earliestEpisodes = [];
    Episodes.queryForTranslationType(this._id, translationType).forEach((episode) => {
      let selector = {
        showId: episode.showId,
        translationType: episode.translationType,
        episodeNumStart: episode.episodeNumStart,
        episodeNumEnd: episode.episodeNumEnd
      };

      if (earliestEpisodes.hasPartialObjects(selector)) {
        let other = earliestEpisodes.getPartialObjects(selector)[0];
        other.uploadDate = ScrapingHelpers.determineEarliestAiringDate(other.uploadDate, episode.uploadDate);
        earliestEpisodes = earliestEpisodes.replacePartialObjects(selector, other);
      }

      else {
        selector.uploadDate = episode.uploadDate;
        earliestEpisodes.push(selector);
      }
    });

    // Stop if there are less than 2 distinct episodes
    if (earliestEpisodes.length < 2) {
      return;
    }

    // Sort episodes by upload date ascending
    earliestEpisodes.sort((a, b) => {
      return moment.duration(moment.utc(a.uploadDate) - moment.utc(b.uploadDate)).asMinutes();
    });

    // Calculate delays between episodes and sort ascending
    let episodeDelays = [];
    for (let i = 1; i < earliestEpisodes.length; i++) {
      episodeDelays.push(moment.duration(moment.utc(earliestEpisodes[i].uploadDate) - moment.utc(earliestEpisodes[i - 1].uploadDate)).asMinutes());
    }
    episodeDelays = episodeDelays.sort((a, b) => {
      return a - b;
    });

    // Spread delays among bins depending on their distance and sort by length descending
    let episodeDelayBins = [];
    episodeDelays.forEach((delay) => {
      if (!episodeDelayBins.empty() && delay - episodeDelayBins.peek().peek() < 1440) {
        episodeDelayBins.peek().push(delay);
      } else {
        episodeDelayBins.push([delay]);
      }
    });
    episodeDelayBins = episodeDelayBins.sort((a, b) => {
      return b.length - a.length;
    });

    // Determine the amount of maximum size bins
    let biggestBinCount = 1;
    while (episodeDelayBins[biggestBinCount] && episodeDelayBins[biggestBinCount].length === episodeDelayBins[biggestBinCount - 1].length) {
      biggestBinCount++;
    }

    // Calculate the total and count for the maximum bins
    let total = 0;
    let count = 0;
    for (let i = 0; i < biggestBinCount; i++) {
      episodeDelayBins[i].forEach((delay) => {
        total += delay;
        count++;
      })
    }

    // Store the average on this
    if (!this.determinedIntervalMinutes) {
      this.determinedIntervalMinutes = {};
    }
    this.determinedIntervalMinutes[translationType] = Math.round(total / count);

    // Store in the database
    Shows.update(this._id, {
      $set: {
        determinedIntervalMinutes: this.determinedIntervalMinutes
      }
    });
  },

  mergePartialShow(other) {
    // Copy and merge attributes
    Object.keys(other).forEach((key) => {
      if ((typeof this[key] === 'undefined' && !['_id', 'lastUpdateStart', 'lastUpdateEnd'].includes(key))
        || (Shows.objectKeys.includes(key) && Object.countNonEmptyValues(other[key]) > Object.countNonEmptyValues(this[key]))) {
        this[key] = other[key];
      }
      else if (Shows.arrayKeys.includes(key)) {
        this[key] = this[key].concat(other[key]);
      }
    });

    // Determine if the description should be replaced
    if (this.description && other.description && this.description.endsWith(Shows.descriptionCutoff) && other.description.length > this.description.length) {
      this.description = other.description;
    }

    // Update database
    Shows.update({
      _id: this._id,
      lastUpdateStart: this.lastUpdateStart
    }, {
      $set: Schemas.Show.clean(this, {
        mutate: true
      })
    });

    if (other._id) {
      // Remove other from database
      other.remove();
      // Move episodes
      Episodes.moveEpisodes(other._id, this._id);
    }
  },

  attemptUpdate() {
    if (this.expired()) {
      // Mark update as started
      this.lastUpdateStart = moment().toDate();
      Shows.update(this._id, {
        $set: {
          lastUpdateStart: this.lastUpdateStart
        }
      });

      // Track found episodes
      let episodeIds = [];

      Streamers.createFullShow(this, (show) => {

        // Determine which streamers failed to download
        let streamersIdsFailed = show.streamerUrls.filter((streamerUrl) => {
          return streamerUrl.lastDownloadFailed;
        }).map((streamerUrl) => {
          return streamerUrl.streamerId;
        });

        // Remove missing episodes
        Episodes.remove({
          showId: this._id,
          _id: {
            $nin: episodeIds
          },
          streamerId: {
            $nin: streamersIdsFailed
          }
        });

        // Replace existing fields
        Object.keys(show).forEach((key) => {
          this[key] = show[key];
        });

        // Update result
        this.lastUpdateEnd = moment().toDate();
        Shows.update({
          _id: this._id,
          lastUpdateStart: this.lastUpdateStart
        }, {
          $set: Schemas.Show.clean(this, {
            mutate: true
          })
        });

        // Merge duplicate shows
        let others = Shows.queryMatchingShows(this);
        others.forEach((other) => {
          if (other._id !== this._id) {
            this.mergePartialShow(other);
          }
        })

      }, (partial, episodes) => {

        // Insert any partial results found in the process
        Shows.addPartialShow(partial, episodes);

      }, (full) => {

        // Copy and merge attributes
        Object.keys(full).forEach((key) => {
          if ((typeof this[key] === 'undefined' && !['_id', 'lastUpdateStart', 'lastUpdateEnd'].includes(key))
            || (Shows.objectKeys.includes(key) && Object.countNonEmptyValues(full[key]) > Object.countNonEmptyValues(this[key]))) {
            this[key] = full[key];
          }
          else if (Shows.arrayKeys.includes(key)) {
            this[key] = this[key].concat(full[key]);
          }
        });

        // Update result
        Shows.update({
          _id: this._id,
          lastUpdateStart: this.lastUpdateStart
        }, {
          $set: Schemas.Show.clean(this, {
            mutate: true
          })
        });

      }, (episode) => {

        // Add found episodes
        episodeIds.push(Episodes.addEpisode(episode));

      });
    }
  }
});

Shows.addPartialShow = function(show, episodes) {
  let ids = [];

  // Grab matching shows from database
  let others = Shows.queryMatchingShows(show);

  // Merge if shows were found
  if (others.count()) {
    let othersFull = [];
    let othersPartial = [];

    others.forEach((other) => {
      if (other.lastUpdateStart) {
        othersFull.push(other);
      } else {
        othersPartial.push(other);
      }
    });

    if (othersFull.empty()) {
      othersFull.push(othersPartial.shift());
    }

    othersFull.forEach((otherFull) => {
      othersPartial.forEach((otherPartial) => {
        otherFull.mergePartialShow(otherPartial);
      });
      otherFull.mergePartialShow(show);
      ids.push(otherFull._id);
    });
  }

  // Insert otherwise
  else {
    ids.push(Shows.insert(show));
  }

  // Add any episodes
  if (episodes) {
    ids.forEach((id) => {
      episodes.forEach((episode) => {
        episode.showId = id;
        Episodes.addEpisode(episode);
      });
    });
  }

  // Return ids
  return ids;
};

// Methods
Meteor.methods({
  'shows.attemptUpdate'(id) {
    Schemas.id.validate({id});
    Shows.findOne(id).attemptUpdate();
  }
});

// Queries
Shows.querySearch = function(search, limit) {
  // Clean
  Schemas.Search.clean(search, {
    mutate: true
  });

  // Validate
  Schemas.Search.validate(search);
  new SimpleSchema({
    limit: {
      type: Number
    }
  }).validate({limit});

  // Setup initial options
  let selector = {};
  let options = {};
  if (Meteor.isClient || !search.query) {
    options.limit = limit;
  }

  // Search on 'types'
  if (!search.types.empty()) {
    if (search.types.includes('Unknown')) {
      search.types.push(undefined);
    }
    if (search.includeTypes) {
      selector.type = {
        $in: search.types
      };
    } else {
      selector.type = {
        $nin: search.types
      };
    }
  }

  // Search on 'genres'
  if (!search.genres.empty()) {
    if (search.includeGenres) {
      if (search.genres.includes('Unknown')) {
        selector.$or = [{
          genres: {$in: search.genres}
        }, {
          genres: {$exists: false}
        }, {
          genres: []
        }];
      } else {
        selector.genres = {
          $in: search.genres
        };
      }
    } else {
      if (search.genres.includes('Unknown')) {
        selector.genres = {
          $nin: search.genres,
          $exists: true,
          $ne: []
        };
      } else {
        selector.genres = {
          $nin: search.genres
        };
      }
    }
  }

  // Search on 'query'
  if (search.query) {
    selector.altNames = {
      $regex: '.*' + search.query.split('').map((character) => {
        return RegExp.escape(character);
      }).join('.*') + '.*',
      $options: 'i'
    };

    if (Meteor.isClient) {
      let cleanQuery = search.query.replace(/["'「」]/g, '');
      options.sort = function(a, b) {
        let scoreA = a.altNames.reduce((bestSore, altName) => {
          if (bestSore === 1) return 1;
          return Math.max(bestSore, score(altName.cleanQuery().replace(/["'「」]/g, ''), cleanQuery, 0.1));
        }, 0);
        let scoreB = b.altNames.reduce((bestSore, altName) => {
          if (bestSore === 1) return 1;
          return Math.max(bestSore, score(altName.cleanQuery().replace(/["'「」]/g, ''), cleanQuery, 0.1));
        }, 0);
        return scoreB - scoreA;
      }
    }
  }

  else {
    options.sort = {
      name: 1
    };
  }

  // Return results cursor
  return Shows.find(selector, options);
};

Shows.queryWithIds = function(ids) {
  // Validate
  Schemas.ids.validate({ids});

  // Return results cursor
  return Shows.find({
    _id: {
      $in: ids
    }
  });
};

Shows.queryMatchingShows = function(show) {
  return ScrapingHelpers.queryMatchingShows(Shows, show);
};

Shows.queryWithMalIds = function(malIds) {
  // Validate
  new SimpleSchema({
    malIds: Array,
    'malIds.$': SimpleSchema.Integer
  }).validate({malIds});

  // Return results cursor
  return Shows.find({
    malId: {
      $in: malIds
    }
  });
};
