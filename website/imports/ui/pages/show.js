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

  this.recursivelySubscribe = function(show, direction, visitedIds=[]) {
    if (show) {
      visitedIds.push(show._id);
      Tracker.nonreactive(() => {
        Meteor.call('shows.attemptUpdate', show._id);
      });
      this.subscribe('shows.withIds', show.relatedShows.pluck('showId'));
      show.relatedShows.getPartialObjects({
        relation: direction
      }).forEach((related) => {
        if (!visitedIds.includes(related.showId)) {
          this.recursivelySubscribe(Shows.findOne(related.showId), direction, visitedIds);
        }
      });
    }
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
      if (typeof show.malId !== 'undefined' && Meteor.userId()) {
        this.subscribe('watchStates.currentUserUnique', show.malId);
      }
    }
  });

  // Recursively subscribe to all prequels and sequels
  this.autorun(() => {
    let visitedIds = [];
    this.recursivelySubscribe(Shows.findOne(FlowRouter.getParam('showId')), 'prequel', visitedIds);
    this.recursivelySubscribe(Shows.findOne(FlowRouter.getParam('showId')), 'sequel', visitedIds);
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
