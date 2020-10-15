window.nextgenEditor.addShortcode('notice', {
  type: 'block',
  plugin: 'shortcode-core',
  title: 'Notice',
  button: {
    group: 'shortcode-core',
    label: 'Notice',
  },
  attributes: {
    notice: {
      type: String,
      title: 'Type',
      bbcode: true,
      widget: {
        type: 'radios',
        values: [
          { value: 'info', label: 'Info' },
          { value: 'warning', label: 'Warning' },
          { value: 'note', label: 'Note' },
          { value: 'tip', label: 'Tip' },
        ],
      },
      default: 'info',
    },
  },
  titlebar({ attributes }) {
    const notice = attributes.notice
      ? this.attributes.notice.widget.values.find((item) => item.value === attributes.notice)
      : '';

    const type = notice
      ? notice.label
      : '';

    return `type: <strong>${type}</strong>`;
  },
  content({ attributes }) {
    return `<div class="sc-notice sc-notice-${attributes.notice}"><div class="sc-notice-wrapper">{{content_editable}}</div></div>`;
  },
});
