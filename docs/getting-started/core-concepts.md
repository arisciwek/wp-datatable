# Core Concepts

**Version**: 0.1.0
**Last Updated**: 2025-11-09

---

## 🎯 Layout Strategies

WP DataTable supports two layout strategies:

### 1. Dual Panel Layout

**Use Case**: Dashboard dengan detail panel yang slide dari kanan

**Features**:
- Left panel: DataTable list
- Right panel: Detail view dengan tabs
- Smooth slide animations
- Hash-based navigation
- Panel state management

**Example**: Customer dashboard, Company dashboard, Invoice list dengan detail

**Visual**:
```
┌──────────────┬────────────────┐
│              │                │
│  DataTable   │  Detail Panel  │
│   (List)     │   (Tabs)       │
│              │                │
└──────────────┴────────────────┘
```

---

### 2. Single Panel Layout

**Use Case**: Simple DataTable tanpa detail panel

**Features**:
- Full-width DataTable
- Optional statistics cards
- No side panel
- Simpler structure

**Example**: Report lists, Simple data grids, Export logs

**Visual**:
```
┌──────────────────────────────┐
│                              │
│        DataTable             │
│       (Full Width)           │
│                              │
└──────────────────────────────┘
```

---

## 📊 Entity System

### What is an Entity?

Entity adalah representasi dari object/data type yang ditampilkan di DataTable.

**Examples**:
- `customer` - Customer data
- `company` - Company/Branch data
- `invoice` - Invoice records
- `employee` - Employee records

### Entity Naming Convention

**PENTING**: Entity name harus konsisten di seluruh sistem!

```php
// ✅ BENAR - Semua lowercase
'entity' => 'customer'
data-entity="customer"
data-customer-id="123"
$_POST['customer_id']

// ❌ SALAH - Mixed case
'entity' => 'Customer'
data-entity="customer_branch"
data-company-id="123" // tapi entity adalah 'branch'
```

### Entity Configuration

Setiap entity membutuhkan:

1. **Entity Name**: Unique identifier (lowercase)
2. **DataTable Model**: Format data untuk DataTable
3. **Controller**: Handle AJAX requests
4. **Views**: Templates untuk list dan detail

---

## 🗂️ Tab System

### Tab Types

#### 1. Direct Include Tab (First Tab)

**Characteristics**:
- Content langsung di-include saat panel terbuka
- NO lazy-loading
- Instant display
- Best untuk info/overview

**When to Use**:
- Tab pertama yang selalu dilihat user
- Content simple dan cepat di-render
- Static information display

**Template Pattern**:
```php
<?php
// Direct include - no wrapper, no lazy-load
include 'partials/info-content.php';
?>
```

---

#### 2. Lazy-Load Tab (Other Tabs)

**Characteristics**:
- Content loaded via AJAX saat tab diklik
- Initial state: loading spinner
- Better performance untuk data heavy
- Support nested DataTables

**When to Use**:
- Tab yang tidak selalu dibuka
- Content dengan DataTable atau data banyak
- Content yang perlu real-time data

**Template Pattern**:
```php
<div class="wpdt-tab-autoload"
     data-customer-id="<?php echo esc_attr($customer_id); ?>"
     data-load-action="load_customer_employees_tab"
     data-content-target=".wpdt-employees-content"
     data-error-message="Failed to load employees">

    <div class="wpdt-tab-loading">
        <p>Loading...</p>
    </div>

    <div class="wpdt-employees-content wpdt-tab-loaded-content">
        <!-- AJAX content injected here -->
    </div>

    <div class="wpdt-tab-error">
        <p class="wpdt-error-message"></p>
    </div>
</div>
```

---

### Tab Lifecycle

```
Panel Opens
    ↓
Tab 1 (Direct): Content visible immediately
Tab 2+ (Lazy): Show loading spinner
    ↓
User Clicks Tab 2
    ↓
Check if .loaded class exists
    ↓
If NOT loaded:
    - AJAX request (data-load-action)
    - Response injected to data-content-target
    - Add .loaded class
    - Trigger wpdt:tab-data-loaded event
    ↓
If already loaded:
    - Skip AJAX
    - Just show tab
    ↓
Trigger wpdt:tab-switched event
```

---

## 🔄 Data Flow Patterns

### Server-Side Processing (DataTables)

WP DataTable menggunakan **server-side processing** untuk performa optimal:

**Client Request**:
```javascript
{
    draw: 1,
    start: 0,
    length: 10,
    search: { value: "john" },
    order: [{ column: 0, dir: "asc" }],
    columns: [...]
}
```

**Server Response**:
```javascript
{
    draw: 1,
    recordsTotal: 1000,
    recordsFiltered: 50,
    data: [
        {
            DT_RowId: "customer-1",
            DT_RowData: {
                id: 1,
                entity: "customer",
                status: "active"
            },
            code: "C001",
            name: "John Doe",
            email: "john@example.com",
            actions: "<button>...</button>"
        }
    ]
}
```

