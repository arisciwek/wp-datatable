<?php
/**
 * Abstract DataTable Class - Version 2.0
 *
 * @package     WP_DataTable
 * @subpackage  Core
 * @version     0.2.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Core/AbstractDataTable.php
 *
 * Description: Complete DataTable base class dengan server-side processing.
 *              Merged dari wp-app-core's DataTableModel + UI helpers.
 *              Uses WordPress native $wpdb pattern (NOT QueryBuilder).
 *
 * Features:
 * - ✅ Server-side processing dengan $wpdb
 * - ✅ Hook system untuk extensibility
 * - ✅ UI helpers (buttons, badges, panel data)
 * - ✅ Search, filter, sort, pagination
 * - ✅ Role-based hook support
 * - ✅ Optional wp-qb integration
 *
 * Usage Example:
 * ```php
 * class DivisionDataTableModel extends AbstractDataTable {
 *     public function __construct() {
 *         parent::__construct();
 *
 *         global $wpdb;
 *         $this->table = $wpdb->prefix . 'app_agency_divisions d';
 *         $this->index_column = 'd.id';
 *         $this->searchable_columns = ['d.code', 'd.name'];
 *         $this->base_joins = [
 *             "LEFT JOIN {$wpdb->prefix}wi_regencies r ON d.regency_id = r.id"
 *         ];
 *     }
 *
 *     protected function get_select_columns(): array {
 *         return ['d.id', 'd.code', 'd.name', 'r.name as regency_name'];
 *     }
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
 *                     'edit_capability' => 'edit_divisions',
 *                     'delete_capability' => 'delete_divisions'
 *                 ])
 *             ]
 *         );
 *     }
 * }
 * ```
 *
 * Changelog:
 * 0.2.0 - 2025-12-28
 * - MAJOR: Merged wp-app-core's DataTableModel logic
 * - Added server-side processing dengan WordPress native $wpdb
 * - Added hook system (wpapp_datatable_{entity}_{type})
 * - Added role-based hooks
 * - Included UI helpers dari DataTableHelpers trait
 * - Removed abstract query methods (now handled internally)
 * - WordPress native pattern (no QueryBuilder dependency)
 *
 * 0.1.0 - 2025-11-08
 * - Initial version with UI helpers only
 */

namespace WPDataTable\Core;

use WPDataTable\Traits\DataTableHelpers;

defined('ABSPATH') || exit;

abstract class AbstractDataTable implements DataTableInterface {

    // Include UI helpers (buttons, badges, etc.)
    use DataTableHelpers;

    // ========================================
    // PROTECTED PROPERTIES
    // ========================================

    /**
     * Database table name (with prefix and optional alias)
     * Child classes MUST set this in constructor
     *
     * @var string
     * @example "wp_customers c" or "wp_app_divisions d"
     */
    protected $table;

    /**
     * Columns to select from database
     * Override get_select_columns() method for dynamic logic
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Searchable columns (for DataTables global search)
     * Child classes SHOULD set this for search functionality
     *
     * @var array
     * @example ['c.name', 'c.email', 'c.phone']
     */
    protected $searchable_columns = [];

    /**
     * Primary key column name (with table alias if needed)
     *
     * @var string
     * @example 'id' or 'c.id'
     */
    protected $index_column = 'id';

    /**
     * Base WHERE conditions that always apply
     * Can be set by child classes for default filtering
     *
     * @var array
     * @example ["status = 'active'", "deleted_at IS NULL"]
     */
    protected $base_where = [];

    /**
     * Base JOIN clauses that always apply
     * Can be set by child classes for default joins
     *
     * @var array
     * @example ["LEFT JOIN wp_users u ON c.user_id = u.ID"]
     */
    protected $base_joins = [];

    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    protected $wpdb;

    // ========================================
    // CONSTRUCTOR
    // ========================================

    /**
     * Constructor
     * Sets up WordPress database instance
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    // ========================================
    // ABSTRACT METHODS (Must be implemented by child classes)
    // ========================================

    /**
     * Format database row untuk output
     *
     * Transform raw database row menjadi format yang sesuai untuk DataTables.
     * Apply escaping, formatting, badges, buttons, etc.
     *
     * Use UI helper methods:
     * - $this->format_panel_row_data($row, 'entity') for DT_RowId/DT_RowData
     * - $this->format_status_badge($row->status) for status badges
     * - $this->generate_action_buttons($row, [...]) for action buttons
     *
     * @param object $row Database row object
     * @return array Formatted row data (associative array)
     */
    abstract protected function format_row($row): array;

    // ========================================
    // OVERRIDABLE METHODS (Can be overridden by child classes)
    // ========================================

