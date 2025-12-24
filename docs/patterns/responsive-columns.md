# Responsive Columns Pattern

**Version**: 0.1.0
**Last Updated**: 2025-11-09
**Feature**: Auto-hide columns when panel opens for better space utilization

---

## 🎯 Overview

Responsive Columns feature automatically hides less important columns when the detail panel opens (Dual Panel mode), giving more space for essential data while the panel is visible.

**Benefits**:
- ✅ Better space utilization in dual panel layout
- ✅ Focus on important columns when panel is open
- ✅ Automatic show/hide without manual intervention
- ✅ Smooth transitions with no layout shift
- ✅ Priority-based column visibility

---

## 🎨 Visual Behavior

### Before Panel Opens (Full Width)
```
┌───────────────────────────────────────────────┐
│ Code │ Name  │ Type │ Email │ Phone │ Status │
├──────┼───────┼──────┼───────┼───────┼────────┤
│ C001 │ ACME  │ HQ   │ ...   │ ...   │ Active │
│ C002 │ Beta  │ Br   │ ...   │ ...   │ Active │
└───────────────────────────────────────────────┘
```

### After Panel Opens (Split View)
```
┌─────────────────────┬────────────────┐
│ Code │ Name │ Status│   Detail       │
├──────┼──────┼───────│   Panel        │
│ C001 │ ACME │ Active│                │
│ C002 │ Beta │ Active│   (Tabs here)  │
└─────────────────────┴────────────────┘
     ↑
Type, Email, Phone columns hidden
```

---

## 📋 Priority Levels

### Priority 1: Always Visible (Critical)
**Use for**:
- Primary identifier (Code, ID)
- Main title/name
- Status indicators
- Action buttons

**Example**: Code, Name, Status, Actions

---

### Priority 2: Hide When Panel Opens (Important)
**Use for**:
- Secondary information
- Columns that are nice to have but not critical
- Information that's also shown in detail panel

**Example**: Type, Phone, Category

---

### Priority 3+: Hide on Smaller Screens (Optional)
**Use for**:
- Tertiary information
- Detailed data that's available in detail panel
- Long text fields

**Example**: Email, Address, Description

---

## 🔧 Implementation

### Step 1: Add responsivePriority to Column Definitions

**Example: Company DataTable**

```javascript
var companyTable = $('#company-datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { /* ... */ },
    columns: [
        {
            data: 'code',
            name: 'code',
            responsivePriority: 1  // ← Always visible
        },
        {
            data: 'name',
            name: 'name',
            responsivePriority: 1  // ← Always visible
        },
        {
            data: 'type',
            name: 'type',
            responsivePriority: 2  // ← Hide when panel opens
        },
        {
            data: 'email',
            name: 'email',
            responsivePriority: 3  // ← Hide when panel opens (lower priority)
        },
        {
            data: 'phone',
            name: 'phone',
            responsivePriority: 2  // ← Hide when panel opens
        },
        {
            data: 'status',
            name: 'status',
            responsivePriority: 1  // ← Always visible
        },
        {
            data: 'actions',
            name: 'actions',
            orderable: false,
            searchable: false,
            responsivePriority: 1  // ← Always visible (critical)
        }
    ],
    // ... other config
});
```

---

### Step 2: That's It!

Panel Manager automatically handles show/hide based on `responsivePriority`:

**When panel opens**:
```javascript
// panel-manager.js automatically calls:
this.toggleResponsiveColumns(false); // Hide priority 2+ columns
```

**When panel closes**:
```javascript
// panel-manager.js automatically calls:
this.toggleResponsiveColumns(true); // Show all columns
```

---

## 📐 Decision Matrix

Use this matrix to decide column priority:

| Column Type | Priority | Visible When Panel Open? |
|-------------|----------|--------------------------|
| Primary ID/Code | 1 | ✅ YES |
| Main Title/Name | 1 | ✅ YES |
| Status Badge | 1 | ✅ YES |
| Action Buttons | 1 | ✅ YES |
| Category/Type | 2 | ❌ NO |
| Phone Number | 2 | ❌ NO |
| Short Text Field | 2 | ❌ NO |
| Email Address | 3 | ❌ NO |
| Long Description | 3 | ❌ NO |
| Timestamps | 3 | ❌ NO |

