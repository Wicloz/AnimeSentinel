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

                // Get the urls
                result['streamerUrls'] = [];
                if (streamer.searchSelectorInformationUrl) {
                  result['streamerUrls'].push({
                    id: streamer.id,
                    hasShowInfo: true,
                    url: streamer.searchAttributeInformationUrl(page(element).find(streamer.searchSelectorInformationUrl)),
                  });
                }
                result['streamerUrls'].push({
                  id: streamer.id,
                  hasShowInfo: !streamer.searchSelectorInformationUrl,
                  hasEpisodeInfo: streamer.searchSelectorEpisodeUrlType ? streamer.searchAttributeEpisodeUrlType(page(element).find(streamer.searchSelectorEpisodeUrlType)) : streamer.searchAttributeEpisodeUrlType,
                  url: streamer.searchAttributeEpisodeUrl(page(element).find(streamer.searchSelectorEpisodeUrl)),
                });

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

        // Get the urls
        result['streamerUrls'] = [];
        if (streamer.showSelectorInformationUrl) {
          result['streamerUrls'].push({
            id: streamer.id,
            hasShowInfo: true,
            url: streamer.showAttributeInformationUrl(page(streamer.showSelectorInformationUrl)),
          });
        }
        result['streamerUrls'].push({
          id: streamer.id,
          hasShowInfo: !streamer.showSelectorInformationUrl,
          hasEpisodeInfo: streamer.showSelectorEpisodeUrlType ? streamer.showAttributeEpisodeUrlType(page(streamer.showSelectorEpisodeUrlType)) : streamer.showAttributeEpisodeUrlType,
          url: streamer.showAttributeEpisodeUrl(page(streamer.showSelectorEpisodeUrl)),
        });

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

          // Merge altNames into working set
          if (result.altNames) {
            result.altNames.forEach((altName) => {
              if (!altNames.includes(altName)) {
                altNames.push(altName);
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

          // Clean the working result
          finalResult = Schemas.Show.clean(finalResult);

          // Check if done
          streamerUrlsToDo--;
          if (streamerUrlsToDo === 0) {
            resultCallback(finalResult);
          }

        });
      });
    });
  }
}
