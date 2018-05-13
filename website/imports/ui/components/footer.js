import './footer.html';

Template.components_footer.onCreated(function () {
  // Set the initially selected translation type
  this.autorun(() => {
    if (typeof getStorageItem('SelectedTranslationType') === 'undefined') {
      setStorageItem('SelectedTranslationType', 'sub');
    }
  });
});

AutoForm.hooks({
  translationTypeSelectionForm: {
    onSubmit(insertDoc) {
      setStorageItem('SelectedTranslationType', insertDoc.translationType);
      this.done();
      return false;
    }
  }
});
