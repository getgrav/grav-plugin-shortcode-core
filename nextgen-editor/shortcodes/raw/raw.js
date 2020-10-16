window.nextgenEditor.addShortcode('raw', {
  type: 'block',
  plugin: 'shortcode-core',
  title: 'Raw',
  button: {
    group: 'shortcode-core',
    label: 'Raw',
  },
  content() {
    return '{{content_editable}}';
  },
});
