export let template = {
  // General data
  id: '',
  name: '',
  homepage: '',
  recentPage: '',
  minimalPageTypes: [''],

  // Search page data
  search: {
    createUrl: function(search) {
      return '';
    },
    rowSelector: '',

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
      airedStart: function(partial, full) {
        return {
          year: 0,
          month: 0,
          date: 0,
          hour: 0,
          minute: 0
        };
      },
      airedEnd: function(partial, full) {
        return {
          year: 0,
          month: 0,
          date: 0,
          hour: 0,
          minute: 0
        };
      },
      season: function(partial, full) {
        return {
          quarter: '',
          year: 0
        };
      },
      episodeCount: function(partial, full) {
        return 0;
      },
      broadcastInterval: function(partial, full) {
        return 0;
      },
      episodeDuration: function(partial, full) {
        return 0;
      },
      rating: function(partial, full) {
        return '';
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
      airedStart: function(partial, full) {
        return {
          year: 0,
          month: 0,
          date: 0,
          hour: 0,
          minute: 0
        };
      },
      airedEnd: function(partial, full) {
        return {
          year: 0,
          month: 0,
          date: 0,
          hour: 0,
          minute: 0
        };
      },
      season: function(partial, full) {
        return {
          quarter: '',
          year: 0
        };
      },
      episodeCount: function(partial, full) {
        return 0;
      },
      broadcastInterval: function(partial, full) {
        return 0;
      },
      episodeDuration: function(partial, full) {
        return 0;
      },
      rating: function(partial, full) {
        return '';
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

  // Show API page data
  showApi: {
    // Show API page attribute data
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
      airedStart: function(partial, full) {
        return {
          year: 0,
          month: 0,
          date: 0,
          hour: 0,
          minute: 0
        };
      },
      airedEnd: function(partial, full) {
        return {
          year: 0,
          month: 0,
          date: 0,
          hour: 0,
          minute: 0
        };
      },
      season: function(partial, full) {
        return {
          quarter: '',
          year: 0
        };
      },
      episodeCount: function(partial, full) {
        return 0;
      },
      broadcastInterval: function(partial, full) {
        return 0;
      },
      episodeDuration: function(partial, full) {
        return 0;
      },
      rating: function(partial, full) {
        return '';
      },
    },

    // Show API page thumbnail data
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
    relation: function(partial, full) {
      return '';
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
      airedStart: function(partial, full) {
        return {
          year: 0,
          month: 0,
          date: 0,
          hour: 0,
          minute: 0
        };
      },
      airedEnd: function(partial, full) {
        return {
          year: 0,
          month: 0,
          date: 0,
          hour: 0,
          minute: 0
        };
      },
      season: function(partial, full) {
        return {
          quarter: '',
          year: 0
        };
      },
      episodeCount: function(partial, full) {
        return 0;
      },
      broadcastInterval: function(partial, full) {
        return 0;
      },
      episodeDuration: function(partial, full) {
        return 0;
      },
      rating: function(partial, full) {
        return '';
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
    cannotCount: false,

    // Episode list attribute data
    attributes: {
      episodeNumStart: function(partial, full) {
        return 0;
      },
      episodeNumEnd: function(partial, full) {
        return 0;
      },
      notes: function(partial, full) {
        return '';
      },
      translationType: function(partial, full) {
        return '';
      },
      sources: function(partial, full) {
        return [{
          sourceName: '',
          sourceUrl: '',
          uploadDate: {
            year: 0,
            month: 0,
            date: 0,
            hour: 0,
            minute: 0
          },
          flags: ['']
        }];
      },
    },
  },

  // Recent page data
  recent: {
    rowSelector: '',

    // Recent episode attribute data
    attributes: {
      episodeNumStart: function(partial, full) {
        return 0;
      },
      episodeNumEnd: function(partial, full) {
        return 0;
      },
      notes: function(partial, full) {
        return '';
      },
      translationType: function(partial, full) {
        return '';
      },
    },
  },

  // Recent show data
  recentShow: {
    // Recent show attribute data
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
      airedStart: function(partial, full) {
        return {
          year: 0,
          month: 0,
          date: 0,
          hour: 0,
          minute: 0
        };
      },
      airedEnd: function(partial, full) {
        return {
          year: 0,
          month: 0,
          date: 0,
          hour: 0,
          minute: 0
        };
      },
      season: function(partial, full) {
        return {
          quarter: '',
          year: 0
        };
      },
      episodeCount: function(partial, full) {
        return 0;
      },
      broadcastInterval: function(partial, full) {
        return 0;
      },
      episodeDuration: function(partial, full) {
        return 0;
      },
      rating: function(partial, full) {
        return '';
      },
    },

    // Recent show thumbnail data
    thumbnails: {
      rowSelector: '',
      getUrl: function (partial, full) {
        return '';
      },
    },
  },
};
