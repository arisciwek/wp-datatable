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

HTML Structure (CRITICAL!)
[ ] 14. Right panel: Use wpdt-tab-wrapper (NOT nav-tab-wrapper)
[ ] 15. Tab content: Use wpdt-tab-content (NOT tab-content)
[ ] 16. First tab: Must have "active" class
[ ] 17. Tab IDs: Match data-tab attribute in nav links
[ ] 18. Verify in browser: Check CSS classes in DevTools

Frontend (JavaScript)
[ ] 19. Main DataTable: createdRow callback
[ ] 20. Main DataTable: Register to wpdtPanelManager
[ ] 21. Nested DataTable: Listen wpdt:tab-switched event
[ ] 22. Nested DataTable: Check isDataTable before init

Security & Data
[ ] 23. All AJAX: wpdt_nonce verification
[ ] 24. All AJAX: Capability checks
[ ] 25. All AJAX: Input sanitization
[ ] 26. All AJAX: Response format ['html' => $html] or direct DataTable JSON
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

### Step 4: HTML Structure & Required CSS Classes

**⚠️ CRITICAL:** Tab system requires specific CSS classes to work correctly. Using wrong classes will cause all tabs to display stacked vertically instead of switching.

#### Required CSS Classes

**1. Tab Navigation Wrapper**

```html
<!-- ✅ CORRECT: Use wpdt-tab-wrapper -->
<div class="wpdt-tab-wrapper">
    <a href="#" class="nav-tab nav-tab-active" data-tab="info">Info</a>
    <a href="#" class="nav-tab" data-tab="staff">Staff</a>
    <a href="#" class="nav-tab" data-tab="history">History</a>
</div>

<!-- ❌ WRONG: Do NOT use nav-tab-wrapper -->
<div class="nav-tab-wrapper">
    <!-- This won't work! tab-manager.js looks for .wpdt-tab-wrapper -->
</div>
```

**Why `wpdt-tab-wrapper`?**
- Required by `tab-manager.js` (line 88): `this.tabWrapper = $('.wpdt-tab-wrapper')`
- Without this class, JavaScript cannot find tab navigation
- Result: Tabs won't switch when clicked

---

**2. Tab Content Containers**

```html
<!-- ✅ CORRECT: Use wpdt-tab-content + active on first tab -->
<div id="info" class="wpdt-tab-content active">
    <!-- First tab content - visible by default -->
    <h3>Company Information</h3>
    <table>...</table>
</div>

<div id="staff" class="wpdt-tab-content">
    <!-- Second tab content - hidden by default -->
    <h3>Staff List</h3>
    <table>...</table>
</div>

<div id="history" class="wpdt-tab-content">
    <!-- Third tab content - hidden by default -->
    <h3>History</h3>
    <table>...</table>
</div>

<!-- ❌ WRONG: Do NOT use tab-content -->
<div id="info" class="tab-content">
    <!-- CSS rules won't apply! -->
</div>

<!-- ❌ WRONG: Missing 'active' class on first tab -->
<div id="info" class="wpdt-tab-content">
    <!-- All tabs will be hidden! -->
</div>
```

**Why `wpdt-tab-content`?**
- Required by `tab-manager.js` (line 95): `this.tabContents = $('.wpdt-tab-content')`
- Required by CSS inline styles:
  ```css
  .wpdt-tab-content { display: none !important; }
  .wpdt-tab-content.active { display: block !important; }
  ```
- Without this class:
  - CSS hiding rules won't apply
  - All tabs will display stacked vertically
  - Tab switching won't work

**Why `active` class on first tab?**
- Makes first tab visible on page load
- Without it, ALL tabs will be hidden initially
- Must be added to first tab only

---

#### Complete HTML Example

**File**: `src/Views/admin/company/right-panel.php`

