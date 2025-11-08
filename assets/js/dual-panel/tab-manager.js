/**
 * Tab Manager - Dual Panel
 *
 * Manages tab navigation within right panel detail views.
 * Implements WordPress-style tab switching dengan smooth transitions.
 *
 * @package     WP_DataTable
 * @subpackage  Assets/JS/DualPanel
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/assets/js/dual-panel/tab-manager.js
 *
 * Description: Tab navigation manager untuk right panel detail view.
 *              Handles tab switching, hash-based state, smooth transitions,
 *              keyboard navigation, dan generic entity support.
 *
 * Features:
 * - Tab switching without page reload
 * - Hash-based tab state (#entity-123&tab=details)
 * - Smooth fade animations (CSS transitions)
 * - Event system untuk extensibility (wpdt:tabActivated)
 * - Keyboard navigation (left/right arrow keys)
 * - Generic entity support (not tied to specific entity type)
 * - AJAX tab content loading
 *
 * Entity Configuration:
 * - Set data-entity-type on .wpdt-panel element
 * - Tab views must have data-{entity}-id attribute
 * - AJAX handlers receive {entity}_id parameter
 *
 * Keyboard Navigation:
 * - Left Arrow: Previous tab
 * - Right Arrow: Next tab
 * - Tab wraps around (last → first, first → last)
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Ported dari wp-app-core/assets/js/datatable/wpapp-tab-manager.js
 * - Updated selectors: wpapp- → wpdt-
 * - Updated events: wpapp: → wpdt:
 * - Updated script handles: wpapp- → wpdt-
 * - Updated object names: wpAppTabManager → wpdtTabManager
 * - Preserved all functionality dari wp-app-core
 *
 * Original Source: wp-app-core v1.1.0 (2025-11-01)
 *
 * Example:
 * ```html
 * <div class="wpdt-panel" data-entity-type="customer">
 *   <div class="wpdt-tab-content wpdt-tab-autoload"
 *        data-customer-id="123"
 *        data-load-action="load_customer_branches_tab">
 * ```
 *
 * Events Triggered:
 * - wpdt:tab-switching - Before tab switches
 * - wpdt:tab-switched - After tab switched
 *
 * Usage:
 * ```javascript
 * jQuery(document).on('wpdt:tab-switched', function(e, data) {
 *     console.log('Tab switched to:', data.tabId);
 * });
 * ```
 */

