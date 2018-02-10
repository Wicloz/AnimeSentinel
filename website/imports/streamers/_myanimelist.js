import ScrapingHelpers from "./scrapingHelpers";
import moment from 'moment-timezone';

function getMalIdFromUrl(url) {
  return url.replace(/^.*\/(\d+)\/.*$/, '$1');
}

function determineAiringDateShowPage(partial, index) {
  let stringDay = undefined;
  let stringTime = undefined;
  let broadcastBits = partial.find('td.borderClass div.js-scrollfix-bottom div:contains("Broadcast:")').text().replace('Broadcast:', '').cleanWhitespace().split(' ');
  if (broadcastBits.length === 4) {
    stringDay = broadcastBits[0];
    stringTime = broadcastBits[2];
  }

  return ScrapingHelpers.buildAiringDateFromStandardStrings(
    'Asia/Tokyo',
    index,
    partial.find('td.borderClass div.js-scrollfix-bottom div:contains("Aired:")').text().replace('Aired:', ''),
    partial.find('td.borderClass div.js-scrollfix-bottom div:contains("Premiered:") a').text(),
    stringDay,
    stringTime
  );
}

function determineAiringDateSearchPage(string) {
  let airingDateResult = {};

  let dateBits = string.split('-');
  if (dateBits.length === 3) {
    if (!dateBits[0].includes('?')) {
      airingDateResult.month = dateBits[0];
    }
    if (!dateBits[1].includes('?')) {
      airingDateResult.date = dateBits[1];
    }
    if (!dateBits[2].includes('?')) {
      let prepend = Math.floor(moment().year() / 100);
      if (dateBits[2] > moment().year() % 100 + 10) {
        prepend--;
      }
      airingDateResult.year = prepend + dateBits[2];
    }
  }

  return airingDateResult;
}

const validTypes = ['TV', 'OVA', 'Movie', 'Special', 'ONA'];
const validGenres = ['Action', 'Adventure', 'Cars', 'Comedy', 'Dementia', 'Demons', 'Mystery', 'Drama', 'Ecchi',
  'Fantasy', 'Game', 'Hentai', 'Historical', 'Horror', 'Kids', 'Magic', 'Martial Arts', 'Mecha', 'Music', 'Parody', 'Samurai',
  'Romance', 'School', 'Sci-Fi', 'Shoujo', 'Shoujo Ai', 'Shounen', 'Shounen Ai', 'Space', 'Sports', 'Super Power',
  'Vampire', 'Yaoi', 'Yuri', 'Harem', 'Slice of Life', 'Supernatural', 'Military', 'Police', 'Psychological',
  'Thriller', 'Seinen', 'Josei'];

