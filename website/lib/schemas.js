import SimpleSchema from 'simpl-schema';

SimpleSchema.extendOptions(['autoform']);

Schemas = {};

Schemas.id = new SimpleSchema({
  id: {
    type: String
  }
}, { tracker: Tracker });

Schemas.ids = new SimpleSchema({
  ids: {
    type: Array
  },
  'ids.$': {
    type: String
  }
}, { tracker: Tracker });

Schemas.episodeSelection = new SimpleSchema({
  episodeNumber: {
    type: String,
    autoform: {
      label: 'Select Episode:',
      firstOption: false
    }
  }
}, { tracker: Tracker });
