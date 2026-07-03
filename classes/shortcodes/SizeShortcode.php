<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class SizeShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('size', function(ShortcodeInterface $sc) {
            $size = $sc->getParameter('size', $this->getBbCode($sc));
            if (is_numeric($size)) {
                $size .= 'px';
            }
            $size = self::escAttr($size);

            return '<span style="font-size: ' . $size . ';">' . $sc->getContent() . '</span>';
        });
    }
}
