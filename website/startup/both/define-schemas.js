import {WatchStates} from '../../imports/api/watchstates/watchstates';
import SimpleSchema from 'simpl-schema';

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

Schemas.statusesSelection = new SimpleSchema({
  statuses: {
    type: Array,
    optional: true,
    autoform: {
      options: WatchStates.validStatuses.map((status) => {
        return {label: WatchStates.makeFancyStatus(status), value: status};
      }),
      type: 'select-checkbox-inline',
      label: false
    }
  },
  'statuses.$': {
    type: String,
    allowedValues: WatchStates.validStatuses
  }
}, { tracker: Tracker });

Schemas.translationTypeSelection = new SimpleSchema({
  translationType: {
    type: String,
    allowedValues: ['sub', 'dub', 'raw'],
    autoform: {
      options: [
        {label: 'Subs', value: 'sub'},
        {label: 'Dubs', value: 'dub'},
        {label: 'Raws', value: 'raw'}
      ],
      type: 'select-radio-inline',
      label: 'Select preferred episode type:'
    }
  }
}, { tracker: Tracker });
