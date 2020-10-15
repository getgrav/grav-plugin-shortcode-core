window.nextgenEditor.addShortcode('fa', {
  type: 'inline',
  plugin: 'shortcode-core',
  title: 'Font Awesome',
  wrapOnInsert: false,
  button: {
    group: 'shortcode-core',
    label: 'Font Awesome',
  },
  attributes: {
    icon: {
      type: String,
      title: 'Icon',
      bbcode: true,
      widget: 'input-text',
      default: 'grav',
    },
    extras: {
      type: String,
      title: 'Extras',
      widget: 'input-text',
      default: '',
    },
  },
  content({ attributes }) {
    let faclass = 'fa';

    let icon = attributes.icon && !attributes.icon.startsWith('fa-')
      ? `fa-${attributes.icon}`
      : attributes.icon;

    if (attributes.extras) {
      attributes.extras.split(',').forEach((extra) => {
        if (extra) {
          if (['fab', 'fal', 'fas', 'far', 'fad'].includes(extra)) {
            faclass = extra;
            return;
          }

          icon += !extra.startsWith('fa-')
            ? ` fa-${extra}`
            : ` ${extra}`;
        }
      });
    }

    return `<span class="${faclass} ${icon}" style="margin:4px"></span>`;
  },
});
