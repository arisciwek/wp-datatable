# TODO-7107: Action Buttons Helper Methods for DataTable

**Created:** 2025-11-09
**Version:** 1.0.0
**Status:** Ready for Implementation
**Priority:** MEDIUM
**Context:** Standardize action buttons generation across all DataTables
**Depends On:** TODO-7106 (WP DataTable Core Framework)

---

## 🎯 Objective

Add helper methods to wp-datatable framework untuk generate standard action buttons:
- `generate_action_buttons()` - Untuk main DataTable (View + Edit + Delete)
- `generate_nested_action_buttons()` - Untuk nested DataTable di tabs (Edit + Delete only)

Dengan fitur:
- ✅ Support callable permission checks
- ✅ Customizable button classes
- ✅ Consistent dengan wp-datatable dual-panel pattern
- ✅ Dapat di-extend oleh plugin untuk custom logic

---

## 📋 Background

**Current Problem:**
Setiap plugin membuat method `generate_action_buttons()` sendiri dengan:
- Code duplication (View, Edit, Delete button HTML)
- Inconsistent pattern antar plugin
- Permission logic scattered
- Nested DataTable buttons juga duplicate code

**Example Duplication:**
```php
// wp-customer/src/Models/Company/CompanyInvoiceDataTableModel.php
private function generate_action_buttons($row): string {
    $buttons[] = sprintf(
        '<button type="button" class="button button-small wpdt-panel-trigger" data-id="%d" data-entity="company-invoice"...
    );
    // ... duplicate 40 lines
}

// wp-customer/src/Models/Customer/CustomerDataTableModel.php
private function generate_action_buttons($row): string {
    $buttons[] = sprintf(
        '<button type="button" class="button button-small wpdt-panel-trigger" data-id="%d" data-entity="customer"...
    );
    // ... duplicate 40 lines
}
```

**Solution:**
Centralize di wp-datatable framework sebagai reusable helper methods.

---

## 🏗️ Implementation Plan

### 1. Create Helper Class

**File:** `wp-datatable/src/Helpers/ActionButtonHelper.php`

