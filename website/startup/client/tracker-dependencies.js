invalidateSecond = new Tracker.Dependency();
Meteor.setInterval(() => {
  invalidateSecond.changed();
}, 1000);

invalidateMinute = new Tracker.Dependency();
Meteor.setInterval(() => {
  invalidateMinute.changed();
}, 60000);

invalidateHour = new Tracker.Dependency();
Meteor.setInterval(() => {
  invalidateHour.changed();
}, 3600000);
