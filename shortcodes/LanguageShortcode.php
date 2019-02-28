<?php
namespace Grav\Plugin\Shortcodes;

use Grav\Common\Language\Language;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class LanguageShortcode extends Shortcode
{

    public function init()
    {
        $this->shortcode->getHandlers()->add('lang', function(ShortcodeInterface $sc) {
            $params = $sc->getParameters();
            if (is_array($params)) {
                /** @var Language $language */
                $language = $this->grav['language'];

                // hack to get short param style
                $current = $language->getActive();
                $keys = array_keys($params);
                $lang = trim(array_shift($keys), '=');

                if ($current == $lang) {
                    return $sc->getContent();
                }
            }
            return '';
        });
    }
}