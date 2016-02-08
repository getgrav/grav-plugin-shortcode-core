<?php

namespace Shortcode;

use GravShortcode\BaseShortcode;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use Thunder\Shortcode\EventHandler\FilterRawEventHandler;
use Thunder\Shortcode\Events;

/**
 * Class Raw handles a shortcode that do not process the shortcodes between these raw shortcode tags
 *
 * @author Giansimon Diblas
 */
class Raw extends BaseShortcode
{
    /**
     * {@inheritdoc}
     */
    public function shortcode()
    {
        return 'raw';
    }
    
    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return array(
            Events::FILTER_SHORTCODES => new FilterRawEventHandler(array('raw')),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function template()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function renderOutput(ShortcodeInterface $shortcode)
    {
        return trim($shortcode->getContent());
    }
}
