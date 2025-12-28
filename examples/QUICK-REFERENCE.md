# DataTableHelpers Trait - Quick Reference

## 🎯 Purpose

Provide UI helper methods for DataTable models that extend from **wp-app-core's DataTableModel**.

**Problem:** wp-app-core provides query power, but no UI helpers
**Solution:** Use wp-datatable's `DataTableHelpers` trait for composition

---

## 📦 Installation

```php
use WPAppCore\Models\DataTable\DataTableModel;
use WPDataTable\Traits\DataTableHelpers;

class YourDataTableModel extends DataTableModel {
    use DataTableHelpers;  // ← Add this line
}
```

---

## 🔧 Methods Available

### 1. `generate_action_buttons($row, $options)`

Generate edit/delete buttons with permission checks.

**Parameters:**
- `$row` (object) - Database row (must have `id` property)
- `$options` (array):
  - `entity` (required) - Entity name for button class
  - `edit_capability` (optional) - WP capability for edit
  - `delete_capability` (optional) - WP capability for delete
  - `text_domain` (optional) - i18n text domain
  - `show_view` (optional) - Show view button (dual-panel)
  - `custom_buttons` (optional) - Array of custom HTML

**Returns:** HTML string with action buttons

**Example:**
```php
$actions = $this->generate_action_buttons($row, [
    'entity' => 'division',
    'edit_capability' => 'edit_all_divisions',
    'delete_capability' => 'delete_division',
    'text_domain' => 'wp-agency'
]);
```

**Generated HTML:**
```html
<button type="button" class="button button-small division-edit-btn"
        data-id="123" data-entity="division" title="Edit">
    <span class="dashicons dashicons-edit"></span>
</button>

<button type="button" class="button button-small division-delete-btn"
        data-id="123" data-entity="division" title="Delete">
    <span class="dashicons dashicons-trash"></span>
</button>
```

**Button Classes:** `{entity}-edit-btn`, `{entity}-delete-btn`
**Events Triggered:** `wpdt:action-edit`, `wpdt:action-delete`

---

### 2. `format_status_badge($status, $options)`

Generate colored status badge.

**Parameters:**
- `$status` (string) - Status value
- `$options` (array):
  - `active_value` (optional) - Value for active (default: 'active')
  - `text_domain` (optional) - i18n text domain
  - `custom_class` (optional) - Additional CSS class

**Returns:** HTML badge with color coding

**Example:**
```php
$badge = $this->format_status_badge('active', [
    'text_domain' => 'wp-agency'
]);
// Output: <span class="wpdt-badge wpdt-badge-success">Active</span>

$badge = $this->format_status_badge('inactive');
// Output: <span class="wpdt-badge wpdt-badge-error">Inactive</span>
```

**Colors:**
- `active` / `aktif` → Green badge
- `inactive` → Red badge

---

### 3. `format_panel_row_data($row, $entity, $additional_data)`

Generate `DT_RowId` and `DT_RowData` for panel integration.

**Parameters:**
- `$row` (object) - Database row
- `$entity` (string) - Entity name
- `$additional_data` (array, optional) - Extra data for DT_RowData

**Returns:** Array with DT_RowId and DT_RowData

**Example:**
```php
$panel_data = $this->format_panel_row_data($row, 'division');
// Result:
// [
//     'DT_RowId' => 'division-123',
//     'DT_RowData' => ['id' => 123, 'entity' => 'division']
// ]

// With additional data
$panel_data = $this->format_panel_row_data($row, 'division', ['status' => 'active']);
// Result:
// [
//     'DT_RowId' => 'division-123',
//     'DT_RowData' => ['id' => 123, 'entity' => 'division', 'status' => 'active']
// ]
```

---

### 4. `esc_output($value, $fallback)`

Escape HTML with fallback for empty values.

**Parameters:**
- `$value` (mixed) - Value to escape
- `$fallback` (string, optional) - Fallback if empty (default: '-')

**Returns:** Escaped HTML string

**Example:**
```php
echo $this->esc_output($row->name);  // "John Doe"
echo $this->esc_output(null);        // "-"
echo $this->esc_output('', 'N/A');   // "N/A"
```

