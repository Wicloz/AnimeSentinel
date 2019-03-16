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

  // When the show is found
  this.autorun(() => {
    let show = Shows.findOne(FlowRouter.getParam('showId'));
    if (show) {
      Session.set('PageTitle', show.name);
      this.subscribe('thumbnails.withHashes', show.thumbnails);
      if (show.canHaveWatchState()) {
        this.subscribe('watchStates.currentUserUnique', show.malId);
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

  'mouseout .mal-widget-status'(event) {
    $('.mal-widget-episodes > iframe, .mal-widget-score > iframe').attr('src', (index, attr) => {
      return attr;
    });
  },

  'mouseout .mal-widget-episodes'(event) {
    $('.mal-widget-status > iframe, .mal-widget-score > iframe').attr('src', (index, attr) => {
      return attr;
    });
  },

  'mouseout .mal-widget-score'(event) {
    $('.mal-widget-status > iframe, .mal-widget-episodes > iframe').attr('src', (index, attr) => {
      return attr;
    });
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
      // Add required ids
      doc.malId = Shows.findOne(FlowRouter.getParam('showId')).malId;
      doc.userId = Meteor.userId();
      // Return
      return doc;
    },

    onSubmit: function(insertDoc) {
      Meteor.call('watchStates.changeWatchState', insertDoc, (error) => {
        this.done(error);
      });
      return false;
    }
  }
});
