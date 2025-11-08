<?php
/**
 * Single Panel Assets Strategy
 *
 * @package     WP_DataTable
 * @subpackage  Controllers/Assets
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Controllers/Assets/SinglePanelAssets.php
 *
 * Description: Asset loading strategy untuk single-panel layout.
 *              Loads CSS dan JS yang diperlukan untuk single-panel functionality.
 *              Simplified dari dual-panel - full-width listing dengan filters.
 *
 * Assets Loaded:
 * - CSS: single-panel.css (full-width layout, stats, filters)
 * - JS: datatable.js (filter handling, refresh capability)
 *
 * Conditional Loading:
 * - Checks wpdt_use_single_panel filter
 * - Only loads if single-panel layout is active
 * - Falls back if filter returns false
 *
 * Dependencies:
 * - jQuery (WordPress core)
 * - DataTables.js (from BaseAssets)
 * - Common dependencies loaded by parent
 *
 * Localized Data:
 * - Extends base data from parent
 * - Adds single-panel specific config
 * - Filter configuration
 * - Refresh settings
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial implementation
 * - Single panel asset loading
 * - Conditional loading strategy
 * - Extended localize data for single panel
 */

namespace WPDataTable\Controllers\Assets;

defined('ABSPATH') || exit;

class SinglePanelAssets extends BaseAssets {
    /**
     * Strategy name
     *
     * @var string
     */
    protected $strategy_name = 'single-panel';

    /**
     * Enqueue single panel CSS
     *
     * Load single-panel.css with full-width layout, stats, and filters styles.
     *
     * @return void
     */
    public function enqueue_styles(): void {
        // Enqueue common dependencies first
        $this->enqueue_common_dependencies();

        // Single panel CSS
        wp_enqueue_style(
            'wpdt-single-panel',
            $this->get_plugin_url() . 'assets/css/single-panel.css',
            ['datatables'],
            $this->get_version()
        );
    }

    /**
     * Enqueue single panel JavaScript
     *
     * Load datatable manager script dengan filter handling dan refresh capability.
     *
     * @return void
     */
    public function enqueue_scripts(): void {
        // Enqueue common dependencies first
        $this->enqueue_common_dependencies();

        $plugin_url = $this->get_plugin_url();
        $version = $this->get_version();

        // DataTable Manager - Filter handling dan refresh
        wp_enqueue_script(
            'wpdt-single-panel-datatable',
            $plugin_url . 'assets/js/single-panel/datatable.js',
            ['jquery', 'datatables'],
            $version,
            true
        );

        // Localize data to datatable script
        wp_localize_script(
            'wpdt-single-panel-datatable',
            'wpdtConfig',
            $this->get_localize_data()
        );
    }

    /**
     * Get localize data for single panel
     *
     * Extends base data dengan single panel specific configuration.
     *
     * @return array Localized data
     */
    public function get_localize_data(): array {
        // Get base data from parent
        $base_data = parent::get_localize_data();

        // Add single panel specific configuration
        $single_panel_config = [
            'layout' => [
                'type' => 'single-panel',
                'fullWidth' => true,
                'enableAnimation' => false,
            ],
            'filters' => [
                'enableAutoApply' => false,
                'applyOnEnter' => true,
                'rememberFilters' => true,
            ],
            'autoRefresh' => [
                'enabled' => true,
                'events' => [
                    'wpdt:itemCreated',
                    'wpdt:itemUpdated',
                    'wpdt:itemDeleted',
                    'wpdt:filtersApplied',
                    'wpdt:filtersReset',
                ],
            ],
        ];

        // Merge configurations
        return array_merge($base_data, $single_panel_config);
    }

    /**
     * Get i18n strings for single panel
     *
     * Extends base translations dengan single panel specific strings.
     *
     * @return array Translation strings
     */
    protected function get_i18n_strings(): array {
        // Get base strings from parent
        $base_strings = parent::get_i18n_strings();

        // Add single panel specific strings
        $single_panel_strings = [
            'applyFilters' => __('Apply Filters', 'wp-datatable'),
            'resetFilters' => __('Reset', 'wp-datatable'),
            'noResults' => __('No results found', 'wp-datatable'),
            'filterPlaceholder' => __('Search...', 'wp-datatable'),
            'refreshing' => __('Refreshing...', 'wp-datatable'),
        ];

        // Merge translations
        return array_merge($base_strings, $single_panel_strings);
    }

    /**
     * Determine if single panel should load
     *
     * Checks if current context requires single panel layout.
     * Uses wpdt_use_single_panel filter for detection.
     *
     * Detection methods:
     * 1. Check filter wpdt_use_single_panel
     * 2. Check settings option (if filter not used)
     * 3. Default to false
     *
     * @return bool True if single panel should load
     */
    public function should_load(): bool {
        // Only load on admin pages
        if (!$this->is_admin_page()) {
            return false;
        }

        /**
         * Filter: Determine if single panel layout should load
         *
         * Plugins can use this filter to activate single panel layout
         * when their datatable is being displayed.
         *
         * @param bool $use_single_panel Use single panel layout (default: false)
         *
         * @return bool True to use single panel
         *
         * @example
         * add_filter('wpdt_use_single_panel', function($use_single_panel) {
         *     // Activate single panel on logs page
         *     if (isset($_GET['page']) && $_GET['page'] === 'wp-logs') {
         *         return true;
         *     }
         *     return $use_single_panel;
         * });
         */
        $use_single_panel = apply_filters('wpdt_use_single_panel', false);

        // If filter not used, check settings option
        if (!$use_single_panel) {
            $settings = get_option('wpdt_settings', []);
            $default_layout = isset($settings['default_layout']) ? $settings['default_layout'] : '';
            $use_single_panel = ($default_layout === 'single-panel');
        }

        return $use_single_panel;
    }
}
