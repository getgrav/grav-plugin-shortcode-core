window.nextgenEditor.addShortcode('section', {
  type: 'block',
  plugin: 'shortcode-core',
  title: 'Section',
  button: {
    group: 'shortcode-core',
    label: 'Section',
  },
  attributes: {
    name: {
      type: String,
      title: 'Name',
      widget: 'input-text',
      default: '',
    },
  },
  titlebar({ attributes }) {
    return attributes.name
      ? `name: <strong>${attributes.name}</strong>`
      : '';
  },
  content() {
    return '{{content_editable}}';
  },
});
