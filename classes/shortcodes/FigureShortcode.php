<?php
namespace Grav\Plugin\Shortcodes;

use Grav\Common\Utils;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class FigureShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('figure', function(ShortcodeInterface $sc) {
            $id = self::escAttr($sc->getParameter('id'));
            $class = self::escAttr($sc->getParameter('class'));
            $caption = $sc->getParameter('caption');
            $page = $this->grav['page'];

            // Process any markdown on caption
            $caption = Utils::processMarkdown($caption, false, $page);

            $id_output = $id ? 'id="' . $id . '" ': '';
            $class_output = $class ? 'class="' . $class . '"' : '';
            $caption_output = $caption ? '<figcaption>' . $caption . '</figcaption>' : '';

            return '<figure ' . $id_output . ' ' . $class_output . '>'.$sc->getContent(). $caption_output . '</figure>';
        });
    }
}
