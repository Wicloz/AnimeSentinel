import {Shows} from '../api/shows/shows';
import ScrapingHelpers from './scrapingHelpers';

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
    partial.find('div.info div.row dl:first-of-type dt:contains("Date aired:")').next().text().split(' to '),
    undefined,
    partial.find('div.info div.row dl:last-of-type dt:contains("Premiered:")').next().find('a').text(),
    undefined
  );
}

function getEpisodeData(episodeString) {
  let episodeBits = episodeString.split('-').map((episodeBit) => {
    return episodeBit.cleanWhitespace();
  });

  if ((!isNumeric(episodeBits[0]) && episodeBits[0] !== 'Full') || episodeBits.peek() === 'Preview') {
    return false;
  }

  let data = {
    start: episodeBits[0]
  };

  let lastNumber = 0;
  if (isNumeric(episodeBits[1])) {
    lastNumber = 1;
  }

  data.end = episodeBits[lastNumber];
  data.notes = episodeBits[lastNumber + 1] ? episodeBits[lastNumber + 1].replaceFull('Uncen', 'Uncensored') : undefined;

  return data;
}

const validTypes = ['TV', 'OVA', 'Movie', 'Special', 'ONA'];
const validGenres = ['Action', 'Adventure', 'Cars', 'Comedy', 'Dementia', 'Demons', 'Drama', 'Ecchi', 'Fantasy', 'Game',
  'Harem', 'Hentai', 'Historical', 'Horror', 'Josei', 'Kids', 'Magic', 'Martial Arts', 'Mecha', 'Military', 'Music', 'Mystery',
  'Parody', 'Police', 'Psychological', 'Romance', 'Samurai', 'School', 'Sci-Fi', 'Seinen', 'Shoujo', 'Shoujo Ai',
  'Shounen', 'Shounen Ai', 'Slice of Life', 'Space', 'Sports', 'Super Power', 'Supernatural', 'Thriller', 'Vampire',
  'Yaoi', 'Yuri'];

const posterAttributes = {
  streamerUrls: function(partial, full) {
    let type = getTypeFromName(partial.find('a.name').text());
    let url = partial.find('a.name').attr('href');
    return [{
      type: type,
      url: url
    }, {
      type: 'episodes-' + type,
      url: 'https://www1.9anime.to/ajax/film/servers/' + url.split('.').peek()
    }];
  },
  name: function(partial, full) {
    return cleanName(partial.find('a.name').text());
  },
  type: function(partial, full) {
    let found = undefined;
    partial.find('a.poster div.status div').each((index, element) => {
      if (!found && Shows.validTypes.includes(partial.find(element).text())) {
        found = partial.find(element).text();
      }
    });
    return found;
  },
  episodeCount: function(partial, full) {
    return partial.find('a.poster div.status div.ep').text().split('/')[1];
  },
};

const posterThumbnails = {
  rowSelector: 'img',
  getUrl: function (partial, full) {
    return partial.attr('src');
  },
};

