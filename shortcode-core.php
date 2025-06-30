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

        // don't continue if this is admin and plugin is disabled for admin
        if (!$this->config->get('plugins.shortcode-core.active_admin') && $this->isAdmin()) {
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
                'icon' => '⊡',
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
                'icon' => '⊣',
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
                'icon' => '⊢',
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
                'icon' => '⊞',
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
                'icon' => '⫸',
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
                'icon' => '⧈',
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
                'icon' => '⧉',
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
                'icon' => '⧇',
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
                'icon' => '⚠',
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
                'icon' => '🖍',
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
                'icon' => '★',
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
                'icon' => '🎨',
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
                'icon' => '↕',
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
                'icon' => 'U̲',
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
                'icon' => '🖼',
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
                'icon' => '▶',
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
                'icon' => 'H₁',
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
                'icon' => 'H₂',
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
                'icon' => 'H₃',
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
                'icon' => 'H₄',
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
                'icon' => 'H₅',
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
                'icon' => 'H₆',
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
                'icon' => '</>',
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
                'icon' => '🌐',
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
                'icon' => '¶',
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
                'icon' => '@',
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
