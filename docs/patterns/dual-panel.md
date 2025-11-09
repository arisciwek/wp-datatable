# Dual Panel Pattern

**Version**: 0.1.0
**Last Updated**: 2025-11-09
**Pattern**: Complete implementation guide for Dual Panel layout

---

## 📋 Complete Checklist

Gunakan checklist ini saat membuat Dual Panel DataTable baru:

```
Backend (PHP)
[ ] 1. DataTableModel dengan DT_RowData + entity field
[ ] 2. View button dengan data-entity attribute
[ ] 3. Controller signal_dual_panel() filter
[ ] 4. Controller render() dengan entity config
[ ] 5. Controller register_tabs() untuk tab registration
[ ] 6. Controller render_tabs_content() return ARRAY
[ ] 7. Controller handle_get_details() send tabs ARRAY
[ ] 8. Controller handle_datatable() untuk main DataTable
[ ] 9. Controller handle_load_{tab}_tab() untuk lazy-load
[ ] 10. DataTable view dengan class="wpdt-datatable"
[ ] 11. Tab 1 view: Direct include (NO lazy-load)
[ ] 12. Tab 2+ views: Lazy-load dengan wpdt-tab-autoload
[ ] 13. Tab partial views: No outer wrapper div

Frontend (JavaScript)
[ ] 14. Main DataTable: createdRow callback
[ ] 15. Main DataTable: Register to wpdtPanelManager
[ ] 16. Nested DataTable: Listen wpdt:tab-switched event
[ ] 17. Nested DataTable: Check isDataTable before init

Security & Data
[ ] 18. All AJAX: wpdt_nonce verification
[ ] 19. All AJAX: Capability checks
[ ] 20. All AJAX: Input sanitization
[ ] 21. All AJAX: Response format ['html' => $html] or direct DataTable JSON
```

---

## 🏗️ Implementation Steps

### Step 1: Create DataTable Model

**File**: `src/Models/{Entity}/{Entity}DataTableModel.php`

```php
<?php
/**
 * Company DataTable Model
 *
 * @package     YourPlugin
 * @subpackage  Models/Company
 * @version     1.0.0
 */

namespace YourPlugin\Models\Company;

use WPAppCore\Models\DataTable\DataTableModel;

class CompanyDataTableModel extends DataTableModel {

    /**
     * @var string Database table name
     */
    protected $table = 'wp_app_customer_branches';

    /**
     * @var string Table alias for queries
     */
    protected $table_alias = 'comp';

    /**
     * Get columns configuration
     *
     * @return array Column definitions
     */
    protected function get_columns(): array {
        return [
            [
                'data' => 'code',
                'name' => 'code',
                'title' => __('Code', 'your-plugin'),
                'searchable' => true,
                'orderable' => true
            ],
            [
                'data' => 'name',
                'name' => 'name',
                'title' => __('Company Name', 'your-plugin'),
                'searchable' => true,
                'orderable' => true
            ],
            // ... other columns
            [
                'data' => 'actions',
                'name' => 'actions',
                'title' => __('Actions', 'your-plugin'),
                'searchable' => false,
                'orderable' => false
            ]
        ];
    }

    /**
     * Format row data for DataTable
     *
     * ✅ CRITICAL: Must include DT_RowData with entity field
     *
     * @param object $row Database row
     * @return array Formatted row
     */
    protected function format_row($row): array {
        return [
            // ✅ REQUIRED: Row ID for DataTable
            'DT_RowId' => 'company-' . ($row->id ?? 0),

            // ✅ REQUIRED: Row metadata for panel interaction
            'DT_RowData' => [
                'id' => $row->id ?? 0,
                'entity' => 'company',  // ← MUST match entity config!
                'customer_id' => $row->customer_id ?? 0,
                'status' => $row->status ?? 'active',
                // Add other metadata needed by JavaScript
            ],

            // Visible columns
            'code' => esc_html($row->code ?? ''),
            'name' => esc_html($row->name ?? ''),
            'type' => $this->format_type($row->type),
            'email' => esc_html($row->email ?? '-'),
            'phone' => esc_html($row->phone ?? '-'),
            'status' => $this->format_status_badge($row->status),
            'actions' => $this->generate_action_buttons($row)
        ];
    }

    /**
     * Generate action buttons for row
     *
     * ✅ CRITICAL: View button must have data-entity attribute
     *
     * @param object $row Database row
     * @return string HTML buttons
     */
    private function generate_action_buttons($row): string {
        $buttons = [];

        // ✅ View button - REQUIRED for panel trigger
        $buttons[] = sprintf(
            '<button type="button"
                     class="button button-small wpdt-panel-trigger"
                     data-id="%d"
                     data-entity="company"
                     title="%s">
                <span class="dashicons dashicons-visibility"></span>
            </button>',
            esc_attr($row->id),
            esc_attr__('View Details', 'your-plugin')
        );

        // Edit button (optional)
        if (current_user_can('edit_companies')) {
            $buttons[] = sprintf(
                '<button type="button"
                         class="button button-small company-edit-btn"
                         data-id="%d"
                         title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Edit Company', 'your-plugin')
            );
        }

        // Delete button (optional)
        if (current_user_can('delete_companies')) {
            $buttons[] = sprintf(
                '<button type="button"
                         class="button button-small company-delete-btn"
                         data-id="%d"
                         title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Delete Company', 'your-plugin')
            );
        }

        return implode(' ', $buttons);
    }

    /**
     * Format status badge
     */
    private function format_status_badge($status): string {
        $class = $status === 'active' ? 'status-active' : 'status-inactive';
        $text = $status === 'active'
            ? __('Active', 'your-plugin')
            : __('Inactive', 'your-plugin');

        return sprintf(
            '<span class="status-badge %s">%s</span>',
            esc_attr($class),
            esc_html($text)
        );
    }
}
```

