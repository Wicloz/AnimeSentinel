import moment from 'moment-timezone';

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

Template.registerHelper('displaySeason', (season) => {
  if (!season || typeof season.quarter === 'undefined' || typeof season.year === 'undefined') {
    return 'unknown season';
  }
  return season.quarter + ' ' + season.year;
});

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
    formatTime = 'HH:mm (z)';
  }

  return moment.utc(date).tz(moment.tz.guess()).format((formatDate ? formatDate : '?') + (formatTime ? ' [at] ' + formatTime : ''));
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
    formatDate += 'DD';
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
    formatTime = 'HH:mm (z)';
  }

  return moment.utc(date).tz(moment.tz.guess()).format((formatDate ? formatDate : '?') + (formatTime ? ' [at] ' + formatTime : ''));
});

Template.registerHelper('displayMinuteInterval', (minutes) => {
  if (!minutes) {
    return 'unknown interval';
  }

  let dayRemainder = minutes % 1440;
  let days = (minutes - dayRemainder) / 1440;
  let hourRemainder = dayRemainder % 60;
  let hours = (dayRemainder - hourRemainder) / 60;
  minutes = hourRemainder;

  let string = '';

  if (days) {
    string += days + ' day' + (days === 1 || days === -1 ? '' : 's');
  }
  if (hours) {
    string += (days ? ', ' : '') + hours + ' hour' + (hours === 1 || hours === -1 ? '' : 's');
  }
  if (minutes) {
    string += (days || hours ? ', ' : '') + minutes + ' minute' + (minutes === 1 || minutes === -1 ? '' : 's');
  }

  return string;
});

Template.registerHelper('$GetStorageItem', (key) => {
  return getStorageItem(key);
});

Template.registerHelper('$dot', (object, key) => {
  return object[key];
});
