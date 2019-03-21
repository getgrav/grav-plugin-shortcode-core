<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use Thunder\Shortcode\Shortcode\ProcessedShortcode;

class MarkShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('mark', function(ProcessedShortcode $sc) {
            return '<mark>'.$sc->getContent().'</mark>';
        });
    }
}
