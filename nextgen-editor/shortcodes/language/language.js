window.nextgenEditor.addShortcode('lang', {
  type: 'block',
  plugin: 'shortcode-core',
  title: 'Language',
  button: {
    group: 'shortcode-core',
    label: 'Language',
  },
  attributes: {
    lang: {
      type: String,
      title: 'Language',
      bbcode: true,
      widget: 'input-text',
      default: 'en',
    },
  },
  titlebar({ attributes }) {
    return `language: <strong>${attributes.lang}</strong>`;
  },
  content() {
    return '{{content_editable}}';
  },
});
