import './show.html';
import {Shows} from '/imports/api/shows/shows.js';

Template.pages_show.onCreated(function() {
  // Set page variables
  Session.set('BreadCrumbs', JSON.stringify([{
    name: 'Anime',
    url: FlowRouter.path('search')
  }]));

  // Local functions
  this.fixCarouselHeight = function() {
    Tracker.afterFlush(() => {
      $('#thumbnailCarousel').height(Math.max(...$('#thumbnailCarouselFake img').map(function() {
        return $(this).height();
      }).toArray(), 0));
    });
  };

  this.restartCarousel = function() {
    Tracker.afterFlush(() => {
      let carouselElement = $('#thumbnailCarousel');
      carouselElement.carousel('dispose');
      carouselElement.carousel();
    });
  };

  this.isUpdating = function() {
    let show = Shows.findOne(FlowRouter.getParam('showId'));
    return show && show.locked();
  };

  this.isRelatedUpdating = function(show, direction, visitedIds=[]) {
    if (!show) {
      return true;
    }
    visitedIds.push(show._id);

    return show.locked() || show.relatedShows.filter((related) => {
      return related.relation === direction && !visitedIds.includes(related.showId);
    }).some((related) => {
      return this.isRelatedUpdating(Shows.findOne(related.showId), direction, visitedIds);
    }) || show.relatedShows.filter((related) => {
      return !['prequel', 'sequel'].includes(related.relation) && !visitedIds.includes(related.showId);
    }).some((related) => {
      return !Shows.findOne(related.showId);
    });
  };

  this.recursivelySubscribe = function(show, direction, visitedIds=[]) {
    if (!show) {
      return;
    }
    visitedIds.push(show._id);

    Tracker.nonreactive(() => {
      Meteor.call('shows.attemptUpdate', show._id);
    });
    this.subscribe('shows.withIds', show.relatedShows.pluck('showId'));
    show.relatedShows.filter((related) => {
      return related.relation === direction && !visitedIds.includes(related.showId);
    }).forEach((related) => {
      this.recursivelySubscribe(Shows.findOne(related.showId), direction, visitedIds);
    });
  };

  // Local variables
  Template.makeState({
    malStatusDoc: Schemas.WatchState.clean({}),
    malStatusInDB: false
  });

  // When the user is found
  this.autorun(() => {
    if (Meteor.userId()) {
      Template.findState(this).set('malStatusDoc', Object.assign(Template.findState(this).get('malStatusDoc'), {
        userId: Meteor.userId()
      }));
    }
  });

  // Subscribe based on the show id
  this.autorun(() => {
    this.subscribe('shows.withId', FlowRouter.getParam('showId'));
    this.subscribe('episodes.forShow', FlowRouter.getParam('showId'));
  });

  // Check if the show exists
  this.autorun(() => {
    if (this.subscriptionsReady() && !Shows.findOne(FlowRouter.getParam('showId'))) {
      FlowRouter.go('notFound');
    }
  });

  this.autorun(() => {
    // When the show is found
    let show = Shows.findOne(FlowRouter.getParam('showId'));
    if (show) {
      Session.set('PageTitle', show.name);
      Template.findState(this).set('malStatusDoc', Object.assign(Template.findState(this).get('malStatusDoc'), {
        malId: show.malId
      }));
      this.subscribe('thumbnails.withHashes', show.thumbnails);
      if (show.canHaveWatchState()) {
        this.subscribe('watchStates.currentUserUnique', show.malId);
      }

      // When the watchState is found
      let watchState = show.watchState();
      if (watchState) {
        Template.findState(this).set('malStatusDoc', watchState);
        Template.findState(this).set('malStatusInDB', true);
      } else {
        Template.findState(this).set('malStatusInDB', false);
      }
    }
  });

  // Recursively subscribe to all prequels and sequels
  this.autorun(() => {
    let visitedIds = [];
    let mainShow = Shows.findOne(FlowRouter.getParam('showId'));
    this.recursivelySubscribe(mainShow, 'prequel', visitedIds);
    this.recursivelySubscribe(mainShow, 'sequel', visitedIds);
  });

  // Set 'LoadingBackground' parameter
  this.autorun(() => {
    Session.set('LoadingBackground', this.isUpdating());
  });
});

