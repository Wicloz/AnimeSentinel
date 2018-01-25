import {Shows} from '/imports/api/shows/shows.js';
import Cheerio from "cheerio";

export let myanimelist = {
  // General data
  id: 'myanimelist',
  name: 'MyAnimeList',
  homepage: 'https://myanimelist.net/',

  // Search page data
  search: {
    createUrl: function(query) {
      return 'https://myanimelist.net/anime.php?q=' + encodeURIComponentReplaceSpaces(query, '+') + '&type=0&score=0&status=0&p=0&r=0&sm=0&sd=0&sy=0&em=0&ed=0&ey=0&c[]=a&c[]=b&c[]=c&c[]=d&c[]=e&c[]=f&c[]=g&gx=1&genre[]=12';
    },
    rowSelector: '.js-block-list.list table tbody tr',
    rowSkips: 1,

    // Search page attribute data
    attributes: {
      informationUrl: function(partial) {
        return partial.find('td a.hoverinfo_trigger').attr('href').replace(/\/[^\/]*$/, '');
      },
      episodeUrl: function(partial) {
        return partial.find('td a.hoverinfo_trigger').attr('href').replace(/\/[^\/]*$/, '') + '/X/video';
      },
      episodeUrlType: function(partial) {
        return 'multi';
      },
      name: function(partial) {
        return partial.find('td a.hoverinfo_trigger strong').text();
      },
      description: function(partial) {
        return partial.find('td div.pt4').text().replace(/\.\.\.read more\.$/, Shows.descriptionCutoff);
      },
      type: function(partial) {
        return partial.find('td[width=45]').text().replace(/Unknown/g, '');
      },
      malId: function(partial) {
        return partial.find('td a.hoverinfo_trigger').attr('href').replace(/^.*\/([0-9]+)\/.*$/, '$1');
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
      url: function(partial) {
        return partial.find('div.breadcrumb div:last-of-type a').attr('href').replace(/\/[^\/]*$/, '');
      },
      episodeUrl: function(partial) {
        return partial.find('div.breadcrumb div:last-of-type a').attr('href').replace(/\/[^\/]*$/, '') + '/X/video';
      },
      episodeUrlType: function(partial) {
        return 'multi';
      },
      name: function(partial) {
        return partial.find('div#contentWrapper div:first-of-type h1 span').text();
      },
      altNames: function(partial) {
        return partial.find('td.borderClass div.js-scrollfix-bottom').find('div.spaceit_pad').map((index, element) => {
          let altNames = Cheerio.load(element);
          altNames('span').remove();
          return altNames.text().split(', ');
        }).get();
      },
      description: function(partial) {
        return partial.find('td span[itemprop=description]').html();
      },
      type: function(partial) {
        return partial.find('td.borderClass div.js-scrollfix-bottom div:nth-of-type(6)').text().split(':')[1].replace(/Unknown/g, '');
      },
      malId: function(partial) {
        return partial.find('div.breadcrumb div:last-of-type a').attr('href').replace(/^.*\/([0-9]+)\/.*$/, '$1');
      },
    },

    // Related shows data
    related: {
      rowSelector: 'table.anime_detail_related_anime tbody a',
      rowIgnore: function(partial) {
        return partial.attr('href').startsWith('/manga/');
      },

      // Related shows attribute data
      attributes: {
        name: function(partial) {
          return partial.text();
        },
        informationUrl: function(partial) {
          return myanimelist.homepage.replace(/\/$/, '') + partial.attr('href').replace(/\/[^\/]*$/, '');
        },
        episodeUrl: function(partial) {
          return myanimelist.homepage.replace(/\/$/, '') + partial.attr('href').replace(/\/[^\/]*$/, '') + '/X/video';
        },
        episodeUrlType: function(partial) {
          return 'multi';
        },
        malId: function(partial) {
          return partial.attr('href').replace(/^.*\/([0-9]+)\/.*$/, '$1');
        },
      },
    },
  },
};
