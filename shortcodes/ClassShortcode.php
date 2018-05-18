<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ClassShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('class', function(ShortcodeInterface $sc) {
            $class = $sc->getParameter('class', $sc->getBbCode());
            $class_output = $class ? 'class="' . $class . '"' : '';
            return '<span ' . $class_output . '>'.$sc->getContent().'</span>';
        });
    }
}