<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Processor\Processor;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use Thunder\Shortcode\Syntax\CommonSyntax;

class ShortcodeCorePlugin extends Plugin
{
    protected $handlers;
    protected $assets;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        require_once(__DIR__.'/vendor/autoload.php');
        require_once(__DIR__.'/classes/assetcontainer.php');

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
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
            'onPageContentProcessed' => ['onPageContentProcessed', 0],
            'onPageInitialized' => ['onPageInitialized', 0],
        ]);

        $this->handlers = new HandlerContainer();
        $this->assets = new AssetContainer();

        $this->grav->fireEvent('onShortcodeHandlers', new Event(['handlers' => &$this->handlers, 'assets' => &$this->assets]));


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

        if ($page && $config->get('enabled')) {
            $content = $e['page']->getRawContent();
            $processor = new Processor(new RegularParser(new CommonSyntax()), $this->handlers);
            $processed_content = $processor->process($content);

            $e['page']->setRawContent($processed_content);
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
        $cache = $this->grav['cache'];

        // Initialize all page content up front before Twig happens
        if (isset($page->header()->content['items'])) {
            foreach ($page->collection() as $item) {
                $item->content();
            }
        } else {
            $page->content();
        }

        // Get and set the cache as required
        $cache_id = md5('shortcode-core'.$page->path().$cache->getKey());

        if (empty($this->assets->get())) {
            $this->assets = $cache->fetch($cache_id);
        } else {
            $cache->save($cache_id, $this->assets);
        }

        if (!$this->assets) {
            return;
        }

        // if we actually have data now, add it to asset manager
        foreach ($this->assets->get() as $type => $asset) {
            foreach ($asset as $item) {
                if (is_array($item)) {
                    $assets->add($item[0], $item[1]);
                } else {
                    $method = 'add'.ucfirst($type);
                    $assets->$method($item);
                }
            }
        }

    }

    /**
     * Event that handles registering hanlder for shortcodes
     *
     * @param Event $e
     */
    public function onShortcodeHandlers(Event $e)
    {
        $this->handlers = $e['handlers'];
        $this->assets = $e['assets'];

        $this->addUnderlineHandler();
        $this->addSizeHandler();
        $this->addColorHandler();
        $this->addCenterHandler();
        $this->addLeftHandler();
        $this->addRightHandler();
        $this->addRawHandler();
        $this->addSafeEmailHandler();
    }

    private function addUnderlineHandler()
    {
        $this->handlers->add('u', function(ShortcodeInterface $shortcode) {
            return '<span style="text-decoration: underline;">'.$shortcode->getContent().'</span>';
        });
    }

    private function addSizeHandler()
    {
        $this->handlers->add('size', function(ShortcodeInterface $shortcode) {
            $size = $shortcode->getParameter('size', $shortcode->getParameterAt(0));
            return '<span style="font-size: '.$size.'px;">'.$shortcode->getContent().'</span>';
        });
    }

    private function addColorHandler()
    {
        $this->handlers->add('color', function(ShortcodeInterface $shortcode) {
            $color = $shortcode->getParameter('color', $shortcode->getParameterAt(0));
            return '<span style="color: '.$color.';">'.$shortcode->getContent().'</span>';
        });
    }

    private function addCenterHandler()
    {
        $this->handlers->add('center', function(ShortcodeInterface $shortcode) {
            return '<div style="text-align: center;">'.$shortcode->getContent().'</div>';
        });
    }

    private function addLeftHandler()
    {
        $this->handlers->add('left', function(ShortcodeInterface $shortcode) {
            return '<div style="text-align: left;">'.$shortcode->getContent().'</div>';
        });
    }

    private function addRightHandler()
    {
        $this->handlers->add('right', function(ShortcodeInterface $shortcode) {
            return '<div style="text-align: right;">'.$shortcode->getContent().'</div>';
        });
    }

    private function addRawHandler()
    {
        $this->handlers->add('raw', function(ShortcodeInterface $shortcode) {
            $raw = trim(preg_replace('/\[raw\](.*?)\[\/raw\]/is','${1}', $shortcode->getShortcodeText()));
            return $raw;
        });
    }

    private function addSafeEmailHandler()
    {
        $this->handlers->add('safe-email', function(ShortcodeInterface $shortcode) {
            // Load assets if required
            if ($this->config->get('plugins.shortcode-core.load_fontawesome', false)) {
                $this->assets->add('css', '//maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css');
            }

            // Get shortcode content and parameters
            $str = $shortcode->getContent();
            $icon = $shortcode->getParameter('icon', false);
            $autolink = $shortcode->getParameter('autolink', false);

            // Encode email
            $email = '';
            $str_len = strlen($str);
            for ($i = 0; $i < $str_len; $i++) {
                $email .= "&#" . ord($str[$i]). ";";
            }

            // Handle autolinking
            if ($autolink) {
                $output = '<a href="mailto:'.$email.'">'.$email.'</a>';
            } else {
                $output = $email;
            }

            // Handle icon option
            if ($icon) {
                $output = '<i class="fa fa-'.$icon.'"></i> ' . $output;
            }

            return $output;
        });

    }


}
