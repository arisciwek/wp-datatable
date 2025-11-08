<?php
/**
 * Base Assets Class
 *
 * @package     WP_DataTable
 * @subpackage  Controllers/Assets
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Controllers/Assets/BaseAssets.php
 *
 * Description: Abstract base class untuk asset strategies.
 *              Provides common functionality yang digunakan semua strategies.
 *              Child classes extend ini dan implement strategy-specific logic.
 *
 * Shared Functionality:
 * - Common asset registration (DataTables.js, jQuery)
 * - Common localize data (ajaxUrl, nonce, debug)
 * - Helper methods untuk asset loading
 * - Default implementations
 *
 * Child Classes Override:
 * - enqueue_styles(): Strategy-specific CSS
 * - enqueue_scripts(): Strategy-specific JS
 * - should_load(): Detection logic
 * - get_name(): Strategy identifier
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial implementation
 * - Provide common asset loading helpers
 * - Base localize data structure
 * - Ready for dual-panel and single-panel strategies
 */

namespace WPDataTable\Controllers\Assets;

defined('ABSPATH') || exit;

abstract class BaseAssets implements AssetStrategyInterface {
    /**
     * Strategy name/identifier
     *
     * @var string
     */
    protected $strategy_name = 'base';

    /**
     * Enqueue common dependencies
     *
     * Load assets yang dibutuhkan semua strategies:
     * - jQuery (WordPress core)
     * - DataTables.js (if needed)
     *
     * @return void
     */
    protected function enqueue_common_dependencies(): void {
        // jQuery (WordPress core)
        wp_enqueue_script('jquery');

        // DataTables.js (enqueue if not already loaded)
        if (!wp_script_is('datatables', 'enqueued')) {
            wp_enqueue_script(
                'datatables',
                'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
                ['jquery'],
                '1.13.6',
                true
            );
        }

        // DataTables CSS (enqueue if not already loaded)
        if (!wp_style_is('datatables', 'enqueued')) {
            wp_enqueue_style(
                'datatables',
                'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css',
                [],
                '1.13.6'
            );
        }
    }

    /**
     * Get common localize data
     *
     * Return base data yang digunakan semua strategies.
     * Child classes dapat extend/override ini.
     *
     * @return array Common localize data
     */
    public function get_localize_data(): array {
        return [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpdt_nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'i18n' => $this->get_i18n_strings(),
        ];
    }

    /**
     * Get i18n translation strings
     *
     * Return translated strings untuk JavaScript.
     * Child classes dapat override untuk add strategy-specific strings.
     *
     * @return array Translation strings
     */
    protected function get_i18n_strings(): array {
        return [
            'loading' => __('Loading...', 'wp-datatable'),
            'error' => __('Error loading data', 'wp-datatable'),
            'close' => __('Close', 'wp-datatable'),
            'save' => __('Save', 'wp-datatable'),
            'cancel' => __('Cancel', 'wp-datatable'),
            'delete' => __('Delete', 'wp-datatable'),
            'confirm' => __('Are you sure?', 'wp-datatable'),
        ];
    }

    /**
     * Get strategy name
     *
     * @return string Strategy identifier
     */
    public function get_name(): string {
        return $this->strategy_name;
    }

    /**
     * Check if we're on admin page
     *
     * Helper method untuk check admin context.
     *
     * @return bool True if admin page
     */
    protected function is_admin_page(): bool {
        return is_admin();
    }

    /**
     * Get plugin URL
     *
     * Helper untuk get plugin URL path.
     *
     * @return string Plugin URL
     */
    protected function get_plugin_url(): string {
        return WP_DATATABLE_URL;
    }

    /**
     * Get plugin version
     *
     * Helper untuk get plugin version.
     *
     * @return string Plugin version
     */
    protected function get_version(): string {
        return WP_DATATABLE_VERSION;
    }

    /**
     * Abstract methods - must be implemented by child classes
     */

    /**
     * Enqueue CSS stylesheets
     *
     * Child classes MUST implement this.
     *
     * @return void
     */
    abstract public function enqueue_styles(): void;

    /**
     * Enqueue JavaScript files
     *
     * Child classes MUST implement this.
     *
     * @return void
     */
    abstract public function enqueue_scripts(): void;

    /**
     * Determine if strategy should load
     *
     * Child classes MUST implement this.
     *
     * @return bool True if should load
     */
    abstract public function should_load(): bool;
}
