import Cheerio from 'cheerio';
import { Shows } from '/imports/api/shows/shows.js';
import { myanimelist } from './_myanimelist';
import { kissanime } from './_kissanime';
import { nineanime } from './_nineanime';
import {Episodes} from "../api/episodes/episodes";

let streamers = [myanimelist, kissanime, nineanime];

export default class Streamers {
  static getStreamerById(id) {
    return streamers.find((streamer) => {
      return streamer.id === id;
    });
  }

  static getSimpleStreamerById(id) {
    let streamer = this.getStreamerById(id);
    if (streamer) {
      return {
        id: streamer.id,
        name: streamer.name,
        homepage: streamer.homepage
      };
    }
    return undefined;
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
      streamerUrl.streamerId = streamer.id;
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
        let showResult = this.processShowPage(html, streamer, logData);

        results = results.concat(showResult.partials.map((partial) => {
          return {
            partial: partial,
            episodes: []
          };
        }));

        if (showResult.full) {
          results.push({
            partial: showResult.full,
            episodes: showResult.episodes,
            fromShowPage: true
          });
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
                results.push({
                  partial: result,
                  episodes: []
                });
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

  static doSearch(query, doneCallback, resultCallback, streamersIdsExcluded=[]) {
    // Filter streamers
    let filteredStreamers = streamers.filter((streamer) => {
      return !streamersIdsExcluded.includes(streamer.id);
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
          resultCallback(result.partial, result.episodes, result.fromShowPage);
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

    this.altNames = oldShow.altNames;
    this.currentAltNameIndex = 0;
    this.searchWithCurrentAltLooping = false;
  }

  isStreamerDone(streamer) {
    return streamer.minimalPageTypes.every((minimalPageType) => {
      return this.streamerUrlsStarted.hasPartialObjects({
        streamerId: streamer.id,
        type: minimalPageType
      });
    });
  }

  areDownloadsDone() {
    return this.streamerUrlsStarted.every((streamerUrl) => {
      return this.streamerUrlsDone.hasPartialObjects({
        streamerId: streamerUrl.streamerId,
        type: streamerUrl.type
      });
    });
  }

  areStreamersOrAltsDone() {
    return this.currentAltNameIndex >= this.altNames.length || streamers.every((streamer) => {
      return this.isStreamerDone(streamer);
    });
  }

  getStreamerIdsDone() {
    return streamers.filter((streamer) => {
      return this.isStreamerDone(streamer);
    }).map((streamer) => {
      return streamer.id;
    });
  }

  start() {
    if (Meteor.isDevelopment) {
      console.log('Started creating full show with name: \'' + this.oldShow.name + '\'');
    }

    // Start processing all existing streamer urls
    this.processUnprocessedStreamerUrls(this.oldShow.streamerUrls);

    // Start the alt search loop
    if (!this.areStreamersOrAltsDone() && !this.searchWithCurrentAltLooping) {
      this.searchWithCurrentAlt();
    }
  }

  processUnprocessedStreamerUrls(streamerUrls) {
    streamerUrls.filter((streamerUrl) => {
      return !this.streamerUrlsStarted.hasPartialObjects({
        streamerId: streamerUrl.streamerId,
        type: streamerUrl.type
      });
    }).forEach((streamerUrl) => {
      this.processStreamerUrl(streamerUrl)
    });
  }

  processStreamerUrl(streamerUrl) {
    // Mark as started
    this.markAsStarted(streamerUrl);

    // Download and process show page
    Streamers.getShowResults(streamerUrl.url, Streamers.getStreamerById(streamerUrl.streamerId), this.oldShow.name, (result) => {
      this.processShowResult(result, streamerUrl);

      // Start the loop again if possible
      if (!this.areStreamersOrAltsDone() && !this.searchWithCurrentAltLooping) {
        this.searchWithCurrentAlt();
      }

      // When completely done
      if (this.areDownloadsDone() && this.areStreamersOrAltsDone()) {
        this.finish();
      }
    });
  }

  searchWithCurrentAlt() {
    this.searchWithCurrentAltLooping = true;

    // Search all the pending streamers with the current altName
    Streamers.doSearch(this.altNames[this.currentAltNameIndex], () => {

      // Increment alt index
      this.currentAltNameIndex++;

      // When all alts or streamers are done
      if (this.areStreamersOrAltsDone()) {
        this.searchWithCurrentAltLooping = false;
        // When we have nothing to do anymore
        if (this.areDownloadsDone()) {
          this.finish();
        }
      }

      // When some streamers are not done
      else {
        this.searchWithCurrentAlt();
      }

    }, (partial, episodes, fromShowPage) => {

      // If the partial matches this show
      if (partial.altNames.some((resultAltName) => {
          resultAltName = Shows.prepareAltForMatching(resultAltName);
          return this.altNames.some((thisAltName) => {
            return thisAltName.match(resultAltName);
          });
        })) {

        if (fromShowPage) {
          // Mark as started and process
          this.markAsStarted(partial.streamerUrls[0]);
          this.processShowResult({
            full: partial,
            partials: [],
            episodes: episodes
          }, partial.streamerUrls[0]);
        }

        else {
          // Process it's unprocessed streamer urls
          this.processUnprocessedStreamerUrls(partial.streamerUrls);
        }

      }

      // Otherwise store as partial
      else {
        this.partialCallback(partial, episodes);
      }

    }, this.getStreamerIdsDone());
  }

  markAsStarted(streamerUrl) {
    // Mark streamerUrl as started
    this.streamerUrlsStarted.push(streamerUrl);
  }

  processShowResult(result, streamerUrl) {
    // Get the streamer
    let streamer = Streamers.getStreamerById(streamerUrl.streamerId);

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

    // Store as partial show
    if (Shows.simpleSchema().newContext().validate(this.newShow)) {
      this.partialCallback(this.newShow);
    }

    // Mark streamerUrl as done
    this.streamerUrlsDone.push(streamerUrl);
  }

  finish() {
    if (!this.newShow.streamerUrls) {
      this.newShow.streamerUrls = [];
    }

    this.newShow.streamerUrls = this.newShow.streamerUrls.concat(this.streamerUrlsStarted.filter((streamerUrlStarted) => {
      return !this.newShow.streamerUrls.hasPartialObjects({
        streamerId: streamerUrlStarted.streamerId,
        type: streamerUrlStarted.type
      });
    }).map((streamerUrlStarted) => {
      streamerUrlStarted.lastDownloadFailed = true;
      return streamerUrlStarted;
    }));

    this.showCallback(this.newShow);

    if (Meteor.isDevelopment) {
      console.log('Done creating full show with name: \'' + this.oldShow.name + '\'');
    }
  }
}
