<?php

namespace GravShortcode;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

/**
 * Defines the base interface to handle a Grav shortcode
 *
 * @author Giansimon Diblas
 */
interface GravShortcodeInterface
{
    /**
     * Returns the sortcode tag
     * 
     * @return string
     */
    public function shortcode();

    /**
     * Processes the given shortcode. 
     * 
     * Tnis method renders the shortcode and immediately returns the rendered shortcode output when requeste in the shortcode definition, otherwise it makes available the rendered shortcode output in a twig variable, so it can be used directly in a template
     * 
     * @param ShortcodeInterface $shortcode
     * @return string
     */
    public function processShortcode(ShortcodeInterface $shortcode);

    /**
     * Add extra assets required by the shortcode.
     * 
     * An example could be the following one:
     * return array(
            'plugin://gravstrap/css/gravstrap_navbar.css',
            'plugin://gravstrap/js/gravstrap_navbar.js',
            'plugin://gravstrap/js/scroll.js',
        );
     * 
     * @return array
     */
    public function assets();
    
    /**
     * Registers shortcode events
     * 
     * An example could be the following one:
     * return array(
            Events::FILTER_SHORTCODES => new FilterRawEventHandler(array('raw')),
        );
     * 
     * @return array
     */
    public function events();
}
