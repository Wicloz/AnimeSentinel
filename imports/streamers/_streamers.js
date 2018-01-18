import Cheerio from 'cheerio';
import { myanimelist } from './myanimelist';
import { kissanime } from './kissanime';

let streamers = [myanimelist, kissanime];

export default class Streamers {
  static doSearch(query, validator, callback) {
    // For each streamer
    streamers.forEach((streamer) => {
      // Download and load search results page
      downloadWithCallback(streamer.searchCreateUrl(query), (page) => {
        if (page) { // Skip failed downloads
          try {
            page = Cheerio.load(page);
            // For each row of data
            page(streamer.searchRowSelector).each((index, element) => {
              if (index >= streamer.searchRowSkips) { // Skip table headers if required
                try {
                  // Create empty result
                  let result = {
                    isSearchResult: true
                  };

                  // Get the url
                  result['streamerUrls'] = [{
                    id: streamer.id,
                    url: streamer.searchAttributeUrl(page(element).find(streamer.searchSelectorUrl)).replace(/\/+/g, '/')
                  }];

                  // Get 'name'
                  result['name'] = streamer.searchAttributeName(page(element).find(streamer.searchSelectorName));
                  // Get 'altNames'
                  if (streamer.searchSelectorAltNames) {
                    result['altNames'] = streamer.searchAttributeAltNames(page(element).find(streamer.searchSelectorAltNames));
                  } else {
                    result['altNames'] = [result['name']];
                  }

                  // Get 'description'
                  if (streamer.searchSelectorDescription) {
                    result['description'] = streamer.searchAttributeDescription(page(element).find(streamer.searchSelectorDescription));
                  }

                  // Clean and validate result
                  result = validator.clean(result);
                  validator.validate(result);

                  // Give result to callback
                  callback(result);
                }
                catch(err) {
                  console.error('Failed to process search page with query: \'' + query + '\' and streamer \'' + streamer.id + '\'.');
                  console.error('Failed to process row number ' + index + '.');
                  console.error(err);
                }
              }
            });
          }
          catch(err) {
            console.error('Failed to process search page with query: \'' + query + '\' and streamer \'' + streamer.id + '\'.');
            console.error(err);
          }
        }
      });
    });
  }
}
