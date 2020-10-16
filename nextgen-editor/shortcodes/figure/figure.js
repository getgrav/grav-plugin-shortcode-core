window.nextgenEditor.addShortcode('figure', {
  type: 'block',
  plugin: 'shortcode-core',
  title: 'Figure',
  button: {
    group: 'shortcode-core',
    label: 'Figure',
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
    caption: {
      type: String,
      title: 'Caption',
      widget: 'input-text',
      default: '',
    },
  },
  titlebar({attributes }) {
    return []
      .concat([
        attributes.id ? `id: <strong>${attributes.id}</strong>` : null,
        attributes.class ? `class: <strong>${attributes.class}</strong>` : null,
      ])
      .filter((item) => !!item)
      .join(', ');
  },
  content({ attributes }) {
    const id = attributes.id || '';
    const cclass = attributes.class || '';
    const caption = attributes.caption || '';

    return `<div id="${id}" class="${cclass}">{{content_editable}}<div style="margin:0 8px;">${caption}</div></div>`;
  },
});
