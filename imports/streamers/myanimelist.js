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
  searchSelectorInformationUrl: 'td a.hoverinfo_trigger',
  searchAttributeInformationUrl: function(partial) {
    return partial.attr('href').replace(/\/[^\/]*$/, '');
  },
  searchSelectorEpisodeUrl: 'td a.hoverinfo_trigger',
  searchAttributeEpisodeUrl: function(partial) {
    return partial.attr('href').replace(/\/[^\/]*$/, '') + '/X/video';
  },
  searchAttributeEpisodeUrlType: 'multi',
  searchSelectorName: 'td a.hoverinfo_trigger strong',
  searchAttributeName: function(partial) {
    return partial.text();
  },
  searchSelectorDescription: 'td div.pt4',
  searchAttributeDescription: function(partial) {
    return partial.text().replace(/\.\.\.read more\.$/, Shows.descriptionCutoff);
  },
  searchSelectorType: 'td[width=45]',
  searchAttributeType: function(partial) {
    return partial.text().replace(/Unknown/g, '');
  },
  searchSelectorMalId: 'td a.hoverinfo_trigger',
  searchAttributeMalId: function(partial) {
    return partial.attr('href').replace(/^.*\/([0-9]+)\/.*$/, '$1');
  },

  // Show page data
  showCheckIfPage: function(page) {
    return page('meta[property="og:url"]').attr('content').match(/^https*:\/\/myanimelist.net\/anime\/[0-9]+\/.*$/);
  },

  // Show page attribute data
  showSelectorInformationUrl: 'div.breadcrumb div:last-of-type a',
  showAttributeInformationUrl: function(partial) {
    return partial.attr('href').replace(/\/[^\/]*$/, '');
  },
  showSelectorEpisodeUrl: 'div.breadcrumb div:last-of-type a',
  showAttributeEpisodeUrl: function(partial) {
    return partial.attr('href').replace(/\/[^\/]*$/, '') + '/X/video';
  },
  showAttributeEpisodeUrlType: 'multi',
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
  showSelectorType: 'td.borderClass div.js-scrollfix-bottom div:nth-of-type(6)',
  showAttributeType: function(partial) {
    return partial.text().split(':')[1].replace(/Unknown/g, '');
  },
  showSelectorMalId: 'div.breadcrumb div:last-of-type a',
  showAttributeMalId: function(partial) {
    return partial.attr('href').replace(/^.*\/([0-9]+)\/.*$/, '$1');
  },

  // Show page related attribute data
  relatedRowSelector: 'table.anime_detail_related_anime tbody a',
  relatedRowIgnore: function(partial) {
    return partial.attr('href').startsWith('/manga/');
  },
  relatedAttributeName: function(partial) {
    return partial.text();
  },
  relatedAttributeInformationUrl: function(partial) {
    return this.homepage.replace(/\/$/, '') + partial.attr('href').replace(/\/[^\/]*$/, '');
  },
  relatedAttributeEpisodeUrl: function(partial) {
    return this.homepage.replace(/\/$/, '') + partial.attr('href').replace(/\/[^\/]*$/, '') + '/X/video';
  },
  relatedAttributeEpisodeUrlType: 'multi',
  relatedAttributeMalId: function(partial) {
    return partial.attr('href').replace(/^.*\/([0-9]+)\/.*$/, '$1');
  },
};
