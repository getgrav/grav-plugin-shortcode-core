<?php
namespace Grav\Plugin\Shortcodes;

use Grav\Common\Language\Language;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class LanguageShortcode extends Shortcode
{

    public function init()
    {
        $this->shortcode->getHandlers()->add('lang', function(ShortcodeInterface $sc) {
            $lang = $this->getBbCode($sc);

            if ($lang) {
                /** @var Language $language */
                $language = $this->grav['language'];
                $current = $language->getLanguage();

                if ($current == $lang) {
                    return $sc->getContent();
                }
            }

            return '';
        });
    }
}