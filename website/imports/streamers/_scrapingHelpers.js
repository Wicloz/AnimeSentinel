import {Shows} from "../api/shows/shows";

export default class ScrapingHelpers {
  static processEpisodeNumber(episodeNum) {
    return isNumeric(episodeNum) ? episodeNum : 1;
  }

  static replaceDescriptionCutoff(description, oldCutoff) {
    return description.replaceEnd(oldCutoff, Shows.descriptionCutoff);
  }
}
