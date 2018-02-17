import moment from 'moment-timezone';

Template.registerHelper('displaySeason', (season) => {
  if (!season || typeof season.quarter === 'undefined' || typeof season.year === 'undefined') {
    return 'Unknown';
  }
  return season.quarter + ' ' + season.year;
});

Template.registerHelper('displayAiringDate', (date) => {
  if (!date || (typeof date.year === 'undefined'
      && typeof date.month === 'undefined'
      && typeof date.date === 'undefined'
      && typeof date.hour === 'undefined'
      && typeof date.minute === 'undefined')) {
    return 'Unknown';
  }

  let formatDate = '';
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

  let formatTime = '';
  if (typeof date.hour !== 'undefined' && typeof date.minute !== 'undefined') {
    formatTime = 'HH:mm';
  }

  return moment(date).format((formatDate ? formatDate : '?') + (formatTime ? ' [at] ' + formatTime : ''));
});

Template.registerHelper('displayUploadDate', (date) => {
  if (!date || (typeof date.year === 'undefined'
      && typeof date.month === 'undefined'
      && typeof date.date === 'undefined'
      && typeof date.hour === 'undefined'
      && typeof date.minute === 'undefined')) {
    return 'Unknown';
  }

  let formatDate = '';
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

  let formatTime = '';
  if (typeof date.hour !== 'undefined' && typeof date.minute !== 'undefined') {
    formatTime = 'HH:mm';
  }

  return moment(date).format((formatDate ? formatDate : '?') + (formatTime ? ' [at] ' + formatTime : ''));
});
