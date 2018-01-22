import {Shows} from '/imports/api/shows/shows.js';
import Cheerio from "cheerio";

export let myanimelist = {
  // General data
  id: 'myanimelist',
  name: 'MyAnimeList',
  homepage: 'https://myanimelist.net/',

  // Search page data
  searchCreateUrl: function(query) {
    return 'https://myanimelist.net/anime.php?q=' + encodeURIComponent(query).replace(/%20/g, '+') + '&type=0&score=0&status=0&p=0&r=0&sm=0&sd=0&sy=0&em=0&ed=0&ey=0&c[]=a&c[]=b&c[]=c&c[]=d&c[]=e&c[]=f&c[]=g&gx=1&genre[]=12';
  },
  searchRowSelector: '.js-block-list.list table tbody tr',
  searchRowSkips: 1,

  // Search page attribute data
  searchSelectorUrl: 'td a.hoverinfo_trigger',
  searchAttributeUrl: function(partial) {
    return partial.attr('href').replace(/\/[^\/]*$/, '');
  },
  searchAttributeUrlType: 'multi',
  searchSelectorName: 'td a.hoverinfo_trigger strong',
  searchAttributeName: function(partial) {
    return partial.text();
  },
  searchSelectorDescription: 'td div.pt4',
  searchAttributeDescription: function(partial) {
    return partial.text().replace(/\.\.\.read more\.$/, Shows.descriptionCutoff);
  },

  // Show page data
  showCheckIfPage: function(page) {
    return page('meta[property="og:url"]').attr('content').match(/^https*:\/\/myanimelist.net\/anime\/[0-9]+\/.*$/);
  },

  // Show page attribute data
  showSelectorUrl: 'div.breadcrumb div:last-of-type a',
  showAttributeUrl: function(partial) {
    return partial.attr('href').replace(/\/[^\/]*$/, '');
  },
  showAttributeUrlType: 'multi',
  showSelectorName: 'div#contentWrapper div:first-of-type h1 span',
  showAttributeName: function(partial) {
    return partial.text();
  },
  showSelectorAltNames: 'td.borderClass div.js-scrollfix-bottom',
  showAttributeAltNames: function(partial) {
    return partial.find('div.spaceit_pad').map((index, element) => {
      let altNames = Cheerio.load(element);
      altNames('span').remove();
      return altNames.text().split(', ');
    }).get();
  },
  showSelectorDescription: 'td span[itemprop=description]',
  showAttributeDescription: function(partial) {
    return partial.html();
  },
};
