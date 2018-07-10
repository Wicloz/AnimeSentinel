import ScrapingHelpers from "./scrapingHelpers";
import moment from 'moment-timezone';

function getMalIdFromUrl(url) {
  return url.replace(/^.*\/(\d+)\/.*$/, '$1');
}

function cleanUrl(url) {
  return url.replace(/\/[^\/]*$/, '/-');
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
    partial.find('td.borderClass div.js-scrollfix-bottom div:contains("Aired:")').text().replace('Aired:', '').replace('Not available', '').split(' to '),
    [stringTime, stringTime],
    partial.find('td.borderClass div.js-scrollfix-bottom div:contains("Premiered:") a').text(),
    stringDay
  );
}

function determineAiringDateSearchPage(string) {
  let airingDateResult = {};

  let dateBits = string.split('-');
  if (dateBits.length === 3) {
    if (!dateBits[0].includes('?')) {
      airingDateResult.month = dateBits[0] - 1;
    }
    if (!dateBits[1].includes('?')) {
      airingDateResult.date = dateBits[1];
    }
    if (!dateBits[2].includes('?')) {
      let prepend = Math.floor(moment.fromUtc().year() / 100);
      if (dateBits[2] > moment.fromUtc().year() % 100 + 10) {
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
  recentPage: 'https://myanimelist.net/watch/episode',
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
    rowSelector: '.js-block-list.list table tbody tr:has(td a.hoverinfo_trigger)',

    // Search page attribute data
    attributes: {
      malId: function(partial, full) {
        return getMalIdFromUrl(partial.find('td a.hoverinfo_trigger').attr('href'));
      },
      streamerUrls: function(partial, full) {
        return [{
          type: 'details',
          url: cleanUrl(partial.find('td a.hoverinfo_trigger').attr('href'))
        }, {
          type: 'pictures',
          url: cleanUrl(partial.find('td a.hoverinfo_trigger').attr('href')) + '/pics'
        }];
      },
      name: function(partial, full) {
        return partial.find('td a.hoverinfo_trigger strong').text();
      },
      description: function(partial, full) {
        return ScrapingHelpers.replaceDescriptionCutoff(partial.find('td div.pt4').text(), '...read more.');
      },
      type: function(partial, full) {
        return partial.find('td:nth-of-type(3)').text().replace('Unknown', '');
      },
      airedStart: function(partial, full) {
        return determineAiringDateSearchPage(partial.find('td:nth-of-type(6)').text());
      },
      airedEnd: function(partial, full) {
        return determineAiringDateSearchPage(partial.find('td:nth-of-type(7)').text());
      },
      episodeCount: function(partial, full) {
        return partial.find('td:nth-of-type(4)').text().cleanWhitespace().replace('-', '');
      },
      rating: function(partial, full) {
        return partial.find('td:nth-of-type(9)').text().replace(/^\s*-\s*$/, '');
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
          url: cleanUrl(partial.find('div#horiznav_nav ul li:first-of-type a').attr('href'))
        }, {
          type: 'pictures',
          url: cleanUrl(partial.find('div#horiznav_nav ul li:first-of-type a').attr('href')) + '/pics'
        }];

        if (partial.find('div#horiznav_nav ul li a').text().includes('Episodes')) {
          urls.push({
            type: 'episodes-0',
            url: cleanUrl(partial.find('div#horiznav_nav ul li:first-of-type a').attr('href')) + '/episode'
          });

          partial.find('div.pagination a.link').each((index, element) => {
            let link = partial.find(element).attr('href');
            let offset = link.replace(/^.*offset=/, '');
            if (offset !== '0') {
              urls.push({
                type: 'episodes-' + offset,
                url: cleanUrl(link)
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
      episodeCount: function(partial, full) {
        return partial.find('td.borderClass div.js-scrollfix-bottom div:contains("Episodes:")').text()
          .replace('Episodes:', '').cleanWhitespace().replace('Unknown', '');
      },
      broadcastInterval: function(partial, full) {
        let broadcast = partial.find('td.borderClass div.js-scrollfix-bottom div:contains("Broadcast:")').text().cleanWhitespace();
        return broadcast && /Broadcast: [A-Za-z]+ at [0-9:?]+/.test(broadcast) ? 604800000 : undefined;
      },
      episodeDuration: function(partial, full) {
        let base = partial.find('td.borderClass div.js-scrollfix-bottom div:contains("Duration:")')
          .text().cleanWhitespace().split(' ')[1];
        if (isNumeric(base)) {
          return base * 60000;
        } else {
          return undefined;
        }
      },
      rating: function(partial, full) {
        return partial.find('td.borderClass div.js-scrollfix-bottom div:contains("Rating:")')
          .text().cleanWhitespace().split(' ')[1].replace('None', '');
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

  // Show API page data
  showApi: {
    // Show API page attribute data
    attributes: {
      malId: function(partial, full) {
        return partial.series_animedb_id[0];
      },
      streamerUrls: function(partial, full) {
        return [{
          type: 'details',
          url: myanimelist.homepage + '/anime/' + partial.series_animedb_id[0] + '/-'
        }, {
          type: 'pictures',
          url: myanimelist.homepage + '/anime/' + partial.series_animedb_id[0] + '/-/pics'
        }];
      },
      name: function(partial, full) {
        return partial.series_title[0];
      },
      altNames: function(partial, full) {
        return partial.series_synonyms[0].split('; ');
      },
      type: function(partial, full) {
        return validTypes[partial.series_type[0] - 1];
      },
      airedStart: function(partial, full) {
        let splitDate = partial.series_start[0].split('-');
        return {
          year: splitDate[0] === '0000' ? undefined : splitDate[0],
          month: splitDate[1] === '00' ? undefined : splitDate[1] - 1,
          date: splitDate[2] === '00' ? undefined : splitDate[2]
        };
      },
      airedEnd: function(partial, full) {
        let splitDate = partial.series_end[0].split('-');
        return {
          year: splitDate[0] === '0000' ? undefined : splitDate[0],
          month: splitDate[1] === '00' ? undefined : splitDate[1] - 1,
          date: splitDate[2] === '00' ? undefined : splitDate[2]
        };
      },
      episodeCount: function(partial, full) {
        return partial.series_episodes[0] === '0' ? undefined : partial.series_episodes[0];
      },
      episodeDuration: function(partial, full) {
        // TODO
      },
      rating: function(partial, full) {
        // TODO
      },
    },

    // Show API page thumbnail data
    thumbnails: {
      rowSelector: 'series_image',
      getUrl: function (partial, full) {
        return partial === 'https://myanimelist.cdn-dena.com/images/anime//0.jpg' ? undefined : partial;
      },
    },
  },

  // Related shows data
  showRelated: {
    rowSelector: 'table.anime_detail_related_anime tbody a[href^="/anime/"]',

    // Related shows attribute data
    attributes: {
      malId: function(partial, full) {
        return getMalIdFromUrl(partial.attr('href'));
      },
      streamerUrls: function(partial, full) {
        return [{
          type: 'details',
          url: cleanUrl(myanimelist.homepage + partial.attr('href'))
        }, {
          type: 'pictures',
          url: cleanUrl(myanimelist.homepage + partial.attr('href')) + '/pics'
        }];
      },
      name: function(partial, full) {
        return partial.text();
      },
    },
  },

  // Episode list data
  showEpisodes: {
    rowSelector: 'table.episode_list.ascend tbody tr:has(td.episode-video a img)',
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
      sources: function(partial, full) {
        let uploadDate = {};
        let dateBits = partial.find('td.episode-aired').text().replace(/,/g, '').split(' ');
        if (dateBits.length === 3) {
          uploadDate.year = dateBits[2];
          uploadDate.month = moment.fromUtc().month(dateBits[0]).month();
          uploadDate.date = dateBits[1];
        }

        return [{
          sourceName: 'Crunchyroll',
          sourceUrl: partial.find('td.episode-title a').attr('href') + '?provider_id=1',
          uploadDate: uploadDate,
          flags: ['flash', 'requires-plugins']
        }];
      },
    },
  },

  // Recent page data
  recent: {
    rowSelector: 'div.watch-anime-list div.video-block div.video-list-outer-vertical',

    // Recent episode attribute data
    attributes: {
      episodeNumStart: function(partial, full) {
        return partial.find('div.episode div.info-container div.title a:first-of-type').attr('href').split('/').pop();
      },
      episodeNumEnd: function(partial, full) {
        return partial.find('div.episode div.info-container div.title a:first-of-type').attr('href').split('/').pop();
      },
      translationType: function(partial, full) {
        return 'sub';
      },
    },
  },

  // Recent show data
  recentShow: {
    // Recent show attribute data
    attributes: {
      malId: function(partial, full) {
        return getMalIdFromUrl(partial.find('div.video-info-title a:last-of-type').attr('href'));
      },
      streamerUrls: function(partial, full) {
        return [{
          type: 'details',
          url: cleanUrl(partial.find('div.video-info-title a:last-of-type').attr('href'))
        }, {
          type: 'pictures',
          url: cleanUrl(partial.find('div.video-info-title a:last-of-type').attr('href')) + '/pics'
        }];
      },
      name: function(partial, full) {
        return partial.find('div.video-info-title a:last-of-type').text();
      },
      episodeDuration: function(partial, full) {
        // TODO
      },
      rating: function(partial, full) {
        // TODO
      },
    },

    // Recent show thumbnail data
    thumbnails: {
      rowSelector: 'div.episode',
      getUrl: function (partial, full) {
        let url = partial.css('background-image').replace('url(\'', '').replace('\')', '');
        if (!url.includes('icon-banned-youtube-rect')) {
          return url;
        }
        return undefined;
      },
    },
  },
};
