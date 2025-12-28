<?php
/**
 * Dual Panel Assets Strategy
 *
 * @package     WP_DataTable
 * @subpackage  Controllers/Assets
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Controllers/Assets/DualPanelAssets.php
 *
 * Description: Asset loading strategy untuk dual-panel layout.
 *              Loads CSS dan JS yang diperlukan untuk dual-panel functionality.
 *              Implements conditional loading based on layout detection.
 *
 * Assets Loaded:
 * - CSS: dual-panel.css (layout, animations, responsive)
 * - JS: panel-manager.js (panel interactions, AJAX)
 * - JS: tab-manager.js (tab navigation, keyboard support)
 * - JS: auto-refresh.js (event-driven table refresh)
 *
 * Conditional Loading:
 * - Checks wpdt_use_dual_panel filter
 * - Only loads if dual-panel layout is active
 * - Falls back to single-panel if filter returns false
 *
 * Dependencies:
 * - jQuery (WordPress core)
 * - DataTables.js (from BaseAssets)
 * - Common dependencies loaded by parent
 *
 * Localized Data:
 * - Extends base data from parent
 * - Adds dual-panel specific config
 * - Panel animation settings
 * - Layout configuration
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial implementation
 * - Dual panel asset loading
 * - Conditional loading strategy
 * - Extended localize data for dual panel
 */

namespace WPDataTable\Controllers\Assets;

defined('ABSPATH') || exit;

class DualPanelAssets extends BaseAssets {
    /**
     * Strategy name
     *
     * @var string
     */
    protected $strategy_name = 'dual-panel';

    /**
     * Enqueue dual panel CSS
     *
     * Load dual-panel.css with layout, animations, and responsive styles.
     *
     * @return void
     */
    public function enqueue_styles(): void {
        // Enqueue common dependencies first
        $this->enqueue_common_dependencies();

        // Dual panel CSS
        wp_enqueue_style(
            'wpdt-dual-panel',
            $this->get_plugin_url() . 'assets/css/dual-panel.css',
            ['datatables'],
            $this->get_version()
        );
    }

    /**
     * Enqueue dual panel JavaScript
     *
     * Load panel manager, tab manager, and auto-refresh scripts
     * with proper dependencies and localization.
     *
     * @return void
     */
    public function enqueue_scripts(): void {
        // Enqueue common dependencies first
        $this->enqueue_common_dependencies();

        $plugin_url = $this->get_plugin_url();
        $version = $this->get_version();

        // Panel Manager - Core panel interactions and AJAX
        wp_enqueue_script(
            'wpdt-panel-manager',
            $plugin_url . 'assets/js/dual-panel/panel-manager.js',
            ['jquery', 'datatables'],
            $version,
            true
        );

        // Action Buttons Handler - Global edit/delete button handlers
        wp_enqueue_script(
            'wpdt-action-buttons-handler',
            $plugin_url . 'assets/js/dual-panel/action-buttons-handler.js',
            ['jquery', 'datatables'],
            $version,
            true
        );

        // Modal Integration - Auto-wire action buttons to WP-Modal
        // Note: Don't use 'wp-modal' as dependency due to alphabetical loading race condition
        // modal-integration.js will check for WPModal availability at runtime
        wp_enqueue_script(
            'wpdt-modal-integration',
            $plugin_url . 'assets/js/dual-panel/modal-integration.js',
            ['jquery', 'wpdt-action-buttons-handler'],
            $version,
            true
        );

        // Tab Manager - Tab navigation and keyboard support
        wp_enqueue_script(
            'wpdt-tab-manager',
            $plugin_url . 'assets/js/dual-panel/tab-manager.js',
            ['jquery', 'wpdt-panel-manager'],
            $version,
            true
        );

        // Auto Refresh - Event-driven table refresh system
        wp_enqueue_script(
            'wpdt-auto-refresh',
            $plugin_url . 'assets/js/dual-panel/auto-refresh.js',
            ['jquery', 'datatables'],
            $version,
            true
        );

        // Get localize data and allow plugins to inject entity configs
        $localize_data = $this->get_localize_data();

        /**
         * Filter: wpdt_localize_data
         *
         * Allows plugins to inject entity-specific configuration into wpdtConfig.
         * Example: action_buttons config for modal integration.
         *
         * @param array $localize_data Base config from DualPanelAssets
         * @return array Modified config with entity configs
         */
        $localize_data = apply_filters('wpdt_localize_data', $localize_data);

        // Localize data to panel manager (main entry point)
        wp_localize_script(
            'wpdt-panel-manager',
            'wpdtConfig',
            $localize_data
        );
    }

