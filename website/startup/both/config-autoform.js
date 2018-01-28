import SimpleSchema from 'simpl-schema';

SimpleSchema.extendOptions(['autoform']);

if (Meteor.isClient) {
  AutoForm.setDefaultTemplate('materialize');
}
