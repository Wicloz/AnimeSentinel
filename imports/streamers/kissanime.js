import Cheerio from 'cheerio';

export let kissanime = {
  id: 'kissanime',
  name: 'KissAnime',
  homepage: 'http://kissanime.ru/',

  searchCreateUrl: function(query) {
    return 'http://kissanime.ru/Search/Anime?keyword=' + query.replace(/\s/g, '+');
  },
  searchRowSelector: 'table.listing tbody tr',
  searchRowSkips: 2,

  searchSelectorUrl: 'td a',
  searchAttributeUrl: function(partial) {
    return this.homepage + partial.attr('href');
  },
  searchSelectorName: 'td a',
  searchAttributeName: function(partial) {
    return partial.text().replace(/\(Dub\)$/, '').replace(/\(Sub\)$/, '');
  },
  searchSelectorDescription: 'td',
  searchAttributeDescription: function(partial) {
    return Cheerio.load(partial.attr('title'))('div p').text();
  }
};
