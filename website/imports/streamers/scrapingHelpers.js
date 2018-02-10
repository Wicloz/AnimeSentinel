import {Shows} from "../api/shows/shows";

export default class ScrapingHelpers {
  static replaceDescriptionCutoff(description, oldCutoff) {
    return description.replaceEnd(oldCutoff, Shows.descriptionCutoff);
  }

  static prepareAltForMatching(altName) {
    // allow matching of 'and', 'to' and 'und' to each other
    // allow matching of '&' to synonymous words
    // allow matching of ': ' to ' '
    let regex = '^' + RegExp.escape(altName).replace(/((?:\\:)? ?)\band\b((?:\\:)? ?)|((?:\\:)? ?)\bund\b((?:\\:)? ?)|((?:\\:)? ?)\bto\b((?:\\:)? ?)|((?:\\:)? ?)&((?:\\:)? ?)/g, '(?:$1$3$5$7 ?and ?$2$4$6$8|$1$3$5$7 ?und ?$2$4$6$8|$1$3$5$7 ?to ?$2$4$6$8|(?:$1$3$5$7)?&(?:$2$4$6$8)?)').replace(/\\: | /g, '(?:\\: | )') + '$';
    // allow case insensitive matching
    return new RegExp(regex, 'i');
  }

  static queryMatchingShows(collection, show) {
    // Validate
    Schemas.Show.validate(show);

    // Process names to regex
    let altNames = show.altNames.map((altName) => {
      return this.prepareAltForMatching(altName);
    });
    let name = this.prepareAltForMatching(show.name);

    // Create query bits
    let orBits = [];

    orBits.push({
      name: {
        $in: altNames
      }
    });
    orBits.push({
      altNames: name
    });

    if (typeof show.malId !== 'undefined') {
      orBits = orBits.map((orBit) => {
        orBit.malId = {
          $exists: false
        };
        return orBit;
      });
      orBits.push({
        malId: show.malId
      });
    }

    // Return results cursor
    return collection.find({
      $or: orBits
    });
  }
}
