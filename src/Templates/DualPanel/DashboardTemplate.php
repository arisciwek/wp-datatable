<?php
/**
 * Dashboard Template - Dual Panel
 *
 * Main dashboard orchestrator untuk dual panel layout.
 * Ported dari wp-app-core dengan adaptasi namespace & hooks.
 *
 * @package     WP_DataTable
 * @subpackage  Templates\DualPanel
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Templates/dual-panel/DashboardTemplate.php
 *
 * Description: Main dashboard container template untuk DataTable pages.
 *              Provides structure and hook points untuk plugin customization.
 *              Template ini handle rendering complete dashboard dengan:
 *              - Page header (title & action buttons)
 *              - Statistics box (optional)
 *              - Filters (optional)
 *              - Dual panel layout
 *              - Auto-asset loading (Plug & Play pattern)
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Ported dari wp-app-core/src/Views/DataTable/Templates/DashboardTemplate.php
 * - Updated namespace: WPAppCore\Views\DataTable\Templates → WPDataTable\Templates\DualPanel
 * - Updated hooks: wpapp_ → wpdt_
 * - Updated CSS classes: wpapp- → wpdt-
 * - Removed modal template reference (not in v0.1.0)
 * - Updated asset controller reference for wp-datatable
 * - Updated text domain: wp-app-core → wp-datatable
 *
 * Original Source: wp-app-core v1.1.0 (2025-10-29)
 *
 * Usage:
 * ```php
 * use WPDataTable\Templates\DualPanel\DashboardTemplate;
 *
 * DashboardTemplate::render([
 *     'entity' => 'customer',
 *     'title' => 'Customers',
 *     'ajax_action' => 'get_customer_details',
 *     'has_stats' => true,
 *     'has_tabs' => true,
 *     'nonce' => wp_create_nonce('customer_nonce')
 * ]);
 * ```
 */

namespace WPDataTable\Templates\DualPanel;

defined('ABSPATH') || exit;

class DashboardTemplate {

    /**
     * Render dashboard template
     *
     * Main method to render complete dashboard with all components
     *
     * @param array $config Configuration array
     * @return void
     */
    public static function render($config) {
        // Validate config
        $config = self::validate_config($config);

        // AUTO-LOAD ASSETS (Plug & Play Pattern)
        // Container detects usage and loads assets automatically
        // Plugin tidak perlu register hooks atau modifikasi core
        self::ensure_assets_loaded();

        // Fire action untuk tracking
        do_action('wpdt_dashboard_template_rendered', $config);

        // Start rendering
        ?>
        <div class="wrap wpdt-dashboard-wrap">
        <div class="wrap wpdt-datatable-page" data-entity="<?php echo esc_attr($config['entity']); ?>">

            <!-- Page Header Container (Global Scope) -->
            <?php self::render_page_header($config); ?>

            <?php
            /**
             * Action: Before dashboard content
             *
             * @param array $config Dashboard configuration
             * @param string $entity Entity name
             */
            do_action('wpdt_dashboard_before_content', $config, $config['entity']);
            ?>

            <!-- Statistics Section (if enabled) -->
            <?php if (!empty($config['has_stats'])): ?>
                <?php StatsBoxTemplate::render($config['entity']); ?>
            <?php endif; ?>

            <!-- Filters Section -->
            <?php FiltersTemplate::render($config['entity'], $config); ?>

            <!-- Main Panel Layout -->
            <?php
            PanelLayoutTemplate::render($config);
            ?>

            <?php
            /**
             * Action: After dashboard content
             *
             * @param array $config Dashboard configuration
             * @param string $entity Entity name
             */
            do_action('wpdt_dashboard_after_content', $config, $config['entity']);
            ?>

        </div>
        </div>
        <?php
    }

