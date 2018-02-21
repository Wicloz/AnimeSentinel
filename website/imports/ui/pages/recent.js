import './recent.html';
import {Episodes} from '../../api/episodes/episodes';
import {Shows} from '../../api/shows/shows';
import Streamers from '../../streamers/streamers';

Template.pages_recent.onCreated(function () {
  // Set page variables
  Session.set('BreadCrumbs', JSON.stringify([]));
  Session.set('PageTitle', 'Recently Aired');

  // Local variables
  this.state = new ReactiveDict();
  this.state.setDefault({
    episodesLimit: 100
  });

  // Find all recent episodes
  // TODO: Make this work better (remove / make caching + indicator work)
  Meteor.call('episodes.findRecentEpisodes');

  // Subscribe to latest episodes
  this.autorun(() => {
    this.subscribe('episodes.latest', this.state.get('episodesLimit'));
  });

  // Subscribe to shows for episodes
  this.autorun(() => {
    this.subscribe('shows.withIds', Episodes.queryLatest(this.state.get('episodesLimit')).fetch().pluck('showId'));
  });

  // Subscribe to thumbnails for all shows
  this.autorun(() => {
    this.subscribe('thumbnails.withHashes', Shows.queryWithIds(Episodes.queryLatest(this.state.get('episodesLimit')).fetch().pluck('showId')).fetch().reduce((total, show) => {
      return total.concat(show.thumbnails);
    }, []));
  });
});

Template.pages_recent.onRendered(function() {
  $('#load-more-episodes').appear();
});

Template.pages_recent.helpers({
  episodesJoined() {
    let episodes = [];

    Episodes.queryLatest(Template.instance().state.get('episodesLimit')).forEach((episode) => {
      if (!episodes.hasPartialObjects({
          showId: episode.showId,
          translationType: episode.translationType,
          episodeNumStart: episode.episodeNumStart,
          episodeNumEnd: episode.episodeNumEnd
        })) {
        episode.show = Shows.findOne(episode.showId);
        episode.streamer = Streamers.getSimpleStreamerById(episode.streamerId);
        episodes.push(episode);
      }
    });

    return episodes;
  },

  episodesLoading() {
    return !Template.instance().subscriptionsReady() || Episodes.queryLatest(Template.instance().state.get('episodesLimit')).count() >= Template.instance().state.get('episodesLimit');
  }
});

Template.pages_recent.events({
  'appear #load-more-episodes'(event) {
    Template.instance().state.set('episodesLimit', Template.instance().state.get('episodesLimit') + 100);
  }
});