(function($) {
    'use strict';

    /**
     * Tab Manager Class
     */
    class WPDTTabManager {
        constructor() {
            this.currentTab = null;
            this.currentEntity = null;
            this.tabWrapper = null;
            this.tabContents = null;

            this.init();
        }

        /**
         * Initialize tab manager
         */
        init() {
            this.tabWrapper = $('.wpdt-tab-wrapper');

            if (this.tabWrapper.length === 0) {
                // No tabs found
                return;
            }

            this.tabContents = $('.wpdt-tab-content');
            this.currentEntity = $('.wpdt-datatable-layout').data('entity');

            // Bind events
            this.bindEvents();

            // Check hash/query for active tab
            this.checkUrlForTab();

            // Debug mode
            if (typeof wpdtConfig !== 'undefined' && wpdtConfig.debug) {
                console.log('[WPDT Tab] Initialized', {
                    entity: this.currentEntity,
                    tabCount: this.tabWrapper.find('.nav-tab').length
                });
            }
        }

        /**
         * Bind event handlers
         */
        bindEvents() {
            const self = this;

            // Tab click
            this.tabWrapper.on('click', '.nav-tab', function(e) {
                e.preventDefault();

                const $tab = $(this);
                const tabId = $tab.data('tab');

                if (tabId) {
                    self.switchTab(tabId);
                }
            });

            // Listen to panel data loaded event to reinitialize
            $(document).on('wpdt:panel-data-loaded', function() {
                self.reinit();
            });

            // Keyboard navigation (arrow keys)
            this.tabWrapper.on('keydown', '.nav-tab', function(e) {
                const $tabs = self.tabWrapper.find('.nav-tab');
                const $current = $(this);
                const currentIndex = $tabs.index($current);

                let $next = null;

                // Left arrow or Up arrow
                if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    $next = $tabs.eq(currentIndex - 1);
                    if ($next.length === 0) {
                        $next = $tabs.last(); // Wrap to last
                    }
                }

                // Right arrow or Down arrow
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    $next = $tabs.eq(currentIndex + 1);
                    if ($next.length === 0) {
                        $next = $tabs.first(); // Wrap to first
                    }
                }

                if ($next && $next.length > 0) {
                    $next.focus().click();
                }
            });
        }

        /**
         * Switch to a specific tab
         *
         * @param {string} tabId Tab identifier
         */
        switchTab(tabId) {
            const $targetTab = $(`.nav-tab[data-tab="${tabId}"]`);
            const $targetContent = $(`#${tabId}.wpdt-tab-content`);

            if ($targetTab.length === 0 || $targetContent.length === 0) {
                console.warn('[WPDT Tab] Tab not found:', tabId);
                return;
            }

            // Check if already active
            if ($targetTab.hasClass('nav-tab-active')) {
                return;
            }

            // Trigger switching event
            const switchingEvent = $.Event('wpdt:tab-switching', {
                entity: this.currentEntity,
                fromTab: this.currentTab,
                toTab: tabId
            });
            $(document).trigger(switchingEvent);

            // If event prevented, stop
            if (switchingEvent.isDefaultPrevented()) {
                return;
            }

            // Remove active class from all tabs
            this.tabWrapper.find('.nav-tab').removeClass('nav-tab-active');

            // Add active class to target tab
            $targetTab.addClass('nav-tab-active');

            // Hide all tab contents
            this.tabContents.removeClass('active');

            // Show target content with fade animation
            $targetContent.addClass('active');

            // Update current tab
            this.currentTab = tabId;

            // Update URL hash
            this.updateUrlHash(tabId);

            // Trigger switched event
            $(document).trigger('wpdt:tab-switched', {
                entity: this.currentEntity,
                tabId: tabId
            });

            // Auto-load tab content if needed
            this.autoLoadTabContent($targetContent);

            // Debug
            if (typeof wpdtConfig !== 'undefined' && wpdtConfig.debug) {
                console.log('[WPDT Tab] Switched to:', tabId);
            }
        }

        /**
         * Auto-load tab content via AJAX if tab has wpdt-tab-autoload class
         *
         * @param {jQuery} $tab Tab content element
         */
        autoLoadTabContent($tab) {
            console.log('[WPDT Tab] autoLoadTabContent called');
            console.log('[WPDT Tab] Tab element:', $tab);
            console.log('[WPDT Tab] Has wpdt-tab-autoload:', $tab.hasClass('wpdt-tab-autoload'));
            console.log('[WPDT Tab] Has loaded:', $tab.hasClass('loaded'));

            // Check if tab needs auto-loading
            if (!$tab.hasClass('wpdt-tab-autoload')) {
                console.log('[WPDT Tab] Tab does NOT have wpdt-tab-autoload class - skipping');
                return;
            }

            // Check if already loaded
            if ($tab.hasClass('loaded')) {
                console.log('[WPDT Tab] Tab already loaded - skipping');
                return;
            }

            // Get entity type from panel (default to 'agency' for backward compatibility)
            const $panel = $('.wpdt-panel');
            const entityType = $panel.attr('data-entity-type') || this.currentEntity || 'agency';
            const entityIdAttr = 'data-' + entityType + '-id';

            // Get data attributes (use .attr() to avoid jQuery .data() caching)
            const entityId = $tab.attr(entityIdAttr);
            const loadAction = $tab.attr('data-load-action');
            const contentTarget = $tab.attr('data-content-target');
            const errorMessage = $tab.attr('data-error-message') || 'Failed to load content';

            console.log('[WPDT Tab] Data attributes:', {
                entityType: entityType,
                entityIdAttr: entityIdAttr,
                entityId: entityId,
                loadAction: loadAction,
                contentTarget: contentTarget,
                errorMessage: errorMessage
            });

            if (!loadAction || !entityId) {
                console.error('[WPDT Tab] Missing required data attributes for auto-load');
                console.error('[WPDT Tab] loadAction:', loadAction);
                console.error('[WPDT Tab] ' + entityIdAttr + ':', entityId);
                return;
            }

            console.log('[WPDT Tab] Starting AJAX request for:', loadAction);

            // Show loading state
            $tab.find('.wpdt-tab-loading').show();
            $tab.find('.wpdt-tab-loaded-content').hide();
            $tab.find('.wpdt-tab-error').removeClass('visible');

            // Build AJAX data with dynamic entity ID parameter
            const ajaxData = {
                action: loadAction,
                nonce: wpdtConfig.nonce
            };
            ajaxData[entityType + '_id'] = entityId;

            // Make AJAX request
            $.ajax({
                url: wpdtConfig.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    console.log('[WPDT Tab] AJAX Success Response:', response);
                    $tab.find('.wpdt-tab-loading').hide();

                    if (response.success && response.data.html) {
                        // Load content into target
                        console.log('[WPDT Tab] Loading HTML into:', contentTarget);
                        console.log('[WPDT Tab] HTML length:', response.data.html.length);

                        const $content = $tab.find(contentTarget);
                        console.log('[WPDT Tab] Target element found:', $content.length);

                        $content.html(response.data.html).addClass('loaded').show();

                        // Mark tab as loaded
                        $tab.addClass('loaded');

                        console.log('[WPDT Tab] Content loaded successfully for:', loadAction);
                        console.log('[WPDT Tab] HTML preview:', response.data.html.substring(0, 200));
                    } else {
                        // Show error
                        $tab.find('.wpdt-error-message').text(response.data.message || errorMessage);
                        $tab.find('.wpdt-tab-error').addClass('visible');

                        console.error('[WPDT Tab] Load failed:', response);
                    }
                },
                error: function(xhr, status, error) {
                    $tab.find('.wpdt-tab-loading').hide();
                    $tab.find('.wpdt-error-message').text(errorMessage);
                    $tab.find('.wpdt-tab-error').addClass('visible');

                    console.error('[WPDT Tab] AJAX error:', error);
                }
            });
        }

        /**
         * Update URL hash with tab ID
         *
         * @param {string} tabId Tab identifier
         */
        updateUrlHash(tabId) {
            const currentHash = window.location.hash;

            // Parse existing hash (e.g., #customer-123)
            const hashParts = currentHash.substring(1).split('&');
            const entityHash = hashParts[0]; // customer-123

            // Create new hash with tab parameter
            const newHash = entityHash ? `${entityHash}&tab=${tabId}` : `tab=${tabId}`;

            // Update hash without triggering hashchange event
            history.replaceState(null, null, `#${newHash}`);
        }

        /**
         * Check URL for tab parameter
         *
         * Supports both hash (#tab=details) and query string (?tab=details)
         */
        checkUrlForTab() {
            let tabId = null;

            // Check hash parameter (#entity-123&tab=details)
            const hash = window.location.hash.substring(1);
            if (hash) {
                const hashParams = hash.split('&');
                for (let param of hashParams) {
                    if (param.startsWith('tab=')) {
                        tabId = param.split('=')[1];
                        break;
                    }
                }
            }

            // Check query string (?tab=details)
            if (!tabId) {
                const urlParams = new URLSearchParams(window.location.search);
                tabId = urlParams.get('tab');
            }

            // Switch to tab if found
            if (tabId) {
                this.switchTab(tabId);
            } else {
                // Switch to first tab as default
                const $firstTab = this.tabWrapper.find('.nav-tab').first();
                if ($firstTab.length > 0) {
                    this.switchTab($firstTab.data('tab'));
                }
            }
        }

        /**
         * Reinitialize after panel content changes
         *
         * Called after AJAX loads new panel content
         */
        reinit() {
            // Update references
            this.tabWrapper = $('.wpdt-tab-wrapper');
            this.tabContents = $('.wpdt-tab-content');

            if (this.tabWrapper.length === 0) {
                return;
            }

            // Rebind events (using event delegation, so not needed)
            // this.bindEvents();

            // Check for tab in URL
            this.checkUrlForTab();

            // Debug
            if (typeof wpdtConfig !== 'undefined' && wpdtConfig.debug) {
                console.log('[WPDT Tab] Reinitialized after panel load');
            }
        }

        /**
         * Public API: Switch to tab programmatically
         *
         * @param {string} tabId Tab identifier
         */
        goTo(tabId) {
            this.switchTab(tabId);
        }

        /**
         * Public API: Get current active tab
         *
         * @return {string|null} Current tab ID
         */
        getCurrent() {
            return this.currentTab;
        }

        /**
         * Public API: Get all available tabs
         *
         * @return {Array} Array of tab IDs
         */
        getAll() {
            const tabs = [];
            this.tabWrapper.find('.nav-tab').each(function() {
                tabs.push($(this).data('tab'));
            });
            return tabs;
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Create global instance
        window.wpdtTabManager = new WPDTTabManager();
    });

})(jQuery);
