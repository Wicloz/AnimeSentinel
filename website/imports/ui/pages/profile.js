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
  }
});

AutoForm.hooks({
  updateProfileForm: {
    onSubmit: function (insertDoc) {
      this.template.view.parentView.parentView._templateInstance.state.set('updateProfileFormError', undefined);
      Meteor.call('users.updateCurrentUser', insertDoc, (error) => {
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
