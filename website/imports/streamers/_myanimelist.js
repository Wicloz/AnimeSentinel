import ScrapingHelpers from "./scrapingHelpers";

function getMalIdFromUrl(url) {
  return url.replace(/^.*\/(\d+)\/.*$/, '$1');
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
        let filler = search.query.length < 3 ? ' '.repeat(3 - search.query.length) : '';
        query = '&q=' + encodeURIComponentReplaceSpaces(search.query + filler, '+');
      }

      let type = '';
      if (search.includeTypes && search.types && search.types.length === 1 && search.types[0] !== 'Unknown') {
        type = '&type=' + (validTypes.indexOf(search.types[0]) + 1);
      }
      else if (!search.includeTypes && search.types.length === validTypes.length && search.types.includes('Unknown')) {
        type = '&type=' + (validTypes.findIndex((type) => {
          return !search.types.includes(type);
        }) + 1);
      }

      let exclude = '&gx=1';
      let genres = [12];
      if (search.includeGenres && search.genres && search.genres.length === 1 && search.genres[0] !== 'Unknown') {
        exclude = '';
        genres = [validGenres.indexOf(search.genres[0]) + 1];
      }
      else if (!search.includeGenres && search.genres && !search.genres.empty()) {
        genres = genres.concat(search.genres.filter((genre) => {
          return genre !== 'Unknown';
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
        return partial.find('td[width=45]').text().replace(/Unknown/g, '');
      },
      malId: function(partial, full) {
        return getMalIdFromUrl(partial.find('td a.hoverinfo_trigger').attr('href'));
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
        return partial.find('td.borderClass div.js-scrollfix-bottom').find('div.spaceit_pad').map((index, element) => {
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
      malId: function(partial, full) {
        return getMalIdFromUrl(partial.find('div#horiznav_nav ul li:first-of-type a').attr('href'));
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
      malId: function(partial, full) {
        return getMalIdFromUrl(partial.attr('href'));
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
