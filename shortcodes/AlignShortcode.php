<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class AlignShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('center', function(ShortcodeInterface $sc) {
            return '<div style="text-align: center;">'.$sc->getContent().'</div>';
        });

        $this->shortcode->getHandlers()->add('left', function(ShortcodeInterface $sc) {
            return '<div style="text-align: left;">'.$sc->getContent().'</div>';
        });

        $this->shortcode->getHandlers()->add('right', function(ShortcodeInterface $sc) {
            return '<div style="text-align: right;">'.$sc->getContent().'</div>';
        });
    }
}