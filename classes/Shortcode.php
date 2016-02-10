<?php

namespace Grav\Plugin\Shortcodes;

use Grav\Common\Grav;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class Shortcode
{
    protected $shortcode;
    protected $grav;
    protected $config;
    protected $twig;

    public function __construct()
    {
        $this->grav = Grav::instance();
        $this->shortcode = $this->grav['shortcode'];
        $this->config = $this->grav['config'];
        $this->twig = $this->grav['twig'];
    }

    public function init()
    {
        $this->shortcode->handlers->add('u', function(ShortcodeInterface $shortcode) {
            return $shortcode->getContent();
        });
    }

    public function getName()
    {
        return get_class($this);
    }

}