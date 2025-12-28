<?php
/**
 * Abstract DataTable Class
 *
 * @package     WP_DataTable
 * @subpackage  Core
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Core/AbstractDataTable.php
 *
 * Description: Abstract base class untuk DataTable implementations.
 *              Provides common functionality dan helper methods.
 *              Child classes hanya perlu implement entity-specific logic.
 *
 * Benefits:
 * - Code reuse across different datatables
 * - Consistent UI patterns (badges, buttons, etc.)
 * - Standardized permission checks
 * - Panel integration helpers
 * - Status badge formatting
 * - Action button generation
 *
 * Usage Example:
 * ```php
 * class CustomerDataTable extends AbstractDataTable {
 *     protected function get_table_name(): string {
 *         return $GLOBALS['wpdb']->prefix . 'app_customers';
 *     }
 *
 *     protected function format_row($row): array {
 *         return [
 *             'id' => $row->id,
 *             'name' => esc_html($row->name),
 *             'status' => $this->format_status_badge($row->status),
 *             'actions' => $this->generate_action_buttons($row),
 *         ];
 *     }
 * }
 * ```
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial development version
 * - Based on wp-app-core AbstractDataTableModel pattern
 * - Adapted for independent framework
 * - Provides status badge formatting
 * - Provides action button generation
 * - Provides panel integration helpers
 * - Ready for dual-panel and single-panel layouts
 */

namespace WPDataTable\Core;

defined('ABSPATH') || exit;

abstract class AbstractDataTable implements DataTableInterface {

    // ========================================
    // ABSTRACT METHODS (Must be implemented by child classes)
    // ========================================

    /**
     * Get database table name
     *
     * @return string Full table name with prefix
     *
     * @example
     * ```php
     * protected function get_table_name(): string {
     *     global $wpdb;
     *     return $wpdb->prefix . 'app_customers';
     * }
     * ```
     */
    abstract protected function get_table_name(): string;

    /**
     * Format database row untuk output
     *
     * Transform raw database row menjadi format yang sesuai untuk DataTables.
     * Apply escaping, formatting, badges, buttons, etc.
     *
     * @param object $row Database row object
     * @return array Formatted row data
     *
     * @example
     * ```php
     * protected function format_row($row): array {
     *     return [
     *         'DT_RowId' => 'customer-' . $row->id,
     *         'id' => $row->id,
     *         'code' => esc_html($row->code),
     *         'name' => esc_html($row->name),
     *         'status' => $this->format_status_badge($row->status),
     *         'actions' => $this->generate_action_buttons($row),
     *     ];
     * }
     * ```
     */
    abstract protected function format_row($row): array;

    /**
     * Get database query for fetching rows
     *
     * @param array $request DataTables request parameters
     * @return string SQL query
     */
    abstract protected function get_query(array $request): string;

    /**
     * Get total records count (without filtering)
     *
     * @return int Total records
     */
    abstract protected function get_total_count(): int;

    /**
     * Get filtered records count
     *
     * @param array $request DataTables request parameters
     * @return int Filtered records count
     */
    abstract protected function get_filtered_count(array $request): int;

    // ========================================
    // INTERFACE IMPLEMENTATION (Default implementations)
    // ========================================

    /**
     * Get DataTable data based on request
     *
     * Implements DataTableInterface::get_data()
     * Provides default server-side processing logic.
     */
    public function get_data(array $request): array {
        global $wpdb;

        // Get counts
        $total_records = $this->get_total_count();
        $filtered_records = $this->get_filtered_count($request);

        // Get query
        $query = $this->get_query($request);

        // Fetch rows
        $results = $wpdb->get_results($query);

        // Format rows
        $data = [];
        foreach ($results as $row) {
            $data[] = $this->format_row($row);
        }

        return [
            'draw' => isset($request['draw']) ? intval($request['draw']) : 1,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $filtered_records,
            'data' => $data
        ];
    }

