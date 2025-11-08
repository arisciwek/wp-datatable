/**
 * Panel Manager - Dual Panel
 *
 * Manages left/right panel interactions untuk DataTable dashboards.
 * Implements Perfex CRM-style smooth panel transitions.
 *
 * @package     WP_DataTable
 * @subpackage  Assets/JS/DualPanel
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/assets/js/dual-panel/panel-manager.js
 *
 * Description: Panel interaction manager untuk dual panel layout.
 *              Handles panel open/close, AJAX loading, smooth transitions,
 *              hash-based navigation, dan nested entity prevention.
 *
 * Features:
 * - Smooth panel open/close animations (CSS transitions)
 * - AJAX data loading untuk panel content
 * - Hash-based navigation (#entity-123)
 * - Event system untuk extensibility (wpdt:panelOpened, wpdt:panelClosed)
 * - Close button handling
 * - Nested entity prevention (prevents URL collision)
 * - Loading states dan error handling
 *
 * Nested Entity Prevention:
 * - Buttons dengan class .wpdt-panel-trigger inside .wpdt-tab-content are ignored
 * - Buttons dengan data-nested="true" are ignored
 * - Recommendation: Use .wpdt-nested-trigger class for nested entities
 *
 * Button Class Convention:
 * - .wpdt-panel-trigger → Opens right panel (parent entity only)
 * - .wpdt-nested-trigger → For nested entities (won't open parent panel)
 * - data-nested="true" → Flag untuk nested buttons
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Ported dari wp-app-core/assets/js/datatable/wpapp-panel-manager.js
 * - Updated selectors: wpapp- → wpdt-
 * - Updated events: wpapp: → wpdt:
 * - Updated script handles: wpapp- → wpdt-
 * - Updated object names: wpAppPanelManager → wpdtPanelManager
 * - Preserved all functionality dari wp-app-core
 *
 * Original Source: wp-app-core v1.1.1 (2025-01-02)
 * - .wpdt-nested-trigger → For nested entities (handled by custom code)
 *
 * Events Triggered:
 * - wpdt:panel-opening - Before panel opens
 * - wpdt:panel-opened - After panel fully opened
 * - wpdt:panel-closing - Before panel closes
 * - wpdt:panel-closed - After panel fully closed
 * - wpdt:panel-loading - Data loading started
 * - wpdt:panel-data-loaded - Data loaded successfully
 * - wpdt:panel-error - Error occurred
 *
 * Usage:
 * ```javascript
 * jQuery(document).on('wpdt:panel-data-loaded', function(e, data) {
 *     console.log('Panel loaded:', data.entity, data.id);
 * });
 * ```
 *
 * Nested Entity Example:
 * ```html
 * <!-- Parent entity (opens panel) -->
 * <button class="wpdt-panel-trigger" data-id="123" data-entity="customer">
 *     View Customer
 * </button>
 *
 * <!-- Nested entity (ignored by panel manager) -->
 * <button class="wpdt-nested-trigger" data-id="5" data-entity="branch">
 *     View Branch
 * </button>
 * ```
 */

