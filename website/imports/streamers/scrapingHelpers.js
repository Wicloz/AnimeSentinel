import {Shows} from "../api/shows/shows";
import moment from 'moment-timezone';

export default class ScrapingHelpers {
  static replaceDescriptionCutoff(description, oldCutoff) {
    return description.replaceEnd(oldCutoff, Shows.descriptionCutoff);
  }

  static buildAiringDate(timezone, build) {
    let airingDateResult = {};
    let airingDateMoment = moment.tz(timezone);

    build(airingDateResult, airingDateMoment);

    if (airingDateResult.hour) {
      airingDateMoment.tz('UTC');
    }

    Object.keys(airingDateResult).forEach((key) => {
      if (airingDateResult[key]) {
        airingDateResult[key] = airingDateMoment.get(key);
        if (key === 'month') {
          airingDateResult[key]++;
        }
      }
    });

    return airingDateResult;
  }

  static buildAiringDateFromStandardStrings(timezone, index, stringDates, stringSeason, stringDay, stringTime) {
    if (!timezone) {
      timezone = 'UTC';
    }

    return ScrapingHelpers.buildAiringDate(timezone, (airingDateResult, airingDateMoment) => {

      if (stringDates) {
        let stringDatesBits = stringDates.cleanWhitespace().split(' to ');
        if (stringDatesBits[0]) {
          let stringDatesBitsBits = stringDatesBits[stringDatesBits.length === 2 ? index : 0].replace(/,/g, '').split(' ');
          if (stringDatesBitsBits.length >= 1 && !stringDatesBitsBits[stringDatesBitsBits.length - 1].includes('?')) {
            airingDateMoment.year(stringDatesBitsBits[stringDatesBitsBits.length - 1]);
            airingDateResult.year = true;
          }
          if (stringDatesBitsBits.length >= 2 && !stringDatesBitsBits[0].includes('?')) {
            airingDateMoment.month(stringDatesBitsBits[0]);
            airingDateResult.month = true;
          }
          if (stringDatesBitsBits.length >= 3 && !stringDatesBitsBits[1].includes('?')) {
            airingDateMoment.date(stringDatesBitsBits[1]);
            airingDateResult.date = true;
          }
        }
      }

      if (stringSeason && index === 0) {
        let stringSeasonBits = stringSeason.cleanWhitespace().split(' ');
        if (stringSeasonBits.length === 2) {
          if (!airingDateResult.year) {
            airingDateMoment.year(stringSeasonBits[1]);
            airingDateResult.year = true;
          }
          if (!airingDateResult.month) {
            airingDateMoment.quarter(Shows.validQuarters.indexOf(stringSeasonBits[0]) + 1);
            airingDateResult.month = true;
          }
        }
      }

      if (stringDay && !airingDateResult.date && airingDateResult.month && airingDateResult.year) {
        airingDateMoment.day(stringDay);
        airingDateResult.date = true;
      }

      if (stringTime && airingDateResult.date && airingDateResult.month && airingDateResult.year) {
        airingDateMoment.hour(stringTime.split(':')[0]);
        airingDateResult.hour = true;
        airingDateMoment.minute(stringTime.split(':')[1]);
        airingDateResult.minute = true;
      }

    });
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

    // TODO: Use date information for better matching

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
