import ScrapingHelpers from "./scrapingHelpers";
import moment from 'moment-timezone';
import {Shows} from '../api/shows/shows';

function getMalIdFromUrl(url) {
  return url.replace(/^.*\/(\d+)\/.*$/, '$1');
}

function determineAiringDateShowPage(partial, index) {
  let stringDay = undefined;
  let stringTime = undefined;
  let broadcastBits = partial.find('td.borderClass div[style~="width:"] div:contains("Broadcast:")').text().replace('Broadcast:', '').cleanWhitespace().split(' ');
  if (broadcastBits.length === 4) {
    stringDay = broadcastBits[0];
    stringTime = broadcastBits[2];
  }

  return ScrapingHelpers.buildAiringDateFromStandardStrings(
    'Asia/Tokyo',
    index,
    partial.find('td.borderClass div[style~="width:"] div:contains("Aired:")').text().replace('Aired:', '').replace('Not available', '').cleanWhitespace().split(' to '),
    [stringTime, stringTime],
    partial.find('td.borderClass div[style~="width:"] div:contains("Premiered:") a').text(),
    stringDay
  );
}

function determineAiringDateSearchPage(string, normalOrder) {
  let airingDateResult = {};
  if (!string) {
    return airingDateResult;
  }

  let positionDate = normalOrder ? 0 : 1;
  let positionMonth = normalOrder ? 1 : 0;

  let dateBits = string.split('-');
  if (dateBits.length === 3) {
    if (!dateBits[positionDate].includes('?') && dateBits[positionDate] !== '00') {
      airingDateResult.date = dateBits[positionDate];
    }
    if (!dateBits[positionMonth].includes('?') && dateBits[positionMonth] !== '00') {
      airingDateResult.month = dateBits[positionMonth] - 1;
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

function cleanThumbnailUrl(thumbnailUrl) {
  if (!thumbnailUrl.includes('/images/anime/')) {
    return undefined;
  } else {
    return thumbnailUrl.replace(/^.*\/images\/anime\/(\d+\/\d+\.[A-Za-z]+).*$/, 'https://cdn.myanimelist.net/images/anime/$1');
  }
}

function showApiUsesNormalOrder(entries) {
  for (let i = 0; i < entries.length; i++) {
    if ((entries[i].anime_start_date_string && entries[i].anime_start_date_string.split('-')[0] > 12) ||
      (entries[i].anime_end_date_string && entries[i].anime_end_date_string.split('-')[0] > 12)) {
      return true;
    }
    if ((entries[i].anime_start_date_string && entries[i].anime_start_date_string.split('-')[1] > 12) ||
      (entries[i].anime_end_date_string && entries[i].anime_end_date_string.split('-')[1] > 12)) {
      return false;
    }
  }
  return false;
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

      let startDate = '';
      if (typeof search.year !== 'undefined') {
        if (typeof search.season !== 'undefined') {
          let momentStart = moment.fromUtc({
            year: search.year
          }).quarter(Shows.validQuarters.indexOf(search.season) + 1);
          startDate = '&sd=' + momentStart.date() + '&sm=' + (momentStart.month() + 1) + '&sy=' + momentStart.year();
        } else {
          startDate = '&sd=1&sm=1&sy=' + search.year;
        }
      }

      let sort = '';
      let sortDirection = search.sortDirection === 1 ? '2' : '1';
      switch(search.sortBy) {
        case 'Latest Update':
          sort = '&o=2&w=' + sortDirection;
          break;
        case 'Type':
          sort = '&o=6&w=' + sortDirection;
          break;
      }

      return myanimelist.homepage + '/anime.php?c[]=a&c[]=b&c[]=c&c[]=d&c[]=e&c[]=f&c[]=g' + sort + startDate + query + type + exclude + '&genre[]=' + genres.join('&genre[]=');
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
        return partial.find('td:nth-of-type(3)').text().replace('Unknown', '');
      },
      airedStart: function(partial, full) {
        return determineAiringDateSearchPage(partial.find('td:nth-of-type(6)').text(), false);
      },
      airedEnd: function(partial, full) {
        return determineAiringDateSearchPage(partial.find('td:nth-of-type(7)').text(), false);
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
        return cleanThumbnailUrl(partial.attr('data-src'));
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
        let header = partial.find('div#contentWrapper div:first-of-type h1 > span > span');
        header.find('span').remove();
        return header.text();
      },
      altNames: function(partial, full) {
        return partial.find('td.borderClass div[style~="width:"] div.spaceit_pad').map((index, element) => {
          let altNames = partial.find(element);
          altNames.find('span').remove();
          return altNames.text().split(', ');
        }).get();
      },
      description: function(partial, full) {
        return partial.find('td span[itemprop=description]').html();
      },
      type: function(partial, full) {
        return partial.find('td.borderClass div[style~="width:"] div:contains("Type:") a').text();
      },
      genres: function(partial, full) {
        return partial.find('td.borderClass div[style~="width:"] div:contains("Genres:") a').map((index, element) => {
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
        let bits = partial.find('td.borderClass div[style~="width:"] div:contains("Premiered:") a').text().split(' ');
        if (bits.length === 2) {
          return {
            quarter: bits[0],
            year: bits[1]
          };
        }
        return undefined;
      },
      episodeCount: function(partial, full) {
        return partial.find('td.borderClass div[style~="width:"] div:contains("Episodes:")').text()
          .replace('Episodes:', '').cleanWhitespace().replace('Unknown', '');
      },
      broadcastInterval: function(partial, full) {
        let broadcast = partial.find('td.borderClass div[style~="width:"] div:contains("Broadcast:")').text().cleanWhitespace();
        return broadcast && /Broadcast: [A-Za-z]+ at [0-9:?]+/.test(broadcast) ? 604800000 : undefined;
      },
      episodeDuration: function(partial, full) {
        let base = partial.find('td.borderClass div[style~="width:"] div:contains("Duration:")')
          .text().cleanWhitespace().split(' ')[1];
        if (isNumeric(base)) {
          return base * 60000;
        } else {
          return undefined;
        }
      },
      rating: function(partial, full) {
        return partial.find('td.borderClass div[style~="width:"] div:contains("Rating:")')
          .text().cleanWhitespace().split(' ')[1].replace('None', '');
      },
    },

    // Show page thumbnail data
    thumbnails: {
      rowSelector: 'div.picSurround a.js-picture-gallery img, img.ac',
      getUrl: function (partial, full) {
        return cleanThumbnailUrl(partial.attr('data-src'));
      },
    },
  },

  // Show API page data
  showApi: {
    // Show API page attribute data
    attributes: {
      malId: function(partial, full) {
        return partial.anime_id;
      },
      streamerUrls: function(partial, full) {
        return [{
          type: 'details',
          url: myanimelist.homepage + partial.anime_url
        }, {
          type: 'pictures',
          url: myanimelist.homepage + partial.anime_url + '/pics'
        }];
      },
      name: function(partial, full) {
        return partial.anime_title;
      },
      type: function(partial, full) {
        return partial.anime_media_type_string.replace('Unknown', '');
      },
      airedStart: function(partial, full) {
        return determineAiringDateSearchPage(partial.anime_start_date_string, showApiUsesNormalOrder(full));
      },
      airedEnd: function(partial, full) {
        return determineAiringDateSearchPage(partial.anime_end_date_string, showApiUsesNormalOrder(full));
      },
      episodeCount: function(partial, full) {
        return partial.anime_num_episodes === 0 ? undefined : partial.anime_num_episodes;
      },
      rating: function(partial, full) {
        return partial.anime_mpaa_rating_string;
      },
    },

    // Show API page thumbnail data
    thumbnails: {
      getUrl: function (partial, full) {
        return cleanThumbnailUrl(partial.anime_image_path);
      },
    },
  },

  // Related shows data
  showRelated: {
    rowSelector: 'table.anime_detail_related_anime tr > td > a[href^="/anime/"]',
    relation: function(partial, full) {
      return partial.parent().parent().find('td:first-of-type').text().replace(':', '').toLowerCase();
    },

    // Related shows attribute data
    attributes: {
      malId: function(partial, full) {
        return getMalIdFromUrl(partial.attr('href'));
      },
      streamerUrls: function(partial, full) {
        if (!partial.text()) {
          return [];
        }
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
          url: partial.find('div.video-info-title a:last-of-type').attr('href')
        }, {
          type: 'pictures',
          url: partial.find('div.video-info-title a:last-of-type').attr('href') + '/pics'
        }];
      },
      name: function(partial, full) {
        return partial.find('div.video-info-title a:last-of-type').text();
      },
    },

    // Recent show thumbnail data
    thumbnails: {
      rowSelector: 'div.episode',
      getUrl: function (partial, full) {
        return cleanThumbnailUrl(partial.css('background-image').replace('url(\'', '').replace('\')', ''));
      },
    },
  },
};
