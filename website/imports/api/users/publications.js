Meteor.publish(null, function() {
  if (!this.userId) {
    return this.ready();
  }
  return Meteor.users.find(this.userId, {
    fields: {
      storage: true,
      malUsernameValid: true,
      malPasswordValid: true
    }
  });
});