Template.pages_show.onRendered(function() {
  // When the selected translation type changes
  this.autorun(() => {
    $('#episodeList-collapse-' + getStorageItem('SelectedTranslationType')).collapse('show');
  });

  // When the collapse state of the related shows changes
  this.autorun(() => {
    $('#relatedShowsCollapse').collapse(getStorageItem('RelatedShowsCollapsed') ? 'hide' : 'show');
  });

  // When thumbnails change
  this.lastThumbnails = undefined;
  this.autorun(() => {
    let show = Shows.findOne(FlowRouter.getParam('showId'));
    let currentThumbnails = show ? show.thumbnailUrls() : undefined;
    if (!_.isEqual(this.lastThumbnails, currentThumbnails)) {
      this.fixCarouselHeight();
      this.restartCarousel();
      this.lastThumbnails = currentThumbnails;
    }
  });

  // When the window size or orientation changes
  $(window).on('resize orientationchange', this.fixCarouselHeight);
});

Template.pages_show.onDestroyed(function() {
  Session.set('LoadingBackground', false);
  $(window).off('resize orientationchange', this.fixCarouselHeight);
});

Template.pages_show.events({
  'load #thumbnailCarouselFake img'(event) {
    Template.instance().fixCarouselHeight();
  },

  'hide.bs.collapse #relatedShowsCollapse'(event) {
    setStorageItem('RelatedShowsCollapsed', true);
  },

  'show.bs.collapse #relatedShowsCollapse'(event) {
    setStorageItem('RelatedShowsCollapsed', false);
  },

  'mouseout .show-mal-widget-status'(event) {
    $('.show-mal-widget-episodes > iframe, .show-mal-widget-score > iframe').attr('src', (index, attr) => {
      return attr;
    });
  },

  'mouseout .show-mal-widget-episodes'(event) {
    $('.show-mal-widget-status > iframe, .show-mal-widget-score > iframe').attr('src', (index, attr) => {
      return attr;
    });
  },

  'mouseout .show-mal-widget-score'(event) {
    $('.show-mal-widget-status > iframe, .show-mal-widget-episodes > iframe').attr('src', (index, attr) => {
      return attr;
    });
  },

  'click .btn-submit-status'(event) {
    Template.findState(this).set('malStatusInDB', true);
  },

  'click .btn-remove-status'(event) {
    Meteor.call('watchStates.removeWatchState', Template.findState(this).get('malStatusDoc'));
  }
});

Template.pages_show.helpers({
  show() {
    return Shows.findOne(FlowRouter.getParam('showId'));
  },

  queryType(type) {
    return {
      types: [type]
    };
  },

  queryGenre(genre) {
    return {
      genres: [genre]
    };
  },

  querySeason(season) {
    return {
      season: season.quarter,
      year: season.year
    };
  },

  relatedShowsLoading() {
    let visitedIds = [];
    let mainShow = Shows.findOne(FlowRouter.getParam('showId'));
    return Template.instance().isRelatedUpdating(mainShow, 'prequel', visitedIds) || Template.instance().isRelatedUpdating(mainShow, 'sequel', visitedIds);
  },

  malStatusDoc() {
    return Template.findState(this).get('malStatusDoc');
  }
});

Template.pages_show_episodes.helpers({
  show() {
    return Shows.findOne(FlowRouter.getParam('showId'));
  },

  episodesLoading() {
    return !Template.parentInstance().subscriptionsReady() || Template.parentInstance().isUpdating();
  }
});

AutoForm.hooks({
  malStatusForm: {
    formToDoc: function(doc) {
      // Remove score if empty
      if (!doc.hasOwnProperty('score')) {
        doc.score = null;
      }
      // Set missing values
      doc = Object.assign(Template.findState(this).get('malStatusDoc'), doc);

      // Get the show and previous state
      let show = Shows.findOne(FlowRouter.getParam('showId'));
      let prevDoc = Template.findState(this).get('malStatusDoc');
      // Set status based on episodes
      if (doc.episodesWatched !== prevDoc.episodesWatched && doc.episodesWatched >= show.episodeCount) {
        doc.status = 'completed';
      }
      // Set episodes based on status
      else if (doc.status !== prevDoc.status && doc.status === 'completed' && doc.episodesWatched <= show.episodeCount) {
        doc.episodesWatched = show.episodeCount;
      }
      // Reset episodes when rewatching
      else if (doc.rewatching !== prevDoc.rewatching && doc.rewatching === true) {
        doc.episodesWatched = 0;
      }

      // Disable rewatching if needed
      if (doc.status !== 'watching') {
        doc.rewatching = false;
      }
      // Return
      return doc;
    },

    onSubmit: function(insertDoc) {
      Template.findState(this).set('malStatusDoc', insertDoc);
      if (Template.findState(this).get('malStatusInDB')) {
        Meteor.call('watchStates.changeWatchState', Template.findState(this).get('malStatusDoc'), (error) => {
          this.done(error);
        });
      } else {
        this.done();
      }
      return false;
    }
  }
});
