<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class SizeShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('size', function(ShortcodeInterface $sc) {
            $size = $sc->getParameter('size', trim($sc->getParameterAt(0), '='));
            return '<span style="font-size: '.$size.'px;">'.$sc->getContent().'</span>';
        });
    }
}