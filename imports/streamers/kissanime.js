import Cheerio from 'cheerio';
import {Shows} from '/imports/api/shows/shows.js';

export let kissanime = {
  // General data
  id: 'kissanime',
  name: 'KissAnime',
  homepage: 'http://kissanime.ru/',

  // Search page data
  searchCreateUrl: function(query) {
    return 'http://kissanime.ru/Search/Anime?keyword=' + encodeURIComponent(query).replace(/%20/g, '+');
  },
  searchRowSelector: 'table.listing tbody tr',
  searchRowSkips: 2,

  // Search page attribute data
  searchSelectorEpisodeUrl: 'td:first-of-type a',
  searchAttributeEpisodeUrl: function(partial) {
    return this.homepage.replace(/\/$/, '') + partial.attr('href');
  },
  searchSelectorEpisodeUrlType: 'td:first-of-type a',
  searchAttributeEpisodeUrlType: function(partial) {
    return partial.text().match(/\(Dub\)$/) ? 'dub' : 'sub';
  },
  searchSelectorName: 'td:first-of-type a',
  searchAttributeName: function(partial) {
    return partial.text().replace(/\(Dub\)$/, '').replace(/\(Sub\)$/, '');
  },
  searchSelectorDescription: 'td:first-of-type',
  searchAttributeDescription: function(partial) {
    return Cheerio.load(partial.attr('title'))('div p').text().replace(/ \.\.\.\n\s*$/, Shows.descriptionCutoff);
  },

  // Show page data
  showCheckIfPage: function(page) {
    return page('title').text().cleanWhitespace().match(/^.* anime \| Watch .* anime online in high quality$/);
  },

  // Show page attribute data
  showSelectorEpisodeUrl: 'a.bigChar',
  showAttributeEpisodeUrl: function(partial) {
    return this.homepage.replace(/\/$/, '') + partial.attr('href');
  },
  showSelectorEpisodeUrlType: 'a.bigChar',
  showAttributeEpisodeUrlType: function(partial) {
    return partial.text().match(/\(Dub\)$/) ? 'dub' : 'sub';
  },
  showSelectorName: 'a.bigChar',
  showAttributeName: function(partial) {
    return partial.text().replace(/\(Dub\)$/, '').replace(/\(Sub\)$/, '');
  },
  showSelectorAltNames: '.bigBarContainer .barContent div:nth-of-type(2) p:first-of-type',
  showAttributeAltNames: function(partial) {
    if (partial.find('span.info').text() !== 'Other name:') {
      return [];
    }
    return partial.find('a').map((index, element) => {
      return Cheerio.load(element).text();
    }).get();
  },
  showSelectorDescription: '.bigBarContainer .barContent div:nth-of-type(2) p:nth-last-of-type(2)',
  showAttributeDescription: function(partial) {
    return partial.html();
  },
};