**Key Points**:
- ✅ `DT_RowData` MUST include `'entity' => 'company'`
- ✅ View button MUST have `data-entity="company"`
- ✅ Entity name must be LOWERCASE and CONSISTENT everywhere

---

### Step 2: Create Dashboard Controller

**File**: `src/Controllers/Company/CompanyDashboardController.php`

```php
<?php
/**
 * Company Dashboard Controller
 *
 * @package     YourPlugin
 * @subpackage  Controllers/Company
 * @version     1.0.0
 */

namespace YourPlugin\Controllers\Company;

use WPDataTable\Templates\DualPanel\DashboardTemplate;
use YourPlugin\Models\Company\CompanyDataTableModel;
use YourPlugin\Models\Company\CompanyModel;

class CompanyDashboardController {

    /**
     * @var CompanyModel
     */
    private $model;

    /**
     * @var CompanyDataTableModel
     */
    private $datatable_model;

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new CompanyModel();
        $this->datatable_model = new CompanyDataTableModel();

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // ✅ REQUIRED: Signal to use dual panel
        add_filter('wpdt_use_dual_panel', [$this, 'signal_dual_panel'], 10, 1);

        // ✅ REQUIRED: Register tabs
        add_filter('wpdt_datatable_tabs', [$this, 'register_tabs'], 10, 2);

        // ✅ REQUIRED: Register content hooks
        add_action('wpdt_left_panel_content', [$this, 'render_datatable'], 10, 1);
        add_action('wpdt_statistics_content', [$this, 'render_statistics'], 10, 1);

        // ✅ REQUIRED: AJAX handlers
        add_action('wp_ajax_get_company_datatable', [$this, 'handle_datatable']);
        add_action('wp_ajax_get_company_details', [$this, 'handle_get_details']);
        add_action('wp_ajax_get_company_stats', [$this, 'handle_get_stats']);

        // ✅ Tab lazy-load handlers
        add_action('wp_ajax_load_company_info_tab', [$this, 'handle_load_info_tab']);
        add_action('wp_ajax_load_company_staff_tab', [$this, 'handle_load_staff_tab']);

        // Optional: Nested DataTable
        add_action('wp_ajax_get_company_employees_datatable', [$this, 'handle_employees_datatable']);
    }

    /**
     * ✅ REQUIRED: Signal wp-datatable to use dual panel layout
     *
     * @param bool $use Current value
     * @return bool Whether to use dual panel
     */
    public function signal_dual_panel($use): bool {
        // Check if this is our page
        if (isset($_GET['page']) && $_GET['page'] === 'companies') {
            return true;
        }
        return $use;
    }

    /**
     * ✅ REQUIRED: Render dashboard page
     *
     * Called by menu callback
     */
    public function render(): void {
        // Permission check
        if (!current_user_can('view_companies')) {
            wp_die(__('You do not have permission to access this page.', 'your-plugin'));
        }

        // ✅ Render dual panel dashboard
        DashboardTemplate::render([
            'entity' => 'company',  // ← MUST match Model entity field
            'title' => __('Companies', 'your-plugin'),
            'description' => __('Manage your companies', 'your-plugin'),
            'has_stats' => true,
            'has_tabs' => true,
            'has_filters' => false,
            'ajax_action' => 'get_company_details',  // AJAX for detail panel
        ]);
    }

    /**
     * ✅ REQUIRED: Register tabs for company dashboard
     *
     * @param array $tabs Current tabs
     * @param string $entity Entity name
     * @return array Tabs configuration
     */
    public function register_tabs($tabs, $entity): array {
        if ($entity !== 'company') {
            return $tabs;
        }

        return [
            'info' => [
                'title' => __('Company Information', 'your-plugin'),
                'template' => PLUGIN_PATH . 'src/Views/admin/company/tabs/info.php',
                'priority' => 10
            ],
            'staff' => [
                'title' => __('Staff', 'your-plugin'),
                'template' => PLUGIN_PATH . 'src/Views/admin/company/tabs/staff.php',
                'priority' => 20
            ]
        ];
    }

    /**
     * ✅ REQUIRED: Render DataTable in left panel
     *
     * @param array $config Panel configuration
     */
    public function render_datatable($config): void {
        if ($config['entity'] !== 'company') {
            return;
        }

        $view_file = PLUGIN_PATH . 'src/Views/admin/company/datatable/datatable.php';

        if (!file_exists($view_file)) {
            echo '<p>' . __('DataTable view not found', 'your-plugin') . '</p>';
            return;
        }

        include $view_file;
    }

    /**
     * Render statistics cards
     *
     * @param array $config Panel configuration
     */
    public function render_statistics($config): void {
        if ($config['entity'] !== 'company') {
            return;
        }

        $view_file = PLUGIN_PATH . 'src/Views/admin/company/statistics/statistics.php';

        if (!file_exists($view_file)) {
            return;
        }

        include $view_file;
    }

    /**
     * ✅ CRITICAL: Render tabs content - MUST RETURN ARRAY!
     *
     * @param object $company Company data
     * @return array Array of tab_id => html_content
     */
    private function render_tabs_content($company): array {
        $tabs_content = [];
        $registered_tabs = $this->register_tabs([], 'company');

        foreach ($registered_tabs as $tab_id => $tab) {
            if (!isset($tab['template']) || !file_exists($tab['template'])) {
                continue;
            }

            // Render tab template
            ob_start();
            $data = $company; // ← Make $data available to template
            include $tab['template'];
            $content = ob_get_clean();

            $tabs_content[$tab_id] = $content;
        }

        return $tabs_content; // ← MUST return array, NOT string!
    }

    // ... AJAX handlers (see next section)
}
```

