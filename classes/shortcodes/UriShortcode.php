<?php
namespace Grav\Plugin\Shortcodes;

use Grav\Common\Uri;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

/**
 * Print a value from the current URL, the safe in-content replacement for
 * `{{ uri.param('foo') }}` and friends. The value is user-controlled (it comes
 * straight from the request), so it is ALWAYS HTML-escaped on output — that
 * filtering is the whole point of using the shortcode instead of raw Twig in
 * content. There is deliberately no raw/unescaped opt-out.
 *
 * Usage:
 *   [uri param="user" /]              {{ uri.param('user') }}
 *   [uri query="q" /]                 {{ uri.query('q') }}
 *   [uri param="ref" default="home" /]
 */
class UriShortcode extends Shortcode
{
    /** Sources we expose, mapped to the Uri accessor that reads them. */
    private const SOURCES = ['param', 'query'];

    public function init()
    {
        $this->shortcode->getHandlers()->add('uri', function (ShortcodeInterface $sc) {
            /** @var Uri $uri */
            $uri = $this->grav['uri'];

            $value = null;
            foreach (self::SOURCES as $source) {
                $name = $sc->getParameter($source);
                if ($name !== null && $name !== '') {
                    // Uri::param() / Uri::query() — read the named value.
                    $value = $uri->{$source}((string) $name);
                    break;
                }
            }

            if ($value === null || $value === false || $value === '') {
                $value = $sc->getParameter('default', '');
            }

            // User-controlled input: escape unconditionally.
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        });
    }
}
