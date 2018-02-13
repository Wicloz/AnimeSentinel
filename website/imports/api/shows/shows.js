import Cheerio from 'cheerio';
import SimpleSchema from 'simpl-schema';
import Streamers from "../../streamers/streamers";
import {Episodes} from "../episodes/episodes";
import {Thumbnails} from '../thumbnails/thumbnails';
import ScrapingHelpers from '../../streamers/scrapingHelpers';
import moment from 'moment-timezone';

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
    index: 'text',
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
    if (this.thumbnails && !this.thumbnails.empty()) {
      let urls = Thumbnails.queryWithHashes(this.thumbnails).fetch().filterMap((thumbnail) => {
        return thumbnail.url({store: Session.get('FeatureSupportWebP') ? 'thumbnailsWEBP' : 'thumbnailsJPEG'});
      });
      if (!urls.empty()) {
        return urls;
      }
    }
    return ['/media/unknown.gif'];
  },

  expired() {
    let now = moment();
    return (!this.locked() && (!this.lastUpdateStart || moment(this.lastUpdateEnd).add(Shows.timeUntilRecache) < now)) ||
            (this.locked() && moment(this.lastUpdateStart).add(Shows.maxUpdateTime) < now);
  },

  locked() {
    return this.lastUpdateStart && (!this.lastUpdateEnd || this.lastUpdateStart > this.lastUpdateEnd);
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
        let others = ScrapingHelpers.queryMatchingShows(Shows, this);
        others.forEach((other) => {
          if (other._id !== this._id) {
            this.mergePartialShow(other);
          }
        })

      }, (partial, episodes) => {

        // Insert any partial results found in the process
        Shows.addPartialShow(partial, episodes);

      }, (full) => {

        // Replace existing fields
        Object.keys(full).forEach((key) => {
          this[key] = full[key];
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
  let others = ScrapingHelpers.queryMatchingShows(Shows, show);

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
Shows.querySearch = function(search, limit) { // TODO: Improve searching
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
  let options = {
    limit: limit
  };

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
    if (Meteor.isServer) {
      selector.$text = {
        $search: '"' + search.query.replace(/\s-([^\s])/g, ' $1') + '"',
        $language: 'english',
      };
      options.fields = {
        score: {
          $meta: 'textScore'
        }
      };
    }
    options.sort = {
      textScore: -1
    };
  }

  else {
    options.sort = {
      name: 1
    };
  }

  // Return results cursor
  return Shows.find(selector, options);
};
