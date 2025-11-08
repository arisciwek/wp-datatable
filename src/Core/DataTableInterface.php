<?php
/**
 * DataTable Interface
 *
 * @package     WP_DataTable
 * @subpackage  Core
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Core/DataTableInterface.php
 *
 * Description: Interface yang mendefinisikan kontrak untuk semua DataTable implementations.
 *              Memastikan konsistensi API across different DataTable types.
 *              Plugins yang ingin register datatable harus implement interface ini.
 *
 * Contract Methods:
 * - get_id(): Unique identifier untuk datatable
 * - get_columns(): Column definitions
 * - get_data(): Data untuk datatable
 * - get_config(): Configuration options
 *
 * Usage Example:
 * ```php
 * class MyCustomerDataTable implements DataTableInterface {
 *     public function get_id(): string {
 *         return 'my_customers';
 *     }
 *
 *     public function get_columns(): array {
 *         return [
 *             ['data' => 'id', 'title' => 'ID'],
 *             ['data' => 'name', 'title' => 'Name'],
 *         ];
 *     }
 *
 *     // ... implement other methods
 * }
 * ```
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial development version
 * - Based on wp-app-core AbstractDataTableModel pattern
 * - Adapted for independent framework
 * - Defined core contract methods
 * - Ready for dual-panel and single-panel layouts
 */

namespace WPDataTable\Core;

defined('ABSPATH') || exit;

interface DataTableInterface {
    /**
     * Get unique DataTable identifier
     *
     * This ID is used for:
     * - Registry lookup
     * - CSS/JS targeting
     * - Hook names
     * - Panel integration
     *
     * Must be unique across all datatables.
     * Use plugin prefix to avoid conflicts.
     *
     * @return string Unique ID (lowercase, underscores only)
     *
     * @example 'wp_customers', 'wp_agencies', 'wp_invoices'
     */
    public function get_id(): string;

    /**
     * Get DataTable column definitions
     *
     * Returns array of column configuration compatible with DataTables.js.
     * Each column should define: data, title, and optional render/className.
     *
     * @return array Column definitions
     *
     * @example
     * ```php
     * return [
     *     ['data' => 'id', 'title' => 'ID', 'width' => '80px'],
     *     ['data' => 'name', 'title' => 'Name', 'className' => 'dt-name'],
     *     ['data' => 'status', 'title' => 'Status', 'render' => 'badge'],
     *     ['data' => 'actions', 'title' => 'Actions', 'orderable' => false],
     * ];
     * ```
     */
    public function get_columns(): array;

    /**
     * Get DataTable data based on request
     *
     * Process DataTables server-side request and return formatted response.
     * Must handle: pagination, search, sorting, filtering.
     *
     * @param array $request Request parameters from DataTables
     * @return array DataTables response format
     *
     * @example
     * ```php
     * return [
     *     'draw' => $request['draw'],
     *     'recordsTotal' => 100,
     *     'recordsFiltered' => 50,
     *     'data' => [
     *         ['id' => 1, 'name' => 'John', 'status' => 'active'],
     *         ['id' => 2, 'name' => 'Jane', 'status' => 'active'],
     *     ]
     * ];
     * ```
     */
    public function get_data(array $request): array;

    /**
     * Get DataTable configuration options
     *
     * Returns configuration array with:
     * - layout: 'dual-panel' or 'single-panel'
     * - ajax_action: WordPress AJAX action name
     * - capabilities: Required permissions
     * - options: Additional DataTables.js options
     *
     * @return array Configuration array
     *
     * @example
     * ```php
     * return [
     *     'layout' => 'dual-panel',
     *     'ajax_action' => 'get_customers_datatable',
     *     'capabilities' => ['view_customers'],
     *     'options' => [
     *         'pageLength' => 20,
     *         'ordering' => true,
     *         'searching' => true,
     *     ],
     *     'panel_config' => [
     *         'ajax_action' => 'get_customer_detail',
     *         'template' => 'customer-detail.php',
     *     ]
     * ];
     * ```
     */
    public function get_config(): array;

    /**
     * Get entity name for this DataTable
     *
     * Used for:
     * - Panel integration
     * - Action buttons
     * - Hook names
     *
     * @return string Entity name (singular, lowercase, underscores)
     *
     * @example 'customer', 'agency', 'platform_staff'
     */
    public function get_entity_name(): string;

    /**
     * Check if current user can access this DataTable
     *
     * Implement permission checks based on WordPress capabilities.
     * Return false to deny access.
     *
     * @return bool True if user has access
     *
     * @example
     * ```php
     * public function can_access(): bool {
     *     return current_user_can('view_customers');
     * }
     * ```
     */
    public function can_access(): bool;

    /**
     * Get text domain for translations
     *
     * Used for __() translation functions.
     *
     * @return string Text domain
     *
     * @example 'wp-customer', 'wp-agency', 'my-plugin'
     */
    public function get_text_domain(): string;

    /**
     * Get panel template path (for dual-panel layout)
     *
     * Return absolute path to panel template file.
     * Return empty string if single-panel layout.
     *
     * @return string Template file path or empty string
     *
     * @example
     * ```php
     * return WP_CUSTOMER_PATH . 'templates/panels/customer-detail.php';
     * ```
     */
    public function get_panel_template(): string;
}