(function($) {
    'use strict';

    /**
     * Panel Manager Class
     */
    class WPDTPanelManager {
        constructor() {
            this.layout = null;
            this.leftPanel = null;
            this.rightPanel = null;
            this.currentEntity = null;
            this.currentId = null;
            this.isOpen = false;
            this.ajaxRequest = null;
            this.loadingTimeout = null;
            this.dataTable = null;

            this.init();
        }

        /**
         * Initialize panel manager
         */
        init() {
            this.layout = $('.wpdt-datatable-layout');

            if (this.layout.length === 0) {
                // No DataTable layout found
                return;
            }

            this.leftPanel = this.layout.find('.wpdt-left-panel');
            this.rightPanel = this.layout.find('.wpdt-right-panel');
            this.currentEntity = this.layout.data('entity');

            // Bind events
            this.bindEvents();

            // Get DataTable instance
            this.getDataTableInstance();

            // Check hash on load
            this.checkHashOnLoad();

            // Debug mode
            if (typeof wpdtConfig !== 'undefined' && wpdtConfig.debug) {
                console.log('[WPDT Panel] Initialized', {
                    entity: this.currentEntity,
                    hasLayout: this.layout.length > 0,
                    hasLeftPanel: this.leftPanel.length > 0,
                    hasRightPanel: this.rightPanel.length > 0,
                    hasDataTable: this.dataTable !== null
                });
            }
        }

        /**
         * Get DataTable instance from DOM
         */
        getDataTableInstance() {
            const $table = $('.wpdt-datatable');

            if ($table.length > 0 && $.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
                this.dataTable = $table.DataTable();
                console.log('[WPDT Panel] DataTable instance found');
            } else {
                console.log('[WPDT Panel] No DataTable instance found');
            }
        }

        /**
         * Bind event handlers
         */
        bindEvents() {
            const self = this;

            // DataTable row click
            $(document).on('click', '.wpdt-datatable tbody tr', function(e) {
                // Ignore if clicking on action buttons
                if ($(e.target).closest('.wpdt-actions').length > 0) {
                    return;
                }

                // ✅ NESTED ENTITY PREVENTION
                // Check if row is inside a tab content (nested context)
                const $row = $(this);
                const isNested = $row.closest('.wpdt-tab-content').length > 0;

                if (isNested) {
                    console.warn('[WPDT Panel] Nested entity row clicked - ignoring panel trigger', {
                        rowId: $row.attr('id'),
                        suggestion: 'Row clicks only work for parent entity DataTables'
                    });
                    return; // Don't trigger panel for nested entities
                }

                const entityId = $row.data('id');

                if (entityId) {
                    self.openPanel(entityId);
                }
            });

            // Panel trigger button click (View button)
            $(document).on('click', '.wpdt-panel-trigger', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const entityId = $(this).data('id');
                const entity = $(this).data('entity');

                // ✅ NESTED ENTITY PREVENTION
                // Check if button is inside a tab content (nested context)
                const isNested = $(this).closest('.wpdt-tab-content').length > 0;
                const isNestedFlag = $(this).data('nested') === true;

                if (isNested || isNestedFlag) {
                    console.warn('[WPDT Panel] Nested entity button detected - ignoring panel trigger', {
                        entity: entity,
                        id: entityId,
                        suggestion: 'Use .wpdt-nested-trigger class for nested entities'
                    });
                    return; // Don't trigger panel for nested entities
                }

                // Verify entity matches current panel entity
                if (entity === self.currentEntity && entityId) {
                    self.openPanel(entityId);
                }
            });

            // Close button click
            this.rightPanel.on('click', '.wpdt-panel-close', function(e) {
                e.preventDefault();
                self.closePanel();
            });

            // Hash change (browser back/forward)
            $(window).on('hashchange', function() {
                self.checkHashChange();
            });

            // Escape key to close
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.isOpen) {
                    self.closePanel();
                }
            });

            // Click outside to close (optional)
            // Uncomment if you want this behavior
            // $(document).on('click', function(e) {
            //     if (self.isOpen &&
            //         !$(e.target).closest('.wpdt-right-panel').length &&
            //         !$(e.target).closest('.wpdt-datatable tbody tr').length) {
            //         self.closePanel();
            //     }
            // });
        }

        /**
         * Open right panel
         *
         * @param {number} entityId Entity ID
         */
        openPanel(entityId) {
            if (this.currentId === entityId && this.isOpen) {
                // Already open with same ID
                return;
            }

            // Trigger opening event
            const openingEvent = $.Event('wpdt:panel-opening', {
                entity: this.currentEntity,
                id: entityId
            });
            $(document).trigger(openingEvent);

            // If event prevented, stop
            if (openingEvent.isDefaultPrevented()) {
                return;
            }

            this.currentId = entityId;

            // Update hash
            this.updateHash(entityId);

            // Show panel with animation
            this.showPanel();

            // Load data via AJAX
            this.loadPanelData(entityId);
        }

        /**
         * Close right panel
         */
        closePanel() {
            if (!this.isOpen) {
                return;
            }

            // Trigger closing event
            const closingEvent = $.Event('wpdt:panel-closing', {
                entity: this.currentEntity,
                id: this.currentId
            });
            $(document).trigger(closingEvent);

            // If event prevented, stop
            if (closingEvent.isDefaultPrevented()) {
                return;
            }

            // Abort any pending AJAX
            if (this.ajaxRequest) {
                this.ajaxRequest.abort();
                this.ajaxRequest = null;
            }

            // Hide panel with animation
            this.hidePanel();

            // Clear hash
            this.clearHash();

            // Reset current ID
            this.currentId = null;

            // Trigger closed event
            $(document).trigger('wpdt:panel-closed', {
                entity: this.currentEntity
            });
        }

        /**
         * Show panel with animation
         * Simplified pattern - exact copy from platform-staff
         */
        showPanel() {
            const self = this;

            console.group('🔍 DEBUG: Panel Opening Sequence');

            // Anti-flicker: Delay showing loading placeholder for 300ms
            // If AJAX completes < 300ms, loading won't show at all
            this.loadingTimeout = setTimeout(function() {
                self.rightPanel.find('.wpdt-loading-placeholder').addClass('visible');
                console.log('⏱️ Loading placeholder shown after 300ms delay');
            }, 300);

            // === BEFORE STATE ===
            const before = {
                scrollY: window.scrollY || window.pageYOffset,
                scrollX: window.scrollX || window.pageXOffset,
                docHeight: document.documentElement.scrollHeight,
                viewportHeight: window.innerHeight,
                layoutHeight: this.layout.outerHeight(),
                leftPanelWidth: this.leftPanel.width(),
                rightPanelDisplay: this.rightPanel.css('display'),
                rightPanelVisible: this.rightPanel.hasClass('visible'),
                timestamp: Date.now()
            };

            console.log('📊 BEFORE Panel Open:', before);

            // Step 1: Show panel immediately (no delay)
            console.log('⏱️ Step 1: Show panel (add visible class)');
            this.rightPanel.removeClass('hidden').addClass('visible');

            // Check immediately after
            const afterStep1 = {
                scrollY: window.scrollY || window.pageYOffset,
                rightPanelDisplay: this.rightPanel.css('display'),
                scrollDelta: (window.scrollY || window.pageYOffset) - before.scrollY
            };
            console.log('📊 After Step 1:', afterStep1);
            if (afterStep1.scrollDelta !== 0) {
                console.error('⚠️ SCROLL JUMP at Step 1! Delta:', afterStep1.scrollDelta);
            }

            // Step 2: Trigger left panel shrink
            console.log('⏱️ Step 2: Trigger left panel shrink (add with-right-panel class)');
            this.layout.addClass('with-right-panel');

            const afterStep2 = {
                scrollY: window.scrollY || window.pageYOffset,
                leftPanelWidth: this.leftPanel.width(),
                scrollDelta: (window.scrollY || window.pageYOffset) - before.scrollY
            };
            console.log('📊 After Step 2:', afterStep2);
            if (afterStep2.scrollDelta !== 0) {
                console.error('⚠️ SCROLL JUMP at Step 2! Delta:', afterStep2.scrollDelta);
            }

            this.isOpen = true;

            // Step 3: Wait for CSS transition (300ms) + buffer (50ms) = 350ms
            // Then adjust DataTable for new width
            setTimeout(function() {
                console.log('⏱️ Step 3: After 350ms - Adjust DataTable');

                if (self.dataTable) {
                    console.log('  → DataTable found, adjusting columns...');

                    // Force recalculation of column widths
                    // NO REDRAW needed - columns.adjust() is enough for panel resize
                    // This prevents flicker in left panel
                    self.dataTable.columns.adjust();

                    console.log('  → DataTable columns adjusted (no redraw to prevent flicker)');
                } else {
                    console.warn('  → No DataTable instance found');
                }

                const final = {
                    scrollY: window.scrollY || window.pageYOffset,
                    leftPanelWidth: self.leftPanel.width(),
                    rightPanelWidth: self.rightPanel.width(),
                    totalDelta: (window.scrollY || window.pageYOffset) - before.scrollY,
                    elapsed: Date.now() - before.timestamp
                };

                console.log('📊 FINAL State:', final);

                if (final.totalDelta !== 0) {
                    console.error('❌ TOTAL SCROLL JUMP: ' + final.totalDelta + 'px');
                } else {
                    console.log('✅ NO SCROLL JUMP - Perfect!');
                }

                console.groupEnd();
            }, 350);

            // Trigger opened event after animation
            setTimeout(() => {
                $(document).trigger('wpdt:panel-opened', {
                    entity: this.currentEntity,
                    id: this.currentId
                });
            }, 400); // After DataTable adjustment
        }

        /**
         * Hide panel with animation
         * Anti-flicker pattern adopted from platform-staff-script.js
         */
        hidePanel() {
            const self = this;
            console.log('[WPDT Panel] Closing right panel - Left panel will expand to 100%');

            // Clear loading timeout if still pending
            if (this.loadingTimeout) {
                clearTimeout(this.loadingTimeout);
                this.loadingTimeout = null;
            }

            // Remove visible class to trigger CSS transition
            this.rightPanel.removeClass('visible');

            // After CSS transition (300ms), clean up and adjust DataTable
            setTimeout(function() {
                self.rightPanel.addClass('hidden');
                self.layout.removeClass('with-right-panel');
                self.isOpen = false;

                // Adjust DataTable for full width
                if (self.dataTable) {
                    console.log('[WPDT Panel] Adjusting DataTable after panel closed');

                    // Force recalculation of column widths
                    self.dataTable.columns.adjust();

                    // Small delay then redraw for smooth rendering
                    setTimeout(function() {
                        self.dataTable.draw(false); // false = keep current page
                        console.log('[WPDT Panel] DataTable adjusted to full width');
                    }, 50);
                }

                console.log('[WPDT Panel] Left panel width:', self.leftPanel.width());
            }, 300); // Match CSS transition duration
        }

        /**
         * Load panel data via AJAX
         *
         * @param {number} entityId Entity ID
         */
        loadPanelData(entityId) {
            const ajaxAction = this.layout.data('ajax-action');

            if (!ajaxAction) {
                console.warn('[WPDT Panel] No AJAX action defined');
                return;
            }

            console.group('📡 DEBUG: AJAX Data Loading');
            const ajaxStart = Date.now();
            console.log('🔹 Entity:', this.currentEntity);
            console.log('🔹 Entity ID:', entityId);
            console.log('🔹 AJAX Action:', ajaxAction);
            console.log('🔹 AJAX URL:', wpdtConfig.ajaxUrl);

            // Trigger loading event
            $(document).trigger('wpdt:panel-loading', {
                entity: this.currentEntity,
                id: entityId
            });

            // Abort previous request
            if (this.ajaxRequest) {
                console.log('⚠️ Aborting previous AJAX request');
                this.ajaxRequest.abort();
            }

            // Make AJAX request
            this.ajaxRequest = $.ajax({
                url: wpdtConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: ajaxAction,
                    entity: this.currentEntity,
                    id: entityId,
                    nonce: wpdtConfig.nonce
                },
                success: (response) => {
                    const elapsed = Date.now() - ajaxStart;
                    console.log('✅ AJAX Success - Elapsed:', elapsed + 'ms');
                    console.log('📦 Response:', response);
                    this.handleAjaxSuccess(response, entityId);
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    const elapsed = Date.now() - ajaxStart;
                    console.error('❌ AJAX Error - Elapsed:', elapsed + 'ms');
                    console.error('📦 Error:', textStatus, errorThrown);
                    this.handleAjaxError(jqXHR, textStatus, errorThrown, entityId);
                },
                complete: () => {
                    const elapsed = Date.now() - ajaxStart;
                    console.log('🏁 AJAX Complete - Total time:', elapsed + 'ms');
                    console.groupEnd();
                    this.ajaxRequest = null;
                }
            });
        }

        /**
         * Handle AJAX success
         *
         * @param {Object} response AJAX response
         * @param {number} entityId Entity ID
         */
        handleAjaxSuccess(response, entityId) {
            console.log('[WPDT Panel] AJAX Response:', response);
            console.log('[WPDT Panel] Response success:', response.success);
            console.log('[WPDT Panel] Response data:', response.data);

            if (response.success && response.data) {
                console.log('[WPDT Panel] Processing successful response');
                console.log('[WPDT Panel] Data title:', response.data.title);
                console.log('[WPDT Panel] Data tabs:', response.data.tabs);
                console.log('[WPDT Panel] Tabs count:', response.data.tabs ? Object.keys(response.data.tabs).length : 0);

                // Update panel content
                this.updatePanelContent(response.data);

                // Trigger data loaded event
                $(document).trigger('wpdt:panel-data-loaded', {
                    entity: this.currentEntity,
                    id: entityId,
                    data: response.data
                });

                console.log('[WPDT Panel] Panel content updated successfully');
            } else {
                console.error('[WPDT Panel] Response error or no data');
                console.error('[WPDT Panel] Response:', response);

                // Error in response
                const errorMessage = response.data ? response.data.message : 'Unknown error';
                console.error('[WPDT Panel] Error message:', errorMessage);

                this.showError(errorMessage);

                // Trigger error event
                $(document).trigger('wpdt:panel-error', {
                    entity: this.currentEntity,
                    id: entityId,
                    message: errorMessage
                });
            }
        }

        /**
         * Handle AJAX error
         *
         * @param {Object} jqXHR jQuery XHR object
         * @param {string} textStatus Status text
         * @param {string} errorThrown Error message
         * @param {number} entityId Entity ID
         */
        handleAjaxError(jqXHR, textStatus, errorThrown, entityId) {
            // Don't show error if request was aborted
            if (textStatus === 'abort') {
                return;
            }

            const errorMessage = errorThrown || 'Network error';
            this.showError(errorMessage);

            // Trigger error event
            $(document).trigger('wpdt:panel-error', {
                entity: this.currentEntity,
                id: entityId,
                message: errorMessage,
                status: jqXHR.status
            });
        }

        /**
         * Update panel content
         *
         * @param {Object} data Response data
         */
        updatePanelContent(data) {
            console.log('[WPDT Panel] updatePanelContent called with:', data);

            // Clear loading timeout to prevent flicker on fast responses
            if (this.loadingTimeout) {
                clearTimeout(this.loadingTimeout);
                this.loadingTimeout = null;
                console.log('✓ Loading timeout cleared (fast response < 300ms)');
            }

            // Hide loading placeholder (from template)
            this.rightPanel.find('.wpdt-loading-placeholder').removeClass('visible');

            // Update title if provided
            if (data.title) {
                console.log('[WPDT Panel] Updating title to:', data.title);
                const $titleEl = this.rightPanel.find('.wpdt-entity-name');
                console.log('[WPDT Panel] Title element found:', $titleEl.length);
                $titleEl.text(data.title);
            }

            // Update tab content if provided
            if (data.tabs) {
                console.log('[WPDT Panel] Updating tabs:', Object.keys(data.tabs));
                let updatedCount = 0;

                $.each(data.tabs, function(tabId, content) {
                    console.log('[WPDT Panel] Looking for tab #' + tabId);
                    const $tab = $(`#${tabId}`);
                    console.log('[WPDT Panel] Tab element found:', $tab.length);

                    if ($tab.length > 0) {
                        console.log('[WPDT Panel] Updating tab #' + tabId + ' with content length:', content.length);

                        // Create temporary element to parse content
                        const $temp = $('<div>').html(content);
                        const $firstChild = $temp.children().first();

                        // If content has a wrapper div, copy its classes and data-attributes to tab
                        if ($firstChild.length > 0) {
                            // Copy classes (except wpdt-tab-content which tab already has)
                            const classes = $firstChild.attr('class');
                            if (classes) {
                                const classArray = classes.split(/\s+/);
                                classArray.forEach(function(cls) {
                                    if (cls && cls !== 'wpdt-tab-content' && !$tab.hasClass(cls)) {
                                        $tab.addClass(cls);
                                        console.log('[WPDT Panel] Added class to tab:', cls);
                                    }
                                });
                            }

                            // Copy data-attributes
                            $.each($firstChild[0].attributes, function(idx, attr) {
                                if (attr.name.startsWith('data-')) {
                                    $tab.attr(attr.name, attr.value);
                                    console.log('[WPDT Panel] Added attribute:', attr.name, '=', attr.value);
                                }
                            });
                        }

                        // Destroy any DataTables in this tab before replacing content
                        $tab.find('table').each(function() {
                            if ($.fn.DataTable && $.fn.DataTable.isDataTable(this)) {
                                console.log('[WPDT Panel] Destroying DataTable:', $(this).attr('id'));
                                $(this).DataTable().destroy();
                            }
                        });

                        // Remove 'loaded' class to allow re-initialization of autoload tabs
                        $tab.removeClass('loaded');
                        console.log('[WPDT Panel] Removed "loaded" class from tab:', tabId);

                        // Inject content
                        $tab.html(content);
                        updatedCount++;
                    } else {
                        console.warn('[WPDT Panel] Tab not found: #' + tabId);
                    }
                });

                console.log('[WPDT Panel] Total tabs updated:', updatedCount);
            }

            // Update simple content if provided (no tabs)
            if (data.content) {
                console.log('[WPDT Panel] Updating simple content');
                this.rightPanel.find('.wpdt-panel-content').html(data.content);
            }

            // Update entire HTML if provided (full control)
            if (data.html) {
                console.log('[WPDT Panel] Updating with full HTML');
                this.rightPanel.find('.wpdt-panel-content').html(data.html);
            }

            console.log('[WPDT Panel] Content update complete');
        }

        /**
         * Show loading state
         */
        showLoading() {
            // Don't show loading for fast requests (< 300ms)
            // This prevents flicker for cached/fast responses
            this.loadingTimeout = setTimeout(() => {
                this.rightPanel.addClass('wpdt-loading');

                // Add loading indicator if not exists
                if (this.rightPanel.find('.wpdt-panel-loading').length === 0) {
                    this.rightPanel.find('.wpdt-panel-content').prepend(
                        '<div class="wpdt-panel-loading" style="opacity: 0; transition: opacity 0.3s;">' +
                            '<p style="text-align: center; padding: 20px; color: #666;">Loading...</p>' +
                        '</div>'
                    );

                    // Fade in smoothly
                    setTimeout(() => {
                        this.rightPanel.find('.wpdt-panel-loading').css('opacity', '1');
                    }, 10);
                }
            }, 300); // Delay loading indicator
        }

        /**
         * Hide loading state
         */
        hideLoading() {
            // Clear loading timeout to prevent flicker on fast responses
            if (this.loadingTimeout) {
                clearTimeout(this.loadingTimeout);
                this.loadingTimeout = null;
            }

            this.rightPanel.removeClass('wpdt-loading');
            this.rightPanel.find('.wpdt-panel-loading').remove();
        }

        /**
         * Show error message
         *
         * @param {string} message Error message
         */
        showError(message) {
            // Clear loading timeout and hide loading placeholder
            if (this.loadingTimeout) {
                clearTimeout(this.loadingTimeout);
                this.loadingTimeout = null;
            }
            this.rightPanel.find('.wpdt-loading-placeholder').removeClass('visible');

            const errorHtml = `
                <div class="notice notice-error wpdt-panel-error">
                    <p><strong>Error:</strong> ${message}</p>
                </div>
            `;

            this.rightPanel.find('.wpdt-panel-content').prepend(errorHtml);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                this.rightPanel.find('.wpdt-panel-error').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }

        /**
         * Update URL hash
         *
         * @param {number} entityId Entity ID
         */
        updateHash(entityId) {
            if (this.currentEntity && entityId) {
                const newHash = `${this.currentEntity}-${entityId}`;
                // Use history.pushState to avoid scroll jump (consistent with clearHash)
                history.pushState(null, document.title, window.location.pathname + window.location.search + '#' + newHash);
            }
        }

        /**
         * Clear URL hash
         */
        clearHash() {
            // Remove hash without triggering hashchange
            history.pushState('', document.title, window.location.pathname + window.location.search);
        }

        /**
         * Check hash on page load
         */
        checkHashOnLoad() {
            const hash = window.location.hash.substring(1); // Remove #
            if (hash) {
                this.parseAndOpenHash(hash);
            }
        }

        /**
         * Check hash change (browser back/forward)
         */
        checkHashChange() {
            const hash = window.location.hash.substring(1); // Remove #

            if (hash) {
                this.parseAndOpenHash(hash);
            } else {
                // Hash cleared, close panel
                if (this.isOpen) {
                    this.hidePanel(); // Direct hide, no hash update
                    this.currentId = null;
                }
            }
        }

        /**
         * Parse hash and open panel
         *
         * @param {string} hash Hash string (e.g., "customer-123")
         */
        parseAndOpenHash(hash) {
            const parts = hash.split('-');

            if (parts.length >= 2) {
                const entity = parts[0];
                const id = parseInt(parts[parts.length - 1], 10);

                // Only open if entity matches current context
                if (entity === this.currentEntity && id > 0) {
                    this.openPanel(id);
                }
            }
        }

        /**
         * Refresh current panel
         */
        refresh() {
            if (this.isOpen && this.currentId) {
                this.loadPanelData(this.currentId);
            }
        }

        /**
         * Public API: Open panel programmatically
         *
         * @param {number} entityId Entity ID
         */
        open(entityId) {
            this.openPanel(entityId);
        }

        /**
         * Public API: Close panel programmatically
         */
        close() {
            this.closePanel();
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Create global instance
        window.wpdtPanelManager = new WPDTPanelManager();
    });

})(jQuery);