---

## 💻 Complete Usage Example

```php
<?php
namespace YourPlugin\Models;

use WPAppCore\Models\DataTable\DataTableModel;
use WPDataTable\Traits\DataTableHelpers;

class DivisionDataTableModel extends DataTableModel {
    use DataTableHelpers;

    public function __construct() {
        parent::__construct();
        // ... setup table, columns, joins
    }

    protected function format_row($row): array {
        return array_merge(
            // Panel integration
            $this->format_panel_row_data($row, 'division', [
                'status' => $row->status ?? 'active'
            ]),

            // Data columns
            [
                'code' => $this->esc_output($row->code),
                'name' => $this->esc_output($row->name),
                'status' => $this->format_status_badge($row->status ?? '', [
                    'text_domain' => 'wp-agency'
                ]),
                'actions' => $this->generate_action_buttons($row, [
                    'entity' => 'division',
                    'edit_capability' => 'edit_all_divisions',
                    'delete_capability' => 'delete_division',
                    'text_domain' => 'wp-agency'
                ])
            ]
        );
    }
}
```

---

## 🎨 JavaScript Integration

Buttons work automatically with `action-buttons-handler.js`:

```javascript
jQuery(document).ready(function($) {
    // Edit button clicked
    $(document).on('wpdt:action-edit', function(e, data) {
        if (data.entity === 'division') {
            DivisionModal.edit(data.id);
        }
    });

    // Delete button clicked
    $(document).on('wpdt:action-delete', function(e, data) {
        if (data.entity === 'division') {
            DivisionModal.confirmDelete(data.id);
        }
    });
});
```

---

## ✅ Benefits

| Before (Manual) | After (With Trait) |
|----------------|-------------------|
| 15+ lines per model | 6 lines |
| Manual permission checks | Auto capability checks |
| Custom class names | Convention-based `{entity}-edit-btn` |
| No event system | Auto triggers `wpdt:action-*` |
| Code duplication | DRY - reuse across models |

---

## 🔄 Migration Steps

1. **Add trait to model:**
   ```php
   use WPDataTable\Traits\DataTableHelpers;

   class YourModel extends DataTableModel {
       use DataTableHelpers;
   }
   ```

2. **Remove manual methods:**
   - Delete `generate_action_buttons()`
   - Delete `format_status_badge()`

3. **Update `format_row()`:**
   ```php
   protected function format_row($row): array {
       return [
           'actions' => $this->generate_action_buttons($row, [
               'entity' => 'your_entity',
               'edit_capability' => 'edit_capability',
               'delete_capability' => 'delete_capability'
           ])
       ];
   }
   ```

4. **Update JavaScript column config:**
   ```javascript
   {
       data: 'actions',
       orderable: false,
       searchable: false
   }
   ```

---

## 📝 Convention Rules

1. **Button Classes:** Must be `{entity}-{action}-btn`
   - ✅ `division-edit-btn`, `employee-delete-btn`
   - ❌ `edit-division`, `btn-delete-employee`

2. **Data Attributes:**
   - `data-id="{row_id}"` - Required
   - `data-entity="{entity}"` - Required

3. **Entity Names:**
   - Lowercase, singular
   - Underscores allowed
   - Examples: `division`, `employee`, `platform_staff`

---

## 🐛 Troubleshooting

### Buttons not appearing?
- Check permission: `current_user_can('your_capability')`
- Verify `entity` parameter is set
- Check button HTML in browser inspector

### Events not firing?
- Ensure `action-buttons-handler.js` is loaded
- Check button class: must be `{entity}-edit-btn` pattern
- Verify `data-id` attribute exists

### Wrong entity in event?
- Check `data-entity` attribute in button HTML
- Verify `entity` parameter in `generate_action_buttons()`

---

## 📚 See Also

- [DataTableHelpers-Example.php](./DataTableHelpers-Example.php) - Complete code examples
- [wp-datatable README.md](../README.md) - Full documentation
- [action-buttons-handler.js](../assets/js/dual-panel/action-buttons-handler.js) - Event handler source
