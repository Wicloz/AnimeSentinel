import SimpleSchema from 'simpl-schema';

Schemas = {};

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