export let myanimelist = {
  // General data
  id: 'myanimelist',
  name: 'MyAnimeList',
  homepage: 'https://myanimelist.net',
  minimalPageTypes: ['details'],

  // Search page data
  search: {
    createUrl: function(search) {
      let query = '';
      if (search.query) {
        query = '&q=' + encodeURIComponentReplaceSpaces(search.completeQuery(3, ' '), '+');
      }

      let type = '';
      if (search.getSingleType(validTypes)) {
        type = '&type=' + (validTypes.indexOf(search.getSingleType(validTypes)) + 1);
      }

      let exclude = '&gx=1';
      let genres = [12];
      if (search.includeGenres && search.getSingleGenre(validGenres)) {
        exclude = '';
        genres = [validGenres.indexOf(search.getSingleGenre(validGenres)) + 1];
      }
      else if (!search.includeGenres && !search.genres.empty()) {
        genres = genres.concat(search.genres.filter((genre) => {
          return validGenres.includes(genre);
        }).map((genre) => {
          return validGenres.indexOf(genre) + 1;
        }));
      }

      return myanimelist.homepage + '/anime.php?c[]=a&c[]=b&c[]=c&c[]=d&c[]=e&c[]=f&c[]=g' + query + type + exclude + '&genre[]=' + genres.join('&genre[]=');
    },
    rowSelector: '.js-block-list.list table tbody tr',
    rowSkips: 1,

    // Search page attribute data
    attributes: {
      malId: function(partial, full) {
        return getMalIdFromUrl(partial.find('td a.hoverinfo_trigger').attr('href'));
      },
      streamerUrls: function(partial, full) {
        return [{
          type: 'details',
          url: partial.find('td a.hoverinfo_trigger').attr('href')
        }, {
          type: 'pictures',
          url: partial.find('td a.hoverinfo_trigger').attr('href') + '/pics'
        }];
      },
      name: function(partial, full) {
        return partial.find('td a.hoverinfo_trigger strong').text();
      },
      description: function(partial, full) {
        return ScrapingHelpers.replaceDescriptionCutoff(partial.find('td div.pt4').text(), '...read more.');
      },
      type: function(partial, full) {
        return partial.find('td:nth-of-type(3)').text().replace(/Unknown/g, '');
      },
      airedStart: function(partial, full) {
        return determineAiringDateSearchPage(partial.find('td:nth-of-type(6)').text());
      },
      airedEnd: function(partial, full) {
        return determineAiringDateSearchPage(partial.find('td:nth-of-type(7)').text());
      },
    },

    // Search page thumbnail data
    thumbnails: {
      rowSelector: 'div.picSurround img',
      getUrl: function (partial, full) {
        return partial.attr('data-src').replace(/^.*\/images\/anime\/(\d+\/\d+\.[A-Za-z]+).*$/, 'https://myanimelist.cdn-dena.com/images/anime/$1');
      },
    },
  },

  // Show page data
  show: {
    checkIfPage: function(page) {
      return page('meta[property="og:url"]').attr('content').match(/^https*:\/\/myanimelist.net\/anime\/[0-9]+\/.*$/);
    },

    // Show page attribute data
    attributes: {
      malId: function(partial, full) {
        return getMalIdFromUrl(partial.find('div#horiznav_nav ul li:first-of-type a').attr('href'));
      },
      streamerUrls: function(partial, full) {
        let urls = [{
          type: 'details',
          url: partial.find('div#horiznav_nav ul li:first-of-type a').attr('href')
        }, {
          type: 'pictures',
          url: partial.find('div#horiznav_nav ul li:first-of-type a').attr('href') + '/pics'
        }];

        if (partial.find('div#horiznav_nav ul li a').text().includes('Episodes')) {
          urls.push({
            type: 'episodes-0',
            url: partial.find('div#horiznav_nav ul li:first-of-type a').attr('href') + '/episode'
          });

          partial.find('div.pagination a.link').each((index, element) => {
            let link = partial.find(element).attr('href');
            let offset = link.replace(/^.*offset=/, '');
            if (offset !== '0') {
              urls.push({
                type: 'episodes-' + offset,
                url: link
              });
            }
          });
        }

        return urls;
      },
      name: function(partial, full) {
        return partial.find('div#contentWrapper div:first-of-type h1 span').text();
      },
      altNames: function(partial, full) {
        return partial.find('td.borderClass div.js-scrollfix-bottom div.spaceit_pad').map((index, element) => {
          let altNames = partial.find(element);
          altNames.find('span').remove();
          return altNames.text().split(', ');
        }).get();
      },
      description: function(partial, full) {
        return partial.find('td span[itemprop=description]').html();
      },
      type: function(partial, full) {
        return partial.find('td.borderClass div.js-scrollfix-bottom div:contains("Type:") a').text();
      },
      genres: function(partial, full) {
        return partial.find('td.borderClass div.js-scrollfix-bottom div:contains("Genres:") a').map((index, element) => {
          return partial.find(element).text();
        }).get();
      },
      airedStart: function(partial, full) {
        return determineAiringDateShowPage(partial, 0);
      },
      airedEnd: function(partial, full) {
        return determineAiringDateShowPage(partial, 1);
      },
      season: function(partial, full) {
        let bits = partial.find('td.borderClass div.js-scrollfix-bottom div:contains("Premiered:") a').text().split(' ');
        if (bits.length === 2) {
          return {
            quarter: bits[0],
            year: bits[1]
          };
        }
        return undefined;
      },
    },

    // Show page thumbnail data
    thumbnails: {
      rowSelector: 'div.picSurround a.js-picture-gallery img, img.ac',
      getUrl: function (partial, full) {
        return partial.attr('src');
      },
    },
  },

  // Related shows data
  showRelated: {
    rowSelector: 'table.anime_detail_related_anime tbody a',
    rowIgnore: function(partial) {
      return partial.attr('href').startsWith('/manga/');
    },

    // Related shows attribute data
    attributes: {
      malId: function(partial, full) {
        return getMalIdFromUrl(partial.attr('href'));
      },
      streamerUrls: function(partial, full) {
        return [{
          type: 'details',
          url: myanimelist.homepage + partial.attr('href')
        }, {
          type: 'pictures',
          url: myanimelist.homepage + partial.attr('href') + '/pics'
        }];
      },
      name: function(partial, full) {
        return partial.text();
      },
    },
  },

  // Episode list data
  showEpisodes: {
    rowSelector: 'table.episode_list.ascend tbody tr',
    rowSkips: 1,
    cannotCount: false,

    // Episode list attribute data
    attributes: {
      episodeNumStart: function(partial, full) {
        return partial.find('td.episode-number').text();
      },
      episodeNumEnd: function(partial, full) {
        return partial.find('td.episode-number').text();
      },
      translationType: function(partial, full) {
        return 'sub';
      },
      sourceUrl: function(partial, full) {
        return partial.find('td.episode-title a').attr('href');
      },
      sources: function(partial, full) {
        return [{
          name: 'Crunchyroll',
          url: partial.find('td.episode-title a').attr('href') + '?provider_id=1',
          flags: ['flash']
        }];
      },
    },
  },
};
