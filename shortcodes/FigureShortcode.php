<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class FigureShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('figure', function(ShortcodeInterface $sc) {
            $id = $sc->getParameter('id');
            $class = $sc->getParameter('class');
	    $caption = $sc->getParameter('caption');

            $id_output = $id ? 'id="' . $id . '" ': '';
            $class_output = $class ? 'class="' . $class . '"' : '';
	    $caption_output = $caption ? '<figcaption>' . $caption . '</figcaption>' : '';
            return '<figure ' . $id_output . ' ' . $class_output . '>'.$sc->getContent(). $caption_output . '</figure>';
        });
    }
}
