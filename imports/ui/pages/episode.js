import './episode.html';
import {Episodes} from "../../api/episodes/episodes";
import Streamers from "../../streamers/_streamers";

Template.pages_episode.onCreated(function() {
  this.selectedEpisode = new ReactiveVar(undefined);

  this.autorun(() => {
    this.subscribe('episodes.forEpisode', FlowRouter.getParam('showId'), Number(FlowRouter.getParam('episodeNum')), FlowRouter.getParam('translationType'));
    if (this.subscriptionsReady()) {
      if (!Episodes.queryForEpisode(FlowRouter.getParam('showId'), Number(FlowRouter.getParam('episodeNum')), FlowRouter.getParam('translationType')).count()) {
        FlowRouter.go('notFound');
      }
      this.selectedEpisode.set(Episodes.queryForEpisode(FlowRouter.getParam('showId'), Number(FlowRouter.getParam('episodeNum')), FlowRouter.getParam('translationType')).fetch()[0]._id);
      Session.set('PageTitle', 'Episode ' + FlowRouter.getParam('episodeNum') + ' (' + FlowRouter.getParam('translationType') + ')');
    }
  });
});

Template.pages_episode.helpers({
  selectedEpisode() {
    return Episodes.findOne(Template.instance().selectedEpisode.get());
  },

  streamers() {
    let streamers = [];

    Episodes.queryForEpisode(FlowRouter.getParam('showId'), Number(FlowRouter.getParam('episodeNum')), FlowRouter.getParam('translationType')).forEach((episode) => {
      if (!streamers.hasPartialObjects({id: episode.streamerId})) {
        streamers.push(Streamers.getStreamerWithId(episode.streamerId));
      }
    });

    return streamers;
  }
});

Template.pages_episode.events({
  'click a.collection-item'(event) {
    Template.instance().selectedEpisode.set(Episodes.queryUnique(FlowRouter.getParam('showId'), Number(FlowRouter.getParam('episodeNum')), FlowRouter.getParam('translationType'), event.target.dataset.id).fetch()[0]._id)
  }
});
