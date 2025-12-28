/**
 * Action Buttons Handler
 *
 * @package     WP_DataTable
 * @subpackage  Assets/JS/DualPanel
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/assets/js/dual-panel/action-buttons-handler.js
 *
 * Description: Global handler untuk action buttons di DataTable.
 *              Menangani button edit, delete, dan custom actions.
 *              Trigger events yang bisa di-listen oleh consumer plugins.
 *
 * Features:
 * - Global edit button handler
 * - Global delete button handler
 * - Custom action button handler
 * - Event system untuk extensibility
 * - Prevent row click when clicking action buttons
 *
 * Button Class Convention:
 * - Buttons should be inside .wpdt-action-buttons container
 * - Buttons should have data-id attribute with entity ID
 * - Buttons should have data-entity attribute with entity type (optional)
 * - Common classes:
 *   - Edit: .wpdt-edit-btn, .{entity}-edit-btn (e.g. .customer-edit-btn)
 *   - Delete: .wpdt-delete-btn, .{entity}-delete-btn (e.g. .customer-delete-btn)
 *
 * Events Triggered:
 * - wpdt:action-edit { entity, id, $button }
 * - wpdt:action-delete { entity, id, $button }
 * - wpdt:action-custom { action, entity, id, $button }
 *
 * Usage Example:
 * ```javascript
 * // In consumer plugin (e.g. wp-customer)
 * jQuery(document).on('wpdt:action-edit', function(e, data) {
 *     console.log('Edit clicked:', data.entity, data.id);
 *     // Open edit modal
 *     WPModal.show({ ... });
 * });
 *
 * jQuery(document).on('wpdt:action-delete', function(e, data) {
 *     console.log('Delete clicked:', data.entity, data.id);
 *     // Show delete confirmation
 *     WPModal.confirm({ ... });
 * });
 * ```
 *
 * Changelog:
 * 0.1.0 - 2025-12-25
 * - Initial implementation
 * - Edit button handler
 * - Delete button handler
 * - Custom action handler
 * - Event system
 */

(function($) {
    'use strict';

    /**
     * Action Buttons Handler Class
     */
    class WPDTActionButtonsHandler {
        constructor() {
            this.init();
        }

        /**
         * Initialize handler
         */
        init() {
            console.log('[WPDT Actions] Initializing action buttons handler...');
            this.bindEvents();
            console.log('[WPDT Actions] Action buttons handler ready');
        }

        /**
         * Bind event handlers
         */
        bindEvents() {
            const self = this;

            // Edit button handler (generic - matches any *-edit-btn class)
            $(document).on('click', '[class*="-edit-btn"]', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent row click

                const $button = $(this);
                const entityId = $button.data('id');
                const entity = $button.data('entity') || self.extractEntityFromClass($button, 'edit');

                if (!entityId) {
                    console.error('[WPDT Actions] Edit button missing data-id attribute', $button);
                    return;
                }

                console.log('[WPDT Actions] Edit button clicked', {
                    entity: entity,
                    id: entityId,
                    button: $button[0]
                });

                // Trigger custom event
                $(document).trigger('wpdt:action-edit', {
                    entity: entity,
                    id: entityId,
                    $button: $button
                });
            });

            // Delete button handler (generic - matches any *-delete-btn class)
            $(document).on('click', '[class*="-delete-btn"]', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent row click

                const $button = $(this);
                const entityId = $button.data('id');
                const entity = $button.data('entity') || self.extractEntityFromClass($button, 'delete');

                if (!entityId) {
                    console.error('[WPDT Actions] Delete button missing data-id attribute', $button);
                    return;
                }

                console.log('[WPDT Actions] Delete button clicked', {
                    entity: entity,
                    id: entityId,
                    button: $button[0]
                });

                // Trigger custom event
                $(document).trigger('wpdt:action-delete', {
                    entity: entity,
                    id: entityId,
                    $button: $button
                });
            });

            // Custom action button handler (for buttons with data-action attribute)
            $(document).on('click', '.wpdt-action-btn[data-action]', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent row click

                const $button = $(this);
                const action = $button.data('action');
                const entityId = $button.data('id');
                const entity = $button.data('entity');

                if (!entityId || !action) {
                    console.error('[WPDT Actions] Custom action button missing required attributes', $button);
                    return;
                }

                console.log('[WPDT Actions] Custom action button clicked', {
                    action: action,
                    entity: entity,
                    id: entityId,
                    button: $button[0]
                });

                // Trigger custom event
                $(document).trigger('wpdt:action-custom', {
                    action: action,
                    entity: entity,
                    id: entityId,
                    $button: $button
                });
            });
        }

        /**
         * Extract entity name from button class
         * Example: "customer-edit-btn" => "customer"
         *
         * @param {jQuery} $button Button element
         * @param {string} action Action type (edit/delete)
         * @return {string} Entity name or 'unknown'
         */
        extractEntityFromClass($button, action) {
            const classes = $button.attr('class').split(/\s+/);
            const pattern = new RegExp(`^(.+)-${action}-btn$`);

            for (let className of classes) {
                const match = className.match(pattern);
                if (match) {
                    return match[1]; // Return entity name
                }
            }

            return 'unknown';
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        window.wpdtActionButtonsHandler = new WPDTActionButtonsHandler();
    });

})(jQuery);
