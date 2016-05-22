<?php
namespace Grav\Plugin;

use Grav\Common\Page\Page;
use Grav\Common\Plugin;
use Grav\Plugin\Shortcodes\ShortCodeObject;
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
        require_once(__DIR__.'/classes/ShortcodeObject.php');
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
            'onTwigPageVariables' => ['onTwigPageVariables', 0],
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
        /** @var Page $page */
        $page = $e['page'];
        $config = $this->mergeConfig($page);
        $meta = [];

        $this->active = $config->get('active', true);

        // if the plugin is not active (either global or on page) exit
        if (!$this->active) {
            return;
        }

        // reset objects and assets for the page
        $this->shortcodes->resetObjects();
        $this->shortcodes->resetAssets();

        // process the content for shortcodes
        $page->setRawContent($this->shortcodes->processContent($page, $config));

        // if objects found set them as page content meta
        $shortcode_objects = $this->shortcodes->getObjects();
        if (!empty($shortcode_objects)) {
            $meta['shortcode'] = $shortcode_objects;
        }

        // if assets founds set them as page content meta
        $shortcode_assets = $this->shortcodes->getAssets();
        if (!empty($shortcode_assets)) {
            $meta['shortcode-assets'] = $shortcode_assets;
        }

        // if we have meta set, let's add it to the content meta
        if (!empty($meta)) {
            $page->addContentMeta('shortcode-meta', $meta);
        }
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

        // Initialize all page content up front before Twig happens
        if (isset($page->header()->content['items'])) {
            foreach ($page->collection() as $item) {
                $item->content();
            }
        }

        // Always initialize current page
        $page->content();

        // get the meta and check for assets
        $meta = $page->getContentMeta('shortcode-meta');

        // if assets found, add them to Assets manager
        if (isset($meta['shortcode-assets'])) {
            $page_assets = (array) $meta['shortcode-assets'];
            if (!empty($page_assets)) {
                // if we actually have data now, add it to asset manager
                foreach ($page_assets as $type => $asset) {
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

    }

    /**
     * Event that handles registering handler for shortcodes
     */
    public function onShortcodeHandlers()
    {
        $this->shortcodes->registerAllShortcodes(__DIR__.'/shortcodes');
    }

    /**
     * set any objects stored in the shortcodes manager as page twig variables
     *
     * @param Event $e
     */
    public function onTwigPageVariables(Event $e)
    {
        // check current event's page content meta for objects, and if found as them as twig variables
        $meta = $e['page']->getContentMeta('shortcode-meta');

        $this->mergeTwigVars($meta);
    }

    /**
     * set any objects stored in the shortcodes manager as site twig variables
     */
    public function onTwigSiteVariables()
    {
        // check current page content meta for objects, and if found as them as twig variables
        $meta = $this->grav['page']->getContentMeta('shortcode-meta');

        $this->mergeTwigVars($meta);
    }

    /**
     * Helper method that merges the content meta shortcode data with twig variables
     *
     * @param $meta
     */
    private function mergeTwigVars($meta)
    {
        // check content meta for objects, and if found as them as twig variables
        if (isset($meta['shortcode'])) {
            $objects = $meta['shortcode'];
            $twig = $this->grav['twig'];

            if (!empty($objects)) {
                foreach ($objects as $key => $object) {
                    $twig->twig_vars['shortcode'][$key] = $object;
                }
            }
        }
    }



}
