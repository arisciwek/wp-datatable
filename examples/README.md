# WP DataTable - Examples

Complete reference implementations for wp-datatable framework.

## Files

### `complete-auto-wire-example.php`
**Production-ready complete reference implementation**

Demonstrates all features of wp-datatable framework with complete auto-wire system:

**Features:**
- ✅ Dual panel layout with tabs
- ✅ Auto-wire Edit/Delete buttons (zero JavaScript needed!)
- ✅ Filter-based config injection (`wpdt_localize_data`)
- ✅ Tab structure definition (`wpdt_datatable_tabs`)
- ✅ DataTable inside tab with action buttons per row
- ✅ 3 AJAX handlers (get_form, update, delete)
- ✅ Automatic nonce handling
- ✅ Event-driven architecture

**What Makes This Special:**
Consumer plugins only need to:
1. Add action buttons with specific classes (`wpdt-edit-btn`, `wpdt-delete-btn`)
2. Create 3 AJAX handlers
3. Inject config via filter `wpdt_localize_data`

Framework handles:
- ✅ Modal open/close
- ✅ AJAX requests with nonce
- ✅ DataTable refresh
- ✅ Success/error notifications
- ✅ All UI interactions

**How to Use:**
```php
// 1. Copy file to your plugin
// 2. Rename class WP_DataTable_Test to Your_Entity_Class
// 3. Update entity name 'test_dual' to 'your_entity'
// 4. Implement AJAX handlers with your business logic
// 5. Activate via filter hooks on your admin page
```

**Required Plugins:**
- `wp-datatable` (this framework)
- `wp-modal` (for modal dialogs)

**Live Example:**
When `wp-datatable-test` plugin is active, visit:
- **WP Admin → DataTable → Dual Panel Test**

---

### `DataTableHelpers-Example.php`
DataTable helper functions demonstration.

### `test-abstractdatatable-v2.php`
AbstractDataTable v2 usage example.

### `test-trait-loading.php`
Trait loading mechanism test.

### `QUICK-REFERENCE.md`
**DataTableHelpers Trait API Reference**

Detailed documentation for using DataTableHelpers trait in your DataTable models:
- `generate_action_buttons()` - Create edit/delete buttons with permissions
- `format_status_badge()` - Colored status badges
- `format_panel_row_data()` - Panel integration data
- `esc_output()` - Safe HTML escaping with fallback

→ **[Read Full API Reference](QUICK-REFERENCE.md)**

---

## Architecture Overview

```
Consumer Plugin (wp-customer, wp-agency, etc.)
    ↓
    Filter: wpdt_localize_data (inject auto-wire config)
    Filter: wpdt_datatable_tabs (define tab structure)
    AJAX Handlers: get_form, update, delete
    ↓
WP DataTable Framework
    ↓
    DualPanelAssets (loads modal-integration.js)
    TabSystemTemplate (renders tab navigation)
    PanelLayoutTemplate (renders panel structure)
    ↓
    Auto-wire system handles ALL modal interactions
    ↓
WP Modal (displays forms & confirmations)
```

## Zero JavaScript Promise

Consumer plugins write **ZERO JavaScript** for:
- ✅ Edit button → Modal with form
- ✅ Delete button → Confirmation dialog
- ✅ Form submission via AJAX
- ✅ Success/error messages
- ✅ DataTable refresh after operations

**All handled by framework automatically!**

---

## Support

- **Documentation:** See `QUICK-REFERENCE.md`
- **Issues:** Check wp-datatable plugin code
- **Updates:** Framework handles breaking changes gracefully

---

## Version History

- **1.0.0** - Complete auto-wire example with tabs and DataTable
- **0.1.0** - Initial examples collection
