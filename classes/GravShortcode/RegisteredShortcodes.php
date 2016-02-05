<?php

namespace GravShortcode;

/**
 * A Class that stores the children shortcodes by parent's hash
 *
 * @author Giansimon Diblas
 */
class RegisteredShortcodes
{
    /**
     * @var array 
     */
    private static $shortcodes = array();

    /**
     * Registers a shortcode
     * 
     * @param string $hash
     * @param mixed $output
     */
    public static function register($hash, $output)
    {
        self::$shortcodes[$hash][] = $output;
    }

    /**
     * Returns the children stored for the given hash.
     * 
     * Returns an empty array when the given hash is not found
     * 
     * @param string $hash
     * @return array
     */
    public static function get($hash)
    {
        if (!array_key_exists($hash, self::$shortcodes)) {
            return array();
        }

        return self::$shortcodes[$hash];
    }
}