---

### Step 3: AJAX Handlers

Continue in same controller file:

```php
    /**
     * ✅ REQUIRED: Handle DataTable AJAX request
     */
    public function handle_datatable(): void {
        // Security check
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'your-plugin')]);
            return;
        }

        // Permission check
        if (!current_user_can('view_companies')) {
            wp_send_json_error(['message' => __('Permission denied', 'your-plugin')]);
            return;
        }

        try {
            // Get data from model
            $response = $this->datatable_model->get_datatable_data($_POST);
            wp_send_json($response);

        } catch (\Exception $e) {
            error_log('[CompanyDashboard] Error in handle_datatable: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error loading companies', 'your-plugin')]);
        }
    }

    /**
     * ✅ REQUIRED: Handle get company details for detail panel
     */
    public function handle_get_details(): void {
        // Security check
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'your-plugin')]);
            return;
        }

        // Permission check
        if (!current_user_can('view_companies')) {
            wp_send_json_error(['message' => __('Permission denied', 'your-plugin')]);
            return;
        }

        // Get company ID
        $company_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$company_id) {
            wp_send_json_error(['message' => __('Company ID required', 'your-plugin')]);
            return;
        }

        try {
            // Get company data
            $company = $this->model->find($company_id);

            if (!$company) {
                wp_send_json_error(['message' => __('Company not found', 'your-plugin')]);
                return;
            }

            // ✅ Render tabs content as ARRAY
            $tabs = $this->render_tabs_content($company);

            // ✅ Send response with tabs ARRAY
            wp_send_json_success([
                'title' => esc_html($company->name),
                'tabs' => $tabs  // ← MUST be array, not string!
            ]);

        } catch (\Exception $e) {
            error_log('[CompanyDashboard] Error in handle_get_details: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error loading company details', 'your-plugin')]);
        }
    }

    /**
     * Handle get statistics
     */
    public function handle_get_stats(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'your-plugin')]);
            return;
        }

        if (!current_user_can('view_companies')) {
            wp_send_json_error(['message' => __('Permission denied', 'your-plugin')]);
            return;
        }

        try {
            global $wpdb;
            $table = $wpdb->prefix . 'app_customer_branches';

            $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
            $active = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active'");
            $inactive = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'inactive'");

            wp_send_json_success([
                'total' => (int) $total,
                'active' => (int) $active,
                'inactive' => (int) $inactive
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading statistics', 'your-plugin')]);
        }
    }

    /**
     * ✅ Handle lazy load info tab content
     */
    public function handle_load_info_tab(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'your-plugin')]);
            return;
        }

        if (!current_user_can('view_companies')) {
            wp_send_json_error(['message' => __('Permission denied', 'your-plugin')]);
            return;
        }

        // ✅ Get company_id (matches data-company-id attribute)
        $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;

        if (!$company_id) {
            wp_send_json_error(['message' => __('Company ID required', 'your-plugin')]);
            return;
        }

        try {
            $company = $this->model->find($company_id);

            if (!$company) {
                wp_send_json_error(['message' => __('Company not found', 'your-plugin')]);
                return;
            }

            ob_start();
            include PLUGIN_PATH . 'src/Views/admin/company/tabs/partials/info-content.php';
            $html = ob_get_clean();

            // ✅ Response format for tab-manager
            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading info tab', 'your-plugin')]);
        }
    }

    /**
     * ✅ Handle lazy load staff tab content
     */
    public function handle_load_staff_tab(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'your-plugin')]);
            return;
        }

        if (!current_user_can('view_companies')) {
            wp_send_json_error(['message' => __('Permission denied', 'your-plugin')]);
            return;
        }

        $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;

        if (!$company_id) {
            wp_send_json_error(['message' => __('Company ID required', 'your-plugin')]);
            return;
        }

        try {
            $company = $this->model->find($company_id);

            if (!$company) {
                wp_send_json_error(['message' => __('Company not found', 'your-plugin')]);
                return;
            }

            ob_start();
            include PLUGIN_PATH . 'src/Views/admin/company/tabs/partials/staff-content.php';
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading staff tab', 'your-plugin')]);
        }
    }
```

---

Dokumentasi masih berlanjut... Saya sudah membuat 4 file pertama. Apakah saya lanjutkan dengan file-file berikutnya (View templates, JavaScript patterns, dll)? Atau Anda ingin review dulu yang sudah dibuat?