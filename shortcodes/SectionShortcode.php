<?php

namespace Grav\Plugin\Shortcodes;


use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class SectionShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('section', function(ShortcodeInterface $sc) {
            $name = $sc->getParameter('name', $sc->getParameter('name'));
            $this->shortcode->addObject($sc->getName(), $name, $sc->getContent());
        });
    }
}