---

### Row Data vs Row Attributes

#### DT_RowData (Server)

**Purpose**: Data yang perlu diakses via JavaScript tapi tidak ditampilkan

```php
protected function format_row($row): array {
    return [
        'DT_RowId' => 'customer-' . $row->id,
        'DT_RowData' => [
            'id' => $row->id,
            'entity' => 'customer',
            'status' => $row->status,
            'has_membership' => $row->has_membership
        ],
        // Visible columns
        'code' => esc_html($row->code),
        'name' => esc_html($row->name)
    ];
}
```

#### DOM Attributes (Client)

**Purpose**: DataTables converts DT_RowData to data-* attributes

```html
<tr id="customer-1"
    data-id="1"
    data-entity="customer"
    data-status="active"
    data-has-membership="true">
    <td>C001</td>
    <td>John Doe</td>
</tr>
```

#### createdRow Callback

**Purpose**: Ensure DT_RowData is properly copied to DOM

```javascript
createdRow: function(row, data, dataIndex) {
    if (data.DT_RowData) {
        $(row).attr('data-id', data.DT_RowData.id);
        $(row).attr('data-entity', data.DT_RowData.entity);
        $(row).attr('data-status', data.DT_RowData.status);
    }
}
```

---

## 🎨 Panel Interaction Patterns

### Panel Triggers

Ada 2 cara untuk trigger panel open:

#### 1. Row Click (Dual Panel Only)

**How it works**:
- Click pada row (kecuali action buttons)
- panel-manager.js detects click pada `.wpdt-datatable tbody tr`
- Extract `data-id` dari row
- Buka panel dengan ID tersebut

**Requirements**:
- Table HARUS punya class `wpdt-datatable`
- Row HARUS punya `data-id` attribute
- Row HARUS punya `data-entity` attribute

---

#### 2. Button Click

**How it works**:
- Click pada button dengan class `.wpdt-panel-trigger`
- Extract `data-id` dan `data-entity` dari button
- Verify entity matches current context
- Buka panel

**Requirements**:
```html
<button class="wpdt-panel-trigger"
        data-id="123"
        data-entity="customer">
    View
</button>
```

**⚠️ CRITICAL**: `data-entity` attribute WAJIB ada!

---

### Panel States

```javascript
{
    isOpen: false,           // Panel open atau closed
    currentId: null,         // Entity ID yang sedang dibuka
    currentEntity: 'customer', // Entity context
    ajaxRequest: null,       // Pending AJAX request
    dataTable: null          // DataTable instance
}
```

---

### Hash Navigation (Dual Panel Only)

**Purpose**: Support browser back/forward, direct links, bookmarks

**Pattern**:
```
URL: /wp-admin/admin.php?page=customers#customer-123
                                        └─────┬─────┘
                                         Entity Hash
```

**Behavior**:
- Panel opens → Hash added `#customer-123`
- Panel closes → Hash removed
- Browser back → Panel closes
- Direct link dengan hash → Panel auto-opens

---

## 🔐 Security Pattern

### Nonce Verification

Semua AJAX requests HARUS verify nonce:

```php
// ✅ BENAR
if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
    wp_send_json_error(['message' => 'Security check failed']);
    return;
}
```

**⚠️ PENTING**: Gunakan `wpdt_nonce`, BUKAN custom nonce!

### Capability Checks

```php
// Check user capability
if (!current_user_can('view_customer_list')) {
    wp_send_json_error(['message' => 'Permission denied']);
    return;
}
```

### Input Sanitization

```php
// Sanitize input
$customer_id = isset($_POST['customer_id'])
    ? intval($_POST['customer_id'])
    : 0;

$status = isset($_POST['status'])
    ? sanitize_text_field($_POST['status'])
    : '';
```

### Output Escaping

```php
// Escape output
echo esc_html($customer->name);
echo esc_attr($customer->id);
echo esc_url($customer->website);
```

---

## ⚡ Performance Patterns

### 1. Server-Side Processing

**Why**: Handle large datasets (1000+ rows) efficiently

**How**:
- Database query dengan LIMIT/OFFSET
- Search dan filtering di SQL level
- Return hanya data yang visible (10-100 rows)

---

### 2. Lazy Loading Tabs

**Why**: Mengurangi initial load time

**How**:
- Tab pertama: direct include (instant)
- Tab lainnya: load saat diklik
- Cache hasil AJAX (class `.loaded`)

---

### 3. DataTable Column Adjustment

**Why**: Prevent layout shift saat panel open/close

**How**:
```javascript
// Saat panel opens
this.dataTable.columns.adjust();

// Saat panel closes
this.dataTable.columns.adjust();
setTimeout(() => {
    this.dataTable.draw(false);
}, 50);
```

---

### 4. Anti-Flicker Loading

**Why**: Prevent spinner flicker pada fast AJAX