```php
<?php
/**
 * Action Button Helper
 *
 * Generate standard action buttons for DataTable rows following wp-datatable pattern.
 *
 * @package     WPDataTable
 * @subpackage  Helpers
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: wp-datatable/src/Helpers/ActionButtonHelper.php
 *
 * Description: Provides reusable methods to generate View, Edit, Delete buttons
 *              for both main DataTables and nested DataTables inside tabs.
 *              Supports callable permission checks and custom button classes.
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-7107)
 * - Initial implementation
 * - generate_action_buttons() for main DataTable
 * - generate_nested_action_buttons() for nested DataTable
 * - Support callable permission checks
 * - Customizable button classes
 */

namespace WPDataTable\Helpers;

class ActionButtonHelper {

    /**
     * Generate standard action buttons for main DataTable rows
     *
     * Creates View, Edit, and Delete buttons following wp-datatable dual-panel pattern.
     * View button is REQUIRED for panel trigger with wpdt-panel-trigger class.
     *
     * @param object $row Database row object (must have id property)
     * @param array $config Configuration options:
     *   - entity (string) REQUIRED: Entity name for panel (e.g., 'company', 'customer')
     *   - can_view (bool|callable) Permission check for view button (default: true)
     *   - can_edit (bool|callable) Permission check for edit button (default: false)
     *   - can_delete (bool|callable) Permission check for delete button (default: false)
     *   - edit_class (string) Custom class for edit button (default: '{entity}-edit-btn')
     *   - delete_class (string) Custom class for delete button (default: '{entity}-delete-btn')
     *   - view_title (string) Custom title for view button (default: 'View Details')
     *   - edit_title (string) Custom title for edit button (default: 'Edit')
     *   - delete_title (string) Custom title for delete button (default: 'Delete')
     *
     * @return string HTML buttons
     *
     * @example Basic usage:
     * ```php
     * $buttons = ActionButtonHelper::generate([
     *     'entity' => 'company',
     *     'can_edit' => current_user_can('edit_companies'),
     *     'can_delete' => current_user_can('delete_companies')
     * ], $row);
     * ```
     *
     * @example With callable permission check:
     * ```php
     * $buttons = ActionButtonHelper::generate([
     *     'entity' => 'invoice',
     *     'can_edit' => function($row) {
     *         return $row->status === 'pending' && current_user_can('edit_invoices');
     *     },
     *     'can_delete' => function($row) {
     *         return $row->status === 'draft' && current_user_can('delete_invoices');
     *     }
     * ], $row);
     * ```
     *
     * @example Custom button classes:
     * ```php
     * $buttons = ActionButtonHelper::generate([
     *     'entity' => 'order',
     *     'can_edit' => true,
     *     'edit_class' => 'custom-order-edit',
     *     'edit_title' => __('Modify Order', 'my-plugin')
     * ], $row);
     * ```
     */
    public static function generate_action_buttons(array $config, object $row): string {
        $buttons = [];

        // Validate required config
        if (empty($config['entity'])) {
            error_log('[ActionButtonHelper] generate: entity is required');
            return '';
        }

        $entity = $config['entity'];
        $row_id = $row->id ?? 0;

        if (!$row_id) {
            error_log('[ActionButtonHelper] generate: row must have id property');
            return '';
        }

        // Permission checks - support bool or callable
        $can_view = $config['can_view'] ?? true;
        $can_edit = $config['can_edit'] ?? false;
        $can_delete = $config['can_delete'] ?? false;

        // Evaluate callable permissions
        if (is_callable($can_view)) {
            $can_view = call_user_func($can_view, $row);
        }
        if (is_callable($can_edit)) {
            $can_edit = call_user_func($can_edit, $row);
        }
        if (is_callable($can_delete)) {
            $can_delete = call_user_func($can_delete, $row);
        }

        // Custom button configuration
        $edit_class = $config['edit_class'] ?? "{$entity}-edit-btn";
        $delete_class = $config['delete_class'] ?? "{$entity}-delete-btn";
        $view_title = $config['view_title'] ?? __('View Details', 'wp-datatable');
        $edit_title = $config['edit_title'] ?? __('Edit', 'wp-datatable');
        $delete_title = $config['delete_title'] ?? __('Delete', 'wp-datatable');

        // ✅ View button - REQUIRED for wp-datatable panel trigger
        if ($can_view) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small wpdt-panel-trigger" data-id="%d" data-entity="%s" title="%s">
                    <span class="dashicons dashicons-visibility"></span>
                </button>',
                esc_attr($row_id),
                esc_attr($entity),
                esc_attr($view_title)
            );
        }

        // Edit button (optional)
        if ($can_edit) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small %s" data-id="%d" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($edit_class),
                esc_attr($row_id),
                esc_attr($edit_title)
            );
        }

        // Delete button (optional)
        if ($can_delete) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small %s" data-id="%d" title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_attr($delete_class),
                esc_attr($row_id),
                esc_attr($delete_title)
            );
        }

        return implode(' ', $buttons);
    }

    /**
     * Generate action buttons for nested DataTables (inside tabs)
     *
     * Similar to generate_action_buttons() but without View button since nested DataTables
     * typically don't open panels, just show Edit/Delete actions.
     *
     * @param array $config Configuration options (same as generate() except no can_view)
     * @param object $row Database row object
     * @return string HTML buttons
     *
     * @example Nested DataTable in tab:
     * ```php
     * $buttons = ActionButtonHelper::generate_nested([
     *     'entity' => 'employee',
     *     'can_edit' => current_user_can('edit_employees'),
     *     'can_delete' => current_user_can('delete_employees')
     * ], $row);
     * ```
     */
    public static function generate_nested_action_buttons(array $config, object $row): string {
        $buttons = [];

        // Validate required config
        if (empty($config['entity'])) {
            error_log('[ActionButtonHelper] generate_nested: entity is required');
            return '';
        }

        $entity = $config['entity'];
        $row_id = $row->id ?? 0;

        if (!$row_id) {
            error_log('[ActionButtonHelper] generate_nested: row must have id property');
            return '';
        }

        // Permission checks
        $can_edit = $config['can_edit'] ?? false;
        $can_delete = $config['can_delete'] ?? false;

        // Evaluate callable permissions
        if (is_callable($can_edit)) {
            $can_edit = call_user_func($can_edit, $row);
        }
        if (is_callable($can_delete)) {
            $can_delete = call_user_func($can_delete, $row);
        }

        // Custom button configuration
        $edit_class = $config['edit_class'] ?? "{$entity}-edit-btn";
        $delete_class = $config['delete_class'] ?? "{$entity}-delete-btn";
        $edit_title = $config['edit_title'] ?? __('Edit', 'wp-datatable');
        $delete_title = $config['delete_title'] ?? __('Delete', 'wp-datatable');

        // Edit button
        if ($can_edit) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small %s" data-id="%d" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($edit_class),
                esc_attr($row_id),
                esc_attr($edit_title)
            );
        }

        // Delete button
        if ($can_delete) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small %s" data-id="%d" title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_attr($delete_class),
                esc_attr($row_id),
                esc_attr($delete_title)
            );
        }

        return implode(' ', $buttons);
    }
}
```

---

## 📝 Usage Examples

### Example 1: Basic Usage in Plugin DataTableModel

