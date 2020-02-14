<?php

namespace Grav\Plugin\Shortcodes;

use Grav\Common\Config\Config;
use Grav\Common\Grav;
use Grav\Common\Twig\Twig;
use Grav\Plugin\ShortcodeCore\ShortcodeManager;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

abstract class Shortcode
{
    /** @var ShortcodeManager */
    protected $shortcode;

    /** @var Grav  */
    protected $grav;

    /** @var Config */
    protected $config;

    /** @var Twig */
    protected $twig;

    /**
     * Shortcode constructor.
     */
    public function __construct()
    {
        $this->grav = Grav::instance();
        $this->shortcode = $this->grav['shortcode'];
        $this->config = $this->grav['config'];
        $this->twig = $this->grav['twig'];
    }

    /**
     * Initialize shortcode handler
     */
    abstract public function init();

    /**
     * Returns the name of the class if required
     *
     * @return string the name of the class
     */
    public function getName()
    {
        return get_class($this);
    }

    /**
     * @return string
     */
    public function getParser()
    {
        return $this->config->get('plugins.shortcode-core.parser');
    }

    /**
     * @param ShortcodeInterface $sc
     * @param string|null $default
     * @return string|null
     */
    public function getBbCode(ShortcodeInterface $sc, $default = null)
    {
        $code = $default;

        if ($this->getParser() === 'wordpress') {
            $params = $sc->getParameters();
            if (is_array($params)) {
                $keys = array_keys($params);
                $code = trim(array_shift($keys), '=');
            }
        } else {
            $code = $sc->getBbCode();
        }

        return $code;
    }
}

// Make sure we also autoload the deprecated class.
class_exists(\Grav\Plugin\ShortcodeCore\Shortcode::class);
