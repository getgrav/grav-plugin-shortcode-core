/**
 * Shortcode Core Integration for Editor Pro
 * Extends Editor Pro with shortcode insertion and preview capabilities
 */

(function() {
    'use strict';

    // Wait for Editor Pro to be available
    function waitForEditorPro(callback) {
        if (window.EditorPro && window.EditorPro.registerPlugin) {
            callback();
        } else {
            setTimeout(() => waitForEditorPro(callback), 100);
        }
    }

    // Shortcode definitions from shortcode-core
    const SHORTCODES = [
        {
            name: 'align',
            title: 'Align Content',
            description: 'Align content left, center, or right',
            params: { direction: { type: 'select', options: ['left', 'center', 'right'], default: 'center' } },
            hasContent: true,
            template: '[align direction="{{direction}}"]{{content}}[/align]'
        },
        {
            name: 'color',
            title: 'Text Color',
            description: 'Change text color',
            params: { 
                color: { type: 'color', default: '#000000' },
                background: { type: 'color', default: '', optional: true }
            },
            hasContent: true,
            template: '[color color="{{color}}"{{#if background}} background="{{background}}"{{/if}}]{{content}}[/color]'
        },
        {
            name: 'columns',
            title: 'Columns Layout',
            description: 'Create multi-column layout',
            params: { 
                count: { type: 'number', default: 2, min: 2, max: 6 },
                gap: { type: 'text', default: '1rem', optional: true }
            },
            hasContent: true,
            template: '[columns count="{{count}}"{{#if gap}} gap="{{gap}}"{{/if}}]{{content}}[/columns]'
        },
        {
            name: 'details',
            title: 'Details/Summary',
            description: 'Collapsible content section',
            params: { 
                summary: { type: 'text', default: 'Click to expand' },
                open: { type: 'checkbox', default: false, optional: true }
            },
            hasContent: true,
            template: '[details summary="{{summary}}"{{#if open}} open="true"{{/if}}]{{content}}[/details]'
        },
        {
            name: 'div',
            title: 'Div Container',
            description: 'Generic div container with class',
            params: { 
                class: { type: 'text', default: '' },
                id: { type: 'text', default: '', optional: true }
            },
            hasContent: true,
            template: '[div class="{{class}}"{{#if id}} id="{{id}}"{{/if}}]{{content}}[/div]'
        },
        {
            name: 'figure',
            title: 'Figure with Caption',
            description: 'Image figure with caption',
            params: { 
                src: { type: 'text', default: '' },
                caption: { type: 'text', default: '' },
                class: { type: 'text', default: '', optional: true }
            },
            hasContent: false,
            template: '[figure src="{{src}}" caption="{{caption}}"{{#if class}} class="{{class}}"{{/if}}]'
        },
        {
            name: 'fontawesome',
            title: 'Font Awesome Icon',
            description: 'Insert Font Awesome icon',
            params: { 
                icon: { type: 'text', default: 'heart' },
                size: { type: 'select', options: ['xs', 'sm', 'lg', 'xl', '2x', '3x'], default: '', optional: true }
            },
            hasContent: false,
            template: '[fontawesome icon="{{icon}}"{{#if size}} size="{{size}}"{{/if}}]'
        },
        {
            name: 'mark',
            title: 'Highlight Text',
            description: 'Highlight text with background color',
            params: { 
                color: { type: 'color', default: '#ffff00' }
            },
            hasContent: true,
            template: '[mark color="{{color}}"]{{content}}[/mark]'
        },
        {
            name: 'notice',
            title: 'Notice Box',
            description: 'Create notice/alert boxes',
            params: { 
                type: { type: 'select', options: ['note', 'info', 'warning', 'error'], default: 'note' }
            },
            hasContent: true,
            template: '[notice type="{{type}}"]{{content}}[/notice]'
        },
        {
            name: 'section',
            title: 'Section Container',
            description: 'Section container with optional background',
            params: { 
                background: { type: 'color', default: '', optional: true },
                class: { type: 'text', default: '', optional: true }
            },
            hasContent: true,
            template: '[section]{{content}}[/section]'
        },
        {
            name: 'size',
            title: 'Text Size',
            description: 'Change text size',
            params: { 
                size: { type: 'select', options: ['xs', 'sm', 'md', 'lg', 'xl', '2xl'], default: 'md' }
            },
            hasContent: true,
            template: '[size size="{{size}}"]{{content}}[/size]'
        }
    ];

    // Shortcode Builder Dialog
    class ShortcodeBuilder {
        constructor(editorPro) {
            this.editorPro = editorPro;
            this.createDialog();
        }

        createDialog() {
            // Create modal backdrop
            this.backdrop = document.createElement('div');
            this.backdrop.className = 'shortcode-builder-backdrop';
            this.backdrop.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 10000;
                display: none;
            `;

            // Create modal dialog
            this.dialog = document.createElement('div');
            this.dialog.className = 'shortcode-builder-dialog';
            this.dialog.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 600px;
                max-width: 90vw;
                max-height: 80vh;
                background: white;
                border-radius: 8px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                overflow: hidden;
                z-index: 10001;
            `;

            this.backdrop.appendChild(this.dialog);
            document.body.appendChild(this.backdrop);

            // Close on backdrop click
            this.backdrop.addEventListener('click', (e) => {
                if (e.target === this.backdrop) {
                    this.close();
                }
            });
        }

        show() {
            this.renderShortcodeList();
            this.backdrop.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        close() {
            this.backdrop.style.display = 'none';
            document.body.style.overflow = '';
        }

        renderShortcodeList() {
            this.dialog.innerHTML = `
                <div style="padding: 20px; border-bottom: 1px solid #eee;">
                    <h3 style="margin: 0; color: #333;">Insert Shortcode</h3>
                    <p style="margin: 10px 0 0; color: #666; font-size: 14px;">Choose a shortcode to insert into your content.</p>
                </div>
                <div style="max-height: 400px; overflow-y: auto; padding: 20px;">
                    ${SHORTCODES.map(shortcode => `
                        <div class="shortcode-option" style="
                            border: 1px solid #e1e1e1;
                            border-radius: 6px;
                            padding: 16px;
                            margin-bottom: 12px;
                            cursor: pointer;
                            transition: all 0.2s;
                        " data-shortcode="${shortcode.name}">
                            <h4 style="margin: 0 0 8px; color: #333; font-size: 16px;">${shortcode.title}</h4>
                            <p style="margin: 0; color: #666; font-size: 14px;">${shortcode.description}</p>
                        </div>
                    `).join('')}
                </div>
                <div style="padding: 20px; border-top: 1px solid #eee; text-align: right;">
                    <button class="cancel-btn" style="
                        background: #f5f5f5;
                        border: 1px solid #ddd;
                        padding: 8px 16px;
                        border-radius: 4px;
                        cursor: pointer;
                        margin-right: 8px;
                    ">Cancel</button>
                </div>
            `;

            // Add hover effects
            const options = this.dialog.querySelectorAll('.shortcode-option');
            options.forEach(option => {
                option.addEventListener('mouseenter', () => {
                    option.style.borderColor = '#4CAF50';
                    option.style.backgroundColor = '#f8fff8';
                });
                option.addEventListener('mouseleave', () => {
                    option.style.borderColor = '#e1e1e1';
                    option.style.backgroundColor = '';
                });
                option.addEventListener('click', () => {
                    const shortcodeName = option.dataset.shortcode;
                    this.showShortcodeForm(shortcodeName);
                });
            });

            // Cancel button
            this.dialog.querySelector('.cancel-btn').addEventListener('click', () => {
                this.close();
            });
        }

        showShortcodeForm(shortcodeName) {
            const shortcode = SHORTCODES.find(s => s.name === shortcodeName);
            if (!shortcode) return;

            this.dialog.innerHTML = `
                <div style="padding: 20px; border-bottom: 1px solid #eee;">
                    <h3 style="margin: 0; color: #333;">${shortcode.title}</h3>
                    <p style="margin: 10px 0 0; color: #666; font-size: 14px;">${shortcode.description}</p>
                </div>
                <form class="shortcode-form" style="padding: 20px;">
                    ${Object.entries(shortcode.params).map(([name, param]) => `
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 600; color: #333;">
                                ${name.charAt(0).toUpperCase() + name.slice(1)}${param.optional ? ' (optional)' : ''}
                            </label>
                            ${this.renderFormField(name, param)}
                        </div>
                    `).join('')}
                    ${shortcode.hasContent ? `
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; margin-bottom: 4px; font-weight: 600; color: #333;">Content</label>
                            <textarea name="content" placeholder="Enter content..." style="
                                width: 100%;
                                min-height: 80px;
                                padding: 8px;
                                border: 1px solid #ddd;
                                border-radius: 4px;
                                font-family: inherit;
                                resize: vertical;
                            "></textarea>
                        </div>
                    ` : ''}
                </form>
                <div style="padding: 20px; border-top: 1px solid #eee; text-align: right;">
                    <button class="back-btn" style="
                        background: #f5f5f5;
                        border: 1px solid #ddd;
                        padding: 8px 16px;
                        border-radius: 4px;
                        cursor: pointer;
                        margin-right: 8px;
                    ">Back</button>
                    <button class="insert-btn" style="
                        background: #4CAF50;
                        color: white;
                        border: none;
                        padding: 8px 16px;
                        border-radius: 4px;
                        cursor: pointer;
                    ">Insert Shortcode</button>
                </div>
            `;

            // Event listeners
            this.dialog.querySelector('.back-btn').addEventListener('click', () => {
                this.renderShortcodeList();
            });

            this.dialog.querySelector('.insert-btn').addEventListener('click', () => {
                this.insertShortcode(shortcode);
            });

            // Focus first input
            const firstInput = this.dialog.querySelector('input, textarea, select');
            if (firstInput) firstInput.focus();
        }

        renderFormField(name, param) {
            const baseStyle = `
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-family: inherit;
            `;

            switch (param.type) {
                case 'select':
                    return `
                        <select name="${name}" style="${baseStyle}">
                            ${param.options.map(option => `
                                <option value="${option}" ${option === param.default ? 'selected' : ''}>${option}</option>
                            `).join('')}
                        </select>
                    `;
                case 'color':
                    return `
                        <input type="color" name="${name}" value="${param.default}" style="${baseStyle} height: 40px;">
                    `;
                case 'number':
                    return `
                        <input type="number" name="${name}" value="${param.default}" 
                               ${param.min ? `min="${param.min}"` : ''} 
                               ${param.max ? `max="${param.max}"` : ''} 
                               style="${baseStyle}">
                    `;
                case 'checkbox':
                    return `
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" name="${name}" ${param.default ? 'checked' : ''} 
                                   style="margin-right: 8px;">
                            Enable
                        </label>
                    `;
                default:
                    return `
                        <input type="text" name="${name}" value="${param.default || ''}" 
                               placeholder="${param.placeholder || ''}" style="${baseStyle}">
                    `;
            }
        }

        insertShortcode(shortcode) {
            const form = this.dialog.querySelector('.shortcode-form');
            const formData = new FormData(form);
            
            // Build shortcode string
            let shortcodeText = `[${shortcode.name}`;
            
            // Add parameters
            Object.entries(shortcode.params).forEach(([name, param]) => {
                const value = formData.get(name);
                if (value && (!param.optional || value !== param.default)) {
                    if (param.type === 'checkbox') {
                        if (form.querySelector(`[name="${name}"]`).checked) {
                            shortcodeText += ` ${name}="true"`;
                        }
                    } else {
                        shortcodeText += ` ${name}="${value}"`;
                    }
                }
            });
            
            shortcodeText += ']';
            
            // Add content for closing shortcodes
            if (shortcode.hasContent) {
                const content = formData.get('content') || '';
                shortcodeText += content + `[/${shortcode.name}]`;
            }

            // Insert into editor
            this.editorPro.insertShortcode(shortcodeText, shortcode);
            this.close();
        }
    }

    // Editor Pro Shortcode Integration Plugin
    const EditorProShortcodePlugin = {
        name: 'shortcode-core',
        
        init(editorPro) {
            this.editorPro = editorPro;
            this.shortcodeBuilder = new ShortcodeBuilder(editorPro);
            this.addToolbarButton();
            this.addShortcut();
        },

        addToolbarButton() {
            // Find shortcode button in toolbar and enhance it
            const shortcodeBtn = this.editorPro.toolbar.querySelector('[data-toolbar-item="shortcodeBlock"]');
            if (shortcodeBtn) {
                shortcodeBtn.title = 'Insert Shortcode (Ctrl+Shift+S)';
                shortcodeBtn.onclick = () => {
                    this.shortcodeBuilder.show();
                };
            }
        },

        addShortcut() {
            // Add keyboard shortcut
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.shiftKey && e.key === 'S') {
                    e.preventDefault();
                    if (this.editorPro.editor.isFocused) {
                        this.shortcodeBuilder.show();
                    }
                }
            });
        }
    };

    // Extend EditorPro class with shortcode insertion method
    function extendEditorPro() {
        if (!window.EditorPro || !window.EditorPro.prototype) {
            setTimeout(extendEditorPro, 100);
            return;
        }

        // Add shortcode insertion method
        window.EditorPro.prototype.insertShortcode = function(shortcodeText, shortcodeData) {
            const blockId = this.preserver.generateBlockId();
            
            // Parse shortcode to extract tag name and params
            const match = shortcodeText.match(/^\[([^\]\/\s]+)([^\]]*)\]/);
            const tagName = match ? match[1] : 'unknown';
            const params = match ? match[2] : '';
            const isClosing = shortcodeText.includes('[/');
            
            this.preservedBlocks.set(blockId, {
                type: 'shortcode',
                tagName: tagName,
                params: params,
                content: isClosing ? shortcodeText.split(']')[1].split('[/')[0] : '',
                original: shortcodeText,
                isClosing: isClosing
            });
            
            this.editor.commands.insertContent({
                type: 'preservedBlock',
                attrs: {
                    blockId: blockId,
                    blockType: 'shortcode',
                    blockContent: shortcodeText,
                    blockData: {
                        type: 'shortcode',
                        tagName: tagName,
                        params: params,
                        content: isClosing ? shortcodeText.split(']')[1].split('[/')[0] : '',
                        original: shortcodeText,
                        isClosing: isClosing
                    }
                }
            });
        };
    }

    // Initialize when Editor Pro is ready
    waitForEditorPro(() => {
        // Wait a bit for Editor Pro to initialize its shortcode registry
        setTimeout(() => {
            // Check if PHP shortcodes have been registered
            let phpShortcodesAvailable = false;
            
            // Check both the global array and the Editor Pro registry
            if (window.EditorProShortcodes && window.EditorProShortcodes.length > 0) {
                phpShortcodesAvailable = true;
            }
            
            // Also check if Editor Pro has a shortcode registry with entries
            if (window.EditorPro && window.EditorPro.shortcodeRegistry) {
                // Trigger initialization if needed
                if (window.EditorPro.shortcodeRegistry.ensureInitialized) {
                    window.EditorPro.shortcodeRegistry.ensureInitialized();
                }
                
                // Check if registry has entries
                if (window.EditorPro.shortcodeRegistry.shortcodes && window.EditorPro.shortcodeRegistry.shortcodes.size > 0) {
                    phpShortcodesAvailable = true;
                }
            }
            
            if (phpShortcodesAvailable) {
                // console.log('Using new PHP-based shortcode registration system');
                return;
            }
            
            // Fallback to old system only if no PHP registrations
            // console.log('No PHP shortcode registrations found, using legacy JS system');
            extendEditorPro();
            
            // Register the plugin
            if (window.EditorPro.registerPlugin) {
                window.EditorPro.registerPlugin(EditorProShortcodePlugin);
            }
            
            // console.log('Shortcode Core integration loaded for Editor Pro (legacy mode)');
        }, 100); // Small delay to ensure Editor Pro has initialized
    });

})();