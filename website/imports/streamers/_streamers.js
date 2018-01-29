import Cheerio from 'cheerio';
import { Shows } from '/imports/api/shows/shows.js';
import { myanimelist } from './myanimelist';
import { kissanime } from './kissanime';
import { nineanime } from './nineanime';
import {Episodes} from "../api/episodes/episodes";

let streamers = [myanimelist, kissanime];

export default class Streamers {
  static getStreamerById(id) {
    for (let i = 0; i < streamers.length; i++) {
      if (streamers[i].id === id) {
        return streamers[i];
      }
    }
  }

  static getSimpleStreamerById(id) {
    for (let i = 0; i < streamers.length; i++) {
      if (streamers[i].id === id) {
        return {
          id: streamers[i].id,
          name: streamers[i].name,
          homepage: streamers[i].homepage
        };
      }
    }
  }

  static convertCheerioToShow(cheerio, streamer, type) {
    // Create empty show
    let show = {};

    // Get 'type'
    if (streamer[type].attributes.type) {
      show.type = streamer[type].attributes.type(cheerio);
      if (show.type && show.type.cleanWhitespace() === 'Music') {
        return false; // Reject music videos
      }
    }

    // Get 'streamerUrls'
    show.streamerUrls = streamer[type].attributes.streamerUrls(cheerio);
    show.streamerUrls = show.streamerUrls.map((streamerUrl) => {
      streamerUrl.streamer = streamer.id;
      return streamerUrl;
    });

    // Get 'name'
    show.name = streamer[type].attributes.name(cheerio);
    // Get 'altNames'
    if (streamer[type].attributes.altNames) {
      show.altNames = streamer[type].attributes.altNames(cheerio);
    } else {
      show.altNames = [];
    }
    show.altNames.push(show.name);

    // Get 'description'
    if (streamer[type].attributes.description) {
      show.description = streamer[type].attributes.description(cheerio);
    }
    // Get 'malId'
    if (streamer[type].attributes.malId) {
      show.malId = streamer[type].attributes.malId(cheerio);
    }

    // Clean and validate show
    Shows.simpleSchema().clean(show, {
      mutate: true
    });
    Shows.simpleSchema().validate(show);

    // Return the show
    return show;
  }

  static convertCheerioToEpisode(cheerio, streamer, type) {
    // Create empty episode
    let episode = {
      showId: 'undefined',
      streamerId: streamer.id
    };

    // Get 'episodeNum'
    episode.episodeNum = streamer[type].attributes.episodeNum(cheerio);

    // Get 'translationType'
    episode.translationType = streamer[type].attributes.translationType(cheerio);
    // Get 'sourceUrl'
    episode.sourceUrl = streamer[type].attributes.sourceUrl(cheerio);

    // Clean and validate episode
    Episodes.simpleSchema().clean(episode, {
      mutate: true
    });
    Episodes.simpleSchema().validate(episode);

    // Return the episode
    return episode;
  }

  static processSearchPage(html, streamer, logData) {
    let results = [];

    if (!html) {
      return results;
    }

    // Load page
    let page = Cheerio.load(html);
    if (streamer.isInvalidPage(page)) {
      throw 'Download Failed!';
    }

    try {
      // Check if we have a show page
      if (streamer.show.checkIfPage(page)) {
        let result = this.processShowPage(html, streamer, logData);
        results.concat(result.partials);
        if (result.full) {
          results.push(result.full);
        }
      }

      // Otherwise we have a search page
      else {
        // For each row of data
        page(streamer.search.rowSelector).each((index, element) => {
          try {
            if (index >= streamer.search.rowSkips) {
              // Create and add show
              let result = this.convertCheerioToShow(page(element), streamer, 'search');
              if (result) {
                results.push(result);
              }
            }
          }

          catch(err) {
            console.error('Failed to process search page with query: \'' + logData + '\' and streamer: \'' + streamer.id + '\'.');
            console.error('Failed to process row number ' + index + '.');
            console.error(err);
          }
        });
      }
    }

    catch(err) {
      console.error('Failed to process search page with query: \'' + logData + '\' and streamer: \'' + streamer.id + '\'.');
      console.error(err);
    }

    return results;
  }

