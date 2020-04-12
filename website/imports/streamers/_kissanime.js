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
    partial.find('div.bigBarContainer div.barContent div:nth-of-type(2) p:has(span:contains("Date aired:"))').text().replace('Date aired:', '').split(' to '),
    undefined,
    undefined,
    undefined
  );
}

function getEpisodeData(episodeString, showString) {
  // Object for return values
  let infoObject = {
    start: false,
    end: false
  };

  // Remove the show title and turn into a cleaned array
  let episodeArray = episodeString.replace(showString, '').cleanWhitespace().split(' ');
  if (episodeArray[0] === 'Episode' || episodeArray[0] === '_Episode') {
    episodeArray.shift();
  }

  // Stop if it still starts with an underscore
  if (episodeArray[0] && episodeArray[0].startsWith('_')) {
    return false;
  }

  // Try to extract episode numbers
  if (isNumeric(episodeArray[0])) {
    infoObject.start = episodeArray.shift();
    if (episodeArray[0] === '-' && isNumeric(episodeArray[1])) {
      episodeArray.shift();
      infoObject.end = episodeArray.shift();
    } else {
      infoObject.end = infoObject.start;
    }
  }

  // Clean and set the notes
  if (episodeArray[0] !== '-') {
    if (validTypes.includes(episodeArray[0])) {
      episodeArray.shift();
    }
    infoObject.notes = episodeArray.join(' ').replaceFull('[Censored]', '').replaceFull('[Uncensored]', 'Uncensored');
  }

  // Done
  return infoObject;
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
  homepage: 'https://kissanime.ru',
  recentPage: 'https://kissanime.ru',
  minimalPageTypes: ['sub', 'dub'],

  // Search page data
  search: {
    createUrl: function(search) {
      let sortPage = '';
      if (search.sortDirection === -1 && search.sortBy === 'Latest Update') {
        sortPage = '/LatestUpdate';
      }

      if (search.query) {
        return kissanime.homepage + '/Search/Anime?keyword=' + encodeURIComponentReplaceSpaces(search.completeQuery(2, search.query), '+');
      }

      else if (search.getSingleType(validTypes)) {
        return kissanime.homepage + '/Genre/' + search.getSingleType(validTypes) + sortPage;
      }

      else if (search.getSingleGenre(validGenres)) {
        return kissanime.homepage + '/Genre/' + search.getSingleGenre(validGenres).replace(/\s/g, '-') + sortPage;
      }

      else {
        return kissanime.homepage + '/AnimeList' + sortPage;
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
        return Cheerio.load(partial.attr('title'))('img').attr('src').ensureStart('https:');
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
      episodeCount: function(partial, full) {
        if (partial.find('div.bigBarContainer div.barContent div:nth-of-type(2) p:has(span:contains("Status:"))').text().includes('Completed')) {
          let link = partial.find('div#rightside div:nth-of-type(3) div.barContent div:nth-of-type(2) a:last-of-type');
          if (link.attr('href') && link.attr('href').count('/') === 3) {
            return link.text().split(' ').pop() - 1;
          }
        }
        return undefined;
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
        return getEpisodeData(partial.find('td:first-of-type a').text(), full.find('a.bigChar').text()).start;
      },
      episodeNumEnd: function(partial, full) {
        return getEpisodeData(partial.find('td:first-of-type a').text(), full.find('a.bigChar').text()).end;
      },
      notes: function(partial, full) {
        return getEpisodeData(partial.find('td:first-of-type a').text(), full.find('a.bigChar').text()).notes;
      },
      translationType: function(partial, full) {
        return getTypeFromName(full.find('a.bigChar').text());
      },
      sources: function(partial, full) {
        let sourceUrl = kissanime.homepage + partial.find('td:first-of-type a').attr('href');
        let dateBits = partial.find('td:last-of-type').text().split('/');
        let uploadDate = {
          year: dateBits[2],
          month: dateBits[0] - 1,
          date: dateBits[1]
        };
        return [{
          sourceName: 'RapidVideo',
          sourceUrl: sourceUrl + '&s=rapidvideo',
          uploadDate: uploadDate,
          flags: ['cloudflare']
        }, {
          sourceName: 'Mp4Upload',
          sourceUrl: sourceUrl + '&s=mp4upload',
          uploadDate: uploadDate,
          flags: ['cloudflare']
        }, {
          sourceName: 'Openload',
          sourceUrl: sourceUrl + '&s=openload',
          uploadDate: uploadDate,
          flags: ['cloudflare', 'requires-plugins']
        }, {
          sourceName: 'Streamango',
          sourceUrl: sourceUrl + '&s=streamango',
          uploadDate: uploadDate,
          flags: ['cloudflare', 'requires-plugins']
        }, {
          sourceName: 'P2P Server',
          sourceUrl: sourceUrl + '&s=p2p',
          uploadDate: uploadDate,
          flags: ['cloudflare']
        }, {
          sourceName: 'Nova Server',
          sourceUrl: sourceUrl + '&s=nova',
          uploadDate: uploadDate,
          flags: ['cloudflare']
        }, {
          sourceName: 'Beta Server',
          sourceUrl: sourceUrl + '&s=beta',
          uploadDate: uploadDate,
          flags: ['cloudflare']
        }, {
          sourceName: 'Beta2 Server',
          sourceUrl: sourceUrl + '&s=beta2',
          uploadDate: uploadDate,
          flags: ['cloudflare']
        }, {
          sourceName: 'HydraX',
          sourceUrl: sourceUrl + '&s=hydrax',
          uploadDate: uploadDate,
          flags: ['cloudflare']
        }];
      },
    },
  },

  // Recent page data
  recent: {
    rowSelector: 'div.bigBarContainer div.barContent div.scrollable div.items div a',

    // Recent episode attribute data
    attributes: {
      episodeNumStart: function(partial, full) {
        return getEpisodeData(partial.find('span').text(), partial.clone().children().remove().end().text()).start;
      },
      episodeNumEnd: function(partial, full) {
        return getEpisodeData(partial.find('span').text(), partial.clone().children().remove().end().text()).end;
      },
      notes: function(partial, full) {
        return getEpisodeData(partial.find('span').text(), partial.clone().children().remove().end().text()).notes;
      },
      translationType: function(partial, full) {
        return getTypeFromName(partial.clone().children().remove().end().text());
      },
    },
  },

  // Recent show data
  recentShow: {
    // Recent show attribute data
    attributes: {
      streamerUrls: function(partial, full) {
        return [{
          type: getTypeFromName(partial.clone().children().remove().end().text()),
          url: kissanime.homepage + '/' + partial.attr('href')
        }];
      },
      name: function(partial, full) {
        return cleanName(partial.clone().children().remove().end().text());
      },
    },

    // Recent show thumbnail data
    thumbnails: {
      rowSelector: 'img',
      getUrl: function (partial, full) {
        return partial.attr('src') || partial.attr('srctemp');
      },
    },
  },
};
