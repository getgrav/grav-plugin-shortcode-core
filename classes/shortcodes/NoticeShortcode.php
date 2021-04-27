<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class NoticeShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('notice', function(ShortcodeInterface $sc) {
            $css_enabled = $this->grav['config']->get('plugins.shortcode-core.css.notice_enabled', true);
            if ($css_enabled) {
                $this->shortcode->addAssets('css', 'plugin://shortcode-core/css/shortcode-notice.css');
            }
            $type = $sc->getParameter('notice', $this->getBbCode($sc)) ?: 'info';

            return '<div class="sc-notice '.$type.'"><div>'.$sc->getContent().'</div></div>';
        });
    }
}