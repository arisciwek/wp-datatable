/**
 * WP DataTable - Modal Integration
 *
 * @package     WP_DataTable
 * @subpackage  Assets/JS/Dual-Panel
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/assets/js/dual-panel/modal-integration.js
 *
 * Description: Auto-wires DataTable action buttons to WP-Modal.
 *              Eliminates need for consumer plugins to write JavaScript.
 *              Reads configuration from wpdtConfig global object.
 *
 * Dependencies:
 * - jQuery
 * - WPModal (from wp-modal plugin)
 * - action-buttons-handler.js (triggers wpdt:action-* events)
 *
 * Features:
 * - Auto-wire edit button → form modal
 * - Auto-wire delete button → confirmation modal
 * - Config-driven (no JavaScript needed in consumer plugins)
 * - AJAX form loading
 * - Form submission handling
 * - Delete confirmation
 * - Success/error notifications
 * - DataTable refresh after operations
 *
 * Usage in Consumer Plugin:
 * wp_localize_script('my-plugin-js', 'wpdtConfig', [
 *     'entity_name' => [
 *         'action_buttons' => [
 *             'edit' => [
 *                 'enabled' => true,
 *                 'modal_title' => 'Edit Item',
 *                 'ajax_action' => 'get_item_form',
 *                 'submit_action' => 'update_item',
 *                 'nonce_action' => 'item_nonce',
 *                 'modal_size' => 'medium',
 *             ],
 *             'delete' => [
 *                 'enabled' => true,
 *                 'ajax_action' => 'delete_item',
 *                 'confirm_message' => 'Are you sure?',
 *                 'nonce_action' => 'item_nonce',
 *             ]
 *         ]
 *     ]
 * ]);
 *
 * Changelog:
 * 1.0.0 - 2025-12-28
 * - Initial implementation
 * - Edit button auto-wiring
 * - Delete button auto-wiring
 * - Config-driven system
 */

