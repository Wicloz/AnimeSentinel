import './profile.html';

Template.pages_profile.onCreated(function () {
  // Set page variables
  Session.set('BreadCrumbs', JSON.stringify([]));
  Session.set('PageTitle', 'Manage Profile');

  // Local variables
  this.state = new ReactiveDict();
  this.state.setDefault({
    updateProfileFormError: undefined
  });
});

Template.pages_profile.helpers({
  updateProfileFormError() {
    return Template.instance().state.get('updateProfileFormError');
  },

  showMalUsernameError() {
    return Meteor.user().profile.malUsername && !Meteor.user().malCanRead;
  },

  showMalPasswordError() {
    return Meteor.user().profile.malUsername && Meteor.user().profile.malPassword && !Meteor.user().malCanWrite;
  },

  showMalSuccess() {
    return Meteor.user().profile.malUsername;
  }
});

AutoForm.hooks({
  updateProfileForm: {
    onSubmit: function (insertDoc) {
      this.template.view.parentView.parentView._templateInstance.state.set('updateProfileFormError', undefined);
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
        this.template.view.parentView.parentView._templateInstance.state.set('updateProfileFormError', error.reason);
      }
    }
  }
});