---

## 💡 Best Practices

### ✅ DO

1. **Keep 3-5 columns visible** when panel opens
   ```javascript
   // Good: Code, Name, Status, Actions = 4 columns
   responsivePriority: 1
   ```

2. **Hide duplicated data**
   ```javascript
   // Email shown in detail panel, hide in list
   { data: 'email', responsivePriority: 3 }
   ```

3. **Always keep actions visible**
   ```javascript
   // Users need to interact even when panel is open
   { data: 'actions', responsivePriority: 1 }
   ```

4. **Test with different screen sizes**
   ```
   - 1920px wide (panel open = ~750px table)
   - 1366px wide (panel open = ~520px table)
   - 1024px wide (panel open = ~390px table)
   ```

5. **Group similar priority levels**
   ```javascript
   // All secondary info at priority 2
   { data: 'type', responsivePriority: 2 },
   { data: 'phone', responsivePriority: 2 },
   { data: 'category', responsivePriority: 2 }
   ```

---

### ❌ DON'T

1. **Don't hide all columns**
   ```javascript
   // ❌ WRONG: Everything hidden when panel opens
   { data: 'code', responsivePriority: 2 },
   { data: 'name', responsivePriority: 2 },
   { data: 'status', responsivePriority: 2 }
   ```

2. **Don't hide primary identifier**
   ```javascript
   // ❌ WRONG: User can't identify rows
   { data: 'id', responsivePriority: 3 }
   { data: 'code', responsivePriority: 2 }
   ```

3. **Don't hide action buttons**
   ```javascript
   // ❌ WRONG: Can't perform actions
   { data: 'actions', responsivePriority: 2 }
   ```

4. **Don't use too many priority levels**
   ```javascript
   // ❌ WRONG: Over-complicated
   responsivePriority: 1, 2, 3, 4, 5, 6, 7

   // ✅ GOOD: Simple 1-3 levels
   responsivePriority: 1, 2, 3
   ```

---

## 🔍 How It Works

### Internal Flow

1. **Panel Opens** (panel-manager.js):
   ```javascript
   showPanel() {
       // ... animation code ...
       setTimeout(function() {
           if (self.dataTable) {
               // ✅ Hide responsive columns
               self.toggleResponsiveColumns(false);

               // Adjust widths
               self.dataTable.columns.adjust();
           }
       }, 350);
   }
   ```

2. **toggleResponsiveColumns(false)**:
   ```javascript
   toggleResponsiveColumns(show) {
       const columns = api.settings()[0].aoColumns;

       columns.forEach(function(column, index) {
           const priority = column.responsivePriority;

           if (priority === undefined || priority === 1) {
               return; // Keep visible
           }

           if (show) {
               api.column(index).visible(true, false);
           } else {
               api.column(index).visible(false, false);
           }
       });
   }
   ```

3. **Panel Closes**:
   ```javascript
   hidePanel() {
       setTimeout(function() {
           if (self.dataTable) {
               // ✅ Show all columns
               self.toggleResponsiveColumns(true);

               self.dataTable.columns.adjust();
               self.dataTable.draw(false);
           }
       }, 300);
   }
   ```

---

## 🎯 Real-World Examples

### Example 1: Customer DataTable

```javascript
columns: [
    { data: 'code', name: 'code', responsivePriority: 1 },           // Always
    { data: 'name', name: 'name', responsivePriority: 1 },           // Always
    { data: 'npwp', name: 'npwp', responsivePriority: 3 },           // Hide (also in detail)
    { data: 'email', name: 'email', responsivePriority: 3 },         // Hide (also in detail)
    { data: 'phone', name: 'phone', responsivePriority: 2 },         // Hide
    { data: 'city', name: 'city', responsivePriority: 2 },           // Hide
    { data: 'membership', name: 'membership', responsivePriority: 2 }, // Hide
    { data: 'status', name: 'status', responsivePriority: 1 },       // Always
    { data: 'actions', name: 'actions', responsivePriority: 1 }      // Always
]
```

**Result**:
- Panel closed: 9 columns visible
- Panel open: 4 columns visible (code, name, status, actions)
- Space saved: ~55%

---

### Example 2: Invoice DataTable

