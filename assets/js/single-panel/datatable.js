/**
 * DataTable Manager - Single Panel
 *
 * Handles simple DataTable initialization, filters, dan basic interactions.
 * Simplified dari dual panel - no panel manager, no tabs, just simple listing.
 *
 * @package     WP_DataTable
 * @subpackage  Assets\JS\SinglePanel
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/assets/js/single-panel/datatable.js
 *
 * Description: JavaScript untuk single panel datatable.
 *              Handles filter interactions, DataTable refresh, events.
 *
 * Features:
 * - Filter apply/reset
 * - DataTable refresh capability
 * - Event-driven refresh
 * - WordPress admin integration
 *
 * Dependencies:
 * - jQuery
 * - DataTables.js
 *
 * Global Objects:
 * - wpdtConfig (localized via wp_localize_script)
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial implementation
 * - Filter handling
 * - Event-driven refresh
 * - Simplified dari dual panel concept
 */

(function($) {
    'use strict';

    /**
     * Single Panel DataTable Manager
     *
     * Manages filter interactions dan DataTable refresh untuk single panel layout.
     */
    class WPDTSinglePanelManager {
        constructor() {
            this.config = window.wpdtConfig || {};
            this.dataTables = {};
            this.filters = {};

            if (this.config.debug) {
                console.log('[WPDT Single Panel] Manager initialized', this.config);
            }
        }

        /**
         * Initialize manager
         */
        init() {
            this.bindFilterEvents();
            this.bindRefreshEvents();
            this.checkAutoRefresh();

            $(document).trigger('wpdt:singlePanelReady');

            if (this.config.debug) {
                console.log('[WPDT Single Panel] Initialization complete');
            }
        }

        /**
         * Bind filter events
         */
        bindFilterEvents() {
            const self = this;

            // Apply filters button
            $(document).on('click', '.wpdt-filter-apply', function(e) {
                e.preventDefault();
                self.applyFilters($(this));
            });

            // Reset filters button
            $(document).on('click', '.wpdt-filter-reset', function(e) {
                e.preventDefault();
                self.resetFilters($(this));
            });

            // Apply on Enter key in filter inputs
            $(document).on('keypress', '.wpdt-filter-input', function(e) {
                if (e.which === 13) { // Enter key
                    e.preventDefault();
                    self.applyFilters($(this));
                }
            });

            if (this.config.debug) {
                console.log('[WPDT Single Panel] Filter events bound');
            }
        }

        /**
         * Bind refresh events
         */
        bindRefreshEvents() {
            const self = this;

            // Listen for custom refresh events
            $(document).on('wpdt:refresh', function(e, data) {
                if (data && data.entity) {
                    self.refreshDataTable(data.entity);
                }
            });

            // Listen for item created/updated/deleted events
            const refreshEvents = [
                'wpdt:itemCreated',
                'wpdt:itemUpdated',
                'wpdt:itemDeleted'
            ];

            refreshEvents.forEach(function(eventName) {
                $(document).on(eventName, function(e, data) {
                    if (data && data.entity) {
                        self.refreshDataTable(data.entity);
                    }
                });
            });

            if (this.config.debug) {
                console.log('[WPDT Single Panel] Refresh events bound');
            }
        }

        /**
         * Apply filters
         *
         * @param {jQuery} $element Clicked element
         */
        applyFilters($element) {
            const $container = $element.closest('.wpdt-filters-container');
            const $panelContainer = $container.siblings('.wpdt-single-panel-container');
            const entity = $panelContainer.data('entity');

            if (!entity) {
                console.warn('[WPDT Single Panel] No entity found for filters');
                return;
            }

            // Collect filter values
            const filterData = {};
            $container.find('.wpdt-filter-input').each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                const value = $input.val();

                if (value) {
                    filterData[name] = value;
                }
            });

            // Store filters
            this.filters[entity] = filterData;

            if (this.config.debug) {
                console.log('[WPDT Single Panel] Applying filters', {
                    entity: entity,
                    filters: filterData
                });
            }

            /**
             * Trigger filter applied event
             *
             * Plugins can listen to this event to reload their datatable
             */
            $(document).trigger('wpdt:filtersApplied', {
                entity: entity,
                filters: filterData
            });

            // Refresh DataTable if registered
            this.refreshDataTable(entity);
        }

        /**
         * Reset filters
         *
         * @param {jQuery} $element Clicked element
         */
        resetFilters($element) {
            const $container = $element.closest('.wpdt-filters-container');
            const $panelContainer = $container.siblings('.wpdt-single-panel-container');
            const entity = $panelContainer.data('entity');

            if (!entity) {
                console.warn('[WPDT Single Panel] No entity found for filters');
                return;
            }

            // Clear filter inputs
            $container.find('.wpdt-filter-input').val('');

            // Clear stored filters
            this.filters[entity] = {};

            if (this.config.debug) {
                console.log('[WPDT Single Panel] Filters reset', { entity: entity });
            }

            /**
             * Trigger filters reset event
             */
            $(document).trigger('wpdt:filtersReset', {
                entity: entity
            });

            // Refresh DataTable
            this.refreshDataTable(entity);
        }

        /**
         * Register DataTable instance
         *
         * Plugins can register their DataTable instances untuk auto-refresh capability.
         *
         * @param {string} entity Entity name
         * @param {object} dataTable DataTable instance
         */
        registerDataTable(entity, dataTable) {
            this.dataTables[entity] = dataTable;

            if (this.config.debug) {
                console.log('[WPDT Single Panel] DataTable registered', { entity: entity });
            }
        }

        /**
         * Refresh DataTable
         *
         * @param {string} entity Entity name
         */
        refreshDataTable(entity) {
            if (!this.dataTables[entity]) {
                if (this.config.debug) {
                    console.log('[WPDT Single Panel] No DataTable registered for entity:', entity);
                }
                return;
            }

            const dataTable = this.dataTables[entity];

            if (this.config.debug) {
                console.log('[WPDT Single Panel] Refreshing DataTable', { entity: entity });
            }

            // Trigger refresh
            if (dataTable.ajax && typeof dataTable.ajax.reload === 'function') {
                dataTable.ajax.reload(null, false); // false = keep current page
            } else {
                dataTable.draw(false); // false = keep current page
            }

            /**
             * Trigger refresh complete event
             */
            $(document).trigger('wpdt:refreshComplete', {
                entity: entity
            });
        }

        /**
         * Get current filters for entity
         *
         * @param {string} entity Entity name
         * @return {object} Filter data
         */
        getFilters(entity) {
            return this.filters[entity] || {};
        }

        /**
         * Check auto-refresh configuration
         */
        checkAutoRefresh() {
            if (!this.config.autoRefresh || !this.config.autoRefresh.enabled) {
                return;
            }

            // Auto-refresh is handled via event listeners bound in bindRefreshEvents()
            if (this.config.debug) {
                console.log('[WPDT Single Panel] Auto-refresh enabled', this.config.autoRefresh);
            }
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Create global instance
        window.wpdtSinglePanel = new WPDTSinglePanelManager();
        window.wpdtSinglePanel.init();
    });

})(jQuery);
