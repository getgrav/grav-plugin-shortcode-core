<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class SpanShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('span', static function(ShortcodeInterface $sc) {
            $id = self::escAttr($sc->getParameter('id'));
            $class = self::escAttr($sc->getParameter('class'));
            $style = self::escAttr($sc->getParameter('style'));

            $id_output = $id ? 'id="' . $id . '" ': '';
            $class_output = $class ? 'class="' . $class . '"' : '';
            $style_output = $style ? 'style="' . $style . '"' : '';

            return '<span ' . $id_output . ' ' . $class_output . ' ' . $style_output . '>' . $sc->getContent() . '</span>';
        });
    }
}
