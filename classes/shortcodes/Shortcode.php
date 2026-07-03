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
    public function init()
    {
        user_error(__METHOD__ . '() method will be abstract in the future, please override it!', E_USER_DEPRECATED);

        // FIXME: This code had to be put back because of some plugins do not properly initialize themselves.
        $this->shortcode->getHandlers()->add('u', static function(ShortcodeInterface $shortcode) {
            return $shortcode->getContent();
        });
    }

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

    /**
     * Escape a user-supplied value for safe inclusion inside an HTML attribute.
     *
     * Shortcode parameters bypass Grav's save-time XSS scan, which only flags a
     * literal `<` and shortcode syntax never contains one, and shortcode output
     * is not re-scanned at render time. Each shortcode is therefore responsible
     * for encoding the parameter values it concatenates into markup, otherwise a
     * value such as `x" onmouseover=alert(1)` closes the attribute and injects a
     * live event handler. (GHSA-q5fw-vpqc-fgph)
     *
     * @param string|null $value
     * @return string
     */
    public static function escAttr($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

// Make sure we also autoload the deprecated class.
class_exists(\Grav\Plugin\ShortcodeCore\Shortcode::class);
