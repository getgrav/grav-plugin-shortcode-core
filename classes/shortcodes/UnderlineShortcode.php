<?php
namespace Grav\Plugin\Shortcodes;

use Grav\Plugin\ShortcodeCore\Shortcode;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class UnderlineShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('u', static function(ShortcodeInterface $sc) {
            return '<span style="text-decoration: underline;">' . $sc->getContent() . '</span>';
        });
    }
}