    /**
     * Get localize data for dual panel
     *
     * Extends base data dengan dual panel specific configuration.
     *
     * @return array Localized data
     */
    public function get_localize_data(): array {
        // Get base data from parent
        $base_data = parent::get_localize_data();

        // Add dual panel specific configuration
        $dual_panel_config = [
            'layout' => [
                'type' => 'dual-panel',
                'leftPanelWidth' => '45%',
                'rightPanelWidth' => '55%',
                'enableAnimation' => true,
                'animationDuration' => 300,
            ],
            'panel' => [
                'enableHashRouting' => true,
                'closeOnEscape' => true,
                'loadMethod' => 'ajax', // or 'inline'
            ],
            'tabs' => [
                'enableKeyboard' => true,
                'rememberLastTab' => true,
                'animateSwitch' => true,
            ],
            'autoRefresh' => [
                'enabled' => true,
                'events' => [
                    'wpdt:itemCreated',
                    'wpdt:itemUpdated',
                    'wpdt:itemDeleted',
                ],
            ],
        ];

        // Merge configurations
        return array_merge($base_data, $dual_panel_config);
    }

    /**
     * Get i18n strings for dual panel
     *
     * Extends base translations dengan dual panel specific strings.
     *
     * @return array Translation strings
     */
    protected function get_i18n_strings(): array {
        // Get base strings from parent
        $base_strings = parent::get_i18n_strings();

        // Add dual panel specific strings
        $dual_panel_strings = [
            'closePanel' => __('Close Panel', 'wp-datatable'),
            'openPanel' => __('Open Panel', 'wp-datatable'),
            'previousTab' => __('Previous Tab', 'wp-datatable'),
            'nextTab' => __('Next Tab', 'wp-datatable'),
            'loadingPanel' => __('Loading panel...', 'wp-datatable'),
            'errorLoadingPanel' => __('Error loading panel', 'wp-datatable'),
            'noDataAvailable' => __('No data available', 'wp-datatable'),
        ];

        // Merge translations
        return array_merge($base_strings, $dual_panel_strings);
    }

    /**
     * Determine if dual panel should load
     *
     * Checks if current context requires dual panel layout.
     * Uses wpdt_use_dual_panel filter for detection.
     *
     * Detection methods:
     * 1. Check filter wpdt_use_dual_panel
     * 2. Check settings option (if filter not used)
     * 3. Default to false (use single-panel)
     *
     * @return bool True if dual panel should load
     */
    public function should_load(): bool {
        // Only load on admin pages
        if (!$this->is_admin_page()) {
            return false;
        }

        /**
         * Filter: Determine if dual panel layout should load
         *
         * Plugins can use this filter to activate dual panel layout
         * when their datatable is being displayed.
         *
         * @param bool $use_dual_panel Use dual panel layout (default: false)
         *
         * @return bool True to use dual panel
         *
         * @example
         * add_filter('wpdt_use_dual_panel', function($use_dual_panel) {
         *     // Activate dual panel on agency list page
         *     if (isset($_GET['page']) && $_GET['page'] === 'wp-agency') {
         *         return true;
         *     }
         *     return $use_dual_panel;
         * });
         */
        $use_dual_panel = apply_filters('wpdt_use_dual_panel', false);

        // If filter not used, check settings option
        if (!$use_dual_panel) {
            $settings = get_option('wpdt_settings', []);
            $use_dual_panel = isset($settings['enable_dual_panel']) && $settings['enable_dual_panel'];
        }

        return $use_dual_panel;
    }
}
