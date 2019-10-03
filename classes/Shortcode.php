<?php

namespace Grav\Plugin\Shortcodes;

use Grav\Common\Config\Config;
use Grav\Common\Grav;
use Grav\Common\Twig\Twig;
use Grav\Plugin\ShortcodeManager;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class Shortcode
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
     * set some instance variable states
     */
    public function __construct()
    {
        $this->grav = Grav::instance();
        $this->shortcode = $this->grav['shortcode'];
        $this->config = $this->grav['config'];
        $this->twig = $this->grav['twig'];
    }

    /**
     * do some work
     */
    public function init()
    {
        $this->shortcode->getHandlers()->add('u', function(ShortcodeInterface $shortcode) {
            return $shortcode->getContent();
        });
    }

    /**
     * returns the name of the class if required
     * 
     * @return string the name of the class
     */
    public function getName()
    {
        return get_class($this);
    }

    public function getParser()
    {
        return $this->config->get('plugins.shortcode-core.parser');
    }

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