    /**
     * Get columns to select in SQL query (internal method)
     *
     * Override this method for dynamic column logic.
     * Default returns $this->columns property.
     *
     * @return array Column names/expressions for SQL SELECT
     */
    protected function get_select_columns(): array {
        return $this->columns;
    }

    /**
     * Get WHERE conditions for filtering
     *
     * Override this for custom WHERE logic.
     * Will be merged with base_where and filtered via hooks.
     *
     * @param array $request_data DataTables request data
     * @return array WHERE conditions
     */
    protected function get_where_conditions(array $request_data): array {
        return $this->base_where;
    }

    /**
     * Get JOIN clauses
     *
     * Override this for dynamic JOIN logic.
     * Default returns $this->base_joins property.
     *
     * @param array $request_data DataTables request data
     * @return array JOIN clauses
     */
    protected function get_joins(array $request_data): array {
        return $this->base_joins;
    }

    // ========================================
    // MAIN DATATABLE METHOD
    // ========================================

    /**
     * Main method for server-side DataTables processing
     *
     * This is the core method that handles all DataTables requests.
     * It applies filters at multiple points to allow module extensions.
     *
     * Filter hooks applied:
     * - wpapp_datatable_{table}_columns: Modify columns
     * - wpapp_datatable_{table}_where: Add WHERE conditions
     * - wpapp_datatable_{table}_joins: Add JOIN clauses
     * - wpapp_datatable_{table}_row_data: Modify row output
     * - wpapp_datatable_{table}_response: Modify final response
     *
     * @param array $request_data DataTables request parameters from $_POST
     * @return array DataTables response format
     */
    public function get_datatable_data($request_data) {

        // 1. Get columns for SQL SELECT (allow modules to modify)
        $columns = $this->get_select_columns();

        /**
         * Filter columns to select in SQL query
         *
         * @param array $columns Current columns
         * @param AbstractDataTable $this Model instance
         * @param array $request_data DataTables request
         */
        $columns = apply_filters(
            $this->get_filter_hook('columns'),
            $columns,
            $this,
            $request_data
        );

        // 2. Build WHERE conditions
        $where_conditions = $this->get_where_conditions($request_data);

        /**
         * Filter WHERE conditions
         *
         * Modules can add WHERE conditions to filter records
         *
         * @param array $where_conditions Current WHERE conditions
         * @param array $request_data DataTables request
         * @param AbstractDataTable $this Model instance
         */
        $where_conditions = apply_filters(
            $this->get_filter_hook('where'),
            $where_conditions,
            $request_data,
            $this
        );

        // 3. Build JOINs
        $joins = $this->get_joins($request_data);

        /**
         * Filter JOIN clauses
         *
         * Modules can add JOINs to include related tables
         *
         * @param array $joins Current JOIN clauses
         * @param array $request_data DataTables request
         * @param AbstractDataTable $this Model instance
         */
        $joins = apply_filters(
            $this->get_filter_hook('joins'),
            $joins,
            $request_data,
            $this
        );

        // 4. Build search WHERE clause
        $search_where = $this->build_search_where($request_data);

        // 5. Build ORDER BY clause
        $order_by = $this->build_order_by($request_data, $columns);

        // 6. Build pagination LIMIT clause
        $limit = $this->build_limit($request_data);

        // 7. Build complete query
        $select_clause = 'SELECT ' . implode(', ', $columns);
        $from_clause = 'FROM ' . $this->table;
        $join_clause = !empty($joins) ? implode(' ', $joins) : '';

        // Combine WHERE conditions
        $all_where = array_filter(array_merge($where_conditions, $search_where ? [$search_where] : []));
        $where_clause = !empty($all_where) ? 'WHERE ' . implode(' AND ', $all_where) : '';

        // Main query
        $main_query = trim("{$select_clause} {$from_clause} {$join_clause} {$where_clause} {$order_by} {$limit}");

        // 8. Execute query and get results
        $results = $this->wpdb->get_results($main_query);

        if ($this->wpdb->last_error) {
            error_log('[AbstractDataTable] Query Error: ' . $this->wpdb->last_error);
            error_log('[AbstractDataTable] Query: ' . $main_query);
        }

        // 9. Count totals for pagination
        $total_records = $this->count_total($where_conditions, $joins);
        $filtered_records = $this->count_filtered($all_where, $joins);

        // 10. Format each row
        $output_data = [];
        foreach ($results as $row) {
            $formatted_row = $this->format_row($row);

            /**
             * Filter formatted row data
             *
             * Modules can modify row output (add buttons, change display, etc)
             *
             * @param array $formatted_row Formatted row data
             * @param object $row Raw database row
             * @param AbstractDataTable $this Model instance
             */
            $formatted_row = apply_filters(
                $this->get_filter_hook('row_data'),
                $formatted_row,
                $row,
                $this
            );

            $output_data[] = $formatted_row;
        }

        // 11. Build DataTables response
        $response = [
            'draw' => isset($request_data['draw']) ? intval($request_data['draw']) : 0,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $filtered_records,
            'data' => $output_data
        ];

        /**
         * Filter final response before sending to client
         *
         * @param array $response DataTables response
         * @param AbstractDataTable $this Model instance
         */
        $response = apply_filters(
            $this->get_filter_hook('response'),
            $response,
            $this
        );

        return $response;
    }

