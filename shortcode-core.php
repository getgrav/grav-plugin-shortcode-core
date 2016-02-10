<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class ShortcodeCorePlugin extends Plugin
{

    /** @var  ShortcodeManager $shortcodes */
    protected $shortcodes;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        require_once(__DIR__.'/vendor/autoload.php');
        require_once(__DIR__.'/classes/Shortcode.php');
        require_once(__DIR__.'/classes/ShortcodeManager.php');

        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
        ];
    }

    /**
     * Initialize configuration
     */
    public function onPluginsInitialized()
    {
        $this->config = $this->grav['config'];

        // don't continue if this is admin and plugin is disabled for admin
        if (!$this->config->get('plugins.shortcode-core.active_admin') && $this->isAdmin()) {
            return;
        }

        $this->enable([
            'onMarkdownInitialized' => ['onMarkdownInitialized', 0],
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
            'onPageContentProcessed' => ['onPageContentProcessed', 0],
            'onPageInitialized' => ['onPageInitialized', 0],
            'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
        ]);

        $this->grav['shortcode'] = $this->shortcodes = new ShortcodeManager();

        $this->grav->fireEvent('onShortcodeHandlers');

    }

    /**
     * Handle the markdown Initialized event by setting up shortcode block tags
     *
     * @param  Event  $event the event containing the markdown parser
     */
    public function onMarkdownInitialized(Event $event)
    {
        $this->shortcodes->setupMarkdown($event['markdown']);
    }

    /**
     * Process shortcodes after Grav's processing, but before caching
     *
     * @param Event $e
     */
    public function onPageContentProcessed(Event $e)
    {
        $page = $e['page'];
        $config = $this->mergeConfig($page);

        $this->active = $config->get('active', true);

        // if the plugin is not active (either global or on page) exit
        if (!$this->active) {
            return;
        }

        $e['page']->setRawContent($this->shortcodes->processContent($page, $config));

    }

    /**
     * Handle the assets that might be associated with this page
     */
    public function onPageInitialized()
    {
        if (!$this->active) {
            return;
        }

        // if the plugin is not active (either global or on page) exit
        if (!$this->active) {
            return;
        }

        $page = $this->grav['page'];
        $assets = $this->grav['assets'];
        $cache = $this->grav['cache'];

        // Initialize all page content up front before Twig happens
        if (isset($page->header()->content['items'])) {
            foreach ($page->collection() as $item) {
                $item->content();
            }
        } else {
            $page->content();
        }

        // cache or retrieve objects as required
        $cache_id = md5('shortcode-objects-'.$page->path());
        $shortcode_objects = $this->shortcodes->getObjects();
        if (empty($shortcode_objects)) {
            $this->shortcodes->setObjects($cache->fetch($cache_id));
        } else {
            $cache->save($cache_id, $shortcode_objects);
        }


        // cache or retrieve assets as required
        $cache_id = md5('shortcode-assets-'.$page->path());
        $shortcode_assets = $this->shortcodes->getAssets();

        if (empty($shortcode_assets)) {
            $shortcode_assets = $cache->fetch($cache_id);
        } else {
            $cache->save($cache_id, $shortcode_assets);
        }

        if (!empty($shortcode_assets)) {
            // if we actually have data now, add it to asset manager
            foreach ($shortcode_assets as $type => $asset) {
                foreach ($asset as $item) {
                    $method = 'add'.ucfirst($type);
                    if (is_array($item)) {
                        $assets->add($item[0], $item[1]);
                    } else {
                        $assets->$method($item);
                    }
                }
            }
        }
    }

    /**
     * Event that handles registering handler for shortcodes
     */
    public function onShortcodeHandlers()
    {
        $this->shortcodes->registerAllShortcodes(__DIR__.'/shortcodes');
    }

    /**
     * set any objects stored in the shortcodes manager as twig variables
     */
    public function onTwigSiteVariables()
    {
        $objects = $this->shortcodes->getObjects();
        $twig = $this->grav['twig'];

        if (!empty($objects)) {
            foreach ($objects as $key => $object) {
               $twig->twig_vars['shortcode'][$key] = $object;
            }
        }
    }

}
