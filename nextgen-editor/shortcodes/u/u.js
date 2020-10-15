window.nextgenEditor.addShortcode('u', {
  type: 'inline',
  plugin: 'shortcode-core',
  title: 'Underline',
  button: {
    group: 'shortcode-core',
    label: 'Underline',
  },
  content() {
    return '<span style="text-decoration:underline">{{content_editable}}</span>';
  },
});
