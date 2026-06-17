<?php
namespace Grav\Plugin\Shortcodes;

use Grav\Common\Language\Language;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

/**
 * Translate a language string by key, the safe in-content replacement for
 * `{{ 'SOME.KEY'|t }}`. The key is author-supplied; the value comes from the
 * site's language files, which are trusted (authored by developers and
 * translators), so the result is returned as-is — translations may legitimately
 * contain HTML. Untrusted, user-controlled values belong in [uri] instead,
 * which escapes its output.
 *
 * Usage:
 *   [translate]PLUGIN_ERROR.ERROR_MESSAGE[/translate]
 *   [translate=PLUGIN_ERROR.ERROR_MESSAGE /]
 *   [translate key="MY.GREETING" Andy /]   (extra params become substitutions)
 */
class TranslateShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('translate', function (ShortcodeInterface $sc) {
            // Key from [translate=KEY], the `key` param, or the wrapped content.
            $key = $sc->getParameter('key', $this->getBbCode($sc));
            if ($key === null || $key === '') {
                $key = $sc->getContent();
            }
            $key = trim((string) $key);

            if ($key === '') {
                return '';
            }

            // Any remaining bare parameters are passed through as ordered
            // substitution arguments (sprintf-style placeholders in the string).
            $args = [];
            foreach ($sc->getParameters() as $name => $value) {
                if ($name === 'key' || $name === $key) {
                    continue;
                }
                $args[] = $value === null ? $name : $value;
            }

            /** @var Language $language */
            $language = $this->grav['language'];

            return $language->translate(array_merge([$key], $args));
        });
    }
}
