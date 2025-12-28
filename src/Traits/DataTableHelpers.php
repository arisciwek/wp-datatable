<?php
/**
 * DataTable Helpers Trait
 *
 * @package     WP_DataTable
 * @subpackage  Traits
 * @version     0.2.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Traits/DataTableHelpers.php
 *
 * Description: Reusable helper methods untuk DataTable implementations.
 *              Provides UI helpers untuk action buttons, status badges, dll.
 *              Can be used by models yang extend dari wp-app-core's DataTableModel.
 *
 * Purpose:
 * - Extract UI helpers dari AbstractDataTable ke standalone trait
 * - Allow composition pattern (models extend DataTableModel, use trait)
 * - Consistent UI across all datatables
 * - Convention-based button generation untuk wp-datatable's action-buttons-handler.js
 *
 * Features:
 * - generate_action_buttons() - Auto-generate edit/delete buttons dengan permission
 * - format_status_badge() - Auto-format status dengan color coding
 * - format_panel_row_data() - Auto-format DT_RowId dan DT_RowData
 * - Convention-based class names: {entity}-edit-btn, {entity}-delete-btn
 *
 * Usage Example:
 * ```php
 * use WPAppCore\Models\DataTable\DataTableModel;
 * use WPDataTable\Traits\DataTableHelpers;
 *
 * class DivisionDataTableModel extends DataTableModel {
 *     use DataTableHelpers;
 *
 *     protected function format_row($row): array {
 *         return array_merge(
 *             $this->format_panel_row_data($row, 'division'),
 *             [
 *                 'code' => esc_html($row->code),
 *                 'name' => esc_html($row->name),
 *                 'status' => $this->format_status_badge($row->status),
 *                 'actions' => $this->generate_action_buttons($row, [
 *                     'entity' => 'division',
 *                     'edit_capability' => 'edit_all_divisions',
 *                     'delete_capability' => 'delete_division',
 *                     'text_domain' => 'wp-agency'
 *                 ])
 *             ]
 *         );
 *     }
 * }
 * ```
 *
 * Integration:
 * - Works with wp-datatable's action-buttons-handler.js
 * - Events triggered: wpdt:action-edit, wpdt:action-delete
 * - Follows WordPress admin button styling
 * - Dashicons for icons
 *
 * Changelog:
 * 0.2.0 - 2025-12-28
 * - Created standalone trait from AbstractDataTable
 * - Added flexible entity parameter for composition pattern
 * - Added text_domain parameter for i18n
 * - Convention-based button classes for action-buttons-handler.js
 * - WordPress admin styling
 */

namespace WPDataTable\Traits;

defined('ABSPATH') || exit;

trait DataTableHelpers {

