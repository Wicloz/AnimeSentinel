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
  // Meteor.call('episodes.findRecentEpisodes');

  // Subscribe to recent episodes
  this.autorun(() => {
    this.subscribe('episodes.recent', this.state.get('episodesLimit'));
  });

  // Subscribe to shows for episodes
  this.autorun(() => {
    this.subscribe('shows.withIds', Episodes.queryRecent(this.state.get('episodesLimit')).fetch().pluck('showId'));
  });

  // Subscribe to thumbnails for all shows
  this.autorun(() => {
    this.subscribe('thumbnails.withHashes', Shows.queryWithIds(Episodes.queryRecent(this.state.get('episodesLimit')).fetch().pluck('showId')).fetch().reduce((total, show) => {
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

    Episodes.queryRecent(Template.instance().state.get('episodesLimit')).forEach((episode) => {
      let selector = {
        showId: episode.showId,
        translationType: episode.translationType,
        episodeNumStart: episode.episodeNumStart,
        episodeNumEnd: episode.episodeNumEnd,
        notes: episode.notes
      };

      if (episodes.hasPartialObjects(selector)) {
        let other = episodes.getPartialObjects(selector)[0];

        if (other.streamers.every((streamer) => {
            return streamer.id !== episode.streamerId;
          })) {
          other.streamers.push(Streamers.getSimpleStreamerById(episode.streamerId));
        }

        episodes = episodes.replacePartialObjects(selector, other);
      }

      else if ((episodes.empty() || episodes[episodes.length - 1].showId !== episode.showId || episodes[episodes.length - 1].translationType !== episode.translationType)
        && (episodes.length < 2 || episodes[episodes.length - 1].showId !== episode.showId || episodes[episodes.length - 2].showId !== episode.showId || episodes[episodes.length - 2].translationType !== episode.translationType)) {
        episode.show = Shows.findOne(episode.showId);
        episode.streamers = [Streamers.getSimpleStreamerById(episode.streamerId)];
        episodes.push(episode);
      }
    });

    return episodes;
  },

  episodesLoading() {
    return !Template.instance().subscriptionsReady() || Episodes.queryRecent(Template.instance().state.get('episodesLimit')).count() >= Template.instance().state.get('episodesLimit');
  }
});

Template.pages_recent.events({
  'appear #load-more-episodes'(event) {
    if (Template.instance().subscriptionsReady()) {
      Template.instance().state.set('episodesLimit', Template.instance().state.get('episodesLimit') + 100);
    }
  }
});
