export let template = {
  // General data
  id: '',
  name: '',
  homepage: '',
  minimalPageTypes: [''],

  // Search page data
  search: {
    createUrl: function(search) {
      return '';
    },
    rowSelector: '',
    rowSkips: 0,

    // Search page attribute data
    attributes: {
      malId: function(partial, full) {
        return 0;
      },
      streamerUrls: function(partial, full) {
        return [{
          type: '',
          url: ''
        }];
      },
      name: function(partial, full) {
        return '';
      },
      altNames: function(partial, full) {
        return [''];
      },
      description: function(partial, full) {
        return '';
      },
      type: function(partial, full) {
        return '';
      },
      genres: function(partial, full) {
        return [''];
      },
    },

    // Search page thumbnail data
    thumbnails: {
      rowSelector: '',
      getUrl: function (partial, full) {
        return '';
      },
    },
  },

  // Show page data
  show: {
    checkIfPage: function(page) {
      return false;
    },

    // Show page attribute data
    attributes: {
      malId: function(partial, full) {
        return 0;
      },
      streamerUrls: function(partial, full) {
        return [{
          type: '',
          url: ''
        }];
      },
      name: function(partial, full) {
        return '';
      },
      altNames: function(partial, full) {
        return [''];
      },
      description: function(partial, full) {
        return '';
      },
      type: function(partial, full) {
        return '';
      },
      genres: function(partial, full) {
        return [''];
      },
    },

    // Show page thumbnail data
    thumbnails: {
      rowSelector: '',
      getUrl: function (partial, full) {
        return '';
      },
    },
  },

  // Related shows data
  showRelated: {
    rowSelector: '',
    rowIgnore: function(partial) {
      return false;
    },

    // Related shows attribute data
    attributes: {
      malId: function(partial, full) {
        return 0;
      },
      streamerUrls: function(partial, full) {
        return [{
          type: '',
          url: ''
        }];
      },
      name: function(partial, full) {
        return '';
      },
      altNames: function(partial, full) {
        return [''];
      },
      description: function(partial, full) {
        return '';
      },
      type: function(partial, full) {
        return '';
      },
      genres: function(partial, full) {
        return [''];
      },
    },

    // Related shows thumbnail data
    thumbnails: {
      rowSelector: '',
      getUrl: function (partial, full) {
        return '';
      },
    },
  },

  // Episode list data
  showEpisodes: {
    rowSelector: '',
    rowSkips: 0,
    cannotCount: false,

    // Episode list attribute data
    attributes: {
      episodeNumStart: function(partial, full) {
        return 0;
      },
      episodeNumEnd: function(partial, full) {
        return 0;
      },
      translationType: function(partial, full) {
        return '';
      },
      sourceUrl: function(partial, full) {
        return '';
      },
      sources: function(partial, full) {
        return [{
          name: '',
          url: '',
          flags: ['']
        }];
      },
    },
  },
};
