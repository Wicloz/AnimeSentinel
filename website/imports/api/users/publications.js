Meteor.publish(null, function() {
  if (!this.userId) {
    return this.ready();
  }
  return Meteor.users.find(this.userId, {
    fields: {
      storage: true,
      malCanRead: true,
      malCanWrite: true,
      lastMalUpdateStart: true,
      lastMalUpdateEnd: true
    }
  });
});
