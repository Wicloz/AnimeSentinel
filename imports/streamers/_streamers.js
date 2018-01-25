import Cheerio from 'cheerio';
import { Shows } from '/imports/api/shows/shows.js';
import { myanimelist } from './myanimelist';
import { kissanime } from './kissanime';

let streamers = [myanimelist, kissanime];

export default class Streamers {
  static processSearchPage(html, streamer, logData) {
    let results = [];

    if (html) {
      try {
        // Load page
        let page = Cheerio.load(html);

        // Check if we have a show page
        if (streamer.show.checkIfPage(page)) {
          let result = this.processShowPage(html, streamer, logData);
          results.concat(result.partial);
          if (result.full) {
            results.push(result.full);
          }
        }

        // Otherwise we have a search page
        else {
          // For each row of data
          page(streamer.search.rowSelector).each((index, element) => {
            try {
              if (index >= streamer.search.rowSkips) { // Skip table headers if required
                // Create empty result
                let result = {};

                // Get the urls
                result['streamerUrls'] = [];
                if (streamer.search.attributes.informationUrl) {
                  result['streamerUrls'].push({
                    id: streamer.id,
                    hasShowInfo: true,
                    url: streamer.search.attributes.informationUrl(page(element)),
                  });
                }
                result['streamerUrls'].push({
                  id: streamer.id,
                  hasShowInfo: !streamer.search.attributes.informationUrl,
                  hasEpisodeInfo: streamer.search.attributes.episodeUrlType(page(element)),
                  url: streamer.search.attributes.episodeUrl(page(element)),
                });

                // Get 'name'
                result['name'] = streamer.search.attributes.name(page(element));
                // Get 'altNames'
                if (streamer.search.attributes.altNames) {
                  result['altNames'] = streamer.search.attributes.altNames(page(element));
                } else {
                  result['altNames'] = [];
                }
                result['altNames'].push(result['name']);

                // Get 'description'
                if (streamer.search.attributes.description) {
                  result['description'] = streamer.search.attributes.description(page(element));
                }
                // Get 'type'
                if (streamer.search.attributes.type) {
                  result['type'] = streamer.search.attributes.type(page(element));
                  if (result['type'] && result['type'].cleanWhitespace() === 'Music') {
                    return;
                  }
                }
                // Get 'malId'
                if (streamer.search.attributes.malId) {
                  result['malId'] = streamer.search.attributes.malId(page(element));
                }

                // Clean and validate result
                Shows.simpleSchema().clean(result, {
                  mutate: true
                });
                Shows.simpleSchema().validate(result);

                // Add results to array
                results.push(result);
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
    }

    return results;
  }

  static processShowPage(html, streamer, logData) {
    let results = {
      full: false,
      partial: []
    };

    if (html) {
      try {
        // Load page
        let page = Cheerio.load(html);

        // For each related show
        page(streamer.show.related.rowSelector).each((index, element) => {
          try {
            if (!streamer.show.related.rowIgnore(page(element))) {
              // Create empty result
              let result = {};

              // Get the urls
              result['streamerUrls'] = [];
              if (streamer.related.attributes.informationUrl) {
                result['streamerUrls'].push({
                  id: streamer.id,
                  hasShowInfo: true,
                  url: streamer.related.attributes.informationUrl(page(element)),
                });
              }
              result['streamerUrls'].push({
                id: streamer.id,
                hasShowInfo: !streamer.related.attributes.informationUrl,
                hasEpisodeInfo: streamer.related.attributes.episodeUrlType(page(element)),
                url: streamer.related.attributes.episodeUrl(page(element)),
              });

              // Get 'name'
              result['name'] = streamer.related.attributes.name(page(element));
              // Get 'altNames'
              result['altNames'] = [result['name']];
              // Get 'malId'
              if (streamer.related.attributes.malId) {
                result['malId'] = streamer.related.attributes.malId(page(element));
              }

              // Clean and validate result
              Shows.simpleSchema().clean(result, {
                mutate: true
              });
              Shows.simpleSchema().validate(result);

              // Add results to array
              results.partial.push(result);
            }
          }

          catch(err) {
            console.error('Failed to process show page for show: \'' + logData + '\' and streamer: \'' + streamer.id + '\'.');
            console.error('Failed to process related row number ' + index + '.');
            console.error(err);
          }
        });

        // Create empty result
        let result = {};

        // Get the urls
        result['streamerUrls'] = [];
        if (streamer.show.attributes.informationUrl) {
          result['streamerUrls'].push({
            id: streamer.id,
            hasShowInfo: true,
            url: streamer.show.attributes.informationUrl(page('body')),
          });
        }
        result['streamerUrls'].push({
          id: streamer.id,
          hasShowInfo: !streamer.show.attributes.informationUrl,
          hasEpisodeInfo: streamer.show.attributes.episodeUrlType(page('body')),
          url: streamer.show.attributes.episodeUrl(page('body')),
        });

        // Get 'name'
        result['name'] = streamer.show.attributes.name(page('body'));
        // Get 'altNames'
        if (streamer.show.attributes.altNames) {
          result['altNames'] = streamer.show.attributes.altNames(page('body'));
        } else {
          result['altNames'] = [];
        }
        result['altNames'].push(result['name']);

        // Get 'description'
        if (streamer.show.attributes.description) {
          result['description'] = streamer.show.attributes.description(page('body'));
        }
        // Get 'type'
        if (streamer.show.attributes.type) {
          result['type'] = streamer.show.attributes.type(page('body'));
          if (result['type'] && result['type'].cleanWhitespace() === 'Music') {
            return results;
          }
        }
        // Get 'malId'
        if (streamer.show.attributes.malId) {
          result['malId'] = streamer.show.attributes.malId(page('body'));
        }

        // Clean and validate result
        Shows.simpleSchema().clean(result, {
          mutate: true
        });
        Shows.simpleSchema().validate(result);

        // Store result
        results.full = result;
      }

      catch(err) {
        console.error('Failed to process show page for show: \'' + logData + '\' and streamer: \'' + streamer.id + '\'.');
        console.error(err);
      }
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

  static doSearch(query, resultCallback, doneCallback) {
    let streamersDone = 0;

    // For each streamer
    streamers.forEach((streamer) => {
      // Download and process search results
      this.getSearchResults(streamer.search.createUrl(query), streamer, query, (results) => {

        // Return results
        results.forEach((result) => {
          resultCallback(result);
        });

        // Check if done
        streamersDone++;
        if (streamersDone === streamers.length) {
          doneCallback();
        }

      });
    });
  }

  static createFullShow(altNames, streamerUrls, resultCallback, partialCallback) {
    let streamerUrlsToDo = streamerUrls.getPartialObjects({hasShowInfo: true}).length;
    let finalResult = {
      streamerUrls: streamerUrls
    };

    // For each streamer for which the show info url is known
    streamers.filter((streamer) => {
      return streamerUrls.hasPartialObjects({id: streamer.id, hasShowInfo: true});
    }).forEach((streamer) => {
      // For each show info url
      streamerUrls.getPartialObjects({id: streamer.id, hasShowInfo: true}).forEach((streamerUrl) => {
        // Download and process show page
        this.getShowResults(streamerUrl.url, streamer, altNames[0], (result) => {

          if (result.full) {
            // Merge altNames into working set
            if (result.full.altNames) {
              result.full.altNames.forEach((altName) => {
                if (!altNames.includes(altName)) {
                  altNames.push(altName);
                }
              });
            }

            // Merge show into final result
            Object.keys(result.full).forEach((key) => {
              if (Shows.arrayKeys.includes(key)) {
                if (typeof finalResult[key] === 'undefined') {
                  finalResult[key] = result.full[key];
                } else {
                  finalResult[key] = finalResult[key].concat(result.full[key]);
                }
              }
              else if (streamer.id === 'myanimelist' || typeof finalResult[key] === 'undefined') {
                finalResult[key] = result.full[key];
              }
            });

            // Clean the working result
            Shows.simpleSchema().clean(finalResult, {
              mutate: true
            });
          }

          // Check if done
          streamerUrlsToDo--;
          if (streamerUrlsToDo === 0) {
            resultCallback(finalResult);
          }

          // Store partial results from show page
          result.partial.forEach((partial) => {
            partialCallback(partial);
          })

        });
      });
    });
  }
}
