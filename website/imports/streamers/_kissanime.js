import Cheerio from 'cheerio';
import ScrapingHelpers from "./scrapingHelpers";
import {Shows} from '../api/shows/shows';

function cleanName(name) {
  return name.replace(/ \(Dub\)$/, '').replace(/ \(Sub\)$/, '');
}

function getTypeFromName(name) {
  return name.endsWith(' (Dub)') ? 'dub' : 'sub';
}

function determineAiringDateShowPage(partial, index) {
  return ScrapingHelpers.buildAiringDateFromStandardStrings(
    undefined,
    index,
    partial.find('div.bigBarContainer div.barContent div:nth-of-type(2) p:has(span:contains("Date aired:"))').text().replace('Date aired:', ''),
    undefined,
    undefined,
    undefined
  );
}

function getEpisodeNumbers(string) {
  let numbers = {};

  let words = string.cleanWhitespace().split(' ');
  numbers.end = words.pop();
  numbers.start = numbers.end;

  if (isNumeric(numbers.end) && words.pop() === '-') {
    numbers.start = words.pop();
  }

  return numbers;
}

const validTypes = ['OVA', 'Movie', 'Special', 'ONA'];
const validGenres = ['Action', 'Adventure', 'Cars', 'Comedy', 'Dementia', 'Demons', 'Mystery', 'Drama', 'Ecchi',
  'Fantasy', 'Game', 'Historical', 'Horror', 'Kids', 'Magic', 'Martial Arts', 'Mecha', 'Music', 'Parody', 'Samurai',
  'Romance', 'School', 'Sci-Fi', 'Shoujo', 'Shoujo Ai', 'Shounen', 'Shounen Ai', 'Space', 'Sports', 'Super Power',
  'Vampire', 'Yaoi', 'Yuri', 'Harem', 'Slice of Life', 'Supernatural', 'Military', 'Police', 'Psychological',
  'Thriller', 'Seinen', 'Josei'];