export let nineanime = {
  // General data
  id: 'nineanime',
  name: '9anime',
  homepage: 'https://9anime.to',
  recentPage: 'https://9anime.to/updated',
  minimalPageTypes: ['sub', 'dub', 'episodes-sub', 'episodes-dub'],

  // Search page data
  search: {
    createUrl: function(search) {
      if (search.query) {
        return nineanime.homepage + '/search?keyword=' + encodeURIComponentReplaceSpaces(search.query, '+');
      }

      let types = search.getTypesAsIncludes(validTypes);
      if (types) {
        types = types.map((type) => {
          return '&type[]=' + type.toLowerCase().replace('tv', 'series');
        }).join('');
      } else {
        types = '';
      }

      let genres = search.getGenresAsIncludes(validGenres);
      if (genres) {
        genres = genres.map((genre) => {
          return '&genre[]=' + (validGenres.indexOf(genre) + 1);
        }).join('');
      } else {
        genres = '';
      }

      let year = '';
      if (search.year) {
        year = '&release[]=' + search.year;
      }

      let season = '';
      if (search.season) {
        season = '&season[]=' + search.season;
      }

      let sort = '';
      let sortDirection = search.sortDirection === 1 ? 'asc' : 'desc';
      switch(search.sortBy) {
        case 'Latest Update':
          sort = 'sort=episode_last_added_at%3A' + sortDirection;
          break;
        default:
          sort = 'sort=title%3Aasc';
          break;
      }

      return nineanime.homepage + '/filter?' + sort + types + genres + year + season;
    },
    rowSelector: 'div.film-list div.item',

    // Search page attribute data
    attributes: posterAttributes,

    // Search page thumbnail data
    thumbnails: posterThumbnails,
  },

  // Show page data
  show: {
    checkIfPage: function (page) {
      return page('title').text().cleanWhitespace().match(/^Watch .* on 9anime.to$/);
    },

    // Show page attribute data
    attributes: {
      streamerUrls: function(partial, full) {
        let url = partial.find('head link').attr('href');
        if (url) {
          let type = getTypeFromName(partial.find('div.widget.player div.widget-title h1.title').text());
          return [{
            type: type,
            url: url
          }, {
            type: 'episodes-' + type,
            url: 'https://www1.9anime.to/ajax/film/servers/' + url.split('.').peek()
          }];
        }
        return [];
      },
      name: function(partial, full) {
        return cleanName(partial.find('div.widget.player div.widget-title h1.title').text());
      },
      altNames: function(partial, full) {
        return partial.find('div.info div.head div.c1 p.alias').text().split('; ');
      },
      description: function(partial, full) {
        return partial.find('div.info div.desc').text()
      },
      type: function(partial, full) {
        return partial.find('div.info div.row dl:first-of-type dt:contains("Type:")').next().text().split(' ')[0];
      },
      genres: function(partial, full) {
        return partial.find('div.info div.row dl:first-of-type dt:contains("Genre:")').next().find('a').map((index, element) => {
          return partial.find(element).text();
        }).get().filter((genre) => {
          return genre !== 'add some';
        });
      },
      airedStart: function(partial, full) {
        return determineAiringDateShowPage(partial, 0);
      },
      airedEnd: function(partial, full) {
        return determineAiringDateShowPage(partial, 1);
      },
      season: function(partial, full) {
        let bits = partial.find('div.info div.row dl:last-of-type dt:contains("Premiered:")').next().find('a').text().split(' ');
        if (bits.length === 2) {
          return {
            quarter: bits[0],
            year: bits[1]
          };
        }
        return undefined;
      },
      episodeCount: function(partial, full) {
        if (partial.find('div.info div.row dl:first-of-type dt:contains("Status:")').next().text() === 'Completed') {
          let maxEpisode = 1;
          partial.find('div.widget.servers div.widget-body div.server ul li a').each((index, element) => {
            let episodeString = partial.find(element).text();
            if (isNumeric(episodeString)) {
              maxEpisode = Math.max(maxEpisode, episodeString);
            }
          });
          return maxEpisode;
        }
        return undefined;
      },
      episodeDuration: function(partial, full) {
        let base = partial.find('div.info div.row dl:last-of-type dt:contains("Duration:")').next().text().split(' ')[0];
        if (isNumeric(base)) {
          return base * 60000;
        } else {
          return undefined;
        }
      },
    },

    // Show page thumbnail data
    thumbnails: {
      rowSelector: 'div.info div.row div.thumb img',
      getUrl: function (partial, full) {
        return partial.attr('src');
      },
    },
  },

  // Related shows data
  showRelated: {
    rowSelector: 'div.widget.simple-film-list div.widget-body div.item, div.list-film div.item',

    // Related shows attribute data
    attributes: posterAttributes,

    // Related shows thumbnail data
    thumbnails: posterThumbnails,
  },

  // Episode list data
  showEpisodes: {
    rowSelector: 'div.widget.servers div.widget-body div.server.active ul li a',
    cannotCount: false,

    // Episode list attribute data
    attributes: {
      episodeNumStart: function(partial, full) {
        return getEpisodeData(partial.text()).start;
      },
      episodeNumEnd: function(partial, full) {
        return getEpisodeData(partial.text()).end;
      },
      notes: function(partial, full) {
        return getEpisodeData(partial.text()).notes;
      },
      translationType: function(partial, full) {
        return partial.attr('href').split('.')[0].endsWith('-dub') ? 'dub' : 'sub';
      },
      sources: function(partial, full) {
        let episodeString = partial.text();
        let found = [];

        let tabs = [];
        full.find('div.widget.servers div.widget-title span.tabs span.tab').each((index, element) => {
          let tab = full.find(element);
          tabs.push({
            data: tab.attr('data-name'),
            name: tab.text()
          });
        });

        full.find('div.widget.servers div.widget-body div.server').each((index, element) => {
          let server = full.find(element);
          let name = tabs.getPartialObjects({data: server.attr('data-name')})[0].name;

          server.find('ul li a').each((index, element) => {
            if (full.find(element).text() === episodeString) {
              let uploadDate = undefined;
              let dateString = full.find(element).attr('data-title');
              if (dateString) {
                uploadDate = ScrapingHelpers.buildAiringDateFromStandardStrings(
                  'EST',
                  undefined,
                  dateString.split(' - ')[0],
                  dateString.split(' - ')[1],
                  undefined,
                  undefined
                );
              }
              found.push({
                sourceName: name,
                sourceUrl: nineanime.homepage + full.find(element).attr('href'),
                uploadDate: uploadDate,
                flags: ['OpenLoad', 'Streamango'].includes(name) ? ['requires-plugins'] : []
              });
            }
          });
        });

        return found;
      },
    },
  },

  // Recent page data
  recent: {
    rowSelector: 'div.film-list div.item',

    // Recent episode attribute data
    attributes: {
      episodeNumStart: function(partial, full) {
        return getEpisodeData(partial.find('a.poster div.status div.ep').text().split('/')[0].replace('Ep ', '')).start;
      },
      episodeNumEnd: function(partial, full) {
        return getEpisodeData(partial.find('a.poster div.status div.ep').text().split('/')[0].replace('Ep ', '')).end;
      },
      translationType: function(partial, full) {
        return getTypeFromName(partial.find('a.name').text());
      },
    },
  },

  // Recent show data
  recentShow: {
    // Recent show attribute data
    attributes: posterAttributes,

    // Recent show thumbnail data
    thumbnails: posterThumbnails,
  },
};
