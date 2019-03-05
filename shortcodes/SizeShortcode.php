<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class SizeShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('size', function(ShortcodeInterface $sc) {
            $size = $sc->getParameter('size', $sc->getBbCode());
            if ( is_numeric($size) ) { $size = $size.'px' ; }
            return '<span style="font-size: '.$size.';">'.$sc->getContent().'</span>';
        });
    }
}
