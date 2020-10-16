const Widget = window.nextgenEditor.classes.widget.class;
const { toWidget, toWidgetEditable } = window.nextgenEditor.classes.widget.utils;

window.nextgenEditor.addPlugin('GravShortcodeCoreConvertersBlock', {
  requires: [Widget],
  init() {
    this.editor.model.schema.register('shortcode-block', {
      isBlock: true,
      isObject: true,
      allowWhere: '$block',
      allowContentOf: '$root',
      allowAttributes: [
        'name',
        'attributes',
        'class',
      ],
    });

    this.editor.conversion.for('upcast').elementToElement({
      view: 'shortcode-block',
      model(viewElement, modelWriter) {
        return modelWriter.createElement('shortcode-block', viewElement.getAttributes());
      },
    });

    this.editor.conversion.for('dataDowncast').elementToElement({
      model: 'shortcode-block',
      view(modelElement, viewWriter) {
        return viewWriter.createContainerElement('shortcode-block', modelElement.getAttributes());
      },
    });

    this.editor.conversion.for('editingDowncast').elementToElement({
      model: 'shortcode-block',
      view(modelElement, viewWriter) {
        const container = viewWriter.createContainerElement('shortcode-block', modelElement.getAttributes());
        return toWidget(container, viewWriter);
      },
    });

    this.editor.model.schema.register('shortcode-block-editable', {
      isLimit: true,
      allowWhere: '$block',
      allowContentOf: '$root',
    });

    this.editor.conversion.for('upcast').elementToElement({
      view: 'shortcode-block-editable',
      model: 'shortcode-block-editable',
    });

    this.editor.conversion.for('dataDowncast').elementToElement({
      model: 'shortcode-block-editable',
      view: 'shortcode-block-editable',
    });

    this.editor.conversion.for('editingDowncast').elementToElement({
      model: 'shortcode-block-editable',
      view(modelElement, viewWriter) {
        const container = viewWriter.createEditableElement('shortcode-block-editable', modelElement.getAttributes());
        return toWidgetEditable(container, viewWriter);
      },
    });

    this.editor.model.schema.register('shortcode-block-readonly', {
      isLimit: true,
      allowWhere: '$block',
      allowContentOf: '$root',
    });

    this.editor.conversion.elementToElement({
      view: 'shortcode-block-readonly',
      model: 'shortcode-block-readonly',
    });
  },
});

window.nextgenEditor.addPlugin('GravShortcodeCoreConvertersInline', {
  requires: [Widget],
  init() {
    this.editor.model.schema.register('shortcode-inline', {
      isObject: true,
      isInline: true,
      allowWhere: '$text',
      allowContentOf: '$block',
      allowAttributes: [
        'name',
        'attributes',
        'class',
      ],
    });

    this.editor.conversion.for('upcast').elementToElement({
      view: 'shortcode-inline',
      model(viewElement, modelWriter) {
        return modelWriter.createElement('shortcode-inline', viewElement.getAttributes());
      },
    });

    this.editor.conversion.for('dataDowncast').elementToElement({
      model: 'shortcode-inline',
      view(modelElement, viewWriter) {
        return viewWriter.createContainerElement('shortcode-inline', modelElement.getAttributes());
      },
    });

    this.editor.conversion.for('editingDowncast').elementToElement({
      model: 'shortcode-inline',
      view(modelElement, viewWriter) {
        const container = viewWriter.createContainerElement('shortcode-inline', modelElement.getAttributes());
        return toWidget(container, viewWriter);
      },
    });

    this.editor.model.schema.register('shortcode-inline-editable', {
      isLimit: true,
      allowWhere: '$text',
      allowContentOf: '$block',
    });

    this.editor.conversion.for('upcast').elementToElement({
      view: 'shortcode-inline-editable',
      model: 'shortcode-inline-editable',
    });

    this.editor.conversion.for('dataDowncast').elementToElement({
      model: 'shortcode-inline-editable',
      view: 'shortcode-inline-editable',
    });

    this.editor.conversion.for('editingDowncast').elementToElement({
      model: 'shortcode-inline-editable',
      view(modelElement, viewWriter) {
        const container = viewWriter.createEditableElement('shortcode-inline-editable', modelElement.getAttributes());
        return toWidgetEditable(container, viewWriter);
      },
    });

    this.editor.model.schema.register('shortcode-inline-readonly', {
      isLimit: true,
      allowWhere: '$text',
      allowContentOf: '$block',
    });

    this.editor.conversion.elementToElement({
      view: 'shortcode-inline-readonly',
      model: 'shortcode-inline-readonly',
    });
  },
});
