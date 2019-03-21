import Cheerio from 'cheerio';
import SimpleSchema from 'simpl-schema';
import {TempShow} from '../../streamers/streamers';
import {Episodes} from "../episodes/episodes";
import {Thumbnails} from '../thumbnails/thumbnails';
import ScrapingHelpers from '../../streamers/scrapingHelpers';
import moment from 'moment-timezone';
import {WatchStates} from '../watchstates/watchstates';
import Streamers from '../../streamers/streamers';
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
      }, []).sort((a, b) => {
        let idCompare = a.streamerId.localeCompare(b.streamerId);
        if (idCompare === 0) {
          return a.type.localeCompare(b.type);
        } else {
          return idCompare;
        }
      });
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
  broadcastInterval: {
    type: SimpleSchema.Integer,
    optional: true
  },
  episodeDuration: {
    type: SimpleSchema.Integer,
    optional: true
  },
  rating: {
    type: String,
    allowedValues: ['G', 'PG', 'PG-13', 'R', 'R+', 'Rx'],
    optional: true
  },

  relatedShows: {
    type: Array,
    optional: true,
    autoValue: function() {
      if (!this.value && (!this.isUpdate || this.isSet)) {
        return [];
      } else if (!this.isSet) {
        return undefined;
      }
      return this.value.reduce((total, value) => {
        if (value) {
          if (value.relation !== 'other') {
            if (total.hasPartialObjects({
              showId: value.showId,
              relation: 'other'
            })) {
              total = total.replacePartialObjects({
                showId: value.showId,
                relation: 'other'
              }, value);
            } else if (!total.hasPartialObjects({
              showId: value.showId,
              relation: value.relation
            })) {
              total.push(value);
            }
          } else if (!total.hasPartialObjects({
            showId: value.showId
          })) {
            total.push(value);
          }
        }
        return total;
      }, []);
    }
  },
  'relatedShows.$': {
    type: Object
  },
  'relatedShows.$.relation': {
    type: String
  },
  'relatedShows.$.showId': {
    type: String
  },

  determinedInterval: {
    type: Object,
    optional: true,
    defaultValue: {}
  },
  'determinedInterval.sub': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'determinedInterval.dub': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'determinedInterval.raw': {
    type: SimpleSchema.Integer,
    optional: true
  },
  lastEpisodeDate: {
    type: Object,
    optional: true,
    defaultValue: {}
  },
  'lastEpisodeDate.sub': {
    type: Object,
    optional: true,
    defaultValue: {}
  },
  'lastEpisodeDate.sub.year': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'lastEpisodeDate.sub.month': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'lastEpisodeDate.sub.date': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'lastEpisodeDate.sub.hour': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'lastEpisodeDate.sub.minute': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'lastEpisodeDate.dub': {
    type: Object,
    optional: true,
    defaultValue: {}
  },
  'lastEpisodeDate.dub.year': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'lastEpisodeDate.dub.month': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'lastEpisodeDate.dub.date': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'lastEpisodeDate.dub.hour': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'lastEpisodeDate.dub.minute': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'lastEpisodeDate.raw': {
    type: Object,
    optional: true,
    defaultValue: {}
  },
  'lastEpisodeDate.raw.year': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'lastEpisodeDate.raw.month': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'lastEpisodeDate.raw.date': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'lastEpisodeDate.raw.hour': {
    type: SimpleSchema.Integer,
    optional: true
  },
  'lastEpisodeDate.raw.minute': {
    type: SimpleSchema.Integer,
    optional: true
  },
  availableEpisodes: {
    type: Object,
    defaultValue: {}
  },
  'availableEpisodes.sub': {
    type: Number,
    defaultValue: 0
  },
  'availableEpisodes.dub': {
    type: Number,
    defaultValue: 0
  },
  'availableEpisodes.raw': {
    type: Number,
    defaultValue: 0
  }
}, { tracker: Tracker });

Shows.attachSchema(Schemas.Show);

