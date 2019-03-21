<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use Thunder\Shortcode\Shortcode\ProcessedShortcode;

class MarkShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('mark', function(ProcessedShortcode $sc) {
            $style = $sc->getParameter('style', $this->getBbCode($sc));
            $css = $style === 'block' ? ' class="mark-block" style="display:block;"' : '';
            $content = $style == 'block' ? trim($sc->getContent(), "\n") : $sc->getContent();

            return "<mark{$css}>{$content}</mark>";
        });
    }
}