```javascript
columns: [
    { data: 'invoice_number', responsivePriority: 1 },    // Always
    { data: 'customer_name', responsivePriority: 1 },     // Always
    { data: 'invoice_date', responsivePriority: 2 },      // Hide
    { data: 'due_date', responsivePriority: 2 },          // Hide
    { data: 'amount', responsivePriority: 1 },            // Always
    { data: 'payment_method', responsivePriority: 3 },    // Hide
    { data: 'status', responsivePriority: 1 },            // Always
    { data: 'actions', responsivePriority: 1 }            // Always
]
```

**Result**:
- Panel closed: 8 columns
- Panel open: 4 columns (invoice_number, customer_name, amount, status, actions)

---

### Example 3: Product DataTable

```javascript
columns: [
    { data: 'sku', responsivePriority: 1 },              // Always
    { data: 'name', responsivePriority: 1 },             // Always
    { data: 'category', responsivePriority: 2 },         // Hide
    { data: 'price', responsivePriority: 1 },            // Always
    { data: 'stock', responsivePriority: 2 },            // Hide
    { data: 'supplier', responsivePriority: 3 },         // Hide
    { data: 'last_updated', responsivePriority: 3 },     // Hide
    { data: 'status', responsivePriority: 1 },           // Always
    { data: 'actions', responsivePriority: 1 }           // Always
]
```

---

## 🐛 Troubleshooting

### Issue: Columns not hiding

**Check**:
1. Is `responsivePriority` set in column definition?
2. Is DataTable registered to `window.wpdtPanelManager`?
3. Check console for errors

**Debug**:
```javascript
// Check if responsivePriority is set
console.log(companyTable.settings()[0].aoColumns.map(c => c.responsivePriority));
// Expected: [1, 1, 2, 3, 2, 1, 1]

// Check panel manager
console.log(window.wpdtPanelManager.dataTable);
// Should be DataTable instance

// Manual test
window.wpdtPanelManager.toggleResponsiveColumns(false);
```

---

### Issue: Wrong columns hidden

**Check**:
1. Verify priority numbers are correct
2. Remember: Priority 1 = always visible, 2+ = hidden

**Fix**:
```javascript
// ❌ WRONG: Actions hidden
{ data: 'actions', responsivePriority: 2 }

// ✅ CORRECT: Actions always visible
{ data: 'actions', responsivePriority: 1 }
```

---

### Issue: Layout shift when toggling

**Check**:
1. Using `columns.adjust()` after toggle
2. No redraw during toggle (use `false` parameter)

**Code**:
```javascript
// ✅ CORRECT: No immediate redraw
api.column(index).visible(false, false);
                                  ↑
                          No redraw flag
```

---

## 📚 API Reference

### responsivePriority (Column Definition)

**Type**: `number`
**Default**: `undefined` (always visible)
**Values**:
- `1` - Always visible (highest priority)
- `2` - Hidden when panel opens (medium priority)
- `3+` - Hidden when panel opens (low priority)

**Example**:
```javascript
{
    data: 'email',
    name: 'email',
    responsivePriority: 3
}
```

---

### toggleResponsiveColumns(show)

**Method**: `WPDTPanelManager.toggleResponsiveColumns(show)`
**Parameters**:
- `show` (boolean) - `true` to show all columns, `false` to hide responsive columns

**Example**:
```javascript
// Hide responsive columns
window.wpdtPanelManager.toggleResponsiveColumns(false);

// Show all columns
window.wpdtPanelManager.toggleResponsiveColumns(true);
```

**Note**: You usually don't need to call this manually. Panel manager calls it automatically.

---

## ✅ Checklist

When implementing responsive columns:

```
[ ] 1. Define responsivePriority for ALL columns
[ ] 2. Set priority 1 for critical columns (3-5 columns)
[ ] 3. Set priority 2-3 for optional columns
[ ] 4. Always keep action buttons visible (priority 1)
[ ] 5. Always keep primary identifier visible (priority 1)
[ ] 6. Test panel open/close behavior
[ ] 7. Test on different screen sizes
[ ] 8. Verify no layout shift during toggle
[ ] 9. Check console for any errors
[ ] 10. Confirm DataTable registered to panel manager
```

---

**Next**: [Event System](event-system.md) →
**Back**: [Dual Panel Pattern](dual-panel.md)
