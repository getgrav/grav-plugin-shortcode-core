window.nextgenEditor.addShortcode('color', {
  type: 'inline',
  plugin: 'shortcode-core',
  title: 'Color',
  button: {
    group: 'shortcode-core',
    label: 'Color',
  },
  attributes: {
    color: {
      type: String,
      title: 'Color',
      bbcode: true,
      widget: 'input-text',
      default: '',
    },
  },
  content({ attributes }) {
    return `<span style="color:${attributes.color}">{{content_editable}}</span>`;
  },
});