  static processShowPage(html, streamer, logData) {
    let results = {
      full: false,
      partials: [],
      episodes: []
    };

    if (!html) {
      return results;
    }

    // Load page
    let page = Cheerio.load(html);
    if (streamer.isInvalidPage(page)) {
      throw 'Download Failed!';
    }

    try {
      // For each related show
      page(streamer.showRelated.rowSelector).each((index, element) => {
        try {
          if (!streamer.showRelated.rowIgnore(page(element))) {
            // Create and add related show
            let result = this.convertCheerioToShow(page(element), streamer, 'showRelated');
            if (result) {
              results.partials.push(result);
            }
          }
        }

        catch(err) {
          console.error('Failed to process show page for show: \'' + logData + '\' and streamer: \'' + streamer.id + '\'.');
          console.error('Failed to process related row number ' + index + '.');
          console.error(err);
        }
      });

      // For each episode
      page(streamer.showEpisodes.rowSelector).each((index, element) => {
        try {
          if (index >= streamer.showEpisodes.rowSkips) {
            // Create and add episode
            let result = this.convertCheerioToEpisode(page(element), streamer, 'showEpisodes');
            if (result) {
              results.episodes.push(result);
            }
          }
        }

        catch(err) {
          console.error('Failed to process show page for show: \'' + logData + '\' and streamer: \'' + streamer.id + '\'.');
          console.error('Failed to process episode row number ' + index + '.');
          console.error(err);
        }
      });

      // Fix episode numbers if required
      if (streamer.showEpisodes.cannotCount && !results.episodes.empty()) {
        let episodeCorrection = results.episodes.reduce((total, episode) => {
          return Math.min(total, episode.episodeNum); // TODO: Fix for strings
        }, Infinity) - 1;
        results.episodes = results.episodes.map((episode) => {
          episode.episodeNum -= episodeCorrection;
          return episode;
        });
      }

      // Create and store show
      let result = this.convertCheerioToShow(page('html'), streamer, 'show');
      if (result) {
        results.full = result;
      }
    }

    catch(err) {
      console.error('Failed to process show page for show: \'' + logData + '\' and streamer: \'' + streamer.id + '\'.');
      console.error(err);
    }

    return results;
  }

  static getSearchResults(url, streamer, logData, resultCallback) {
    downloadWithCallback(url, (html) => {
      resultCallback(this.processSearchPage(html, streamer, logData));
    });
  }

  static getShowResults(url, streamer, logData, resultCallback) {
    downloadWithCallback(url, (html) => {
      resultCallback(this.processShowPage(html, streamer, logData));
    });
  }

  static getEpisodeResults(url, streamer, logData, resultCallback) {
    if (!streamer.episode.requiresDownload) {
      resultCallback(streamer.episode.getSources(url).map((source) => {
        if (url.startsWith('http://')) {
          source.flags.push('mixed-content');
        }
        return source;
      }));
    } else {
      // TODO: Implement this when needed
      console.error('Scraping episode pages has not been implemented yet!');
    }
  }

  static doSearch(query, resultCallback, doneCallback, streamersExcluded=[]) {
    // Filter streamers
    let filteredStreamers = streamers.filter((streamer) => {
      return !streamersExcluded.includes(streamer.id);
    });

    // Stop if none remain
    if (filteredStreamers.empty()) {
      doneCallback();
      return;
    }

    // Variables
    let streamersDone = 0;

    // For each not excluded streamer
    filteredStreamers.forEach((streamer) => {
      // Download and process search results
      this.getSearchResults(streamer.search.createUrl(query), streamer, query, (results) => {

        // Return results
        results.forEach((result) => {
          resultCallback(result);
        });

        // Check if done
        streamersDone++;
        if (streamersDone === filteredStreamers.length) {
          doneCallback();
        }

      });
    });
  }

  static createFullShow(oldShow, showCallback, partialCallback, episodeCallback) {
    let tempShow = new TempShow(oldShow, showCallback, partialCallback, episodeCallback);
    tempShow.start();
  }
}

class TempShow {
  constructor(oldShow, showCallback, partialCallback, episodeCallback) {
    this.showCallback = showCallback;
    this.partialCallback = partialCallback;
    this.episodeCallback = episodeCallback;

    this.oldShow = oldShow;
    this.newShow = {};

    this.streamerUrlsStarted = [];
    this.streamerUrlsDone = [];
    this.streamersHandled = [];

    this.altNames = oldShow.altNames;
    this.currentAltNameIndex = 0;
    this.searchWithNextAltLooping = false;
  }

