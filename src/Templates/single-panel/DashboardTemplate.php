<?php
/**
 * Dashboard Template - Single Panel
 *
 * @package     WP_DataTable
 * @subpackage  Templates\SinglePanel
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Templates/single-panel/DashboardTemplate.php
 *
 * Description: Main dashboard orchestrator untuk single panel layout.
 *              Simplified version tanpa dual panel complexity.
 *              Full-width datatable listing dengan stats & filters support.
 *
 * Features:
 * - Full-width layout (no left/right split)
 * - No tabs (simplified)
 * - Stats support (optional)
 * - Filters support (optional)
 * - Simple orchestration
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial implementation
 * - Simplified dari dual panel concept
 * - Full-width layout
 * - Stats & filters support
 * - No tabs, no panel splitting
 */

namespace WPDataTable\Templates\SinglePanel;

defined('ABSPATH') || exit;

class DashboardTemplate {

    /**
     * Render single panel dashboard
     *
     * Main orchestrator untuk single panel layout.
     * Renders: page header, stats (optional), filters (optional), datatable.
     *
     * @param array $config Dashboard configuration
     *   - entity (string): Entity name (e.g., 'logs', 'reports')
     *   - title (string): Page title
     *   - has_stats (bool): Show statistics box
     *   - has_filters (bool): Show filters
     *   - description (string): Optional page description
     *
     * @return void
     */
    public static function render($config) {
        // Validate config
        $config = self::validate_config($config);

        // Ensure assets loaded
        self::ensure_assets_loaded();

        /**
         * Action: Before dashboard render
         *
         * @param array $config Dashboard configuration
         */
        do_action('wpdt_before_dashboard', $config);

        // Render dashboard container
        ?>
        <div class="wrap wpdt-dashboard-wrap wpdt-single-panel-dashboard">

            <?php
            /**
             * Page Header
             */
            self::render_page_header($config);

            /**
             * Statistics Box (if enabled)
             */
            if ($config['has_stats']) {
                StatsBoxTemplate::render($config);
            }

            /**
             * Filters (if enabled)
             */
            if ($config['has_filters']) {
                FiltersTemplate::render($config);
            }

            /**
             * Panel Layout (datatable container)
             */
            PanelLayoutTemplate::render($config);
            ?>

        </div>
        <?php

        /**
         * Action: After dashboard render
         *
         * @param array $config Dashboard configuration
         */
        do_action('wpdt_after_dashboard', $config);

        /**
         * Action: Dashboard template rendered
         *
         * Triggered after complete dashboard render.
         * Useful for logging, analytics, etc.
         *
         * @param array $config Dashboard configuration
         */
        do_action('wpdt_dashboard_template_rendered', $config);
    }

    /**
     * Render page header
     *
     * @param array $config Dashboard configuration
     * @return void
     */
    private static function render_page_header($config) {
        ?>
        <div class="wpdt-page-header">
            <h1 class="wp-heading-inline"><?php echo esc_html($config['title']); ?></h1>

            <?php
            /**
             * Action: Page header actions
             *
             * Display action buttons next to title (e.g., Add New)
             *
             * @param array $config Dashboard configuration
             */
            do_action('wpdt_page_header_actions', $config);
            ?>

            <?php if (!empty($config['description'])): ?>
                <p class="wpdt-page-description">
                    <?php echo esc_html($config['description']); ?>
                </p>
            <?php endif; ?>
        </div>
        <hr class="wp-header-end">
        <?php
    }

    /**
     * Validate configuration
     *
     * Ensure required config keys exist, set defaults.
     *
     * @param array $config Input configuration
     * @return array Validated configuration
     */
    private static function validate_config($config) {
        $defaults = [
            'entity' => '',
            'title' => __('DataTable', 'wp-datatable'),
            'has_stats' => false,
            'has_filters' => false,
            'description' => '',
        ];

        $config = wp_parse_args($config, $defaults);

        // Entity is required
        if (empty($config['entity'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[WP DataTable] DashboardTemplate: entity is required in config');
            }
        }

        return $config;
    }

    /**
     * Ensure assets loaded
     *
     * Signal single panel usage untuk asset loading strategy.
     *
     * @return void
     */
    private static function ensure_assets_loaded() {
        /**
         * Filter: Signal single panel usage
         *
         * Triggers SinglePanelAssets strategy to load.
         *
         * @param bool $use_single_panel Use single panel layout
         *
         * @return bool True to load single panel assets
         */
        add_filter('wpdt_use_single_panel', '__return_true');

        /**
         * Action: Single panel assets requested
         *
         * @param string $template Template name
         */
        do_action('wpdt_single_panel_assets_requested', 'DashboardTemplate');
    }
}
