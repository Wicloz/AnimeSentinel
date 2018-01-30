import {Shows} from "../api/shows/shows";

export default class ScrapingHelpers {
  static replaceDescriptionCutoff(description, oldCutoff) {
    return description.replaceEnd(oldCutoff, Shows.descriptionCutoff);
  }
}