// Constants
Shows.arrayKeys = Schemas.Show._schemaKeys.filter((key) => {
  return !key.includes('.') && Schemas.Show._schema[key].type.definitions[0].type.toString().includes('Array()');
});
Shows.objectKeys = Schemas.Show._schemaKeys.filter((key) => {
  return !key.includes('.') && Schemas.Show._schema[key].type.definitions[0].type.toString().includes('Object()');
});
Shows.systemKeys = ['_id', 'lastUpdateStart', 'lastUpdateEnd'];
Shows.descriptionCutoff = '&#x2026; (read more)';
Shows.timeUntilRecache = 86400000; // 1 day
Shows.maxUpdateTime = 600000; // 10 minutes

if (Meteor.isDevelopment) {
  Shows.timeUntilRecache = 60000; // 60 seconds
  Shows.maxUpdateTime = 60000; // 60 seconds
}

// Helpers
Shows.helpers({
  expired() {
    if (Meteor.isClient) {
      invalidateSecond.depend();
    }
    return !this.lastUpdateStart ||
      (!this.locked() && moment.fromUtc(this.lastUpdateEnd).add(Shows.timeUntilRecache).isBefore()) ||
      (this.locked() && moment.fromUtc(this.lastUpdateStart).add(Shows.maxUpdateTime).isBefore());
  },

  locked() {
    return this.lastUpdateStart && (!this.lastUpdateEnd || this.lastUpdateStart > this.lastUpdateEnd);
  },

  malUrl() {
    return tryGetProperty(this.streamerUrls.find((streamerUrl) => {
      return streamerUrl.streamerId === 'myanimelist' && streamerUrl.type === 'details';
    }), 'url');
  },

  watchState() {
    if (this.canHaveWatchState()) {
      return WatchStates.queryUnique(Meteor.userId(), this.malId).fetch()[0];
    }
    return undefined;
  },

  canHaveWatchState() {
    return Meteor.userId() && typeof this.malId !== 'undefined';
  },

  hasWatchState() {
    return WatchStates.queryUnique(Meteor.userId(), this.malId).count() > 0;
  },

  latestEpisode(translationType) {
    return Episodes.queryForTranslationType(this._id, translationType, 1).fetch()[0];
  },

  thumbnailUrls() {
    if (!this.thumbnails || this.thumbnails.empty()) {
      return ['/media/unknown.png'];
    }

    let urls = Thumbnails.queryWithHashes(this.thumbnails).fetch().map((thumbnail) => {
      if (thumbnail.uploadedAt) {
        return thumbnail.url({store: Session.get('FeatureSupportWebP') ? 'thumbnailsWEBP' : 'thumbnailsJPEG'});
      } else {
        return undefined;
      }
    }).filter((thumbnailUrl) => {
      return thumbnailUrl;
    });

    while (urls.length < this.thumbnails.length) {
      urls.push('/media/spinner.svg');
    }

    return urls;
  },

  streamersCleaned() {
    return this.streamerUrls.filter((streamerUrl) => {
      return !streamerUrl.type.startsWith('episodes-');
    }).map((streamerUrl) => {
      return {...streamerUrl, ...Streamers.getSimpleStreamerById(streamerUrl.streamerId)};
    });
  },

  episodeList(translationType) {
    let episodes = [];

    Episodes.queryForTranslationType(this._id, translationType).forEach((episode) => {
      let selector = {
        showId: episode.showId,
        translationType: episode.translationType,
        episodeNumStart: episode.episodeNumStart,
        episodeNumEnd: episode.episodeNumEnd,
        notes: episode.notes
      };
      let other = episodes.getPartialObjects(selector)[0];

      if (other) {
        if (other.streamers.every((streamer) => {
          return streamer.id !== episode.streamerId;
        })) {
          let streamer = Streamers.getSimpleStreamerById(episode.streamerId);
          streamer.sourceUrl = episode.sourceUrl;
          other.streamers.push(streamer);
        }
        other.uploadDate = ScrapingHelpers.determineEarliestAiringDate(other.uploadDate, episode.uploadDate);

        episodes = episodes.replacePartialObjects(selector, other);
      }

      else {
        episode.streamers = [Streamers.getSimpleStreamerById(episode.streamerId)];
        episode.streamers[0].sourceUrl = episode.sourceUrl;
        episodes.push(episode);
      }
    });

    return episodes;
  },

  seriesMap(state, visitedIds=[]) {
    let seriesMap = [];
    visitedIds.push(this._id);

    if (state !== 'sequels') {
      this.relatedShows.getPartialObjects({
        relation: 'prequel'
      }).forEach((prequel) => {
        if (!visitedIds.includes(prequel.showId)) {
          let show = Shows.findOne(prequel.showId);
          if (show) {
            seriesMap = seriesMap.concat(show.seriesMap('prequels', visitedIds))
          }
        }
      });
    }

    seriesMap.push({
      root: this,
      items: this.relatedShowsExpanded(true)
    });

    if (state !== 'prequels') {
      this.relatedShows.getPartialObjects({
        relation: 'sequel'
      }).forEach((sequel) => {
        if (!visitedIds.includes(sequel.showId)) {
          let show = Shows.findOne(sequel.showId);
          if (show) {
            seriesMap = seriesMap.concat(show.seriesMap('sequels', visitedIds))
          }
        }
      });
    }

    return seriesMap;
  },

  relatedShowsExpanded(removePrequelSequel) {
    let uniqueRelations = Array.from(new Set(this.relatedShows.pluck('relation')));

    if (removePrequelSequel) {
      uniqueRelations = uniqueRelations.filter((relation) => {
        return relation !== 'prequel' && relation !== 'sequel';
      });
    }

    return uniqueRelations.map((relation) => {
      return {
        relation: relation,
        shows: Shows.queryWithIds(this.relatedShows.getPartialObjects({
          relation: relation
        }).pluck('showId'))
      };
    });
  },

  relevantBroadcastInterval(translationType) {
    if (translationType !== 'dub' && this.broadcastInterval) {
      return this.broadcastInterval;
    } else {
      return this.determinedInterval[translationType];
    }
  },

  nextEpisodeDate(translationType) {
    // Return start date if the show has not started
    if (this.airingState(translationType) === 'Not Yet Aired') {
      switch (translationType) {
        case 'dub':
          return undefined;
        case 'raw':
          return this.airedStart;
        case 'sub':
          let startDateMoment = moment.utc(this.airedStart).add(1, 'hour');
          return Object.keys(this.airedStart).reduce((startDateObject, key) => {
            startDateObject[key] = startDateMoment.get(key);
            return startDateObject;
          }, {});
      }
    }

    // Return next date if the show is airing
    else if (this.airingState(translationType) === 'Airing') {
      let intervalToUse = this.relevantBroadcastInterval(translationType);
      if (typeof intervalToUse !== 'undefined' && !_.isEmpty(this.lastEpisodeDate[translationType])) {
        let nextDateMoment = moment.utc(this.lastEpisodeDate[translationType]).add(intervalToUse);
        return Object.keys(this.lastEpisodeDate[translationType]).reduce((nextDateObject, key) => {
          nextDateObject[key] = nextDateMoment.get(key);
          return nextDateObject;
        }, {});
      }
    }

    // Return unknown date if something goes wrong
    return undefined;
  },

  airingState(translationType) {
    if (this.availableEpisodes[translationType] <= 0) {
      return 'Not Yet Aired';
    } else if (this.availableEpisodes[translationType] >= this.episodeCount) {
      return 'Completed';
    } else {
      return 'Airing';
    }
  },

  nextEpisodeInterval(translationType) {
    if (!_.isEmpty(this.nextEpisodeDate(translationType))) {
      if (Meteor.isClient) {
        invalidateSecond.depend();
      }
      return moment.fromUtc(this.nextEpisodeDate(translationType)).diff(moment.fromUtc());
    } else {
      return undefined;
    }
  },

  watchedEpisodes() {
    let watchState = this.watchState();
    return watchState ? watchState.episodesWatched : 0;
  },

  afterNewEpisode(translationType) {
    // Get the earliest episode for each unique episode
    let earliestEpisodes = [];
    Episodes.queryForTranslationType(this._id, translationType).forEach((episode) => {
      let selector = {
        episodeNumStart: episode.episodeNumStart,
        episodeNumEnd: episode.episodeNumEnd,
        notes: episode.notes
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

    // Get the earliest episode for each unique number combination
    let earliestEpisodesReduced = [];
    earliestEpisodes.forEach((episode) => {
      let selector = {
        episodeNumStart: episode.episodeNumStart,
        episodeNumEnd: episode.episodeNumEnd
      };

      if (earliestEpisodesReduced.hasPartialObjects(selector)) {
        let other = earliestEpisodesReduced.getPartialObjects(selector)[0];
        other.uploadDate = ScrapingHelpers.determineEarliestAiringDate(other.uploadDate, episode.uploadDate);
        earliestEpisodesReduced = earliestEpisodesReduced.replacePartialObjects(selector, other);
      }

      else {
        selector.uploadDate = episode.uploadDate;
        earliestEpisodesReduced.push(selector);
      }
    });

    /*************************************
     * Calculate the 'availableEpisodes' *
     *************************************/

    // Store on this
    this.availableEpisodes[translationType] = earliestEpisodes.empty() ? 0 : earliestEpisodes[0].episodeNumEnd;

    // Store in the database
    Shows.update(this._id, {
      $set: {
        availableEpisodes: this.availableEpisodes
      }
    });

    /***********************************
     * Calculate the 'lastEpisodeDate' *
     ***********************************/

    // Store on this
    this.lastEpisodeDate[translationType] = earliestEpisodes.empty() ? undefined : earliestEpisodes[0].uploadDate;

    // Store in the database
    Shows.update(this._id, {
      $set: {
        lastEpisodeDate: this.lastEpisodeDate
      }
    });

    /**************************************
     * Calculate the 'determinedInterval' *
     **************************************/

    // Store the average found here
    let average = undefined;

    // Continue if there is more than 1 distinct episode
    if (earliestEpisodesReduced.length >= 2) {

      // Sort episodes by upload date ascending
      let earliestEpisodesSorted = earliestEpisodes.slice(0).sort((a, b) => {
        return moment.fromUtc(a.uploadDate).diff(moment.fromUtc(b.uploadDate));
      });

      // Calculate delays between episodes and sort ascending
      let episodeDelays = [];
      for (let i = 1; i < earliestEpisodesSorted.length; i++) {
        episodeDelays.push(moment.fromUtc(earliestEpisodesSorted[i].uploadDate).diff(moment.fromUtc(earliestEpisodesSorted[i - 1].uploadDate)));
      }
      episodeDelays = episodeDelays.sort((a, b) => {
        return a - b;
      });

      // Spread delays among bins depending on their distance and sort by length descending
      let episodeDelayBins = [];
      episodeDelays.forEach((delay) => {
        if (!episodeDelayBins.empty() && delay - episodeDelayBins.peek().peek() < 86400000) {
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

      // Calculate the average
      average = Math.round(total / count);
    }

    // Store on this
    this.determinedInterval[translationType] = average;

    // Store in the database
    Shows.update(this._id, {
      $set: {
        determinedInterval: this.determinedInterval
      }
    });
  },

  mergePartialShow(other) {
    // Copy and merge attributes
    Object.keys(other).forEach((key) => {
      if ((typeof this[key] === 'undefined' && !Shows.systemKeys.includes(key))
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

    if (other._id && other._id !== this._id) {
      // Remove other from database
      Shows.remove(other._id);
      // Move episodes
      Episodes.moveEpisodes(other._id, this._id);
    }
  },

  attemptUpdate() {
    if (this.expired()) {
      // Mark update as started
      this.lastUpdateStart = moment.fromUtc().toDate();
      Shows.update(this._id, {
        $set: {
          lastUpdateStart: this.lastUpdateStart
        }
      });

      // Track found episodes
      let episodeIds = [];

      let tempShow = new TempShow(this, (show) => {

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

        // Delete missing fields
        Object.keys(this).forEach((key) => {
          if (!Shows.systemKeys.includes(key) && !Object.keys(show).includes(key)) {
            this[key] = undefined;
          }
        });

        // Replace existing fields
        Object.keys(show).forEach((key) => {
          this[key] = show[key];
        });

        // Update result
        this.lastUpdateEnd = moment.fromUtc().toDate();
        Schemas.Show.clean(this, {
          mutate: true
        });
        delete this.determinedInterval;
        delete this.lastEpisodeDate;
        delete this.availableEpisodes;

        Shows.update({
          _id: this._id,
          lastUpdateStart: this.lastUpdateStart
        }, {
          $set: this
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
        return Shows.addPartialShow(partial, episodes);

      }, (full) => {

        // Copy and merge attributes
        Object.keys(full).forEach((key) => {
          if ((typeof this[key] === 'undefined' && !Shows.systemKeys.includes(key))
            || (Shows.objectKeys.includes(key) && Object.countNonEmptyValues(full[key]) > Object.countNonEmptyValues(this[key]))) {
            this[key] = full[key];
          }
          else if (Shows.arrayKeys.includes(key)) {
            this[key] = this[key].concat(full[key]);
          }
        });

        // Update result
        Schemas.Show.clean(this, {
          mutate: true
        });
        delete this.determinedInterval;
        delete this.lastEpisodeDate;
        delete this.availableEpisodes;

        Shows.update({
          _id: this._id,
          lastUpdateStart: this.lastUpdateStart
        }, {
          $set: this
        });

      }, (episode) => {

        // Add found episodes
        episodeIds.push(Episodes.addEpisode(episode));

      }, false);

      tempShow.start();
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
Shows.querySearch = function(search, limit, translationType) {
  // Clean
  Schemas.Search.clean(search, {
    mutate: true
  });

  // Validate
  Schemas.Search.validate(search);
  new SimpleSchema({
    limit: {
      type: SimpleSchema.Integer
    },
    translationType: {
      type: String,
      allowedValues: Episodes.validTranslationTypes
    }
  }).validate({limit, translationType});

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

  // Search on 'season'
  if (typeof search.season !== 'undefined') {
    selector['season.quarter'] = search.season;
  }
  if (typeof search.year !== 'undefined') {
    selector['season.year'] = search.year;
  }

  // Search on 'query'
  if (search.query) {
    if (search.sortBy) {
      selector.altNames = {
        $regex: '.*' + RegExp.escape(search.query) + '.*',
        $options: 'i'
      };
    } else {
      selector.altNames = {
        $regex: '.*' + search.query.split('').map((character) => {
          return RegExp.escape(character);
        }).join('.*') + '.*',
        $options: 'i'
      };
    }
    selector.altNames.$regex = RegExp.makeMatchWS(selector.altNames.$regex);
  }

  // Sort if the limit exists
  if (options.hasOwnProperty('limit')) {
    if (search.sortBy === 'Latest Update') {
      options.sort = {};
      options.sort['lastEpisodeDate.' + translationType + '.year'] = search.sortDirection;
      options.sort['lastEpisodeDate.' + translationType + '.month'] = search.sortDirection;
      options.sort['lastEpisodeDate.' + translationType + '.date'] = search.sortDirection;
      options.sort['lastEpisodeDate.' + translationType + '.hour'] = search.sortDirection;
      options.sort['lastEpisodeDate.' + translationType + '.minute'] = search.sortDirection;
      options.sort['airedStart.year'] = search.sortDirection;
      options.sort['airedStart.month'] = search.sortDirection;
      options.sort['airedStart.date'] = search.sortDirection;
      options.sort['airedStart.hour'] = search.sortDirection;
      options.sort['airedStart.minute'] = search.sortDirection;
      if (search.sortDirection === 1) {
        selector.$or = [{
          airedStart: {
            $gt: {}
          }
        }, {}];
        selector.$or[1]['lastEpisodeDate.' + translationType] = {
          $gt: {}
        };
      }
    }

    else if (search.sortBy === 'Type') {
      options.sort = {
        type: search.sortDirection
      };
      if (search.sortDirection === 1 && !selector.type) {
        selector.type = {
          $exists: true
        };
      }
    }

    else if (search.query) {
      options.sort = function(a, b) {
        let scoreA = a.altNames.reduce((bestSore, altName) => {
          if (bestSore === 1) return 1;
          return Math.max(bestSore, score(altName.cleanQuery(), search.query, 0.1));
        }, 0);
        let scoreB = b.altNames.reduce((bestSore, altName) => {
          if (bestSore === 1) return 1;
          return Math.max(bestSore, score(altName.cleanQuery(), search.query, 0.1));
        }, 0);
        if (scoreA !== scoreB) {
          return scoreB - scoreA;
        } else {
          return a.name.localeCompare(b.name);
        }
      }
    }

    if (!search.query) {
      if (!options.sort) {
        options.sort = {};
      }
      options.sort.name = 1;
    }
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

Shows.queryForOverview = function(malIds, limit) {
  // Validate
  new SimpleSchema({
    malIds: Array,
    'malIds.$': SimpleSchema.Integer
  }).validate({malIds});
  new SimpleSchema({
    limit: {
      type: SimpleSchema.Integer,
      optional: true
    }
  }).validate({limit});

  // Sort and limit only on the client
  let options = {};
  if (Meteor.isClient) {
    options.limit = limit;
    options.sort = function(a, b) {
      a = Shows._transform(a);
      b = Shows._transform(b);

      let nextA = a.nextEpisodeDate(getStorageItem('SelectedTranslationType'));
      let nextB = b.nextEpisodeDate(getStorageItem('SelectedTranslationType'));
      let stateA = a.airingState(getStorageItem('SelectedTranslationType'));
      let stateB = b.airingState(getStorageItem('SelectedTranslationType'));
      let diff = undefined;

      // Move completed shows to the bottom
      if (stateA === 'Completed' && stateB === 'Completed') {
        diff = 0;
      }
      else if (stateA === 'Completed') {
        diff = 1;
      }
      else if (stateB === 'Completed') {
        diff = -1;
      }

      // Move shows with unknown next date to the top
      else if ((typeof nextA === 'undefined' || Object.keys(nextA).empty()) && (typeof nextB === 'undefined' || Object.keys(nextB).empty())) {
        diff = 0;
      }
      else if (typeof nextA === 'undefined' || Object.keys(nextA).empty()) {
        diff = -1;
      }
      else if (typeof nextB === 'undefined' || Object.keys(nextB).empty()) {
        diff = 1;
      }

      // Sort by next episode date
      else {
        diff = moment.fromUtc(nextA).diff(moment.fromUtc(nextB));
      }

      // Sort by episodes to watch
      if (diff === 0) {
        diff = (b.availableEpisodes[getStorageItem('SelectedTranslationType')] - b.watchedEpisodes()) - (a.availableEpisodes[getStorageItem('SelectedTranslationType')] - a.watchedEpisodes());
      }

      // Sort by start date
      if (diff === 0) {
        diff = moment.fromUtc(a.airedStart).diff(moment.fromUtc(b.airedStart));
      }

      // Sort by name
      if (diff === 0) {
        diff = a.name.localeCompare(b.name);
      }

      return diff;
    }
  }

  // Return results cursor
  return Shows.find({
    malId: {
      $in: malIds
    }
  }, options);
};
