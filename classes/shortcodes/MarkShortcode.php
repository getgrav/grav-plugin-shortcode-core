<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class MarkShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('mark', function(ShortcodeInterface $sc) {
            $style = $sc->getParameter('style', $this->getBbCode($sc));
            $class = self::escAttr($sc->getParameter('class', 'default'));

            // [mark class=blue] etc. use the bundled color classes
            if ($class !== 'default' && $this->grav['config']->get('plugins.shortcode-core.css.mark_enabled', true)) {
                $this->shortcode->addAssets('css', 'plugin://shortcode-core/css/shortcode-mark.css');
            }

            $css_class = 'class="mark-class-' . $class . '"';

            $styles = [];
            if ($style === 'block') {
                $styles[] = 'display:block';
                $content = trim($sc->getContent(), "\n");
            } else {
                $content = $sc->getContent();
            }

            // [mark color="#99ccff"] is what Editor Pro writes
            $color = self::cssColor($sc->getParameter('color'));
            if ($color !== null) {
                $styles[] = 'background-color:' . $color;
                $text = self::textColorFor($color);
                if ($text !== null) {
                    $styles[] = 'color:' . $text;
                }
            }

            $css_style = $styles ? 'style="' . self::escAttr(implode(';', $styles) . ';') . '"' : '';
            $lang = self::langAttr($sc);

            return "<mark {$css_class} {$css_style}{$lang}>{$content}</mark>";
        });
    }

    /**
     * Accept a hex, rgb()/hsl() or named color; anything else is dropped.
     */
    protected static function cssColor($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(#[0-9a-f]{3,8}|[a-z]{3,20}|(rgb|hsl)a?\(\s*[0-9.,%\s\/deg]+\))$/i', $value)) {
            return $value;
        }

        return null;
    }

    /**
     * Dark or light text for a hex background, so a light highlight stays readable on dark themes.
     */
    protected static function textColorFor(string $color): ?string
    {
        if (!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})([0-9a-f]{1,2})?$/i', $color, $m)) {
            return null;
        }
        $hex = strlen($m[1]) === 3 ? preg_replace('/(.)/', '$1$1', $m[1]) : $m[1];
        [$r, $g, $b] = array_map('hexdec', str_split($hex, 2));

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150 ? '#111' : '#fff';
    }
}
