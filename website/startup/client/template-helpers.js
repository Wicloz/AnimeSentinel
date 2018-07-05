import moment from 'moment-timezone';
import ScrapingHelpers from '../../imports/streamers/scrapingHelpers';

Template.parentInstance = function(levels) {
  return this.instance().parentTemplate(levels);
};

Blaze.TemplateInstance.prototype.parentTemplate = function(levels) {
  let view = this.view;
  if (typeof levels === 'undefined') {
    levels = 1;
  }
  while (view) {
    if (view.name.substring(0, 9) === 'Template.' && !(levels--)) {
      return view.templateInstance();
    }
    view = view.parentView;
  }
};

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

Template.registerHelper('displayUploadDate', (date) => {
  if (!date || (typeof date.year === 'undefined'
      && typeof date.month === 'undefined'
      && typeof date.date === 'undefined'
      && typeof date.hour === 'undefined'
      && typeof date.minute === 'undefined')) {
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
  if (typeof date.hour !== 'undefined' && typeof date.minute !== 'undefined') {
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

Template.registerHelper('displaySeason', (season) => {
  if (!season || typeof season.quarter === 'undefined' || typeof season.year === 'undefined') {
    return 'unknown season';
  }

  return season.quarter + ' ' + season.year;
});

Template.registerHelper('displayInterval', (milliseconds, suffix) => {
  if (suffix !== true) {
    suffix = false;
  }

  if (!isNumeric(milliseconds)) {
    return (suffix ? 'in ' : '') + 'unknown interval';
  }

  return moment.duration(milliseconds).humanize(suffix);
});

Template.registerHelper('displayDuration', (milliseconds) => {
  if (!isNumeric(milliseconds)) {
    return 'unknown duration';
  }

  return moment.duration(milliseconds).humanize();
});

Template.registerHelper('displayTranslationType', (translationType) => {
  return ScrapingHelpers.makeTranslationTypeFancy(translationType);
});

Template.registerHelper('$GetStorageItem', (key) => {
  return getStorageItem(key);
});

Template.registerHelper('$dot', (object, key) => {
  if (_.isObject(object) && object.hasOwnProperty(key)) {
    return object[key];
  } else {
    return undefined;
  }
});

Template.registerHelper('$in', (item, ...list) => {
  list.pop();
  if (list.length === 1 && _.isArray(list[0])) {
    list = list[0];
  }
  return list.includes(item);
});

Template.registerHelper('$nin', (item, ...list) => {
  list.pop();
  if (list.length === 1 && _.isArray(list[0])) {
    list = list[0];
  }
  return !list.includes(item);
});

Template.registerHelper('$firstActive', (index, start) => {
  if (!isNumeric(start)) {
    start = 0;
  }
  return index === start ? 'active' : '';
});

Template.registerHelper('$addOne', (value) => {
  return value + 1;
});
