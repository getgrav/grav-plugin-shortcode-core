window.nextgenEditor.addShortcode('div', {
  type: 'block',
  plugin: 'shortcode-core',
  title: 'Div',
  button: {
    group: 'shortcode-core',
    label: 'Div',
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
  titlebar({ attributes }) {
    return []
      .concat([
        attributes.id ? `id: <strong>${attributes.id}</strong>` : null,
        attributes.class ? `class: <strong>${attributes.class}</strong>` : null,
        attributes.style ? `style: <strong>${attributes.style}</strong>` : null,
      ])
      .filter((item) => !!item)
      .join(', ');
  },
  content({ attributes }) {
    const id = attributes.id || '';
    const cclass = attributes.class || '';
    const style = attributes.style || '';

    return `<div id="${id}" class="${cclass}" style="${style}">{{content_editable}}</div>`;
  },
});
