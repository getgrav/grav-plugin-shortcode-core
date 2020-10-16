window.nextgenEditor.addShortcode('safe-email', {
  type: 'inline',
  plugin: 'shortcode-core',
  title: 'Safe Email',
  button: {
    group: 'shortcode-core',
    label: 'Safe Email',
  },
  attributes: {
    icon: {
      type: String,
      title: 'Icon',
      bbcode: true,
      widget: 'input-text',
      default: 'grav',
    },
    autolink: {
      type: String,
      title: 'Autolink',
      widget: {
        type: 'checkbox',
        valueType: String,
        label: 'Yes',
      },
      default: 'false',
    },
  },
  content({ attributes }) {
    let output = '';

    if (attributes.autolink === 'true') {
      output += '<span style="color:#1c90fb">';
    }

    if (attributes.icon) {
      output += `<span class="fa fa-${attributes.icon}" style="margin-left:4px"></span>`;
    }

    output += '{{content_editable}}';

    if (attributes.autolink === 'true') {
      output += '</span>';
    }

    return output;
  },
});
