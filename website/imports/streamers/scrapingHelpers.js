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

  static queryMatchingShows(collection, show, id=undefined) {
    // Create query bits
    let orBits = [{
      $and: [{
        _id: {$exists: false}
      }, {
        _id: {$exists: true}
      }]
    }];

    // Test with 'altNames'
    if (show.altNames) {
      orBits.push({
        name: {
          $in: show.altNames.map((altName) => {
            return this.prepareAltForMatching(altName);
          })
        }
      });
    }

    // Test with 'name'
    if (show.name) {
      orBits.push({
        altNames: this.prepareAltForMatching(show.name)
      });
    }

    // Test with 'malId'
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
    if (id) {
      return collection.find({
        $and: [{
          $or: orBits
        }, {
          _id: id
        }]
      });
    } else {
      return collection.find({
        $or: orBits
      });
    }
  }
}
