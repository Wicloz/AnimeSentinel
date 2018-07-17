import './tooltippedIcon.html';

Template.components_tooltippedIcon.helpers({
  getId() {
    return Template.instance().state.get('id');
  }
});

Template.components_tooltippedIcon.events({
  'show.bs.tooltip .tooltip-icon'(event) {
    if ($(event.target).attr('id') === Template.instance().state.get('id')) {
      Template.instance().state.set('shown', true);
    }
  },

  'hide.bs.tooltip .tooltip-icon'(event) {
    if ($(event.target).attr('id') === Template.instance().state.get('id')) {
      Template.instance().state.set('shown', false);
    }
  }
});

Template.components_tooltippedIcon.onCreated(function () {
  // Local variables
  this.state = new ReactiveDict();
  this.state.setDefault({
    id: undefined,
    shown: false
  });

  // When the id changes
  this.autorun(() => {
    if (Template.currentData().id) {
      this.state.set('id', Template.currentData().id);
    } else if (!this.state.get('id')) {
      this.state.set('id', createUniqueId());
    }
  });
});

Template.components_tooltippedIcon.onRendered(function () {
  // Enable the tooltip
  $('#' + this.state.get('id')).tooltip();

  // When the position or text changes
  this.positionOld = undefined;
  this.textOld = undefined;
  this.autorun(() => {
    if (this.textOld !== Template.currentData().text || this.positionOld !== Template.currentData().position) {
      Tracker.afterFlush(() => {
        if (this.state.get('shown')) {
          $('#' + this.state.get('id')).tooltip('show');
        }
      });
      this.textOld = Template.currentData().text;
      this.positionOld = Template.currentData().position;
    }
  });
});

Template.components_tooltippedIcon.onDestroyed(function () {
  // Remove the tooltip
  $('#' + this.state.get('id')).tooltip('dispose');
});