    // ========================================
    // QUERY BUILDING HELPERS
    // ========================================

    /**
     * Build search WHERE clause from DataTables request
     *
     * @param array $request_data DataTables request data
     * @return string|null Search WHERE clause or null
     */
    protected function build_search_where($request_data) {
        if (empty($request_data['search']['value']) || empty($this->searchable_columns)) {
            return null;
        }

        $search_value = '%' . $this->wpdb->esc_like($request_data['search']['value']) . '%';
        $search_parts = [];

        foreach ($this->searchable_columns as $column) {
            $search_parts[] = $this->wpdb->prepare("{$column} LIKE %s", $search_value);
        }

        return '(' . implode(' OR ', $search_parts) . ')';
    }

    /**
     * Build ORDER BY clause
     *
     * @param array $request_data DataTables request data
     * @param array $columns Available columns
     * @return string ORDER BY clause
     */
    protected function build_order_by($request_data, $columns) {
        if (isset($request_data['order'][0])) {
            $order_column_index = intval($request_data['order'][0]['column']);
            $order_dir = sanitize_text_field($request_data['order'][0]['dir']);
            $order_dir = strtoupper($order_dir) === 'DESC' ? 'DESC' : 'ASC';

            if (isset($columns[$order_column_index])) {
                $order_column = $columns[$order_column_index];

                // Extract alias if exists (e.g., "c.name as name" -> "name")
                if (stripos($order_column, ' as ') !== false) {
                    $parts = preg_split('/\s+as\s+/i', $order_column);
                    $order_column = trim($parts[1]);
                }

                return "ORDER BY {$order_column} {$order_dir}";
            }
        }

        // Default ordering
        return "ORDER BY {$this->index_column} DESC";
    }

    /**
     * Build LIMIT clause for pagination
     *
     * @param array $request_data DataTables request data
     * @return string LIMIT clause
     */
    protected function build_limit($request_data) {
        $start = isset($request_data['start']) ? intval($request_data['start']) : 0;
        $length = isset($request_data['length']) ? intval($request_data['length']) : 10;

        // Limit max records per page to prevent performance issues
        $length = min($length, 100);

        return $this->wpdb->prepare("LIMIT %d, %d", $start, $length);
    }

    /**
     * Count total records (without filters or search)
     *
     * @param array $where_conditions Base WHERE conditions
     * @param array $joins JOIN clauses
     * @return int Total record count
     */
    protected function count_total($where_conditions, $joins) {
        $join_clause = !empty($joins) ? implode(' ', $joins) : '';
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        $count_query = "SELECT COUNT({$this->index_column}) as total
                        FROM {$this->table}
                        {$join_clause}
                        {$where_clause}";

        return intval($this->wpdb->get_var($count_query));
    }

    /**
     * Count filtered records (with search applied)
     *
     * @param array $all_where All WHERE conditions (including search)
     * @param array $joins JOIN clauses
     * @return int Filtered record count
     */
    protected function count_filtered($all_where, $joins) {
        $join_clause = !empty($joins) ? implode(' ', $joins) : '';
        $where_clause = !empty($all_where) ? 'WHERE ' . implode(' AND ', $all_where) : '';

        $count_query = "SELECT COUNT({$this->index_column}) as total
                        FROM {$this->table}
                        {$join_clause}
                        {$where_clause}";

        return intval($this->wpdb->get_var($count_query));
    }

    // ========================================
    // HOOK SYSTEM
    // ========================================

    /**
     * Generate filter hook name for this table
     *
     * Role-based hook system with fallback to entity-based hooks.
     *
     * Format: wpapp_datatable_{type}_{role} (if user has role)
     * Fallback: wpapp_datatable_{table}_{type} (entity-based, backward compatible)
     *
     * @param string $type Hook type (columns, where, joins, row_data, etc.)
     * @return string Full hook name
     */
    protected function get_filter_hook($type) {
        // Safety check: ensure WordPress is loaded
        if (function_exists('wp_get_current_user')) {
            $user = wp_get_current_user();

            // Priority order for role-based hooks (highest first)
            $priority_roles = [
                'customer_admin',
                'customer_branch_admin',
                'customer_employee',
                'agency_admin_dinas',
                'agency_admin_unit',
                'agency_kepala_dinas',
                'agency_kepala_bidang',
                'agency_kepala_seksi',
                'agency_kepala_unit',
                'agency_pengawas_spesialis',
                'agency_pengawas',
                'agency_employee'
            ];

            // Check if user has any priority role
            if (!empty($user->roles)) {
                foreach ($priority_roles as $role) {
                    if (in_array($role, $user->roles)) {
                        // Return role-based hook
                        return "wpapp_datatable_{$type}_{$role}";
                    }
                }
            }
        }

        // Fallback to entity-based hook for backward compatibility
        // Remove prefix from table name for cleaner hook names
        $table_name = str_replace($this->wpdb->prefix, '', $this->table);

        // Remove 'app_' prefix if present
        $table_name = str_replace('app_', '', $table_name);

        // Remove alias if present (e.g., "agencies a" → "agencies")
        $table_name = preg_replace('/\s+.*$/', '', $table_name);

        return "wpapp_datatable_{$table_name}_{$type}";
    }

