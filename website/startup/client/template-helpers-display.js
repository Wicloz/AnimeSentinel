import moment from 'moment-timezone';
import ScrapingHelpers from '../../imports/streamers/scrapingHelpers';

Template.registerHelper('displayAiringDate', (date) => {
  if (!date || (typeof date.year === 'undefined'
    && typeof date.month === 'undefined'
    && typeof date.date === 'undefined'
    && typeof date.hour === 'undefined'
    && typeof date.minute === 'undefined')) {
    return 'unknown date';
  }

  let formatDate = undefined;
  if (typeof date.year !== 'undefined') {
    formatDate = 'YYYY';
    if (typeof date.month !== 'undefined') {
      if (typeof date.date !== 'undefined') {
        formatDate = 'MMMM Do, ' + formatDate;
      } else {
        formatDate = 'MMMM ' + formatDate;
      }
    }
  }

  let formatTime = undefined;
  if (typeof date.hour !== 'undefined' && typeof date.minute !== 'undefined') {
    formatTime = 'HH:mm (Z)';
  }

  return moment.fromUtc(date).format((formatDate ? formatDate : '?') + (formatTime ? ' [at] ' + formatTime : ''));
});

Template.registerHelper('displayUploadDate', (date, short) => {
  if (short !== true) {
    short = false;
  }

  if (!date || (
    typeof date.year === 'undefined' &&
    typeof date.month === 'undefined' &&
    typeof date.date === 'undefined' &&
    (short || (typeof date.hour === 'undefined' && typeof date.minute === 'undefined'))
  )) {
    return 'unknown date';
  }

  let formatDate = '';
  if (typeof date.date !== 'undefined') {
    formatDate += 'dddd DD';
  } else {
    formatDate += '??';
  }
  formatDate += '/';
  if (typeof date.month !== 'undefined') {
    formatDate += 'MM';
  } else {
    formatDate += '??';
  }
  formatDate += '/';
  if (typeof date.year !== 'undefined') {
    formatDate += 'YYYY';
  } else {
    formatDate += '????';
  }

  let formatTime = undefined;
  if (!short && typeof date.hour !== 'undefined' && typeof date.minute !== 'undefined') {
    formatTime = 'HH:mm (Z)';
  }

  return moment.fromUtc(date).format((formatDate ? formatDate : '?') + (formatTime ? ' [at] ' + formatTime : ''));
});

Template.registerHelper('displayBroadcastDay', (date) => {
  if (!date || (typeof date.date === 'undefined' && typeof date.hour === 'undefined' && typeof date.minute === 'undefined')) {
    return 'unknown day';
  }

  let formatDate = typeof date.date === 'undefined' ? undefined : 'dddd[s]';

  let formatTime = undefined;
  if (typeof date.hour !== 'undefined' && typeof date.minute !== 'undefined') {
    formatTime = 'HH:mm (Z)';
  }

  return moment.fromUtc(date).format((formatDate ? formatDate : '?') + (formatTime ? ' [at] ' + formatTime : ''));
});

Template.registerHelper('displayInterval', (milliseconds, suffix) => {
  if (suffix !== true) {
    suffix = false;
  }

  if (!isNumeric(milliseconds)) {
    return (suffix ? 'in ' : '') + 'unknown interval';
  }

  if (milliseconds === 0) {
    return suffix ? 'now' : 'zero seconds';
  }

  let niceDate = moment.duration(milliseconds).humanize(suffix);
  if (!suffix) {
    niceDate = niceDate.replaceStart('a ', '');
  }

  return niceDate;
});

Template.registerHelper('displayDuration', (milliseconds) => {
  if (!isNumeric(milliseconds)) {
    return 'unknown duration';
  }

  if (milliseconds === 0) {
    return 'no duration';
  }

  return moment.duration(milliseconds).humanize();
});

Template.registerHelper('displaySeason', (season) => {
  if (!season || typeof season.quarter === 'undefined' || typeof season.year === 'undefined') {
    return 'unknown season';
  }

  return season.quarter + ' ' + season.year;
});

Template.registerHelper('displayTranslationType', (translationType) => {
  return ScrapingHelpers.makeTranslationTypeFancy(translationType);
});
