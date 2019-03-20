import './profile.html';

Template.pages_profile.onCreated(function () {
  // Set page variables
  Session.set('BreadCrumbs', JSON.stringify([]));
  Session.set('PageTitle', 'Manage Profile');

  // Local variables
  Template.makeState({
    updateProfileFormError: undefined
  });
});

Template.pages_profile.helpers({
  updateProfileFormError() {
    return Template.findState(this).get('updateProfileFormError');
  },

  showMalUsernameError() {
    return Meteor.user() && Meteor.user().profile.malUsername && !Meteor.user().malCanRead;
  },

  showMalPasswordError() {
    return Meteor.user() && Meteor.user().profile.malUsername && Meteor.user().profile.malPassword && !Meteor.user().malCanWrite;
  },

  showMalSuccess() {
    return Meteor.user() && Meteor.user().profile.malUsername;
  }
});

AutoForm.hooks({
  updateProfileForm: {
    onSubmit: function (insertDoc) {
      Template.findState(this).set('updateProfileFormError', undefined);
      if (typeof insertDoc.profile === 'undefined') {
        insertDoc.profile = {};
      }
      Meteor.call('users.changeCurrentUserInfo', insertDoc, (error) => {
        this.done(error);
      });
      return false;
    },
    onError: function (formType, error) {
      if (error.isClientSafe) {
        Template.findState(this).set('updateProfileFormError', error.reason);
      }
    }
  }
});