```html
<?php
/**
 * Company Right Panel Template
 *
 * ⚠️ IMPORTANT: Must use wpdt- prefixed classes for tab system to work!
 */

defined('ABSPATH') || exit;
?>

<div class="wpdt-panel-header">
    <h2>Company Details: <span id="company-name"></span></h2>
    <button type="button" class="wpdt-close-panel">×</button>
</div>

<div class="wpdt-panel-content">

    <!-- ✅ Tab Navigation - MUST use wpdt-tab-wrapper -->
    <div class="wpdt-tab-wrapper">
        <a href="#" class="nav-tab nav-tab-active" data-tab="info">
            <?php _e('Company Info', 'your-plugin'); ?>
        </a>
        <a href="#" class="nav-tab" data-tab="staff">
            <?php _e('Staff', 'your-plugin'); ?>
        </a>
        <a href="#" class="nav-tab" data-tab="history">
            <?php _e('History', 'your-plugin'); ?>
        </a>
    </div>

    <!-- ✅ Tab 1: Info - MUST have wpdt-tab-content + active -->
    <div id="info" class="wpdt-tab-content active">
        <h3><?php _e('Company Information', 'your-plugin'); ?></h3>
        <table class="form-table">
            <tr>
                <th><?php _e('Company Name', 'your-plugin'); ?></th>
                <td><span id="company-name-value"></span></td>
            </tr>
            <tr>
                <th><?php _e('Code', 'your-plugin'); ?></th>
                <td><span id="company-code"></span></td>
            </tr>
            <tr>
                <th><?php _e('Email', 'your-plugin'); ?></th>
                <td><span id="company-email"></span></td>
            </tr>
        </table>
    </div>

    <!-- ✅ Tab 2: Staff - MUST have wpdt-tab-content (no active) -->
    <div id="staff" class="wpdt-tab-content">
        <h3><?php _e('Staff Members', 'your-plugin'); ?></h3>

        <!-- Example: DataTable inside tab -->
        <table id="staff-table" class="display" style="width:100%">
            <thead>
                <tr>
                    <th><?php _e('Name', 'your-plugin'); ?></th>
                    <th><?php _e('Position', 'your-plugin'); ?></th>
                    <th><?php _e('Email', 'your-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- DataTable will populate via AJAX -->
            </tbody>
        </table>
    </div>

    <!-- ✅ Tab 3: History - MUST have wpdt-tab-content (no active) -->
    <div id="history" class="wpdt-tab-content">
        <h3><?php _e('Activity History', 'your-plugin'); ?></h3>
        <div class="history-timeline">
            <!-- History content -->
        </div>
    </div>

</div>
```

---

#### Common Mistakes & Fixes

| ❌ Mistake | ✅ Fix | 🔍 Symptom |
|-----------|--------|-----------|
| `<div class="nav-tab-wrapper">` | Use `wpdt-tab-wrapper` | Tabs don't switch when clicked |
| `<div class="tab-content">` | Use `wpdt-tab-content` | All tabs visible, stacked vertically |
| Missing `active` on first tab | Add `active` to first `wpdt-tab-content` | All tabs hidden on page load |
| `active` on multiple tabs | Only first tab should have `active` | Multiple tabs visible at once |
| Wrong `id` attribute | Must match `data-tab` in nav link | Clicking tab has no effect |

---

#### Debugging Tab Issues

**Problem**: All tabs showing stacked vertically

**Check**:
1. Inspect HTML in browser DevTools
2. Find tab wrapper - should be `<div class="wpdt-tab-wrapper">`
3. Find tab contents - should be `<div class="wpdt-tab-content">`
4. Check first tab has `<div class="wpdt-tab-content active">`

**Fix**:
```bash
# Search for wrong classes in your templates
grep -r "nav-tab-wrapper" src/Views/
grep -r 'class="tab-content"' src/Views/

# Should use:
# - wpdt-tab-wrapper (not nav-tab-wrapper)
# - wpdt-tab-content (not tab-content)
```

**Verify JavaScript**:
```javascript
// In browser console
console.log('Tab wrapper found:', jQuery('.wpdt-tab-wrapper').length); // Should be 1
console.log('Tab contents found:', jQuery('.wpdt-tab-content').length); // Should be 3 (or your tab count)
console.log('Active tab found:', jQuery('.wpdt-tab-content.active').length); // Should be 1
```

---

#### Why These Specific Classes?

**Technical Details:**

1. **JavaScript Selectors** (`tab-manager.js`):
   ```javascript
   // Line 88: Looking for tab wrapper
   this.tabWrapper = $('.wpdt-tab-wrapper');

   // Line 95: Looking for tab contents
   this.tabContents = $('.wpdt-tab-content');

   // Line 207: Removing active class
   this.tabContents.removeClass('active');

   // Line 210: Adding active class
   $targetContent.addClass('active');
   ```

2. **CSS Rules** (inline styles in `AgencyDashboardController.php`):
   ```css
   .wpdt-tab-content {
       display: none !important;  /* Hide all tabs by default */
   }
   .wpdt-tab-content.active {
       display: block !important; /* Show only active tab */
   }
   ```

3. **Naming Convention**:
   - `wpdt-` prefix = WP DataTable framework
   - Prevents conflicts with WordPress core classes
   - Consistent with framework patterns

---

### Step 5: View Templates

Continue with view template examples...