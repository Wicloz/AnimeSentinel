import './tooltippedIcon.html';

Template.components_tooltippedIcon.helpers({
  getId() {
    return Template.findState(this).get('id');
  }
});

Template.components_tooltippedIcon.events({
  'show.bs.tooltip .tooltip-icon'(event) {
    if ($(event.target).attr('id') === Template.findState(this).get('id')) {
      Template.findState(this).set('shown', true);
    }
  },

  'hide.bs.tooltip .tooltip-icon'(event) {
    if ($(event.target).attr('id') === Template.findState(this).get('id')) {
      Template.findState(this).set('shown', false);
    }
  }
});

Template.components_tooltippedIcon.onCreated(function () {
  // Local variables
  Template.makeState({
    id: undefined,
    shown: false
  });

  // When the id changes
  this.autorun(() => {
    if (Template.currentData().id) {
      Template.findState(this).set('id', Template.currentData().id);
    } else if (!Template.findState(this).get('id')) {
      Template.findState(this).set('id', createUniqueId());
    }
  });
});

Template.components_tooltippedIcon.onRendered(function () {
  // Enable the tooltip
  $('#' + Template.findState(this).get('id')).tooltip();

  // When the position or text changes
  this.positionOld = undefined;
  this.textOld = undefined;
  this.autorun(() => {
    if (this.textOld !== Template.currentData().text || this.positionOld !== Template.currentData().position) {
      Tracker.afterFlush(() => {
        if (Template.findState(this).get('shown')) {
          $('#' + Template.findState(this).get('id')).tooltip('show');
        }
      });
      this.textOld = Template.currentData().text;
      this.positionOld = Template.currentData().position;
    }
  });
});

Template.components_tooltippedIcon.onDestroyed(function () {
  // Remove the tooltip
  $('#' + Template.findState(this).get('id')).tooltip('dispose');
});
