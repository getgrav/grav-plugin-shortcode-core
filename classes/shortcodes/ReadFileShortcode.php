<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

/**
 * `[read-file file="theme://foo.md" /]` — include the contents of a file
 * located by a Grav stream URI into the page.
 *
 * Delegates to `\Grav\Common\Helpers\FileReader::read()` so it inherits the
 * same hardening as the Twig `read_file()` function: stream-only paths,
 * `security.read_file.allowed_streams` allow-list, extension allow-list,
 * canonical realpath containment, max size cap. Anything outside those
 * constraints (raw filesystem paths, traversal, encoded `..`, disallowed
 * extensions, oversize files, missing files) returns the empty string and
 * the shortcode renders as nothing — just like the Twig function returns
 * `false`, which Twig prints as empty.
 *
 * Registered as a *raw* handler, so it runs before Markdown — included
 * Markdown files become part of the page's Markdown source and get rendered
 * normally. HTML / SVG / JSON files come through verbatim.
 *
 * Grav 1.7 fallback: `FileReader` was introduced in Grav 2.0.0-rc.2. On Grav
 * 1.7 the helper isn't available, so the shortcode emits an HTML comment
 * pointing at the version requirement. The comment isn't visible to readers
 * but is easy for an author or theme dev to find when viewing source — much
 * better than the alternative (no handler registered, so `[read-file ...]`
 * leaks as literal text into the rendered page). On 1.7 sites that need
 * file inclusion from page content, use the legacy Twig `read_file()`
 * function instead.
 */
class ReadFileShortcode extends Shortcode
{
    public function init()
    {
        // Always register the handler — even on Grav 1.7 — so a stray
        // `[read-file ...]` in content doesn't leak as literal text.
        // `FileReader` ships with Grav 2.0.0-rc.2+; older Gravs get a
        // diagnostic HTML comment.
        $hasFileReader = class_exists(\Grav\Common\Helpers\FileReader::class);

        // Closure intentionally non-static — `$this->getBbCode($sc)` provides
        // the `[read-file=theme://foo.md /]` BBCode-style shorthand alongside
        // the `[read-file file="theme://foo.md" /]` named-attribute form.
        $handler = function (ShortcodeInterface $sc) use ($hasFileReader) {
            if (!$hasFileReader) {
                return '<!-- [read-file] requires Grav >= 2.0.0-rc.2 -->';
            }

            $file = $sc->getParameter('file', $this->getBbCode($sc));
            if (!is_string($file) || $file === '') {
                return '';
            }

            $contents = \Grav\Common\Helpers\FileReader::read($file);

            return $contents === false ? '' : $contents;
        };

        // Register on the raw-handler container so the shortcode runs in the
        // pre-Markdown pass. Included `.md` files flow into the page's
        // Markdown source and render normally; HTML / SVG / JSON pass
        // through to the post-Markdown HTML untouched.
        $this->shortcode->getRawHandlers()->add('read-file', $handler);
    }
}
