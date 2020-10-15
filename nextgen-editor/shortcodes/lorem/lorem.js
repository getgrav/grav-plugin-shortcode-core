const sentence = 'Lorem ipsum dolor sit amet consectetur adipiscing elit, molestie tortor cubilia eu facilisi ex varius, convallis pretium dapibus fusce porta ligula.';
const words = [].concat(...Array(1000).fill(sentence.toLowerCase().replace(/[.|,]/g, '').split(' ')));
const paragraph = Array(2).fill(sentence).join(' ');

window.nextgenEditor.addShortcode('lorem', {
  type: 'block',
  plugin: 'shortcode-core',
  title: 'Lorem',
  wrapOnInsert: false,
  button: {
    group: 'shortcode-core',
    label: 'Lorem',
  },
  attributes: {
    p: {
      type: Number,
      title: 'Paragraphs',
      bbcode: true,
      widget: {
        type: 'input-number',
        min: 0,
        max: 10,
      },
      default: 2,
    },
    tag: {
      type: String,
      title: 'Tag',
      widget: 'input-text',
      default: 'p',
    },
    s: {
      type: Number,
      title: 'Sentences',
      widget: 'input-number',
      default: 0,
    },
    w: {
      type: Number,
      title: 'Words',
      widget: 'input-number',
      default: 0,
    },
  },
  titlebar({ attributes }) {
    if (attributes.w) {
      return `words: <strong>${attributes.w}</strong>`;
    }

    if (attributes.s) {
      return `sentences: <strong>${attributes.s}</strong>`;
    }

    if (attributes.p) {
      return `paragraphs: <strong>${attributes.p}</strong>`;
    }

    return '';
  },
  content({ attributes }) {
    let output = '';

    output += '<div style="margin:16px 16px">';

    if (attributes.w) {
      output += words.slice(0, attributes.w).join(' ');
    } else if (attributes.s) {
      output += Array(attributes.s).fill(sentence).join(' ');
    } else if (attributes.p) {
      [...Array(attributes.p)].forEach(() => {
        output += `<p>${paragraph}</p>`;
      });
    }

    output += '</div>';

    return output;
  },
});
