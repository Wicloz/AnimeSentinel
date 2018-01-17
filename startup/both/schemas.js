import SimpleSchema from "simpl-schema";

Schemas = {};

Schemas.animeSearch = new SimpleSchema({
  query: {
    type: String,
    min: 3,
    autoform: {
      icon: 'search',
      label: 'Search Anime',
      autocomplete: 'off'
    }
  }
}, { tracker: Tracker });
