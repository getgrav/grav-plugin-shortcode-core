<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ColumnsShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('col-left', function(ShortcodeInterface $sc) {
            return $this->getDiv('left', $sc->getParameters(), $sc->getContent());
        });
        $this->shortcode->getHandlers()->add('col-left-last', function(ShortcodeInterface $sc) {
            return $this->getDiv('left', $sc->getParameters(), $sc->getContent()) . '<div style="clear: both"></div>';
        });
        $this->shortcode->getHandlers()->add('col-right', function(ShortcodeInterface $sc) {
            return $this->getDiv('right', $sc->getParameters(), $sc->getContent());
        });
        $this->shortcode->getHandlers()->add('col-right-last', function(ShortcodeInterface $sc) {
            return $this->getDiv('right', $sc->getParameters(), $sc->getContent()) . '<div style="clear: both"></div>';
        });
    }
    public function getDiv($floatDirection, $params, $content)
    {
      $cssClass = array_key_exists('class', $params) ? $params['class'] : '';
      $cssStyle = array_key_exists('style', $params) ? $params['style'] : '';
      return '<div style="float: ' . $floatDirection . '; ' . $cssStyle . '" class="' . $cssClass . '">' . $content . '</div>';
    }
}
