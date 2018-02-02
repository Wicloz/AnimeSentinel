import SimpleSchema from 'simpl-schema';

Schemas = {};

Schemas.animeSearch = new SimpleSchema({
  query: {
    type: String,
    optional: true,
    autoform: {
      icon: 'search',
      label: 'Search Anime',
      autocomplete: 'off'
    }
  }
}, { tracker: Tracker });

Schemas.id = new SimpleSchema({
  id: {
    type: String
  }
}, { tracker: Tracker });

Schemas.episodeSelection = new SimpleSchema({
  episodeNumber: {
    type: String,
    autoform: {
      label: 'Episode Number',
    }
  }
}, { tracker: Tracker });
