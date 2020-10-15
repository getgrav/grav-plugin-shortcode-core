window.nextgenEditor.addShortcode('mark', {
  type: 'inline',
  plugin: 'shortcode-core',
  title: 'Mark',
  button: {
    group: 'shortcode-core',
    label: 'Mark',
  },
  attributes: {
    style: {
      type: String,
      title: 'Style',
      bbcode: true,
      widget: {
        type: 'radios',
        values: [
          { value: 'inline', label: 'Inline' },
          { value: 'block', label: 'Block' },
        ],
      },
      default: 'inline',
    },
    class: {
      type: String,
      title: 'Class',
      widget: 'input-text',
      default: '',
    },
  },
  titlebar({ attributes }) {
    return []
      .concat([
        attributes.style ? `style: <strong>${attributes.style}</strong>` : null,
        attributes.class ? `class: <strong>${attributes.class}</strong>` : null,
      ])
      .filter((item) => !!item)
      .join(', ');
  },
  content({ attributes }) {
    const style = attributes.style === 'block'
      ? 'display:block'
      : '';

    const cclass = `mark-class-${attributes.class}`;

    return `<span class="${cclass}" style="${style}">{{content_editable}}</span>`;
  },
});
