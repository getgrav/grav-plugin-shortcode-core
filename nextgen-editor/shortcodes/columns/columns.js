window.nextgenEditor.addShortcode('columns', {
  type: 'block',
  plugin: 'shortcode-core',
  title: 'Columns',
  button: {
    group: 'shortcode-core',
    label: 'Columns',
  },
  attributes: {
    count: {
      type: Number,
      title: 'Count',
      widget: {
        type: 'input-number',
        min: 1,
        max: 12,
      },
      default: 2,
    },
    width: {
      type: String,
      title: 'Width',
      widget: 'input-text',
      default: 'auto',
    },
    gap: {
      type: String,
      title: 'Gap',
      widget: 'input-text',
      default: 'normal',
    },
    rule: {
      type: String,
      title: 'Rule',
      widget: 'input-text',
      default: '',
    },
  },
  titlebar({ attributes }) {
    return `columns: <strong>${attributes.count}</strong>`;
  },
  content({ attributes }) {
    const styles = []
      .concat([
        `columns:${attributes.count} ${attributes.width}`,
        `-moz-columns:${attributes.count} ${attributes.width}`,
        `column-gap:${attributes.gap}`,
        `-moz-column-gap:${attributes.gap}`,
        attributes.rule ? `column-rule:${attributes.rule}` : null,
        attributes.rule ? `-moz-column-rule:${attributes.rule}` : null,
      ])
      .filter((item) => !!item)
      .join(';');

    return `<div style="${styles}">{{content_editable}}</div>`;
  },
});