**How**:
```javascript
// Delay loading indicator 300ms
this.loadingTimeout = setTimeout(() => {
    showLoadingSpinner();
}, 300);

// Clear timeout jika AJAX < 300ms
if (ajaxTime < 300) {
    clearTimeout(this.loadingTimeout);
}
```

---

## 🎭 Event-Driven Architecture

### Why Events?

Events memungkinkan loose coupling antar components:

**Without Events** (Tight Coupling):
```javascript
// ❌ Component A directly calls Component B
function openPanel(id) {
    initializeNestedDataTable(); // Tight coupling!
}
```

**With Events** (Loose Coupling):
```javascript
// ✅ Component A triggers event
function openPanel(id) {
    $(document).trigger('wpdt:panel-opened', {id: id});
}

// Component B listens to event
$(document).on('wpdt:panel-opened', function(e, data) {
    initializeNestedDataTable();
});
```

---

### Event Naming Convention

```
wpdt:{component}:{action}
  │      │         │
  │      │         └─ Action (opened, closed, switched, etc)
  │      └─────────── Component (panel, tab, datatable)
  └────────────────── Namespace (wpdt)
```

**Examples**:
- `wpdt:panel-opening` - Before panel opens
- `wpdt:panel-opened` - After panel opened
- `wpdt:panel-closing` - Before panel closes
- `wpdt:panel-closed` - After panel closed
- `wpdt:tab-switched` - Tab switched
- `wpdt:tab-data-loaded` - Tab data loaded via AJAX

---

### Event Data

Setiap event membawa data yang relevan:

```javascript
$(document).trigger('wpdt:panel-opened', {
    entity: 'customer',
    id: 123,
    title: 'John Doe'
});

// Listener receives data
$(document).on('wpdt:panel-opened', function(e, data) {
    console.log(data.entity); // "customer"
    console.log(data.id);     // 123
    console.log(data.title);  // "John Doe"
});
```

---

## 📋 Configuration Pattern

### Dashboard Configuration

```php
DashboardTemplate::render([
    // Required
    'entity' => 'customer',              // Entity name (lowercase)

    // Display
    'title' => 'Customers',              // Page title
    'description' => 'Manage customers', // Optional description

    // Features
    'has_tabs' => true,                  // Enable tab system
    'has_stats' => true,                 // Show statistics cards
    'has_filters' => false,              // Show filter UI (future)

    // AJAX
    'ajax_action' => 'get_customer_details', // AJAX action for detail panel

    // Layout (optional)
    'layout' => 'dual-panel',            // 'dual-panel' or 'single-panel'
]);
```

---

### Tab Configuration

```php
add_filter('wpdt_datatable_tabs', function($tabs, $entity) {
    if ($entity !== 'customer') return $tabs;

    return [
        'info' => [
            'title' => 'Information',
            'template' => '/path/to/info.php',
            'priority' => 10,
            'icon' => 'dashicons-info'  // Optional
        ],
        'employees' => [
            'title' => 'Employees',
            'template' => '/path/to/employees.php',
            'priority' => 20
        ]
    ];
}, 10, 2);
```

---

## 🔍 Debugging Patterns

### Console Logging

Framework provides verbose console logs:

```javascript
// Panel Manager
[WPDT Panel] Initialized
[WPDT Panel] DataTable instance found
📡 DEBUG: AJAX Data Loading
✅ AJAX Success - Elapsed: 159ms

// Tab Manager
[WPDT Tab] Initialized
[WPDT Tab] Switched to: employees
[WPDT Tab] Starting AJAX request for: load_customer_employees_tab
```

### Error Patterns

**Common Error Patterns**:
```javascript
// Entity mismatch
[WPDT Panel] Nested entity button detected - ignoring

// Missing attributes
[WPDT Tab] Missing required data attributes for auto-load

// No DataTable instance
[WPDT Panel] No DataTable instance found
```

---

## 🎯 Best Practices Summary

### ✅ DO

1. **Entity Naming**: Use lowercase, consistent names
2. **Security**: Always check nonce and capabilities
3. **Performance**: Use server-side processing, lazy-load tabs
4. **Events**: Use event system untuk extensibility
5. **Attributes**: Copy DT_RowData to DOM attributes
6. **First Tab**: Direct include untuk instant display
7. **Error Handling**: Always handle AJAX errors
8. **Console Logs**: Keep debug logs during development

### ❌ DON'T

1. **Entity Names**: Don't mix case or inconsistent names
2. **Table Class**: Don't use `wpdt-table` (use `wpdt-datatable`)
3. **Nested Divs**: Don't create duplicate wrapper divs in tabs
4. **Missing Attributes**: Don't forget `data-entity` on buttons
5. **Direct AJAX**: Don't bypass wpdt_nonce
6. **All Lazy-Load**: Don't lazy-load first tab
7. **Tight Coupling**: Don't directly call other components
8. **Silent Failures**: Don't suppress errors

---

**Next**: [Quick Start Guide](quick-start.md) →
**Back**: [Architecture Overview](architecture.md)