**Before (Duplicate Code):**
```php
// wp-customer/src/Models/Company/CompanyDataTableModel.php
private function generate_action_buttons($row): string {
    $buttons[] = sprintf(
        '<button type="button" class="button button-small wpdt-panel-trigger" data-id="%d" data-entity="company"...
        // 40 lines of duplicate code
    );
}
```

**After (Using Helper):**
```php
use WPDataTable\Helpers\ActionButtonHelper;

protected function format_row($row): array {
    return [
        'id' => $row->id,
        'name' => esc_html($row->name),
        'actions' => ActionButtonHelper::generate([
            'entity' => 'company',
            'can_edit' => current_user_can('edit_companies'),
            'can_delete' => current_user_can('delete_companies')
        ], $row)
    ];
}
```

### Example 2: Callable Permission Check (Invoice)

```php
protected function format_row($row): array {
    return [
        'actions' => ActionButtonHelper::generate([
            'entity' => 'company-invoice',
            'can_edit' => function($row) {
                // Complex logic: status check + permission
                return ($row->status === 'pending' || $row->status === 'pending_payment') &&
                       (current_user_can('manage_options') || current_user_can('edit_invoices'));
            },
            'can_delete' => false  // Use custom cancel instead
        ], $row)
    ];
}
```

### Example 3: Custom Button with Additional Actions

```php
protected function format_row($row): array {
    // Get standard buttons
    $buttons = ActionButtonHelper::generate([
        'entity' => 'invoice',
        'can_edit' => $row->status === 'pending',
        'can_delete' => false
    ], $row);

    // Add custom Cancel button
    if ($row->status === 'pending') {
        $buttons .= sprintf(
            ' <button class="invoice-cancel-btn" data-id="%d">Cancel</button>',
            $row->id
        );
    }

    return ['actions' => $buttons];
}
```

### Example 4: Nested DataTable in Tab

```php
// In staff tab - nested employees DataTable
protected function format_employee_row($row): array {
    return [
        'name' => $row->name,
        'actions' => ActionButtonHelper::generate_nested([
            'entity' => 'employee',
            'can_edit' => current_user_can('edit_employees'),
            'can_delete' => current_user_can('delete_employees'),
            'edit_class' => 'employee-edit-modal-trigger',
            'delete_class' => 'employee-delete-confirm'
        ], $row)
    ];
}
```

---

## 🎯 Benefits

### 1. Code Reusability
- ✅ Single source of truth untuk button HTML
- ✅ Consistency across all plugins
- ✅ Reduce 40+ lines per DataTable to 5 lines

### 2. Maintainability
- ✅ Update button style di satu tempat
- ✅ Add new button types centrally
- ✅ Easy to add accessibility features later

### 3. Flexibility
- ✅ Callable permission checks untuk complex logic
- ✅ Customizable button classes
- ✅ Extensible dengan additional buttons

### 4. Developer Experience
- ✅ Clear API dengan documented examples
- ✅ Type hints dan validation
- ✅ Helpful error messages

---

## 📋 Migration Guide

### For Existing Plugins (wp-customer, wp-agency, etc)

**Step 1:** Add use statement
```php
use WPDataTable\Helpers\ActionButtonHelper;
```

**Step 2:** Replace custom method
```php
// OLD: Remove this method
private function generate_action_buttons($row): string {
    // 40 lines...
}

// NEW: Use helper in format_row()
protected function format_row($row): array {
    return [
        'actions' => ActionButtonHelper::generate([
            'entity' => 'your-entity',
            'can_edit' => current_user_can('edit_capability'),
            'can_delete' => current_user_can('delete_capability')
        ], $row)
    ];
}
```

**Step 3:** Test buttons still work
- View button opens panel
- Edit button triggers custom handler
- Delete button triggers custom handler

---

## ✅ Acceptance Criteria

- [ ] Helper class created at `wp-datatable/src/Helpers/ActionButtonHelper.php`
- [ ] `generate()` method works for main DataTable
- [ ] `generate_nested()` method works for nested DataTable
- [ ] Callable permission checks work correctly
- [ ] Custom button classes work
- [ ] Error handling for missing required config
- [ ] PHPDoc complete dengan examples
- [ ] Unit tests untuk both methods
- [ ] Documentation updated di wp-datatable/docs/
- [ ] Migration guide untuk existing plugins

---

## 🔗 Related

- **Depends On:** TODO-7106 (WP DataTable Core Framework)
- **Related Issues:** Code duplication across wp-customer, wp-agency DataTables
- **Documentation:** wp-datatable/docs/patterns/dual-panel.md
- **Example Usage:** wp-customer CompanyInvoiceDataTableModel

---

## 📌 Notes

- Helper uses static methods untuk easy access
- NO database queries di helper - pure presentation logic
- Permission checks passed as config, not hardcoded
- Buttons follow WordPress admin button classes
- Icons use Dashicons (already available di WordPress admin)

---

**Ready for Implementation** ✅
