<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class NoticeShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('notice', function(ShortcodeInterface $sc) {
            $this->shortcode->addAssets('css', 'plugin://shortcode-core/css/shortcode-notice.css');
            $type = $sc->getParameter('notice', $this->getBbCode($sc)) ?: 'info';

            return '<div class="sc-notice '.$type.'"><div>'.$sc->getContent().'</div></div>';
        });
    }
}