(function($) {
    'use strict';

    /**
     * WP DataTable Modal Integration
     */
    var WPDTModalIntegration = {

        /**
         * Configuration cache
         */
        configs: {},

        /**
         * Initialize modal integration
         */
        init: function() {
            // Load configurations from global wpdtConfig
            if (typeof wpdtConfig !== 'undefined') {
                this.configs = wpdtConfig;
                console.log('[WPDT Modal] Loaded configurations for entities:', Object.keys(this.configs));
            } else {
                console.warn('[WPDT Modal] No wpdtConfig found. Modal integration disabled.');
                return;
            }

            // Check if WPModal is available
            if (typeof WPModal === 'undefined') {
                console.warn('[WPDT Modal] WPModal not found. Install wp-modal plugin.');
                return;
            }

            this.bindEvents();
            console.log('[WPDT Modal] Integration initialized');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            // Handle edit button clicks
            $(document).on('wpdt:action-edit', function(e, data) {
                self.handleEdit(data);
            });

            // Handle delete button clicks
            $(document).on('wpdt:action-delete', function(e, data) {
                self.handleDelete(data);
            });
        },

        /**
         * Handle edit button click
         *
         * @param {Object} data Event data
         */
        handleEdit: function(data) {
            var entity = data.entity;
            var id = data.id;
            var config = this.getConfig(entity, 'edit');

            if (!config || !config.enabled) {
                console.log('[WPDT Modal] Edit not configured for entity:', entity);
                return;
            }

            console.log('[WPDT Modal] Opening edit modal for', entity, id);

            // Build AJAX URL for form
            var formUrl = ajaxurl + '?action=' + config.ajax_action + '&id=' + id;

            // Add nonce if configured
            if (config.nonce_action && typeof wpApiSettings !== 'undefined') {
                formUrl += '&nonce=' + wpApiSettings.nonce;
            }

            // Show modal with form
            WPModal.show({
                type: 'form',
                title: config.modal_title || 'Edit ' + this.capitalize(entity),
                bodyUrl: formUrl,
                size: config.modal_size || 'medium',
                onSubmit: function(formData, $form) {
                    // Handle form submission via AJAX
                    this.submitForm(entity, id, formData, $form, config);
                }.bind(this)
            });
        },

        /**
         * Handle delete button click
         *
         * @param {Object} data Event data
         */
        handleDelete: function(data) {
            var entity = data.entity;
            var id = data.id;
            var config = this.getConfig(entity, 'delete');

            if (!config || !config.enabled) {
                console.log('[WPDT Modal] Delete not configured for entity:', entity);
                return;
            }

            console.log('[WPDT Modal] Opening delete confirmation for', entity, id);

            // Show confirmation modal
            WPModal.confirm({
                title: config.confirm_title || 'Confirm Delete',
                body: '<p>' + (config.confirm_message || 'Are you sure you want to delete this item?') + '</p>',
                danger: true,
                confirmLabel: config.confirm_label || 'Delete',
                onConfirm: function() {
                    this.performDelete(entity, id, config);
                }.bind(this)
            });
        },

        /**
         * Submit form via AJAX
         *
         * @param {string} entity Entity name
         * @param {int} id Entity ID
         * @param {string} formData Serialized form data
         * @param {jQuery} $form Form element
         * @param {Object} config Action configuration
         */
        submitForm: function(entity, id, formData, $form, config) {
            var self = this;

            // Disable submit button
            var $submitBtn = $('#wpmodal-submit');
            var originalText = $submitBtn.text();
            $submitBtn.prop('disabled', true).text('Saving...');

            // Prepare AJAX data
            var ajaxData = formData + '&action=' + config.submit_action + '&id=' + id;

            // Add nonce if configured
            if (config.nonce_action && typeof wpApiSettings !== 'undefined') {
                ajaxData += '&nonce=' + wpApiSettings.nonce;
            }

            console.log('[WPDT Modal] Submitting form for', entity, id);

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: ajaxData,
                success: function(response) {
                    console.log('[WPDT Modal] Form submitted successfully:', response);

                    // Check if response indicates success
                    if (response.success) {
                        // Show success message
                        WPModal.info({
                            type: 'info',
                            infoType: 'success',
                            title: 'Success',
                            message: response.data.message || 'Updated successfully',
                            autoClose: 2000
                        });

                        // Refresh DataTable
                        self.refreshDataTable(entity);

                        // Trigger custom event
                        $(document).trigger('wpdt:entity-updated', {
                            entity: entity,
                            id: id,
                            response: response
                        });
                    } else {
                        // Show error message
                        WPModal.info({
                            type: 'info',
                            infoType: 'error',
                            title: 'Error',
                            message: response.data.message || 'Update failed',
                            autoClose: 3000
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[WPDT Modal] Form submission failed:', error);

                    WPModal.info({
                        type: 'info',
                        infoType: 'error',
                        title: 'Error',
                        message: 'Failed to update. Please try again.',
                        autoClose: 3000
                    });

                    // Re-enable submit button on error
                    $submitBtn.prop('disabled', false).text(originalText);
                },
                complete: function() {
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Perform delete operation via AJAX
         *
         * @param {string} entity Entity name
         * @param {int} id Entity ID
         * @param {Object} config Action configuration
         */
        performDelete: function(entity, id, config) {
            var self = this;

            console.log('[WPDT Modal] Deleting', entity, id);

            // Prepare AJAX data
            var ajaxData = {
                action: config.ajax_action,
                id: id
            };

            // Add nonce if configured
            if (config.nonce_action && typeof wpApiSettings !== 'undefined') {
                ajaxData.nonce = wpApiSettings.nonce;
            }

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: ajaxData,
                success: function(response) {
                    console.log('[WPDT Modal] Delete successful:', response);

                    // Check if response indicates success
                    if (response.success) {
                        // Show success message
                        WPModal.info({
                            type: 'info',
                            infoType: 'success',
                            title: 'Success',
                            message: response.data.message || 'Deleted successfully',
                            autoClose: 2000
                        });

                        // Refresh DataTable
                        self.refreshDataTable(entity);

                        // Trigger custom event
                        $(document).trigger('wpdt:entity-deleted', {
                            entity: entity,
                            id: id,
                            response: response
                        });
                    } else {
                        // Show error message
                        WPModal.info({
                            type: 'info',
                            infoType: 'error',
                            title: 'Error',
                            message: response.data.message || 'Delete failed',
                            autoClose: 3000
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[WPDT Modal] Delete failed:', error);

                    WPModal.info({
                        type: 'info',
                        infoType: 'error',
                        title: 'Error',
                        message: 'Failed to delete. Please try again.',
                        autoClose: 3000
                    });
                }
            });
        },

        /**
         * Refresh DataTable after operation
         *
         * @param {string} entity Entity name
         */
        refreshDataTable: function(entity) {
            // Find DataTable instance for this entity
            var tableId = '#' + entity + '-datatable';
            var $table = $(tableId);

            if ($table.length && $.fn.DataTable.isDataTable($table)) {
                console.log('[WPDT Modal] Refreshing DataTable:', tableId);
                $table.DataTable().ajax.reload(null, false); // false = stay on current page
            } else {
                console.warn('[WPDT Modal] DataTable not found:', tableId);
            }
        },

        /**
         * Get configuration for entity and action
         *
         * @param {string} entity Entity name
         * @param {string} action Action name (edit|delete)
         * @return {Object|null} Configuration or null
         */
        getConfig: function(entity, action) {
            if (!this.configs[entity]) {
                return null;
            }

            if (!this.configs[entity].action_buttons) {
                return null;
            }

            return this.configs[entity].action_buttons[action] || null;
        },

        /**
         * Capitalize first letter
         *
         * @param {string} str String to capitalize
         * @return {string} Capitalized string
         */
        capitalize: function(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        WPDTModalIntegration.init();
    });

})(jQuery);