export let kissanime = {
  // General data
  id: 'kissanime',
  name: 'KissAnime',
  homepage: 'http://kissanime.ru',
  minimalPageTypes: ['sub', 'dub'],

  // Search page data
  search: {
    createUrl: function(search) {
      if (search.query) {
        return kissanime.homepage + '/Search/Anime?keyword=' + encodeURIComponentReplaceSpaces(search.completeQuery(2, search.query), '+');
      }

      else if (search.getSingleType(validTypes)) {
        return kissanime.homepage + '/Genre/' + search.getSingleType(validTypes);
      }

      else if (search.getSingleGenre(validGenres)) {
        return kissanime.homepage + '/Genre/' + search.getSingleGenre(validGenres).replace(/\s/g, '-');
      }

      else {
        return kissanime.homepage + '/AnimeList';
      }
    },
    rowSelector: 'table.listing tbody tr:has(td)',

    // Search page attribute data
    attributes: {
      streamerUrls: function(partial, full) {
        return [{
          type: getTypeFromName(partial.find('td:first-of-type a').text()),
          url: kissanime.homepage + partial.find('td:first-of-type a').attr('href')
        }];
      },
      name: function(partial, full) {
        return cleanName(partial.find('td:first-of-type a').text());
      },
      description: function(partial, full) {
        return ScrapingHelpers.replaceDescriptionCutoff(Cheerio.load(partial.find('td:first-of-type').attr('title'))('div p').text(), /\s\.\.\.\n\s*/);
      },
    },

    // Search page thumbnail data
    thumbnails: {
      rowSelector: 'td:first-of-type',
      getUrl: function (partial, full) {
        return Cheerio.load(partial.attr('title'))('img').attr('src');
      },
    },
  },

  // Show page data
  show: {
    checkIfPage: function(page) {
      return page('title').text().cleanWhitespace().match(/^.* anime \| Watch .* anime online in high quality$/);
    },

    // Show page attribute data
    attributes: {
      streamerUrls: function(partial, full) {
        return [{
          type: getTypeFromName(partial.find('a.bigChar').text()),
          url: kissanime.homepage + partial.find('a.bigChar').attr('href')
        }];
      },
      name: function(partial, full) {
        return cleanName(partial.find('a.bigChar').text());
      },
      altNames: function(partial, full) {
        return partial.find('div.bigBarContainer div.barContent div:nth-of-type(2) p:has(span:contains("Other name:")) a').map((index, element) => {
          return partial.find(element).text();
        }).get();
      },
      description: function(partial, full) {
        return partial.find('div.bigBarContainer div.barContent div:nth-of-type(2) p:nth-last-of-type(2)').html();
      },
      type: function(partial, full) {
        let genres = partial.find('div.bigBarContainer div.barContent div:nth-of-type(2) p:has(span:contains("Genres:")) a').map((index, element) => {
          return partial.find(element).text();
        }).get();
        return Shows.validTypes.find((type) => {
          return genres.includes(type);
        });
      },
      genres: function(partial, full) {
        return partial.find('div.bigBarContainer div.barContent div:nth-of-type(2) p:has(span:contains("Genres:")) a').map((index, element) => {
          return partial.find(element).text();
        }).get().filter((genre) => {
          return !Shows.validTypes.includes(genre) && genre !== 'Dub' && genre !== 'Cartoon';
        });
      },
      airedStart: function(partial, full) {
        return determineAiringDateShowPage(partial, 0);
      },
      airedEnd: function(partial, full) {
        return determineAiringDateShowPage(partial, 1);
      },
    },

    // Show page thumbnail data
    thumbnails: {
      rowSelector: 'div#rightside div.barContent div[style="text-align: center"] img',
      getUrl: function (partial, full) {
        return partial.attr('src');
      },
    },
  },

  // Related shows data
  showRelated: {
    rowSelector: 'div#rightside div:nth-of-type(3) div.barContent div:nth-of-type(2) a:not([title])',

    // Related shows attribute data
    attributes: {
      streamerUrls: function(partial, full) {
        return [{
          type: getTypeFromName(partial.text()),
          url: kissanime.homepage + partial.attr('href')
        }];
      },
      name: function(partial, full) {
        return cleanName(partial.text());
      },
    },
  },

  // Episode list data
  showEpisodes: {
    rowSelector: 'table.listing tbody tr:has(td)',
    cannotCount: true,

    // Episode list attribute data
    attributes: {
      episodeNumStart: function(partial, full) {
        return getEpisodeNumbers(partial.find('td:first-of-type a').text()).start;
      },
      episodeNumEnd: function(partial, full) {
        return getEpisodeNumbers(partial.find('td:first-of-type a').text()).end;
      },
      translationType: function(partial, full) {
        return getTypeFromName(full.find('a.bigChar').text());
      },
      flags: function(partial, full) {
        return ['cloudflare', 'mixed-content'];
      },
      sources: function(partial, full) {
        let sourceUrl = kissanime.homepage + partial.find('td:first-of-type a').attr('href');
        let dateBits = partial.find('td:last-of-type').text().split('/');
        return [{
          sourceName: 'Openload',
          sourceUrl: sourceUrl + '&s=openload',
          uploadDate: {
            year: dateBits[2],
            month: dateBits[0] - 1,
            date: dateBits[1]
          }
        }, {
          sourceName: 'RapidVideo',
          sourceUrl: sourceUrl + '&s=rapidvideo',
          uploadDate: {
            year: dateBits[2],
            month: dateBits[0] - 1,
            date: dateBits[1]
          }
        }, {
          sourceName: 'Streamango',
          sourceUrl: sourceUrl + '&s=streamango',
          uploadDate: {
            year: dateBits[2],
            month: dateBits[0] - 1,
            date: dateBits[1]
          }
        }, {
          sourceName: 'Beta Server',
          sourceUrl: sourceUrl + '&s=beta',
          uploadDate: {
            year: dateBits[2],
            month: dateBits[0] - 1,
            date: dateBits[1]
          }
        }];
      },
    },
  },
};
