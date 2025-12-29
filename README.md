# WP DataTable

**Version:** 0.1.0
**Author:** arisciwek
**License:** GPL v2 or later

Reusable DataTable framework untuk WordPress plugins dengan dual panel dan single panel layouts.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Dual Panel Layout](#dual-panel-layout)
- [Single Panel Layout](#single-panel-layout)
- [Hooks & Filters](#hooks--filters)
- [Architecture](#architecture)
- [Development](#development)
- [Changelog](#changelog)

---

## 🎯 Overview

WP DataTable adalah WordPress plugin framework yang menyediakan reusable DataTable templates dengan dua layout patterns:

1. **Dual Panel Layout** - Master-detail pattern dengan sliding panel (45% left, 55% right)
2. **Single Panel Layout** - Full-width listing dengan stats dan filters

Plugin ini dirancang untuk:
- **Developers** yang ingin cepat implement DataTable dengan UI konsisten
- **Plugin authors** yang butuh DataTable framework extensible
- **Projects** yang memerlukan multiple DataTable dengan look & feel sama

### Why WP DataTable?

✅ **Plug & Play** - Hook-based architecture, no complex setup
✅ **Consistent UI** - WordPress admin styling, smooth animations
✅ **Extensible** - Filter/action hooks untuk customization
✅ **Asset Strategy** - Conditional loading, optimize performance
✅ **Battle-tested** - Ported dari wp-app-core v1.1.0 (production-ready)

---

## ✨ Features

### Auto-Wire System (Zero JavaScript!)
- ✅ **Config-Driven Buttons** - Edit/Delete buttons auto-wired via filter injection
- ✅ **Modal Integration** - Seamless integration dengan wp-modal plugin
- ✅ **Automatic AJAX** - Nonce handling, success/error messages, DataTable refresh
- ✅ **Zero JavaScript** - Consumer plugins hanya perlu 3 AJAX handlers (get_form, update, delete)
- ✅ **Event-Driven** - Framework handles ALL modal interactions

### Dual Panel Layout
- ✅ **Master-Detail Pattern** - 45% left panel (listing), 55% right panel (detail)
- ✅ **Sliding Animations** - Smooth panel transitions
- ✅ **Tab System** - WordPress-style horizontal tabs dengan keyboard navigation
- ✅ **Hash Navigation** - Browser back/forward support (#entity-123&tab=details)
- ✅ **AJAX Loading** - Dynamic content loading untuk panel & tabs
- ✅ **Stats Boxes** - Optional statistics grid (1-4 columns)
- ✅ **Filters** - Optional filter controls
- ✅ **Per-Row Actions** - Action buttons inside DataTable rows

### Single Panel Layout
- ✅ **Full-Width Layout** - Simple, clean listing
- ✅ **Stats Boxes** - Responsive grid (1-4 columns)
- ✅ **Filters** - Multiple filter types (select, search, date range)
- ✅ **Auto-Refresh** - Event-driven table refresh
- ✅ **Apply/Reset** - Filter management

### Asset Management
- ✅ **Strategy Pattern** - Conditional asset loading
- ✅ **Auto-Detection** - Load only needed assets
- ✅ **Localized Data** - Pass config to JavaScript
- ✅ **CDN Support** - DataTables.js from CDN
- ✅ **Modal Integration** - Auto-load modal-integration.js for dual panel

---

## 📦 Installation

### Requirements
- WordPress 5.0+
- PHP 7.4+
- jQuery (WordPress core)
- **wp-modal** plugin (required for auto-wire system)

### Install Plugin

1. **Download/Clone** plugin ke `wp-content/plugins/`:
```bash
cd wp-content/plugins/
git clone [repository-url] wp-datatable
```

2. **Install wp-modal** (required dependency):
```bash
wp plugin install wp-modal --activate
```

3. **Activate** wp-datatable:
```bash
wp plugin activate wp-datatable
```

4. **Verify** activation:
```bash
wp plugin list | grep wp-datatable
# Output: wp-datatable  active  none  0.1.0  off
```

5. **(Optional) Install demo plugin** untuk testing:
```bash
wp plugin activate wp-datatable-test
# Visit: WP Admin → Dual Panel Test
```

---

## 🚀 Quick Start

### Example 0: Complete Auto-Wire Reference (RECOMMENDED!)

**Cara tercepat:** Copy dan customize complete auto-wire example!

```bash
# 1. Copy example file ke plugin Anda
cp wp-content/plugins/wp-datatable/examples/complete-auto-wire-example.php \
   wp-content/plugins/your-plugin/includes/

# 2. Rename class dan entity
# - Class: WP_DataTable_Complete_AutoWire_Example → Your_Entity_Class
# - Entity: 'test_dual' → 'your_entity'

# 3. Implement 3 AJAX handlers dengan business logic Anda
# - ajax_get_edit_form()   → Load form HTML
# - ajax_update()          → Save data
# - ajax_delete()          → Delete data

# 4. Done! Framework handles all modal interactions automatically.
```

**File reference:** `wp-datatable/examples/complete-auto-wire-example.php`

**Features included:**
- ✅ Dual panel layout dengan tabs
- ✅ Auto-wire Edit/Delete buttons (ZERO JavaScript!)
- ✅ Filter-based config injection
- ✅ Tab structure definition
- ✅ DataTable inside tab dengan action buttons per row
- ✅ Automatic nonce handling
- ✅ Modal integration
- ✅ Success/error notifications
- ✅ DataTable auto-refresh

**Live demo:** Aktifkan `wp-datatable-test` plugin, visit **WP Admin → Dual Panel Test**

---

### Example 1: Dual Panel (Minimal)

```php
<?php
// File: your-plugin/admin-page.php

use WPDataTable\Templates\DualPanel\DashboardTemplate;

// Signal dual panel usage (for asset loading)
add_filter('wpdt_use_dual_panel', function($use) {
    if (isset($_GET['page']) && $_GET['page'] === 'my-page') {
        return true;
    }
    return $use;
});

// Render dashboard
DashboardTemplate::render([
    'entity' => 'customer',
    'title' => 'Customers',
    'description' => 'Manage your customers',
    'has_stats' => true,
    'has_tabs' => true,
    'ajax_action' => 'my_get_customer_details',
]);

// Register content hooks
add_action('wpdt_left_panel_content', function($config) {
    if ($config['entity'] !== 'customer') return;

    // Render your DataTable
    include __DIR__ . '/views/customers-table.php';
});

// Register tabs
add_filter('wpdt_datatable_tabs', function($tabs, $entity) {
    if ($entity !== 'customer') return $tabs;

    return [
        'details' => ['title' => 'Details', 'priority' => 10],
        'orders' => ['title' => 'Orders', 'priority' => 20],
    ];
}, 10, 2);

// AJAX handler for panel content
add_action('wp_ajax_my_get_customer_details', function() {
    check_ajax_referer('wpdt_nonce', 'nonce');

    $id = intval($_POST['id']);
    // Load customer data...

    wp_send_json_success([
        'content' => '<div>Customer details...</div>',
        'title' => 'Customer #' . $id,
    ]);
});
```

### Example 2: Single Panel (Minimal)

```php
<?php
use WPDataTable\Templates\SinglePanel\DashboardTemplate;

// Signal single panel usage
add_filter('wpdt_use_single_panel', function($use) {
    if (isset($_GET['page']) && $_GET['page'] === 'logs-page') {
        return true;
    }
    return $use;
});

// Render dashboard
DashboardTemplate::render([
    'entity' => 'log',
    'title' => 'Activity Logs',
    'has_stats' => true,
    'has_filters' => true,
]);

// Register content
add_action('wpdt_panel_content', function($config) {
    if ($config['entity'] !== 'log') return;

    include __DIR__ . '/views/logs-table.php';
});

// Register filters
add_filter('wpdt_datatable_filters', function($filters, $entity) {
    if ($entity !== 'log') return $filters;

    return [
        'level' => [
            'type' => 'select',
            'label' => 'Level',
            'options' => [
                'info' => 'Info',
                'warning' => 'Warning',
                'error' => 'Error',
            ],
        ],
        'search' => [
            'type' => 'search',
            'label' => 'Search',
            'placeholder' => 'Search logs...',
        ],
    ];
}, 10, 2);
```

---

## 🎨 Dual Panel Layout

### Features
- **45% / 55% Split** - Left panel untuk listing, right panel untuk detail
- **Sliding Animation** - Smooth panel transitions (CSS transitions)
- **Tab System** - Horizontal tabs dengan keyboard support (arrow keys)
- **Hash Navigation** - URL hash support untuk deep linking
- **AJAX Content** - Dynamic loading untuk panel & tab content

### Dashboard Config

```php
DashboardTemplate::render([
    'entity' => 'product',              // Required: Entity identifier
    'title' => 'Products',              // Required: Page title
    'description' => 'Manage products', // Optional: Page description
    'has_stats' => true,                // Optional: Show stats boxes
    'has_tabs' => true,                 // Optional: Enable tab system
    'has_filters' => false,             // Optional: Show filters
    'ajax_action' => 'get_product',     // Required: AJAX action for panel content
]);
```

### ⚠️ CRITICAL: Required CSS Classes for Tab System

**Tab system requires specific CSS classes to work!** Using wrong classes will cause all tabs to display stacked vertically instead of switching.

**Quick Reference:**
```html
<!-- ✅ CORRECT -->
<div class="wpdt-tab-wrapper">              <!-- NOT nav-tab-wrapper -->
    <a class="nav-tab" data-tab="info">...</a>
</div>

<div id="info" class="wpdt-tab-content active">  <!-- NOT tab-content -->
    <!-- First tab content -->
</div>

<div id="staff" class="wpdt-tab-content">   <!-- No active class -->
    <!-- Other tabs -->
</div>
```

**Why these classes?**
- `wpdt-tab-wrapper` - Required by `tab-manager.js` (line 88)
- `wpdt-tab-content` - Required by CSS rules and `tab-manager.js` (line 95)
- `active` class - Makes first tab visible on load

**❌ Common Mistakes:**
- Using `nav-tab-wrapper` instead of `wpdt-tab-wrapper` → Tabs won't switch
- Using `tab-content` instead of `wpdt-tab-content` → All tabs visible, stacked
- Missing `active` on first tab → All tabs hidden

📖 **Complete guide:** See [docs/patterns/dual-panel.md](docs/patterns/dual-panel.md#step-4-html-structure--required-css-classes) for full HTML examples, debugging tips, and technical details.

---

### Available Hooks

#### Content Hooks
```php
// Left panel (DataTable listing)
add_action('wpdt_left_panel_content', function($config) {
    if ($config['entity'] !== 'product') return;
    // Render DataTable HTML
});

// Right panel content (if no tabs)
add_action('wpdt_right_panel_content', function($config) {
    if ($config['entity'] !== 'product') return;
    // Render detail content
});
```

#### Tab Registration
```php
add_filter('wpdt_datatable_tabs', function($tabs, $entity) {
    if ($entity !== 'product') return $tabs;

    return [
        'details' => [
            'title' => 'Details',
            'priority' => 10,
            // Optional: 'template' => '/path/to/template.php'
        ],
        'variants' => [
            'title' => 'Variants',
            'priority' => 20,
        ],
    ];
}, 10, 2);
```

#### Stats Registration
```php
add_action('wpdt_statistics_content', function($config) {
    if ($config['entity'] !== 'product') return;
    ?>
    <div class="wpdt-stat-box">
        <div class="wpdt-stat-value">150</div>
        <div class="wpdt-stat-label">Total Products</div>
    </div>
    <?php
});
```

### JavaScript Events

```javascript
// Panel opened
jQuery(document).on('wpdt:panel-opened', function(e, data) {
    console.log('Panel opened:', data.entity, data.id);
});

// Tab activated
jQuery(document).on('wpdt:tabActivated', function(e, data) {
    console.log('Tab activated:', data.tabId);
});
```

---

## 📊 Single Panel Layout

### Features
- **Full-Width Layout** - Simple, clean listing
- **Responsive Stats** - Grid layout (1-4 columns)
- **Multiple Filters** - Select, search, date range, text
- **Auto-Refresh** - Event-driven table refresh
- **Filter Management** - Apply/Reset functionality

### Dashboard Config

```php
DashboardTemplate::render([
    'entity' => 'log',                  // Required: Entity identifier
    'title' => 'Activity Logs',         // Required: Page title
    'description' => 'View all logs',   // Optional: Description
    'has_stats' => true,                // Optional: Show stats
    'has_filters' => true,              // Optional: Show filters
]);
```

### Available Hooks

#### Content Hook
```php
add_action('wpdt_panel_content', function($config) {
    if ($config['entity'] !== 'log') return;
    // Render DataTable
});
```

#### Filter Registration
```php
add_filter('wpdt_datatable_filters', function($filters, $entity) {
    if ($entity !== 'log') return $filters;

    return [
        'status' => [
            'type' => 'select',
            'label' => 'Status',
            'options' => ['active' => 'Active', 'inactive' => 'Inactive'],
            'default' => '',
        ],
        'search' => [
            'type' => 'search',
            'label' => 'Search',
            'placeholder' => 'Search...',
        ],
        'date_range' => [
            'type' => 'date_range',
            'label' => 'Date Range',
        ],
    ];
}, 10, 2);
```

### JavaScript Integration

```javascript
jQuery(document).ready(function($) {
    // Initialize DataTable
    var table = $('#my-table').DataTable({
        // ... config
    });

    // Register untuk auto-refresh
    if (window.wpdtSinglePanel) {
        window.wpdtSinglePanel.registerDataTable('log', table);
    }

    // Listen to filter events
    $(document).on('wpdt:filtersApplied', function(e, data) {
        console.log('Filters applied:', data.filters);
        // Reload table dengan filters
    });
});
```

---

## ⚡ Auto-Wire System (Zero JavaScript!)

### Overview

Auto-Wire System menghilangkan kebutuhan JavaScript pada consumer plugins untuk Edit/Delete functionality. Framework handles semua modal interactions secara otomatis.

**Consumer plugins HANYA perlu:**
1. Add action buttons dengan classes khusus (`wpdt-edit-btn`, `wpdt-delete-btn`)
2. Create 3 AJAX handlers (get_form, update, delete)
3. Inject config via filter `wpdt_localize_data`

**Framework handles:**
- ✅ Modal open/close
- ✅ AJAX requests dengan nonce
- ✅ DataTable refresh after operations
- ✅ Success/error notifications
- ✅ ALL UI interactions

### Step 1: Add Action Buttons

```php
// In your DataTable HTML
<button class="button button-small wpdt-edit-btn"
        data-id="<?php echo $item->id; ?>"
        data-entity="customer">
    <span class="dashicons dashicons-edit"></span> Edit
</button>

<button class="button button-small wpdt-delete-btn"
        data-id="<?php echo $item->id; ?>"
        data-entity="customer">
    <span class="dashicons dashicons-trash"></span> Delete
</button>
```

**Required attributes:**
- `class="wpdt-edit-btn"` atau `class="wpdt-delete-btn"`
- `data-id="123"` - Entity ID
- `data-entity="customer"` - Entity name

### Step 2: Inject Auto-Wire Config

```php
add_filter('wpdt_localize_data', function($data) {
    // Only inject on your admin page
    if (!isset($_GET['page']) || $_GET['page'] !== 'my-customers') {
        return $data;
    }

    $data['customer'] = [
        'action_buttons' => [
            'edit' => [
                'enabled' => true,
                'ajax_action' => 'my_get_customer_form',
                'submit_action' => 'my_update_customer',
                'modal_title' => 'Edit Customer',
                'success_message' => 'Customer updated!',
                'modal_size' => 'medium', // small, medium, large
            ],
            'delete' => [
                'enabled' => true,
                'ajax_action' => 'my_delete_customer',
                'confirm_title' => 'Delete Customer',
                'confirm_message' => 'Are you sure?',
                'success_message' => 'Customer deleted!',
            ],
        ],
    ];

    return $data;
});
```

### Step 3: Create AJAX Handlers

```php
// Handler 1: Get edit form HTML
add_action('wp_ajax_my_get_customer_form', function() {
    check_ajax_referer('wpdt_nonce', 'nonce');

    $id = intval($_POST['id']);
    $customer = get_customer($id); // Your logic

    ob_start();
    ?>
    <form id="customer-form">
        <input type="text" name="name" value="<?php echo esc_attr($customer->name); ?>">
        <!-- Your form fields -->
    </form>
    <?php
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
});

// Handler 2: Update customer
add_action('wp_ajax_my_update_customer', function() {
    check_ajax_referer('wpdt_nonce', 'nonce');

    $id = intval($_POST['id']);
    $name = sanitize_text_field($_POST['name']);

    // Your update logic
    update_customer($id, ['name' => $name]);

    wp_send_json_success([
        'message' => 'Customer updated successfully!',
    ]);
});

// Handler 3: Delete customer
add_action('wp_ajax_my_delete_customer', function() {
    check_ajax_referer('wpdt_nonce', 'nonce');

    $id = intval($_POST['id']);

    // Your delete logic
    delete_customer($id);

    wp_send_json_success([
        'message' => 'Customer deleted successfully!',
    ]);
});
```

### That's It!

**ZERO JavaScript required!** Framework automatically:
- Opens modal saat Edit button clicked
- Loads form via AJAX dengan nonce
- Handles form submission
- Shows confirmation dialog untuk Delete
- Refreshes DataTable after success
- Displays success/error notifications

### Advanced: Action Buttons Inside Tabs

Auto-Wire juga works untuk DataTable **inside tabs**:

```php
// Tab content dengan DataTable
<div id="tab-history" class="wpdt-tab-content">
    <table id="history-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Action</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>2025-12-28</td>
                <td>Updated</td>
                <td>
                    <button class="wpdt-edit-btn" data-id="<?php echo $id; ?>" data-entity="customer">
                        Edit
                    </button>
                    <button class="wpdt-delete-btn" data-id="<?php echo $id; ?>" data-entity="customer">
                        Delete
                    </button>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script>
// Initialize DataTable when tab switched
jQuery(document).on('wpdt:tab-switched', function(e, data) {
    if (data.tabId === 'history' && !jQuery.fn.DataTable.isDataTable('#history-table')) {
        jQuery('#history-table').DataTable({
            columnDefs: [
                {
                    targets: -1, // Actions column
                    orderable: false,
                    searchable: false,
                }
            ]
        });
    }
});
</script>
```

**Complete Example:** See `wp-datatable/examples/complete-auto-wire-example.php`

---

## 🔌 Hooks & Filters

### Action Hooks

| Hook | Description | Parameters |
|------|-------------|------------|
| `wpdt_left_panel_content` | Render left panel (DataTable) | `$config` |
| `wpdt_right_panel_content` | Render right panel (no tabs) | `$config` |
| `wpdt_panel_content` | Render single panel content | `$config` |
| `wpdt_statistics_content` | Render statistics boxes | `$config` |
| `wpdt_before_dashboard` | Before dashboard render | `$config` |
| `wpdt_after_dashboard` | After dashboard render | `$config` |
| `wpdt_before_tab_template` | Before tab template include | `$tab_id, $entity` |
| `wpdt_after_tab_template` | After tab template include | `$tab_id, $entity` |

### Filter Hooks

| Hook | Description | Parameters | Return |
|------|-------------|------------|--------|
| `wpdt_use_dual_panel` | Signal dual panel usage | `$use` | `bool` |
| `wpdt_use_single_panel` | Signal single panel usage | `$use` | `bool` |
| `wpdt_localize_data` | Inject auto-wire config | `$data` | `array` |
| `wpdt_datatable_tabs` | Register tabs | `$tabs, $entity` | `array` |
| `wpdt_datatable_filters` | Register filters | `$filters, $entity` | `array` |
| `wpdt_datatable_stats` | Register stats | `$stats, $entity` | `array` |

### JavaScript Events

| Event | Description | Data |
|-------|-------------|------|
| `wpdt:panel-opened` | Panel opened | `{entity, id}` |
| `wpdt:panel-closed` | Panel closed | `{entity}` |
| `wpdt:tabActivated` | Tab switched | `{tabId}` |
| `wpdt:filtersApplied` | Filters applied | `{entity, filters}` |
| `wpdt:filtersReset` | Filters reset | `{entity}` |
| `wpdt:refresh` | Table refresh requested | `{entity}` |

---

## 🏗️ Architecture

### Directory Structure

```
wp-datatable/
├── wp-datatable.php              # Main plugin file
├── README.md                     # This file
│
├── examples/                     # Reference implementations ⭐ NEW
│   ├── README.md                 # Examples documentation
│   ├── complete-auto-wire-example.php   # Production-ready auto-wire template
│   ├── DataTableHelpers-Example.php     # Helper functions demo
│   ├── test-abstractdatatable-v2.php    # AbstractDataTable v2 usage
│   └── QUICK-REFERENCE.md               # Quick reference guide
│
├── includes/                     # Core includes
│   ├── class-autoloader.php      # PSR-4 autoloader
│   ├── class-activator.php       # Activation hooks
│   ├── class-deactivator.php     # Deactivation hooks
│   ├── class-loader.php          # Hook management
│   ├── class-init-hooks.php      # WordPress init
│   ├── class-role-manager.php    # Capabilities
│   └── class-upgrade.php         # Version upgrades
│
├── src/
│   ├── Core/                     # Core framework
│   │   ├── DataTableRegistry.php
│   │   ├── AbstractDataTable.php
│   │   └── DataTableInterface.php
│   │
│   ├── Templates/
│   │   ├── DualPanel/            # Dual panel templates
│   │   │   ├── DashboardTemplate.php
│   │   │   ├── PanelLayoutTemplate.php
│   │   │   ├── TabSystemTemplate.php
│   │   │   ├── StatsBoxTemplate.php
│   │   │   └── FiltersTemplate.php
│   │   │
│   │   └── SinglePanel/          # Single panel templates
│   │       ├── DashboardTemplate.php
│   │       ├── PanelLayoutTemplate.php
│   │       ├── StatsBoxTemplate.php
│   │       └── FiltersTemplate.php
│   │
│   ├── Controllers/
│   │   ├── MenuManager.php
│   │   ├── SettingsController.php
│   │   └── Assets/
│   │       ├── AssetController.php
│   │       ├── AssetStrategyInterface.php
│   │       ├── BaseAssets.php
│   │       ├── DualPanelAssets.php
│   │       └── SinglePanelAssets.php
│   │
│   └── Models/
│       └── Settings/
│           ├── SettingsModel.php
│           └── PermissionModel.php
│
└── assets/
    ├── css/
    │   ├── dual-panel.css
    │   └── single-panel.css
    │
    └── js/
        ├── dual-panel/
        │   ├── panel-manager.js
        │   ├── tab-manager.js
        │   ├── modal-integration.js  # ⭐ Auto-wire system
        │   └── auto-refresh.js
        │
        └── single-panel/
            └── datatable.js
```

### Design Patterns

- **Strategy Pattern** - Asset loading strategies (DualPanelAssets, SinglePanelAssets)
- **Singleton Pattern** - AssetController, DataTableRegistry
- **Registry Pattern** - DataTableRegistry untuk manage datatables
- **Template Method** - AbstractDataTable base class
- **Hook System** - WordPress action/filter hooks untuk extensibility

### Asset Strategy Pattern

```
AssetStrategyInterface
        ↑
        |
   BaseAssets (abstract)
        ↑
        |
    ┌───┴───┐
    |       |
DualPanel  SinglePanel
 Assets     Assets
```

Conditional loading based on `should_load()` detection.

---

## 💻 Development

### Setup Development Environment

```bash
# Clone repository
git clone [repo-url] wp-datatable

# Install to WordPress
cd /path/to/wordpress/wp-content/plugins/
ln -s /path/to/wp-datatable wp-datatable

# Activate plugin
wp plugin activate wp-datatable

# Enable debug mode (wp-config.php)
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Create Test Plugin

See `wp-datatable-test` plugin for complete example.

### Coding Standards

- **PSR-4** autoloading
- **WordPress Coding Standards**
- **PHPDoc** untuk semua methods
- **Namespace:** `WPDataTable\*`
- **Prefix:** `wpdt_` untuk hooks, `wpdt-` untuk CSS classes

### File Header Template

```php
<?php
/**
 * File Title
 *
 * @package     WP_DataTable
 * @subpackage  Namespace\Path
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/path/to/File.php
 *
 * Description: Detailed description...
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial implementation
 */
```

### Testing Checklist

**Dual Panel:**
- [x] Stats boxes render
- [x] DataTable loads
- [x] Row click opens panel
- [x] Panel slides smoothly
- [x] Tabs render and switch
- [ ] Keyboard navigation (arrows)
- [ ] Hash navigation works
- [x] Close button works

**Single Panel:**
- [x] Stats boxes render
- [x] Filters render
- [x] DataTable full-width
- [ ] Apply filters works
- [ ] Reset button works
- [ ] Auto-refresh on events

---

## 📝 Changelog

### Version 0.2.0 (2025-12-28) ⭐ NEW

**Auto-Wire System**
- ✅ Filter-based config injection (`wpdt_localize_data`)
- ✅ Modal integration (wp-modal plugin)
- ✅ Auto-wire Edit/Delete buttons (zero JavaScript!)
- ✅ Automatic nonce handling
- ✅ DataTable auto-refresh after operations
- ✅ Success/error notifications
- ✅ Action buttons inside tabs support

**Examples & Documentation**
- ✅ Complete auto-wire example (`examples/complete-auto-wire-example.php`)
- ✅ Examples README (`examples/README.md`)
- ✅ Updated main README with auto-wire documentation
- ✅ wp-datatable-test plugin (working demo)

**Bug Fixes**
- ✅ Fixed modal-integration.js nonce handling
- ✅ Fixed wp-modal JSON response parsing
- ✅ Fixed tab IDs (details/history) alignment
- ✅ Fixed DataTable inside tabs initialization
- ✅ Fixed file permissions (644 for web-accessible files)

**Known Issues:**
- Requires wp-modal plugin as dependency

---

### Version 0.1.0 (2025-11-08)

**Initial Release**
- ✅ Core framework (Registry, Abstract, Interface)
- ✅ Dual panel templates (5 files)
- ✅ Single panel templates (4 files)
- ✅ Asset strategy pattern (4 strategies)
- ✅ Dual panel assets (CSS + 3 JS files)
- ✅ Single panel assets (CSS + 1 JS file)
- ✅ Settings & permissions
- ✅ Hook system (20+ hooks)
- ✅ Test plugin (wp-datatable-test)
- ✅ Documentation (README.md)

**Features:**
- Master-detail dual panel layout
- Full-width single panel layout
- Tab system dengan keyboard navigation
- Filter system (select, search, date range)
- Stats boxes (responsive grid)
- AJAX loading
- Hash navigation
- Event-driven architecture
- Conditional asset loading

---

## 📄 License

GPL v2 or later

---

## 👨‍💻 Author

**arisciwek**

---

## 🙏 Credits

- Dual panel concept ported from **wp-app-core** v1.1.0
- Perfex CRM-style panel transitions
- DataTables.js (MIT License)
- WordPress core (GPL)

---

## 📞 Support

For issues and questions:
- Check documentation above
- Review `wp-datatable-test` plugin for examples
- Check `TODO/TODO-7106-wp-datatable-core-framework.md` for technical details

---

**Made with ❤️ for WordPress developers**