  getDownloadsDone() {
    return this.streamerUrlsStarted.every((streamerUrl) => {
      return this.streamerUrlsDone.hasPartialObjects({
        streamer: streamerUrl.streamer,
        type: streamerUrl.type
      });
    });
  }

  getStreamersOrAltsDone() {
    return this.streamersHandled.length >= streamers.length || this.currentAltNameIndex >= this.altNames.length;
  }

  start() {
    // Process all existing streamer urls
    this.oldShow.streamerUrls.forEach((streamerUrl) => {
      this.processStreamerUrl(streamerUrl);
    });

    // Start the alt search loop
    if (!this.getStreamersOrAltsDone() && !this.searchWithNextAltLooping) {
      this.searchWithNextAlt();
    }
  }

  processUnprocessedStreamerUrls(streamerUrls) {
    streamerUrls.filter((streamerUrl) => {
      return !this.streamerUrlsStarted.hasPartialObjects({
        streamer: streamerUrl.streamer,
        type: streamerUrl.type
      });
    }).forEach((streamerUrl) => {
      this.processStreamerUrl(streamerUrl)
    });
  }

  processStreamerUrl(streamerUrl) {
    // Get the streamer
    let streamer = Streamers.getStreamerById(streamerUrl.streamer);

    // Mark streamerUrl as started
    this.streamerUrlsStarted.push(streamerUrl);

    // Mark streamer as handled
    if (!this.streamersHandled.includes(streamer.id)) {
      this.streamersHandled.push(streamer.id);
    }

    // Download and process show page
    Streamers.getShowResults(streamerUrl.url, streamer, this.oldShow.name, (result) => {

      if (result.full) {
        // Merge altNames into working set
        result.full.altNames.forEach((altName) => {
          if (!this.altNames.includes(altName)) {
            this.altNames.push(altName);
          }
        });

        // Merge result into the new show
        Object.keys(result.full).forEach((key) => {
          if (typeof this.newShow[key] === 'undefined') {
            this.newShow[key] = result.full[key];
          }
          else if (Shows.arrayKeys.includes(key)) {
            this.newShow[key] = this.newShow[key].concat(result.full[key]);
          }
          else if (streamer.id === 'myanimelist') {
            this.newShow[key] = result.full[key];
          }
        });
      }

      // Store partial results from show page
      result.partials.forEach((partial) => {
        this.partialCallback(partial);
      });

      // Handle episodes
      result.episodes.forEach((episode) => {
        episode.showId = this.oldShow._id;
        this.episodeCallback(episode);
      });

      // Process any new unprocessed streamer urls
      if (result.full) {
        this.processUnprocessedStreamerUrls(result.full.streamerUrls);
      }

      // Mark streamerUrl as done
      this.streamerUrlsDone.push(streamerUrl);

      // Start the loop again if possible
      if (!this.getStreamersOrAltsDone() && !this.searchWithNextAltLooping) {
        this.searchWithNextAlt();
      }

      // When completely done
      if (this.getDownloadsDone() && this.getStreamersOrAltsDone()) {
        this.finish();
      }

    });
  }

  searchWithNextAlt() {
    this.searchWithNextAltLooping = true;

    // Search all the pending streamers with the current altName
    Streamers.doSearch(this.altNames[this.currentAltNameIndex], (result) => {

      // If the result matches this show
      if (result.altNames.some((resultAltName) => {
          resultAltName = Shows.prepareAltForMatching(resultAltName);
          return this.altNames.some((thisAltName) => {
            return thisAltName.match(resultAltName);
          });
        })) {
        // Process it's unprocessed streamer urls
        this.processUnprocessedStreamerUrls(result.streamerUrls);
      }

      // Otherwise store as partial result
      else {
        this.partialCallback(result);
      }

    }, () => {

      // When all alts or streamers are done
      if (this.getStreamersOrAltsDone()) {
        this.searchWithNextAltLooping = false;
        // When we have nothing to do anymore
        if (this.getDownloadsDone()) {
          this.finish();
        }
      }

      // When some streamers are not done
      else {
        this.searchWithNextAlt();
      }

    }, this.streamersHandled);

    this.currentAltNameIndex++;
  }

  finish() {
    if (this.newShow.streamerUrls) {
      this.newShow.streamerUrls = this.newShow.streamerUrls.concat(this.streamerUrlsStarted);
    } else {
      this.newShow.streamerUrls = this.streamerUrlsStarted;
    }
    this.showCallback(this.newShow);
  }
}