    /**
     * Check if current user can access this DataTable
     *
     * Default implementation checks config capabilities.
     * Override untuk custom permission logic.
     */
    public function can_access(): bool {
        $config = $this->get_config();
        $capabilities = $config['capabilities'] ?? [];

        if (empty($capabilities)) {
            return true;
        }

        foreach ($capabilities as $cap) {
            if (!current_user_can($cap)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get panel template path
     *
     * Default implementation returns empty (single-panel).
     * Override untuk dual-panel layout.
     */
    public function get_panel_template(): string {
        return '';
    }

    // ========================================
    // HELPER METHODS (Shared functionality)
    // ========================================

    /**
     * Format status badge with color coding
     *
     * Generate HTML badge untuk status column.
     * Active = green, Inactive = red.
     *
     * @param string $status Status value
     * @param string $active_value Value that represents 'active' (default: 'active')
     * @return string HTML badge
     */
    protected function format_status_badge(string $status, string $active_value = 'active'): string {
        $is_active = strtolower($status) === strtolower($active_value)
                  || $status === 'aktif'; // Indonesian support

        $badge_class = $is_active ? 'success' : 'error';
        $status_text = $is_active
            ? __('Active', $this->get_text_domain())
            : __('Inactive', $this->get_text_domain());

        return sprintf(
            '<span class="wpdt-badge wpdt-badge-%s">%s</span>',
            esc_attr($badge_class),
            esc_html($status_text)
        );
    }

    /**
     * Generate standard action buttons
     *
     * Creates action buttons dengan permission checks:
     * 1. View button (always shown) - opens panel
     * 2. Edit button (if edit_capability provided)
     * 3. Delete button (if delete_capability provided)
     * 4. Custom buttons (optional)
     *
     * @param object $row Database row object
     * @param array $options Button configuration
     * @return string HTML action buttons
     *
     * @example
     * ```php
     * $this->generate_action_buttons($row, [
     *     'edit_capability' => 'edit_customers',
     *     'delete_capability' => 'delete_customers',
     *     'custom_buttons' => ['<button>...</button>']
     * ]);
     * ```
     */
    protected function generate_action_buttons($row, array $options = []): string {
        $buttons = [];
        $entity = $this->get_entity_name();
        $text_domain = $this->get_text_domain();
        $config = $this->get_config();

        // Check if dual-panel layout
        $is_dual_panel = ($config['layout'] ?? 'single-panel') === 'dual-panel';

        // 1. View button (for dual-panel layout)
        if ($is_dual_panel) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small wpdt-panel-trigger"
                        data-id="%d" data-entity="%s" title="%s">
                    <span class="dashicons dashicons-visibility"></span>
                </button>',
                esc_attr($row->id),
                esc_attr($entity),
                esc_attr__('View Details', $text_domain)
            );
        }

        // 2. Edit button (if capability provided and user has permission)
        $edit_cap = $options['edit_capability'] ?? null;
        if ($edit_cap && current_user_can($edit_cap)) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small wpdt-edit-btn %s-edit-btn"
                        data-id="%d" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($entity),
                esc_attr($row->id),
                esc_attr__('Edit', $text_domain)
            );
        }

        // 3. Delete button (if capability provided and user has permission)
        $delete_cap = $options['delete_capability'] ?? null;
        if ($delete_cap && current_user_can($delete_cap)) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small wpdt-delete-btn %s-delete-btn"
                        data-id="%d" title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_attr($entity),
                esc_attr($row->id),
                esc_attr__('Delete', $text_domain)
            );
        }

        // 4. Add custom buttons (if provided)
        if (!empty($options['custom_buttons'])) {
            $buttons = array_merge($buttons, $options['custom_buttons']);
        }

        return implode(' ', $buttons);
    }

    /**
     * Format panel integration data
     *
     * Generate DT_RowId and DT_RowData untuk panel system.
     * Required untuk dual-panel layout.
     *
     * @param object $row Database row object
     * @return array Panel integration data
     *
     * @example
     * ```php
     * return array_merge(
     *     $this->format_panel_row_data($row),
     *     [
     *         'name' => $row->name,
     *         'status' => $row->status,
     *     ]
     * );
     * ```
     */
    protected function format_panel_row_data($row): array {
        $entity = $this->get_entity_name();

        return [
            'DT_RowId' => $entity . '-' . $row->id,
            'DT_RowData' => [
                'id' => $row->id,
                'entity' => $entity
            ]
        ];
    }

    /**
     * Sanitize and prepare DataTables request
     *
     * Clean up request parameters untuk security.
     *
     * @param array $request Raw request
     * @return array Sanitized request
     */
    protected function sanitize_request(array $request): array {
        return [
            'draw' => isset($request['draw']) ? intval($request['draw']) : 1,
            'start' => isset($request['start']) ? intval($request['start']) : 0,
            'length' => isset($request['length']) ? intval($request['length']) : 20,
            'search' => isset($request['search']['value']) ? sanitize_text_field($request['search']['value']) : '',
            'order_column' => isset($request['order'][0]['column']) ? intval($request['order'][0]['column']) : 0,
            'order_dir' => isset($request['order'][0]['dir']) && $request['order'][0]['dir'] === 'desc' ? 'DESC' : 'ASC',
        ];
    }

    /**
     * Build ORDER BY clause from request
     *
     * @param array $request Sanitized request
     * @param array $columns Column definitions
     * @return string ORDER BY clause
     */
    protected function build_order_by(array $request, array $columns): string {
        $order_column_index = $request['order_column'] ?? 0;
        $order_dir = $request['order_dir'] ?? 'ASC';

        if (isset($columns[$order_column_index]['data'])) {
            $order_column = $columns[$order_column_index]['data'];
            return "ORDER BY {$order_column} {$order_dir}";
        }

        return 'ORDER BY id ASC';
    }

    /**
     * Build LIMIT clause from request
     *
     * @param array $request Sanitized request
     * @return string LIMIT clause
     */
    protected function build_limit(array $request): string {
        $start = $request['start'] ?? 0;
        $length = $request['length'] ?? 20;

        return "LIMIT {$start}, {$length}";
    }
}
