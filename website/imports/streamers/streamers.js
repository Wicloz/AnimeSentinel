import Cheerio from 'cheerio';
import { Shows } from '/imports/api/shows/shows.js';
import { myanimelist } from './_myanimelist';
import { kissanime } from './_kissanime';
import { nineanime } from './_nineanime';
import {Episodes} from "../api/episodes/episodes";

let streamers = [myanimelist, kissanime, nineanime];

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

  static convertCheerioToShow(cheerioRow, cheerioPage, streamer, type) {
    // Create empty show
    let show = {};

    // Get 'type'
    if (streamer[type].attributes.type) {
      show.type = streamer[type].attributes.type(cheerioRow, cheerioPage);
      if (show.type && show.type.cleanWhitespace() === 'Music') {
        return false; // Reject music videos
      }
    }

    // Get 'streamerUrls'
    show.streamerUrls = streamer[type].attributes.streamerUrls(cheerioRow, cheerioPage);
    show.streamerUrls = show.streamerUrls.map((streamerUrl) => {
      streamerUrl.streamer = streamer.id;
      return streamerUrl;
    });

    // Get 'name'
    show.name = streamer[type].attributes.name(cheerioRow, cheerioPage);
    // Get 'altNames'
    if (streamer[type].attributes.altNames) {
      show.altNames = streamer[type].attributes.altNames(cheerioRow, cheerioPage);
    } else {
      show.altNames = [];
    }
    show.altNames.push(show.name);

    // Get 'description'
    if (streamer[type].attributes.description) {
      show.description = streamer[type].attributes.description(cheerioRow, cheerioPage);
    }
    // Get 'malId'
    if (streamer[type].attributes.malId) {
      show.malId = streamer[type].attributes.malId(cheerioRow, cheerioPage);
    }

    // Clean and validate show
    Shows.simpleSchema().clean(show, {
      mutate: true
    });
    Shows.simpleSchema().validate(show);

    // Return the show
    return show;
  }

  static convertCheerioToEpisode(cheerioRow, cheerioPage, streamer, type) {
    // Create empty episode
    let episode = {
      showId: 'undefined',
      streamerId: streamer.id
    };

    // Get 'episodeNumStart'
    episode.episodeNumStart = streamer[type].attributes.episodeNumStart(cheerioRow, cheerioPage);
    if (episode.episodeNumStart && !isNumeric(episode.episodeNumStart)) {
      episode.episodeNumStart = 1;
    }
    // Get 'episodeNumEnd'
    episode.episodeNumEnd = streamer[type].attributes.episodeNumEnd(cheerioRow, cheerioPage);
    if (episode.episodeNumEnd && !isNumeric(episode.episodeNumEnd)) {
      episode.episodeNumEnd = 1;
    }
    // Get 'sourceUrl'
    episode.sourceUrl = streamer[type].attributes.sourceUrl(cheerioRow, cheerioPage);

    // Get 'translationType'
    episode.translationType = streamer[type].attributes.translationType(cheerioRow, cheerioPage);

    // Get 'sources'
    if (streamer[type].attributes.sources) {
      episode.sources = streamer[type].attributes.sources(cheerioRow, cheerioPage);
    }

    // Clean and validate episode
    Episodes.simpleSchema().clean(episode, {
      mutate: true
    });
    Episodes.simpleSchema().validate(episode);

    // Return the episode
    return episode;
  }

  static processSearchPage(html, streamer, logData) {
    let results = {
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
      // Check if we have a show page
      if (streamer.show.checkIfPage(page)) {
        let result = this.processShowPage(html, streamer, logData);
        results.episodes = results.episodes.concat(result.episodes);
        results.partials = results.partials.concat(result.partials);
        if (result.full) {
          result.full.fromShowPage = true;
          results.partials.push(result.full);
        }
      }

      // Otherwise we have a search page
      else {
        // For each row of data
        page(streamer.search.rowSelector).each((index, element) => {
          try {
            if (index >= streamer.search.rowSkips) {
              // Create and add show
              let result = this.convertCheerioToShow(page(element), page('html'), streamer, 'search');
              if (result) {
                results.partials.push(result);
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
      // Create and store show
      let result = this.convertCheerioToShow(page('html'), page('html'), streamer, 'show');
      if (result) {
        results.full = result;
      }

      // For each related show
      page(streamer.showRelated.rowSelector).each((index, element) => {
        try {
          if (!streamer.showRelated.rowIgnore(page(element))) {
            // Create and add related show
            let result = this.convertCheerioToShow(page(element), page('html'), streamer, 'showRelated');
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
            let result = this.convertCheerioToEpisode(page(element), page('html'), streamer, 'showEpisodes');
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
          return Math.min(total, episode.episodeNumStart);
        }, Infinity) - 1;
        results.episodes = results.episodes.map((episode) => {
          episode.episodeNumStart -= episodeCorrection;
          episode.episodeNumEnd -= episodeCorrection;
          return episode;
        });
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
    if (streamer.episode) {
      // TODO: Implement this when needed
      console.error('Scraping episode pages has not been implemented yet!');
    } else {
      resultCallback([]);
    }
  }

  static doSearch(query, doneCallback, partialCallback, episodeCallback, streamersExcluded=[]) {
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

        // Return partials
        results.partials.forEach((partial) => {
          partialCallback(partial);
        });

        // Return episodes
        results.episodes.forEach((episode) => {
          episodeCallback(episode);
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
    Streamers.doSearch(this.altNames[this.currentAltNameIndex], () => {

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

    }, (result) => {

      // If the result matches this show
      if (result.altNames.some((resultAltName) => {
          resultAltName = Shows.prepareAltForMatching(resultAltName);
          return this.altNames.some((thisAltName) => {
            return thisAltName.match(resultAltName);
          });
        })) {
        // Process it's unprocessed streamer urls
        this.processUnprocessedStreamerUrls(result.streamerUrls);
        // TODO: check for full shows
      }

      // Otherwise store as partial result
      else {
        this.partialCallback(result);
      }

    }, (episode) => {

      // TODO: Do something with episodes here

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