    /**
     * Generate standard action buttons
     *
     * Creates edit/delete buttons dengan:
     * - Convention-based class names: {entity}-edit-btn, {entity}-delete-btn
     * - Permission checks via capabilities
     * - WordPress admin button styling
     * - Dashicons integration
     * - Compatible dengan wp-datatable's action-buttons-handler.js
     *
     * @param object $row Database row object (must have ->id property)
     * @param array $options Configuration options
     *   - entity (required): Entity name untuk button class (e.g. 'division', 'employee')
     *   - edit_capability (optional): WP capability untuk edit button
     *   - delete_capability (optional): WP capability untuk delete button
     *   - text_domain (optional): Text domain untuk i18n (default: 'wp-datatable')
     *   - custom_buttons (optional): Array of custom button HTML strings
     *   - show_view (optional): Show view button for dual-panel (default: false)
     *
     * @return string HTML action buttons
     *
     * @example
     * ```php
     * $this->generate_action_buttons($row, [
     *     'entity' => 'division',
     *     'edit_capability' => 'edit_all_divisions',
     *     'delete_capability' => 'delete_division',
     *     'text_domain' => 'wp-agency'
     * ]);
     * ```
     *
     * Generated HTML triggers events:
     * - Click on .division-edit-btn triggers wpdt:action-edit event
     * - Click on .division-delete-btn triggers wpdt:action-delete event
     */
    protected function generate_action_buttons($row, array $options = []): string {
        $buttons = [];

        // Required parameter
        $entity = $options['entity'] ?? '';
        if (empty($entity)) {
            error_log('[WPDataTable] generate_action_buttons: entity parameter is required');
            return '-';
        }

        $text_domain = $options['text_domain'] ?? 'wp-datatable';
        $row_id = is_object($row) ? ($row->id ?? 0) : ($row['id'] ?? 0);

        if (!$row_id) {
            error_log('[WPDataTable] generate_action_buttons: row has no id property');
            return '-';
        }

        // 1. View button (optional, for dual-panel layout)
        if (!empty($options['show_view'])) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small wpdt-panel-trigger"
                        data-id="%d" data-entity="%s" title="%s">
                    <span class="dashicons dashicons-visibility"></span>
                </button>',
                esc_attr($row_id),
                esc_attr($entity),
                esc_attr__('View Details', $text_domain)
            );
        }

        // 2. Edit button (if capability provided and user has permission)
        if (!empty($options['edit_capability']) && current_user_can($options['edit_capability'])) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small %s-edit-btn"
                        data-id="%d"
                        data-entity="%s"
                        title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($entity),
                esc_attr($row_id),
                esc_attr($entity),
                esc_attr__('Edit', $text_domain)
            );
        }

        // 3. Delete button (if capability provided and user has permission)
        if (!empty($options['delete_capability']) && current_user_can($options['delete_capability'])) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small %s-delete-btn"
                        data-id="%d"
                        data-entity="%s"
                        title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_attr($entity),
                esc_attr($row_id),
                esc_attr($entity),
                esc_attr__('Delete', $text_domain)
            );
        }

        // 4. Add custom buttons (if provided)
        if (!empty($options['custom_buttons']) && is_array($options['custom_buttons'])) {
            $buttons = array_merge($buttons, $options['custom_buttons']);
        }

        return !empty($buttons) ? implode(' ', $buttons) : '-';
    }

    /**
     * Format status badge with color coding
     *
     * Generate HTML badge untuk status column dengan:
     * - Green badge untuk active status
     * - Red badge untuk inactive status
     * - Support for Indonesian: 'aktif' = active
     * - WordPress-style badge CSS
     *
     * @param string $status Status value ('active', 'inactive', 'aktif', etc.)
     * @param array $options Configuration options
     *   - active_value (optional): Value yang represents 'active' (default: 'active')
     *   - text_domain (optional): Text domain untuk i18n (default: 'wp-datatable')
     *   - custom_class (optional): Additional CSS class
     *
     * @return string HTML badge
     *
     * @example
     * ```php
     * $this->format_status_badge('active');
     * // Output: <span class="wpdt-badge wpdt-badge-success">Active</span>
     *
     * $this->format_status_badge('inactive', ['text_domain' => 'wp-agency']);
     * // Output: <span class="wpdt-badge wpdt-badge-error">Inactive</span>
     * ```
     */
    protected function format_status_badge(string $status, array $options = []): string {
        $active_value = $options['active_value'] ?? 'active';
        $text_domain = $options['text_domain'] ?? 'wp-datatable';
        $custom_class = $options['custom_class'] ?? '';

        // Check if status is active (support Indonesian 'aktif')
        $is_active = strtolower($status) === strtolower($active_value)
                  || strtolower($status) === 'aktif';

        $badge_class = $is_active ? 'success' : 'error';
        $status_text = $is_active
            ? __('Active', $text_domain)
            : __('Inactive', $text_domain);

        return sprintf(
            '<span class="wpdt-badge wpdt-badge-%s %s">%s</span>',
            esc_attr($badge_class),
            esc_attr($custom_class),
            esc_html($status_text)
        );
    }

    /**
     * Format panel integration data
     *
     * Generate DT_RowId dan DT_RowData untuk panel system integration.
     * Required untuk dual-panel layout dengan panel-manager.js.
     *
     * @param object|array $row Database row object or array
     * @param string $entity Entity name (e.g. 'division', 'employee')
     * @param array $additional_data Additional data untuk DT_RowData
     *
     * @return array Panel integration data
     *
     * @example
     * ```php
     * // Basic usage
     * $panel_data = $this->format_panel_row_data($row, 'division');
     * // Result: [
     * //     'DT_RowId' => 'division-123',
     * //     'DT_RowData' => ['id' => 123, 'entity' => 'division']
     * // ]
     *
     * // With additional data
     * $panel_data = $this->format_panel_row_data($row, 'division', ['status' => 'active']);
     * // Result: [
     * //     'DT_RowId' => 'division-123',
     * //     'DT_RowData' => ['id' => 123, 'entity' => 'division', 'status' => 'active']
     * // ]
     *
     * // Use in format_row():
     * return array_merge(
     *     $this->format_panel_row_data($row, 'division'),
     *     [
     *         'code' => $row->code,
     *         'name' => $row->name,
     *     ]
     * );
     * ```
     */
    protected function format_panel_row_data($row, string $entity, array $additional_data = []): array {
        $row_id = is_object($row) ? ($row->id ?? 0) : ($row['id'] ?? 0);

        if (!$row_id) {
            error_log('[WPDataTable] format_panel_row_data: row has no id property');
        }

        $base_data = [
            'id' => $row_id,
            'entity' => $entity
        ];

        return [
            'DT_RowId' => $entity . '-' . $row_id,
            'DT_RowData' => array_merge($base_data, $additional_data)
        ];
    }

    /**
     * Generate column configuration for DataTables.js
     *
     * Helper untuk generate column config array yang compatible dengan DataTables.js.
     * Simplifies column definition dengan sensible defaults.
     *
     * @param array $columns Column definitions
     *   Each column: ['data' => 'field_name', 'title' => 'Column Title', ...]
     *
     * @return array DataTables.js column configuration
     *
     * @example
     * ```php
     * $columns = $this->generate_columns_config([
     *     ['data' => 'code', 'title' => 'Code', 'width' => '15%'],
     *     ['data' => 'name', 'title' => 'Name', 'width' => '30%'],
     *     ['data' => 'status', 'title' => 'Status', 'width' => '15%'],
     *     ['data' => 'actions', 'title' => 'Actions', 'orderable' => false, 'searchable' => false]
     * ]);
     * ```
     */
    protected function generate_columns_config(array $columns): array {
        $config = [];

        foreach ($columns as $column) {
            $col_config = [
                'data' => $column['data'] ?? '',
                'title' => $column['title'] ?? '',
            ];

            // Optional properties
            if (isset($column['width'])) {
                $col_config['width'] = $column['width'];
            }
            if (isset($column['orderable'])) {
                $col_config['orderable'] = (bool) $column['orderable'];
            }
            if (isset($column['searchable'])) {
                $col_config['searchable'] = (bool) $column['searchable'];
            }
            if (isset($column['className'])) {
                $col_config['className'] = $column['className'];
            }

            $config[] = $col_config;
        }

        return $config;
    }

    /**
     * Escape HTML for output
     *
     * Wrapper untuk esc_html dengan fallback untuk empty values.
     *
     * @param mixed $value Value to escape
     * @param string $fallback Fallback value if empty (default: '-')
     * @return string Escaped HTML
     */
    protected function esc_output($value, string $fallback = '-'): string {
        if (empty($value) && $value !== '0') {
            return esc_html($fallback);
        }
        return esc_html($value);
    }
}
