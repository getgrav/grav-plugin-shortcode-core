<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class RubytextShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('ruby', function(ShortcodeInterface $sc) {
            $rubyx = $sc->getContent();
            $ruby = $sc->getParameter('ruby', trim($sc->getParameterAt(0), ':'));
            $rubytext = '<ruby><rb>'.$rubyx.'</rb><rp>（</rp><rt>'.$ruby.'</rt><rp>）</rp></ruby>';
            return $rubytext;
        });
    }
}
