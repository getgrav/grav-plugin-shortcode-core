<?php
namespace Grav\Plugin;

use Grav\Common\Data\Data;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Thunder\Shortcode\EventContainer\EventContainer;
use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Processor\Processor;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use Thunder\Shortcode\Syntax\CommonSyntax;

class ShortcodeManager {

    /** @var Grav $grav */
    protected $grav;

    /** @var  HandlerContainer $handlers */
    protected $handlers;

    /** @var  EventContainer $events */
    protected $events;

    protected $assets;

    protected $states;

    public function __construct()
    {
        $this->grav = Grav::instance();
        $this->config = $this->grav['config'];
        $this->handlers = new HandlerContainer();
        $this->events = new EventContainer();
        $this->assets = [];
    }

    public function addAssets($action, $asset)
    {
        if (is_array($action)) {
            $this->assets['add'] []= $action;
        } else {
            if (isset($this->assets[$action])) {
                if (in_array($asset, $this->assets[$action])) {
                    return;
                }
            }
            $this->assets[$action] []= $asset;
        }
    }

    public function getAssets() {
        return $this->assets;
    }

    public function getHandlers()
    {
        return $this->handlers;
    }

    public function getEvents()
    {
        return $this->events;
    }

    public function registerShortcode($name, $directory)
    {
        $path = rtrim($directory, '/').'/'.$name;
        require_once($path);

        $name = "Grav\\Plugin\\Shortcodes\\" . basename($name, '.php');

        if (class_exists($name)) {
            $shortcode = new $name();
            $shortcode->init();
        }
    }

    public function registerAllShortcodes($directory)
    {
        foreach (new \DirectoryIterator($directory) as $file) {
            if($file->isDot()) {
                continue;
            }
            $this->registerShortcode($file->getFilename(), $directory);
        }
    }



    public function setupMarkdown($markdown)
    {
        $markdown->addBlockType('[', 'ShortCodes', true, false);

        $markdown->blockShortCodes = function($Line) {
            $valid_shortcodes = implode('|', $this->handlers->getNames());
            $regex = '/^(?:\[\/?(?:'.$valid_shortcodes.'))(.*)(?:\])$/';

            if (preg_match($regex, $Line['body'], $matches)) {
                $Block = array(
                    'markup' => $Line['body'],
                );
                return $Block;
            }
        };
    }

    public function processContent(Page $page, Data $config)
    {
        switch($config->get('parser'))
        {
            case 'regular':
                $parser = 'Thunder\Shortcode\Parser\RegularParser';
                break;
            case 'wordpress':
                $parser = 'Thunder\Shortcode\Parser\WordpressParser';
                break;
            default:
                $parser = 'Thunder\Shortcode\Parser\RegexParser';
                break;
        }

        if ($page && $config->get('enabled')) {
            $content = $page->getRawContent();
            $processor = new Processor(new $parser(new CommonSyntax()), $this->handlers);
            $processor = $processor->withEventContainer($this->events);
            $processed_content = $processor->process($content);

            return $processed_content;
        }
    }

    public function getStates($hash)
    {
        if (array_key_exists($hash, $this->states)) {
            return $this->states[$hash];
        }
    }

    public function setStates($hash, ShortcodeInterface $shortcode)
    {
        $this->states[$hash][] = $shortcode;
    }

    public function getId(ShortcodeInterface $shortcode)
    {
        return substr(md5($shortcode->getShortcodeText()), -10);
    }
}
