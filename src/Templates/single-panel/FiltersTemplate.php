<?php
/**
 * Filters Template - Single Panel
 *
 * @package     WP_DataTable
 * @subpackage  Templates\SinglePanel
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Templates/single-panel/FiltersTemplate.php
 *
 * Description: Filter controls untuk single panel layout.
 *              Pure infrastructure - provides container dan filter rendering.
 *              Plugins register filters via wpdt_datatable_filters filter hook.
 *
 * Features:
 * - Hook-based filter registration
 * - Multiple filter types (select, search, date_range)
 * - Responsive layout
 * - WordPress admin styling
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial implementation
 * - Reused concept dari dual panel
 * - Simplified filter rendering
 * - Hook-based registration
 */

namespace WPDataTable\Templates\SinglePanel;

defined('ABSPATH') || exit;

class FiltersTemplate {

    /**
     * Render filters container
     *
     * @param array $config Filter configuration
     *   - entity (string): Entity name
     *
     * @return void
     */
    public static function render($config) {
        // Validate config
        $config = self::validate_config($config);

        // Get filters from filter hook
        $filters = self::get_filters($config['entity']);

        // If no filters, don't render container
        if (empty($filters)) {
            return;
        }

        /**
         * Action: Before filters
         *
         * @param array $config Filter configuration
         */
        do_action('wpdt_before_filters', $config);

        ?>
        <div class="wpdt-filters-container">
            <div class="wpdt-filters-wrapper">
                <?php
                foreach ($filters as $filter_id => $filter) {
                    self::render_filter($filter_id, $filter, $config);
                }
                ?>

                <div class="wpdt-filter-actions">
                    <button type="button" class="button wpdt-filter-apply">
                        <?php esc_html_e('Apply Filters', 'wp-datatable'); ?>
                    </button>
                    <button type="button" class="button wpdt-filter-reset">
                        <?php esc_html_e('Reset', 'wp-datatable'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php

        /**
         * Action: After filters
         *
         * @param array $config Filter configuration
         */
        do_action('wpdt_after_filters', $config);
    }

    /**
     * Get filters for entity
     *
     * @param string $entity Entity name
     * @return array Filters array
     */
    private static function get_filters($entity) {
        /**
         * Filter: Register filters for entity
         *
         * @param array $filters Filters array
         * @param string $entity Entity name
         *
         * @return array Modified filters array
         *
         * @example
         * add_filter('wpdt_datatable_filters', function($filters, $entity) {
         *     if ($entity !== 'logs') return $filters;
         *
         *     return [
         *         'level' => [
         *             'type' => 'select',
         *             'label' => 'Log Level',
         *             'options' => [
         *                 'info' => 'Info',
         *                 'warning' => 'Warning',
         *                 'error' => 'Error',
         *             ],
         *             'default' => '',
         *         ],
         *         'search' => [
         *             'type' => 'search',
         *             'label' => 'Search',
         *             'placeholder' => 'Search logs...',
         *         ],
         *     ];
         * }, 10, 2);
         */
        $filters = apply_filters('wpdt_datatable_filters', [], $entity);

        return $filters;
    }

    /**
     * Render individual filter
     *
     * @param string $filter_id Filter identifier
     * @param array $filter Filter configuration
     * @param array $config Template configuration
     * @return void
     */
    private static function render_filter($filter_id, $filter, $config) {
        $type = isset($filter['type']) ? $filter['type'] : 'text';
        $label = isset($filter['label']) ? $filter['label'] : ucfirst($filter_id);

        ?>
        <div class="wpdt-filter wpdt-filter-<?php echo esc_attr($type); ?>"
             data-filter-id="<?php echo esc_attr($filter_id); ?>"
             data-entity="<?php echo esc_attr($config['entity']); ?>">

            <label for="wpdt-filter-<?php echo esc_attr($filter_id); ?>">
                <?php echo esc_html($label); ?>
            </label>

            <?php
            switch ($type) {
                case 'select':
                    self::render_select_filter($filter_id, $filter);
                    break;

                case 'search':
                    self::render_search_filter($filter_id, $filter);
                    break;

                case 'date_range':
                    self::render_date_range_filter($filter_id, $filter);
                    break;

                default:
                    self::render_text_filter($filter_id, $filter);
                    break;
            }
            ?>

        </div>
        <?php
    }

    /**
     * Render select filter
     *
     * @param string $filter_id Filter ID
     * @param array $filter Filter config
     * @return void
     */
    private static function render_select_filter($filter_id, $filter) {
        $options = isset($filter['options']) ? $filter['options'] : [];
        $default = isset($filter['default']) ? $filter['default'] : '';

        ?>
        <select id="wpdt-filter-<?php echo esc_attr($filter_id); ?>"
                name="<?php echo esc_attr($filter_id); ?>"
                class="wpdt-filter-input">
            <option value=""><?php esc_html_e('All', 'wp-datatable'); ?></option>
            <?php foreach ($options as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>"
                    <?php selected($default, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render search filter
     *
     * @param string $filter_id Filter ID
     * @param array $filter Filter config
     * @return void
     */
    private static function render_search_filter($filter_id, $filter) {
        $placeholder = isset($filter['placeholder']) ? $filter['placeholder'] : '';

        ?>
        <input type="search"
               id="wpdt-filter-<?php echo esc_attr($filter_id); ?>"
               name="<?php echo esc_attr($filter_id); ?>"
               class="wpdt-filter-input"
               placeholder="<?php echo esc_attr($placeholder); ?>">
        <?php
    }

    /**
     * Render text filter
     *
     * @param string $filter_id Filter ID
     * @param array $filter Filter config
     * @return void
     */
    private static function render_text_filter($filter_id, $filter) {
        $placeholder = isset($filter['placeholder']) ? $filter['placeholder'] : '';

        ?>
        <input type="text"
               id="wpdt-filter-<?php echo esc_attr($filter_id); ?>"
               name="<?php echo esc_attr($filter_id); ?>"
               class="wpdt-filter-input"
               placeholder="<?php echo esc_attr($placeholder); ?>">
        <?php
    }

    /**
     * Render date range filter
     *
     * @param string $filter_id Filter ID
     * @param array $filter Filter config
     * @return void
     */
    private static function render_date_range_filter($filter_id, $filter) {
        ?>
        <div class="wpdt-date-range-inputs">
            <input type="date"
                   id="wpdt-filter-<?php echo esc_attr($filter_id); ?>-from"
                   name="<?php echo esc_attr($filter_id); ?>_from"
                   class="wpdt-filter-input"
                   placeholder="<?php esc_attr_e('From', 'wp-datatable'); ?>">
            <span class="wpdt-date-separator">-</span>
            <input type="date"
                   id="wpdt-filter-<?php echo esc_attr($filter_id); ?>-to"
                   name="<?php echo esc_attr($filter_id); ?>_to"
                   class="wpdt-filter-input"
                   placeholder="<?php esc_attr_e('To', 'wp-datatable'); ?>">
        </div>
        <?php
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
                error_log('[WP DataTable] FiltersTemplate: entity is required in config');
            }
        }

        return $config;
    }
}
