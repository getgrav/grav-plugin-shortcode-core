window.nextgenEditor.addShortcode('span', {
  type: 'inline',
  plugin: 'shortcode-core',
  title: 'Span',
  button: {
    group: 'shortcode-core',
    label: 'Span',
  },
  attributes: {
    id: {
      type: String,
      title: 'ID',
      widget: 'input-text',
      default: '',
    },
    class: {
      type: String,
      title: 'Class',
      widget: 'input-text',
      default: '',
    },
    style: {
      type: String,
      title: 'Style',
      widget: 'input-text',
      default: '',
    },
  },
  content({ attributes }) {
    const id = attributes.id || '';
    const cclass = attributes.class || '';
    const style = attributes.style || '';

    return `<span id="${id}" class="${cclass}" style="${style}">{{content_editable}}</span>`;
  },
});