    // ========================================
    // INTERFACE IMPLEMENTATION
    // ========================================

    /**
     * Get DataTable data based on request
     *
     * Alias for get_datatable_data() for interface compliance
     *
     * @param array $request Request parameters
     * @return array DataTables response
     */
    public function get_data(array $request): array {
        return $this->get_datatable_data($request);
    }

    /**
     * Get unique DataTable identifier
     *
     * Default implementation uses table name.
     * Override for custom ID.
     *
     * @return string Unique ID
     */
    public function get_id(): string {
        $table_name = str_replace($this->wpdb->prefix, '', $this->table);
        $table_name = str_replace('app_', '', $table_name);
        $table_name = preg_replace('/\s+.*$/', '', $table_name);
        return $table_name;
    }

    /**
     * Get DataTable column definitions
     *
     * Returns column configuration for DataTables.js
     * Override untuk custom column config.
     *
     * @return array Column definitions
     */
    public function get_columns(): array {
        return [];
    }

    /**
     * Get DataTable configuration options
     *
     * Override untuk custom config
     *
     * @return array Configuration array
     */
    public function get_config(): array {
        return [
            'layout' => 'dual-panel',
            'ajax_action' => '',
            'capabilities' => [],
            'options' => [],
            'action_buttons' => $this->get_action_button_config()
        ];
    }

    /**
     * Get action button configuration for modal auto-wire
     *
     * Override this method to enable auto-wired edit/delete buttons.
     * When configured, buttons will automatically integrate with wp-modal
     * without requiring JavaScript in consumer plugins.
     *
     * @return array Action button configuration
     *
     * @example
     * return [
     *     'edit' => [
     *         'enabled' => true,
     *         'capability' => 'edit_customers',
     *         'modal_title' => __('Edit Customer', 'my-plugin'),
     *         'ajax_action' => 'get_customer_form',
     *         'submit_action' => 'update_customer',
     *         'modal_size' => 'medium',
     *         'nonce_action' => 'customer_nonce',
     *     ],
     *     'delete' => [
     *         'enabled' => true,
     *         'capability' => 'delete_customers',
     *         'ajax_action' => 'delete_customer',
     *         'confirm_message' => __('Are you sure?', 'my-plugin'),
     *         'confirm_title' => __('Confirm Delete', 'my-plugin'),
     *         'confirm_label' => __('Delete', 'my-plugin'),
     *         'nonce_action' => 'customer_nonce',
     *     ]
     * ];
     */
    protected function get_action_button_config(): array {
        return [];
    }

    /**
     * Get entity name for this DataTable
     *
     * Default implementation uses table name.
     * Override for custom entity name.
     *
     * @return string Entity name
     */
    public function get_entity_name(): string {
        return $this->get_id();
    }

    /**
     * Check if current user can access this DataTable
     *
     * Default implementation allows all.
     * Override untuk custom permission logic.
     *
     * @return bool True if user has access
     */
    public function can_access(): bool {
        return true;
    }

    /**
     * Get text domain for translations
     *
     * Default: 'wp-datatable'
     * Override untuk custom text domain
     *
     * @return string Text domain
     */
    public function get_text_domain(): string {
        return 'wp-datatable';
    }

    /**
     * Get panel template path (for dual-panel layout)
     *
     * Default: empty string (single-panel)
     * Override untuk dual-panel layout
     *
     * @return string Template file path or empty string
     */
    public function get_panel_template(): string {
        return '';
    }

    // ========================================
    // PUBLIC GETTERS
    // ========================================

    /**
     * Get table name with prefix
     *
     * @return string Table name
     */
    public function get_table() {
        return $this->table;
    }

    /**
     * Get searchable columns
     *
     * @return array Searchable column names
     */
    public function get_searchable_columns() {
        return $this->searchable_columns;
    }

    /**
     * Get index column
     *
     * @return string Index column name
     */
    public function get_index_column() {
        return $this->index_column;
    }
}
