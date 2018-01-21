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
        if (streamer.showCheckIfPage(page)) {
          let result = this.processShowPage(html, streamer, logData);
          if (result) {
            results.push(result);
          }
        }

        // Otherwise we have a search page
        else {
          // For each row of data
          page(streamer.searchRowSelector).each((index, element) => {
            if (index >= streamer.searchRowSkips) { // Skip table headers if required
              try {
                // Create empty result
                let result = {};

                // Get the url
                result['streamerUrls'] = [{
                  id: streamer.id,
                  url: streamer.searchAttributeUrl(page(element).find(streamer.searchSelectorUrl)),
                  type: streamer.searchSelectorUrlType ? streamer.searchAttributeUrlType(page(element).find(streamer.searchSelectorUrlType)) : streamer.searchAttributeUrlType,
                }];

                // Get 'name'
                result['name'] = streamer.searchAttributeName(page(element).find(streamer.searchSelectorName));
                // Get 'altNames'
                if (streamer.searchSelectorAltNames) {
                  result['altNames'] = streamer.searchAttributeAltNames(page(element).find(streamer.searchSelectorAltNames));
                } else {
                  result['altNames'] = [];
                }
                result['altNames'].push(result['name']);

                // Get 'description'
                if (streamer.searchSelectorDescription) {
                  result['description'] = streamer.searchAttributeDescription(page(element).find(streamer.searchSelectorDescription));
                }

                // Clean and validate result
                result = Schemas.Show.clean(result);
                Schemas.Show.validate(result);

                // Add results to array
                results.push(result);
              }

              catch(err) {
                console.error('Failed to process search page with query: \'' + logData + '\' and streamer: \'' + streamer.id + '\'.');
                console.error('Failed to process row number ' + index + '.');
                console.error(err);
              }
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
    if (html) {
      try {
        // Load page
        let page = Cheerio.load(html);

        // Create empty result
        let result = {};

        // Get the url
        result['streamerUrls'] = [{
          id: streamer.id,
          url: streamer.showAttributeUrl(page(streamer.showSelectorUrl)),
          type: streamer.showSelectorUrlType ? streamer.showAttributeUrlType(page(streamer.showSelectorUrlType)) : streamer.searchAttributeUrlType,
        }];

        // Get 'name'
        result['name'] = streamer.showAttributeName(page(streamer.showSelectorName));
        // Get 'altNames'
        if (streamer.showSelectorAltNames) {
          result['altNames'] = streamer.showAttributeAltNames(page(streamer.showSelectorAltNames));
        } else {
          result['altNames'] = [];
        }
        result['altNames'].push(result['name']);

        // Get 'description'
        if (streamer.showSelectorDescription) {
          result['description'] = streamer.showAttributeDescription(page(streamer.showSelectorDescription));
        }

        // Clean and validate result
        result = Schemas.Show.clean(result);
        Schemas.Show.validate(result);

        // Return result
        return result;
      }

      catch(err) {
        console.error('Failed to process show page for show: \'' + logData + '\' and streamer: \'' + streamer.id + '\'.');
        console.error(err);
      }
    }

    return false;
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
      this.getSearchResults(streamer.searchCreateUrl(query), streamer, query, (results) => {

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
    let streamerUrlsDone = 0;
    let finalResult = {};

    // For each streamer
    streamers.forEach((streamer) => {

      // If the url is already known
      if (streamerUrls.hasPartialObjects({id: streamer.id})) {
        // For each different url
        streamerUrls.getPartialObjects({id: streamer.id}).forEach((streamerUrl) => {
          // Download and process show page
          this.getShowResults(streamerUrl.url, streamer, altNames[0], (result) => {

            // Merge altNames and streamerUrls into working set
            if (result.altNames) {
              result.altNames.forEach((altName) => {
                if (!altNames.includes(altName)) {
                  altNames.push(altName);
                }
              });
            }
            if (result.streamerUrls) {
              result.streamerUrls.forEach((streamerUrl) => {
                if (!streamerUrls.hasPartialObjects({id: streamerUrl.id, type: streamerUrl.type})) {
                  streamerUrls.push(streamerUrl);
                }
              });
            }

            // Merge show into final result
            Object.keys(result).forEach((key) => {
              if (Shows.arrayKeys.includes(key)) {
                if (typeof finalResult[key] === 'undefined') {
                  finalResult[key] = result[key];
                } else {
                  finalResult[key] = finalResult[key].concat(result[key]);
                }
              }
              else if (streamer.id === 'myanimelist' || typeof finalResult[key] === 'undefined') {
                finalResult[key] = result[key];
              }
            });

            // Check if done
            streamerUrlsDone++;
            if (streamerUrlsDone === streamerUrls.length) {
              resultCallback(finalResult);
            }

          });
        });
      }

    });
  }
}