    /**
     * Ensure DataTable assets are loaded
     *
     * Auto-detection pattern: Container loads assets automatically
     * when DashboardTemplate is used. Plugin tidak perlu register hooks.
     *
     * @return void
     */
    private static function ensure_assets_loaded() {
        // Check if assets already loaded
        if (wp_script_is('wpdt-panel-manager', 'enqueued')) {
            return; // Already loaded
        }

        // Check if assets already printed (late enqueue scenario)
        if (wp_script_is('wpdt-panel-manager', 'done')) {
            return; // Already printed
        }

        // Force enqueue assets (when AssetController is implemented)
        // Note: AssetController will be created in Phase 3
        // For now, this is a placeholder for future implementation
        if (class_exists('\\WPDataTable\\Controllers\\AssetController')) {
            \WPDataTable\Controllers\AssetController::force_enqueue();
        }
    }

    /**
     * Render page header with hook system
     *
     * All classes use wpdt- prefix (from wp-datatable)
     *
     * Simplified structure:
     * - Removed outer wpdt-page-header wrapper
     * - Now consistent with wpdt-statistics-container, wpdt-filters-container
     *
     * @param array $config Configuration
     * @return void
     */
    private static function render_page_header($config) {
        ?>
        <!-- Page Header Container (Global Scope) -->
        <div class="wpdt-page-header-container">
            <!-- Header Left: Title & Subtitle -->
            <div class="wpdt-header-left">
                <?php
                /**
                 * Action: Page header left content
                 *
                 * Plugins should hook here to render title and subtitle
                 * Each plugin renders their own HTML with their own CSS classes
                 *
                 * IMPORTANT: Use plugin-specific CSS classes (e.g., agency-, customer-)
                 *
                 * @param array $config Dashboard configuration
                 * @param string $entity Entity name
                 *
                 * @example
                 * add_action('wpdt_page_header_left', function($config, $entity) {
                 *     if ($entity !== 'agency') return;
                 *     echo '<h1 class="agency-title">' . esc_html($config['title']) . '</h1>';
                 *     echo '<div class="agency-subtitle">Manage agencies</div>';
                 * }, 10, 2);
                 */
                do_action('wpdt_page_header_left', $config, $config['entity']);
                ?>

                <?php if (!did_action('wpdt_page_header_left')): ?>
                    <!-- Default title if no hook registered -->
                    <h1 class="wp-heading-inline"><?php echo esc_html($config['title']); ?></h1>
                <?php endif; ?>
            </div>

            <!-- Header Right: Action Buttons -->
            <div class="wpdt-header-right">
                <?php
                /**
                 * Action: Page header right content
                 *
                 * Plugins should hook here to render action buttons
                 * Each plugin renders their own HTML with their own CSS classes
                 *
                 * IMPORTANT: Use plugin-specific CSS classes (e.g., agency-, customer-)
                 *
                 * @param array $config Dashboard configuration
                 * @param string $entity Entity name
                 *
                 * @example
                 * add_action('wpdt_page_header_right', function($config, $entity) {
                 *     if ($entity !== 'agency') return;
                 *     echo '<a href="#" class="button button-primary agency-add-btn">Add New Agency</a>';
                 * }, 10, 2);
                 */
                do_action('wpdt_page_header_right', $config, $config['entity']);
                ?>
            </div>
        </div>

        <hr class="wp-header-end">
        <?php
    }

    /**
     * Validate configuration
     *
     * @param array $config Raw configuration
     * @return array Validated configuration with defaults
     */
    private static function validate_config($config) {
        $defaults = [
            'entity' => '',
            'title' => '',
            'ajax_action' => '',
            'has_stats' => false,
            'has_tabs' => false,
            'nonce' => '',
        ];

        $config = wp_parse_args($config, $defaults);

        // Validate required fields
        if (empty($config['entity'])) {
            wp_die(__('Dashboard entity is required', 'wp-datatable'));
        }

        if (empty($config['title'])) {
            $config['title'] = ucfirst($config['entity']);
        }

        return $config;
    }
}
