import collapse from './collapse';

window.nextgenEditor.addHook('hookHTMLtoMarkdown', {
  weight: -50,
  handler(options, editor, input) {
    let output = input;

    output = collapse(output);

    const domOutput = new DOMParser().parseFromString(output, 'text/html');

    let domShortcode = domOutput.querySelector('shortcode-block, shortcode-inline');

    while (domShortcode) {
      const name = domShortcode.getAttribute('name');
      const shortcode = window.nextgenEditor.shortcodes[name];
      const attributes = JSON.parse(decodeURIComponent(domShortcode.getAttribute('attributes')));

      const attrLine = Object.keys(shortcode.attributes).reduce((acc, attrName) => {
        const attribute = shortcode.attributes[attrName];

        if (attribute.type === Boolean) {
          return attributes[attrName]
            ? `${acc} ${attrName}`
            : acc;
        }

        if (attributes[attrName] === attribute.default.value && !attribute.default.preserve) {
          return acc;
        }

        return !attribute.bbcode
          ? `${acc} ${attrName}="${attributes[attrName]}"`
          : `="${attributes[attrName]}"${acc}`;
      }, '');

      if (shortcode.type === 'block') {
        if (domShortcode.innerHTML === '<p>&nbsp;</p>') {
          domShortcode.innerHTML = '';
        }

        if (domShortcode.innerHTML) {
          domShortcode.outerHTML = `<p>[${shortcode.realName}${attrLine}]</p>${domShortcode.innerHTML}<p>[/${shortcode.realName}]</p>`;
        } else {
          domShortcode.outerHTML = `<p>[${shortcode.realName}${attrLine} /]</p>`;
        }
      }

      if (shortcode.type === 'inline') {
        if (domShortcode.innerHTML === '&nbsp;') {
          domShortcode.innerHTML = '';
        }

        if (domShortcode.innerHTML) {
          domShortcode.outerHTML = `[${shortcode.realName}${attrLine}]${domShortcode.innerHTML}[/${shortcode.realName}]`;
        } else {
          domShortcode.outerHTML = `[${shortcode.realName}${attrLine} /]`;
        }
      }

      domShortcode = domOutput.querySelector('shortcode-block, shortcode-inline');
    }

    output = domOutput.body.innerHTML;

    return output;
  },
});
