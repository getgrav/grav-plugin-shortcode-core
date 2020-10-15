window.nextgenEditor.addShortcode('details', {
  type: 'block',
  plugin: 'shortcode-core',
  title: 'Details',
  button: {
    group: 'shortcode-core',
    label: 'Details',
  },
  attributes: {
    summary: {
      type: String,
      title: 'Summary',
      bbcode: true,
      widget: 'input-text',
      default: '',
    },
    class: {
      type: String,
      title: 'Class',
      widget: 'input-text',
      default: '',
    },
  },
  titlebar({ attributes }) {
    return attributes.class
      ? `class: <strong>${attributes.class}</strong>`
      : '';
  },
  content({ attributes }) {
    let output = '';

    output += `<details class="${attributes.class || ''}" open>`;

    if (attributes.summary) {
      output += `<summary>${attributes.summary}</summary>`;
    }

    output += '{{content_editable}}';
    output += '</details>';

    return output;
  },
  preserve: {
    block: [
      'details',
      'summary',
    ],
  },
});
