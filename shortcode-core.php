<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Assets;
use Grav\Common\Page\Interfaces\PageInterface;
use Grav\Common\Plugin;
use Grav\Common\Utils;
use Grav\Plugin\ShortcodeCore\ShortcodeManager;
use Grav\Plugin\ShortcodeCore\ShortcodeTwigVar;
use RocketTheme\Toolbox\Event\Event;
use Twig\TwigFilter;


class ShortcodeCorePlugin extends Plugin
{
    /** @var  ShortcodeManager $shortcodes */
    protected $shortcodes;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => [
                ['autoload', 100001],
                ['onPluginsInitialized', 10]
            ],
            'registerNextGenEditorPlugin' => [
                ['registerNextGenEditorPlugin', 0],
                ['registerNextGenEditorPluginShortcodes', 0],
            ],
            'registerEditorProPlugin' => [
                ['registerEditorProPlugin', 0],
            ],
            'onEditorProShortcodeRegister' => [
                ['onEditorProShortcodeRegister', 0],
            ]
        ];
    }

    /**
     * [onPluginsInitialized:100000] Composer autoload.
     *
     * @return ClassLoader
     */
    public function autoload()
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize configuration
     */
    public function onPluginsInitialized()
    {
        $this->config = $this->grav['config'];

        $forceFrontend = (bool)($this->grav['shortcode_force_frontend'] ?? false);

        // don't continue if this is admin and plugin is disabled for admin
        if (!$forceFrontend && !$this->config->get('plugins.shortcode-core.active_admin') && $this->isAdmin()) {
            return;
        }

        $this->enable([
            'onThemeInitialized'        => ['onThemeInitialized', 0],
            'onMarkdownInitialized'     => ['onMarkdownInitialized', 0],
            'onShortcodeHandlers'       => ['onShortcodeHandlers', 0],
            'onPageContentRaw'          => ['onPageContentRaw', 0],
            'onPageContentProcessed'    => ['onPageContentProcessed', -10],
            'onPageContent'             => ['onPageContent', 0],
            'onTwigInitialized'         => ['onTwigInitialized', 0],
            'onTwigTemplatePaths'       => ['onTwigTemplatePaths', 0],
        ]);

        $this->grav['shortcode'] = $this->shortcodes = new ShortcodeManager();
    }

    /**
     * Theme initialization is best place to fire onShortcodeHandler event
     * in order to support both plugins and themes
     */
    public function onThemeInitialized()
    {
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
     * Process shortcodes before Grav's processing
     *
     * @param Event $e
     */
    public function onPageContentRaw(Event $e)
    {
        $this->processShortcodes($e['page'], 'processRawContent');
    }

    /**
     * Process shortcodes after Grav's processing, but before caching
     *
     * @param Event $e
     */
    public function onPageContentProcessed(Event $e)
    {
        $this->processShortcodes($e['page'], 'processContent');
    }

    /**
     * @param PageInterface $page
     * @param string $type
     */
    protected function processShortcodes(PageInterface $page, $type = 'processContent') {
        $meta = [];
        $this->shortcodes->resetObjects(); // clear shortcodes that may have been processed in this execution thread before
        $config = $this->mergeConfig($page);

        // Don't run in admin pages other than content
        $admin_pages_only = $config['admin_pages_only'] ?? true;
        if ($admin_pages_only && $this->isAdmin() && !Utils::startsWith($page->filePath(), $this->grav['locator']->findResource('page://'))) {
            return;
        }

        $this->active = $config->get('active', true);

        // if the plugin is not active (either global or on page) exit
        if (!$this->active) {
            return;
        }

        // process the content for shortcodes
        $page->setRawContent($this->shortcodes->$type($page, $config));

        // if objects found set them as page content meta
        $shortcode_objects = $this->shortcodes->getObjects();
        if (!empty($shortcode_objects)) {
            $meta['shortcode'] = $shortcode_objects;
        }

        // if assets founds set them as page content meta
        $shortcode_assets = $this->shortcodes->getAssets();
        if (!empty($shortcode_assets)) {
            $meta['shortcodeAssets'] = $shortcode_assets;
        }

        // if we have meta set, let's add it to the content meta
        if (!empty($meta)) {
            $page->addContentMeta('shortcodeMeta', $meta);
        }
    }

    /**
     * @param PageInterface $page
     * @return \Grav\Common\Data\Data
     */
    protected function getConfig(PageInterface $page)
    {
        $config = $this->mergeConfig($page);
        $this->active = false;

        // Don't run in admin pages other than content
        $admin_pages_only = isset($config['admin_pages_only']) ? $config['admin_pages_only'] : true;
        if ($admin_pages_only &&
            $this->isAdmin() &&
            !Utils::startsWith($page->filePath(), $this->grav['locator']->findResource('page://'))) {

        } else {
            $this->active = $config->get('active', true);
        }

        return $config;
    }

    /**
     * Handle the assets that might be associated with this page
     */
    public function onPageContent(Event $event)
    {
        if (!$this->active) {
            return;
        }

        $page = $event['page'];

        // get the meta and check for assets
        $page_meta = $page->getContentMeta('shortcodeMeta');

        if (is_array($page_meta)) {
            if (isset($page_meta['shortcodeAssets'])) {

                $page_assets = (array) $page_meta['shortcodeAssets'];

                /** @var Assets $assets */
                $assets = $this->grav['assets'];
                // if we actually have data now, add it to asset manager
                foreach ($page_assets as $type => $asset) {
                    foreach ($asset as $item) {
                        $method = 'add'.ucfirst($type);
                        if (is_array($item)) {
                            $assets->$method($item[0], $item[1]);
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
        $include_default_shortcodes = $this->config->get('plugins.shortcode-core.include_default_shortcodes', true);
        if ($include_default_shortcodes) {
            $this->shortcodes->registerAllShortcodes(__DIR__ . '/classes/shortcodes', ['ignore' => ['Shortcode', 'ShortcodeObject']]);
        }

        // Add custom shortcodes directory if provided
        $custom_shortcodes = $this->config->get('plugins.shortcode-core.custom_shortcodes');
        if (isset($custom_shortcodes)) {
            $this->shortcodes->registerAllShortcodes(GRAV_ROOT . $custom_shortcodes);
        }
    }

    /**
     * Add a twig filter for processing shortcodes in templates
     */
    public function onTwigInitialized()
    {
        $this->grav['twig']->twig()->addFilter(new TwigFilter('shortcodes', [$this->shortcodes, 'processShortcodes']));
        $this->grav['twig']->twig_vars['shortcode'] = new ShortcodeTwigVar();
    }

    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    public function registerNextGenEditorPlugin($event) {
        $config = $this->config->get('plugins.shortcode-core.nextgen-editor');
        $plugins = $event['plugins'];

        if ($config['env'] !== 'development') {
            $plugins['css'][] = 'plugin://shortcode-core/nextgen-editor/dist/css/app.css';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/dist/js/app.js';
        } else {
            $plugins['js'][]  = 'http://' . $config['dev_host'] . ':' . $config['dev_port'] . '/js/app.js';
        }

        $event['plugins']  = $plugins;
        return $event;
    }

    public function registerNextGenEditorPluginShortcodes($event) {
        $include_default_shortcodes = $this->config->get('plugins.shortcode-core.include_default_shortcodes', true);
        if ($include_default_shortcodes) {
            $plugins = $event['plugins'];

            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/shortcode-core.js';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/align/align.js';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/color/color.js';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/columns/columns.js';
            $plugins['css'][] = 'plugin://shortcode-core/nextgen-editor/shortcodes/details/details.css';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/details/details.js';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/div/div.js';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/figure/figure.js';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/fontawesome/fontawesome.js';
            $plugins['css'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/headers/headers.css';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/headers/headers.js';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/language/language.js';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/lorem/lorem.js';
            $plugins['css'][] = 'plugin://shortcode-core/nextgen-editor/shortcodes/mark/mark.css';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/mark/mark.js';
            $plugins['css'][] = 'plugin://shortcode-core/nextgen-editor/shortcodes/notice/notice.css';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/notice/notice.js';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/raw/raw.js';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/safe-email/safe-email.js';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/section/section.js';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/size/size.js';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/span/span.js';
            $plugins['js'][]  = 'plugin://shortcode-core/nextgen-editor/shortcodes/u/u.js';

            $event['plugins']  = $plugins;
        }
        return $event;
    }

    public function registerEditorProPlugin($event) {
        $plugins = $event['plugins'];
        
        // Add Editor Pro shortcode integration JavaScript
        $plugins['js'][] = 'plugin://shortcode-core/editor-pro/shortcode-integration.js';
        
        $event['plugins'] = $plugins;
        return $event;
    }

    public function onEditorProShortcodeRegister($event) {
        error_log('ShortcodeCore: onEditorProShortcodeRegister called');
        $shortcodes = $event['shortcodes'];
        
        // Register core shortcodes for Editor Pro
        $coreShortcodes = [
            [
                'name' => 'center',
                'title' => 'Center Align',
                'description' => 'Center align content',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'formatting',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" y1="10" x2="3" y2="10"></line><line x1="17" y1="6" x2="7" y2="6"></line><line x1="17" y1="14" x2="7" y2="14"></line><line x1="21" y1="18" x2="3" y2="18"></line></svg>',
                'attributes' => [],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => 'text-align: center;'
            ],
            [
                'name' => 'left',
                'title' => 'Left Align',
                'description' => 'Left align content',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'formatting',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" y1="10" x2="3" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="21" y1="18" x2="3" y2="18"></line></svg>',
                'attributes' => [],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => 'text-align: left;'
            ],
            [
                'name' => 'right',
                'title' => 'Right Align',
                'description' => 'Right align content',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'formatting',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" y1="10" x2="7" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="21" y1="18" x2="7" y2="18"></line></svg>',
                'attributes' => [],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => 'text-align: right;'
            ],
            [
                'name' => 'justify',
                'title' => 'Justify Align',
                'description' => 'Justify align content',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'formatting',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" y1="10" x2="3" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="21" y1="18" x2="3" y2="18"></line></svg>',
                'attributes' => [],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => 'text-align: justify;'
            ],
            [
                'name' => 'columns',
                'title' => 'Columns Layout',
                'description' => 'Create multi-column layout with customizable spacing',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'layout',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="18" rx="1"></rect><rect x="14" y="3" width="7" height="18" rx="1"></rect></svg>',
                'attributes' => [
                    'count' => [
                        'type' => 'number',
                        'title' => 'Column Count',
                        'min' => 2,
                        'max' => 6,
                        'default' => 2,
                        'required' => true
                    ],
                    'width' => [
                        'type' => 'text',
                        'title' => 'Column Width',
                        'default' => '200px',
                        'placeholder' => 'e.g., 200px or auto'
                    ],
                    'gap' => [
                        'type' => 'text',
                        'title' => 'Gap',
                        'default' => '30px',
                        'placeholder' => 'e.g., 30px or 1rem'
                    ],
                    'rule' => [
                        'type' => 'text',
                        'title' => 'Column Rule',
                        'default' => '',
                        'placeholder' => 'e.g., 1px solid #ccc'
                    ]
                ],
                'titleBarAttributes' => ['count', 'width'],
                'hasContent' => true,
                'cssTemplate' => 'columns: {{count}} {{width}}; column-gap: {{gap}}; column-rule: {{rule}};'
            ],
            [
                'name' => 'div',
                'title' => 'Div element',
                'description' => 'Create a custom Div element',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'layout',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>',
                'attributes' => [
                    'id' => [
                        'type' => 'text',
                        'title' => 'ID',
                        'default' => null,
                        'required' => false
                    ],
                    'class' => [
                        'type' => 'text',
                        'title' => 'Class',
                        'default' => null,
                        'placeholder' => 'e.g., font-bold text-blue-500'
                    ]
                ],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => ''
            ],
            [
                'name' => 'span',
                'title' => 'Span element',
                'description' => 'Create a custom Span element',
                'type' => 'inline',
                'plugin' => 'shortcode-core',
                'category' => 'layout',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4a2 2 0 0 1 2-2h8"></path><path d="M4 17v3a2 2 0 0 0 2 2h8"></path><path d="M20 7V4a2 2 0 0 0-2-2h-8"></path><path d="M20 17v3a2 2 0 0 1-2 2h-8"></path></svg>',
                'attributes' => [
                    'id' => [
                        'type' => 'text',
                        'title' => 'ID',
                        'default' => null,
                        'required' => false
                    ],
                    'class' => [
                        'type' => 'text',
                        'title' => 'Class',
                        'default' => null,
                        'placeholder' => 'e.g., font-bold text-blue-500'
                    ]
                ],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => ''
            ],
            [
                'name' => 'section',
                'title' => 'Section Container',
                'description' => 'Semantic section with optional styling',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'layout',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 22h2a2 2 0 0 0 2-2V7.5L14.5 2H6a2 2 0 0 0-2 2v3"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M3 15h6"></path><path d="M6 12v6"></path></svg>',
                'attributes' => [
                    'name' => [
                        'type' => 'text',
                        'title' => 'Section Name',
                        'default' => '',
                        'placeholder' => 'Optional section identifier'
                    ],
                    'page' => [
                        'type' => 'text',
                        'title' => 'Page of Content',
                        'default' => '',
                        'placeholder' => '/content/my-page',
                        'required' => false
                    ],
                ],
                'titleBarAttributes' => ['name'],
                'hasContent' => true,
                'cssTemplate' => null
            ],
            [
                'name' => 'notice',
                'title' => 'Notice Box',
                'description' => 'Create styled notice/alert boxes',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'content',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
                'attributes' => [
                    'type' => [
                        'type' => 'select',
                        'title' => 'Notice Type',
                        'options' => ['note', 'info', 'warning', 'error'],
                        'default' => 'note',
                        'required' => true
                    ]
                ],
                'titleBarAttributes' => ['type'],
                'hasContent' => true,
                'cssTemplate' => 'padding: 12px 16px; border-radius: 4px; margin: 16px 0; border-left: 4px solid #0ea5e9; background: #f0f9ff; color: #0c4a6e;'
            ],
            [
                'name' => 'mark',
                'title' => 'Highlight Text',
                'description' => 'Highlight text with background color',
                'type' => 'inline',
                'plugin' => 'shortcode-core',
                'category' => 'formatting',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11-6 6v3h9l3-3"></path><path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4"></path></svg>',
                'attributes' => [
                    'color' => [
                        'type' => 'color',
                        'title' => 'Highlight Color',
                        'default' => '#ffff00'
                    ]
                ],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => 'background-color: {{color}}; padding: 1px 2px; border-radius: 2px;'
            ],
            [
                'name' => 'fa',
                'title' => 'Font Awesome Icon',
                'description' => 'Insert Font Awesome icon',
                'type' => 'inline',
                'plugin' => 'shortcode-core',
                'category' => 'media',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
                'attributes' => [
                    'icon' => [
                        'type' => 'text',
                        'title' => 'Icon Name',
                        'default' => 'heart',
                        'required' => true,
                        'placeholder' => 'e.g., heart, star, user'
                    ],
                    'size' => [
                        'type' => 'select',
                        'title' => 'Size',
                        'options' => ['', 'xs', 'sm', 'lg', 'xl', '2x', '3x'],
                        'default' => ''
                    ]
                ],
                'titleBarAttributes' => ['icon'],
                'hasContent' => false,
                'cssTemplate' => '',
                'customRenderer' => 'function(blockData, config) {
                    // Extract icon name from params or content
                    let iconName = "";
                    
                    // Try to get icon from attributes first
                    if (blockData.attributes && blockData.attributes.icon) {
                        iconName = blockData.attributes.icon;
                    } else if (blockData.params) {
                        const iconMatch = blockData.params.match(/icon\\s*=\\s*["\']([^"\']+)["\']|icon\\s*=\\s*([^\\s\\]]+)/);
                        iconName = iconMatch ? (iconMatch[1] || iconMatch[2]) : "";
                    }
                    
                    // If no icon in params, check if content is the icon name
                    if (!iconName && blockData.content && !blockData.content.includes(" ") && !blockData.content.includes("<")) {
                        iconName = blockData.content;
                    }
                    
                    if (iconName) {
                        // Create FontAwesome icon HTML
                        const iconClass = iconName.startsWith("fa-") ? iconName : "fa-" + iconName;
                        let sizeClass = "";
                        
                        // Add size class if specified
                        if (blockData.attributes && blockData.attributes.size) {
                            sizeClass = " fa-" + blockData.attributes.size;
                        }
                        
                        let displayText = "<i class=\"fa " + iconClass + sizeClass + "\" style=\"margin: 0 4px;\"></i>";
                        
                        // Add any additional content after the icon if it is not the icon name
                        if (blockData.content && blockData.content !== iconName) {
                            displayText += " " + blockData.content;
                        }
                        return displayText;
                    } else {
                        // Fallback to showing the content or tagName
                        return blockData.content || blockData.tagName;
                    }
                }'
            ],
            [
                'name' => 'color',
                'title' => 'Text Color',
                'description' => 'Apply color to text',
                'type' => 'inline',
                'plugin' => 'shortcode-core',
                'category' => 'formatting',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5"></circle><circle cx="17.5" cy="10.5" r=".5"></circle><circle cx="8.5" cy="7.5" r=".5"></circle><circle cx="6.5" cy="12.5" r=".5"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"></path></svg>',
                'attributes' => [
                    'color' => [
                        'type' => 'color',
                        'title' => 'Text Color',
                        'default' => '#000000',
                        'required' => true
                    ]
                ],
                'titleBarAttributes' => ['color'],
                'hasContent' => true,
                'cssTemplate' => 'color: {{color}};'
            ],
            [
                'name' => 'size',
                'title' => 'Text Size',
                'description' => 'Change text size',
                'type' => 'inline',
                'plugin' => 'shortcode-core',
                'category' => 'formatting',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"></path><path d="M12 4v16"></path><path d="M6 20h12"></path></svg>',
                'attributes' => [
                    'size' => [
                        'type' => 'text',
                        'title' => 'Font Size',
                        'default' => '16px',
                        'required' => true,
                        'placeholder' => 'e.g., 16px, 1.2em, 120%'
                    ]
                ],
                'titleBarAttributes' => ['size'],
                'hasContent' => true,
                'cssTemplate' => 'font-size: {{size}};'
            ],
            [
                'name' => 'u',
                'title' => 'Underline',
                'description' => 'Underline text',
                'type' => 'inline',
                'plugin' => 'shortcode-core',
                'category' => 'formatting',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4v7a6 6 0 0 0 12 0V4"></path><line x1="4" y1="20" x2="20" y2="20"></line></svg>',
                'attributes' => [],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => 'text-decoration: underline;'
            ],
            [
                'name' => 'figure',
                'title' => 'Figure',
                'description' => 'Figure with optional caption',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'media',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>',
                'attributes' => [
                    'id' => [
                        'type' => 'text',
                        'title' => 'ID',
                        'default' => '',
                        'required' => false
                    ],
                    'class' => [
                        'type' => 'text',
                        'title' => 'Class',
                        'default' => '',
                        'placeholder' => 'e.g., full-width centered'
                    ],
                    'caption' => [
                        'type' => 'text',
                        'title' => 'Caption',
                        'default' => '',
                        'placeholder' => 'Figure caption text'
                    ]
                ],
                'titleBarAttributes' => ['caption'],
                'hasContent' => true,
                'cssTemplate' => ''
            ],
            [
                'name' => 'details',
                'title' => 'Details/Summary',
                'description' => 'Collapsible content section',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'content',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>',
                'attributes' => [
                    'summary' => [
                        'type' => 'text',
                        'title' => 'Summary Text',
                        'default' => 'Click to expand',
                        'required' => true
                    ],
                    'class' => [
                        'type' => 'text',
                        'title' => 'Class',
                        'default' => '',
                        'placeholder' => 'e.g., accordion-item'
                    ]
                ],
                'titleBarAttributes' => ['summary'],
                'hasContent' => true,
                'cssTemplate' => ''
            ],
            [
                'name' => 'h1',
                'title' => 'Heading 1',
                'description' => 'Level 1 heading',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'content',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h8"></path><path d="M4 18V6"></path><path d="M12 18V6"></path><path d="m17 12 3-2v8"></path></svg>',
                'attributes' => [
                    'id' => [
                        'type' => 'text',
                        'title' => 'ID',
                        'default' => '',
                        'required' => false
                    ],
                    'class' => [
                        'type' => 'text',
                        'title' => 'Class',
                        'default' => '',
                        'placeholder' => 'e.g., section-title'
                    ]
                ],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => ''
            ],
            [
                'name' => 'h2',
                'title' => 'Heading 2',
                'description' => 'Level 2 heading',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'content',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h8"></path><path d="M4 18V6"></path><path d="M12 18V6"></path><path d="M21 18h-4c0-4 4-3 4-6 0-1.5-2-2.5-3.5-1.5-.5.5-.5 1.5-.5 1.5"></path><path d="M17 18v-2"></path></svg>',
                'attributes' => [
                    'id' => [
                        'type' => 'text',
                        'title' => 'ID',
                        'default' => '',
                        'required' => false
                    ],
                    'class' => [
                        'type' => 'text',
                        'title' => 'Class',
                        'default' => '',
                        'placeholder' => 'e.g., subsection-title'
                    ]
                ],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => ''
            ],
            [
                'name' => 'h3',
                'title' => 'Heading 3',
                'description' => 'Level 3 heading',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'content',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h8"></path><path d="M4 18V6"></path><path d="M12 18V6"></path><path d="M17.5 10.5c1.7-1 3.5 0 3.5 1.5a2 2 0 0 1-2 2"></path><path d="M17 17.5c2 1.5 4 .3 4-1.5a2 2 0 0 0-2-2"></path></svg>',
                'attributes' => [
                    'id' => [
                        'type' => 'text',
                        'title' => 'ID',
                        'default' => '',
                        'required' => false
                    ],
                    'class' => [
                        'type' => 'text',
                        'title' => 'Class',
                        'default' => '',
                        'placeholder' => 'e.g., minor-heading'
                    ]
                ],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => ''
            ],
            [
                'name' => 'h4',
                'title' => 'Heading 4',
                'description' => 'Level 4 heading',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'content',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h8"></path><path d="M4 18V6"></path><path d="M12 18V6"></path><path d="M17 10v4h4"></path><path d="M21 10v8"></path></svg>',
                'attributes' => [
                    'id' => [
                        'type' => 'text',
                        'title' => 'ID',
                        'default' => '',
                        'required' => false
                    ],
                    'class' => [
                        'type' => 'text',
                        'title' => 'Class',
                        'default' => '',
                        'placeholder' => 'e.g., minor-heading'
                    ]
                ],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => ''
            ],
            [
                'name' => 'h5',
                'title' => 'Heading 5',
                'description' => 'Level 5 heading',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'content',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h8"></path><path d="M4 18V6"></path><path d="M12 18V6"></path><path d="M17 13v-3h4"></path><path d="M17 17.7c.4.2.8.3 1.3.3 1.5 0 2.7-1.1 2.7-2.5S19.8 13 18.3 13c-.5 0-1 .2-1.3.5"></path></svg>',
                'attributes' => [
                    'id' => [
                        'type' => 'text',
                        'title' => 'ID',
                        'default' => '',
                        'required' => false
                    ],
                    'class' => [
                        'type' => 'text',
                        'title' => 'Class',
                        'default' => '',
                        'placeholder' => 'e.g., minor-heading'
                    ]
                ],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => ''
            ],
            [
                'name' => 'h6',
                'title' => 'Heading 6',
                'description' => 'Level 6 heading',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'content',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h8"></path><path d="M4 18V6"></path><path d="M12 18V6"></path><circle cx="19" cy="16" r="2"></circle><path d="M20 10c-2 2-3 3.5-3 6"></path></svg>',
                'attributes' => [
                    'id' => [
                        'type' => 'text',
                        'title' => 'ID',
                        'default' => '',
                        'required' => false
                    ],
                    'class' => [
                        'type' => 'text',
                        'title' => 'Class',
                        'default' => '',
                        'placeholder' => 'e.g., minor-heading'
                    ]
                ],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => ''
            ],
            [
                'name' => 'raw',
                'title' => 'Raw Content',
                'description' => 'Prevent shortcode processing in content',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'content',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>',
                'attributes' => [],
                'titleBarAttributes' => [],
                'hasContent' => true,
                'cssTemplate' => '',
                'customRenderer' => 'function(blockData, config) {
                    // Raw content should be displayed as-is without processing
                    return blockData.content || "";
                }'
            ],
            [
                'name' => 'lang',
                'title' => 'Language Filter',
                'description' => 'Show content only for specific languages',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'content',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>',
                'attributes' => [
                    'lang' => [
                        'type' => 'text',
                        'title' => 'Language Codes',
                        'default' => 'en',
                        'required' => true,
                        'placeholder' => 'e.g., en, fr, de (comma-separated)'
                    ]
                ],
                'titleBarAttributes' => ['lang'],
                'hasContent' => true,
                'cssTemplate' => ''
            ],
            [
                'name' => 'lorem',
                'title' => 'Lorem Ipsum',
                'description' => 'Generate placeholder text',
                'type' => 'block',
                'plugin' => 'shortcode-core',
                'category' => 'content',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
                'attributes' => [
                    'p' => [
                        'type' => 'number',
                        'title' => 'Paragraphs',
                        'default' => 1,
                        'min' => 1,
                        'max' => 10
                    ],
                    's' => [
                        'type' => 'number',
                        'title' => 'Sentences',
                        'default' => 0,
                        'min' => 0,
                        'max' => 20
                    ],
                    'w' => [
                        'type' => 'number',
                        'title' => 'Words',
                        'default' => 0,
                        'min' => 0,
                        'max' => 100
                    ]
                ],
                'titleBarAttributes' => ['p', 's', 'w'],
                'hasContent' => false,
                'cssTemplate' => '',
                'customRenderer' => 'function(blockData, config) {
                    const p = blockData.attributes?.p || 1;
                    const s = blockData.attributes?.s || 0;
                    const w = blockData.attributes?.w || 0;
                    
                    let display = "Lorem ipsum";
                    if (p > 0) display += " - " + p + " paragraph" + (p > 1 ? "s" : "");
                    if (s > 0) display += ", " + s + " sentence" + (s > 1 ? "s" : "");
                    if (w > 0) display += ", " + w + " word" + (w > 1 ? "s" : "");
                    
                    return display;
                }'
            ],
            [
                'name' => 'safe-email',
                'title' => 'Safe Email',
                'description' => 'Obfuscated email address',
                'type' => 'inline',
                'plugin' => 'shortcode-core',
                'category' => 'content',
                'group' => 'Core Shortcodes',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"></path></svg>',
                'attributes' => [
                    'email' => [
                        'type' => 'text',
                        'title' => 'Email Address',
                        'default' => '',
                        'required' => true,
                        'placeholder' => 'user@example.com'
                    ],
                    'autolink' => [
                        'type' => 'checkbox',
                        'title' => 'Auto Link',
                        'default' => true
                    ]
                ],
                'titleBarAttributes' => ['email'],
                'hasContent' => false,
                'cssTemplate' => '',
                'customRenderer' => 'function(blockData, config) {
                    const email = blockData.attributes?.email || blockData.content || "";
                    if (email) {
                        // Display obfuscated version in editor
                        const parts = email.split("@");
                        if (parts.length === 2) {
                            return parts[0] + "[at]" + parts[1].replace(/\\./g, "[dot]");
                        }
                    }
                    return email || "safe-email";
                }'
            ]
        ];
        
        // Add all core shortcodes to the registry
        foreach ($coreShortcodes as $shortcode) {
            $shortcodes[] = $shortcode;
        }
        
        error_log('ShortcodeCore: Added ' . count($coreShortcodes) . ' shortcodes, total: ' . count($shortcodes));
        
        $event['shortcodes'] = $shortcodes;
        return $event;
    }

}
