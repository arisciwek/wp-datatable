<?php
/**
 * Stats Box Template - Single Panel
 *
 * @package     WP_DataTable
 * @subpackage  Templates\SinglePanel
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Templates/single-panel/StatsBoxTemplate.php
 *
 * Description: Statistics container untuk single panel layout.
 *              Pure infrastructure - provides container dan hook only.
 *              Plugins render their stats via wpdt_statistics_content action.
 *
 * Features:
 * - Responsive grid layout (1-4 columns)
 * - Hook-based content rendering
 * - Empty state handling
 * - WordPress admin styling
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial implementation
 * - Reused concept dari dual panel
 * - Pure infrastructure pattern
 * - Hook-based rendering
 */

namespace WPDataTable\Templates\SinglePanel;

defined('ABSPATH') || exit;

class StatsBoxTemplate {

    /**
     * Render statistics container
     *
     * Pure infrastructure template.
     * Provides container dan hook untuk stats rendering.
     *
     * @param array $config Statistics configuration
     *   - entity (string): Entity name
     *
     * @return void
     */
    public static function render($config) {
        // Validate config
        $config = self::validate_config($config);

        /**
         * Action: Before statistics
         *
         * @param array $config Statistics configuration
         */
        do_action('wpdt_before_statistics', $config);

        ?>
        <div class="wpdt-statistics-container">
            <?php
            /**
             * Action: Render statistics content
             *
             * Plugins render their statistics boxes here.
             *
             * @param array $config Statistics configuration
             *
             * @example
             * add_action('wpdt_statistics_content', function($config) {
             *     if ($config['entity'] !== 'logs') return;
             *
             *     echo '<div class="wpdt-stat-box">';
             *     echo '<div class="wpdt-stat-value">1,234</div>';
             *     echo '<div class="wpdt-stat-label">Total Logs</div>';
             *     echo '</div>';
             * });
             */
            do_action('wpdt_statistics_content', $config);

            /**
             * If no stats content rendered, show empty state
             */
            if (!did_action('wpdt_statistics_content') || !self::has_stats_content()) {
                self::render_empty_state($config);
            }
            ?>
        </div>
        <?php

        /**
         * Action: After statistics
         *
         * @param array $config Statistics configuration
         */
        do_action('wpdt_after_statistics', $config);
    }

    /**
     * Render empty state
     *
     * @param array $config Statistics configuration
     * @return void
     */
    private static function render_empty_state($config) {
        ?>
        <div class="wpdt-empty-stats">
            <p>
                <?php
                printf(
                    esc_html__('No statistics available for %s.', 'wp-datatable'),
                    '<strong>' . esc_html($config['entity']) . '</strong>'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Check if stats content was rendered
     *
     * Simple check - can be enhanced with output buffering.
     *
     * @return bool True if stats content rendered
     */
    private static function has_stats_content() {
        // Simple heuristic - check if action has callbacks
        return has_action('wpdt_statistics_content');
    }

    /**
     * Validate configuration
     *
     * @param array $config Input configuration
     * @return array Validated configuration
     */
    private static function validate_config($config) {
        $defaults = [
            'entity' => '',
        ];

        $config = wp_parse_args($config, $defaults);

        // Entity is required
        if (empty($config['entity'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[WP DataTable] StatsBoxTemplate: entity is required in config');
            }
        }

        return $config;
    }
}
