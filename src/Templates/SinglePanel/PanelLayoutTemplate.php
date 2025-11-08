<?php
/**
 * Panel Layout Template - Single Panel
 *
 * @package     WP_DataTable
 * @subpackage  Templates\SinglePanel
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Templates/single-panel/PanelLayoutTemplate.php
 *
 * Description: Full-width panel layout untuk single panel.
 *              Simple container untuk datatable tanpa left/right split.
 *              No sliding animations, no dual panel complexity.
 *
 * Features:
 * - Full-width layout
 * - Single content area
 * - Hook-based content rendering
 * - Responsive container
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial implementation
 * - Full-width panel layout
 * - Simplified dari dual panel
 * - Single content hook
 */

namespace WPDataTable\Templates\SinglePanel;

defined('ABSPATH') || exit;

class PanelLayoutTemplate {

    /**
     * Render single panel layout
     *
     * Full-width container untuk datatable content.
     * Content rendered via wpdt_panel_content action hook.
     *
     * @param array $config Layout configuration
     *   - entity (string): Entity name
     *   - container_class (string): Additional CSS classes
     *
     * @return void
     */
    public static function render($config) {
        // Validate config
        $config = self::validate_config($config);

        /**
         * Action: Before panel layout
         *
         * @param array $config Layout configuration
         */
        do_action('wpdt_before_panel_layout', $config);

        // Additional CSS classes
        $container_classes = ['wpdt-panel-container', 'wpdt-single-panel-container'];
        if (!empty($config['container_class'])) {
            $container_classes[] = $config['container_class'];
        }

        ?>
        <!-- DataTable Container - matches dual panel pattern -->
        <div class="wpdt-datatable-container">
            <div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>"
                 data-entity="<?php echo esc_attr($config['entity']); ?>">

                <div class="wpdt-panel-content">
                    <?php
                    /**
                     * Action: Render panel content
                     *
                     * Main content hook untuk single panel.
                     * Plugins render their datatable here.
                     *
                     * @param array $config Layout configuration
                     *
                     * @example
                     * add_action('wpdt_panel_content', function($config) {
                     *     if ($config['entity'] !== 'logs') return;
                     *     include __DIR__ . '/views/logs/datatable.php';
                     * });
                     */
                    do_action('wpdt_panel_content', $config);
                    ?>
                </div>

            </div>
        </div>
        <?php

        /**
         * Action: After panel layout
         *
         * @param array $config Layout configuration
         */
        do_action('wpdt_after_panel_layout', $config);
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
            'container_class' => '',
        ];

        $config = wp_parse_args($config, $defaults);

        // Entity is required
        if (empty($config['entity'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[WP DataTable] PanelLayoutTemplate: entity is required in config');
            }
        }

        return $config;
    }
}
