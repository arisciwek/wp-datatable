# TODO-7106: WP DataTable Core Framework Implementation

**Created:** 2025-11-08
**Version:** 1.0.0
**Status:** Ready for Implementation
**Priority:** HIGH
**Context:** Independent DataTable framework for WordPress plugins

---

## 🎯 Objective

Create **wp-datatable** as independent WordPress plugin framework that provides:
- Reusable DataTable templates (Dual Panel + Single Panel)
- Hook-based extensibility system
- Asset management dengan Strategy Pattern
- Premium/membership ready architecture
- Clean, modern codebase (no backward compatibility baggage)

---

## 📋 Background

**Problems dengan Current Approach:**
- wp-app-core: Development terpusat, plugin lain tidak bisa contribute
- wpDataTables: Terlalu kompleks untuk use case kita
- Setiap plugin define table sendiri, butuh centralized UI framework

**Solution:**
- Extract & enhance DataTable system dari wp-app-core
- Create independent plugin framework
- Support dual panel (master-detail) + single panel (simple listing)
- Ready untuk premium/membership features

---

## 🏗️ Architecture Overview

### Final Structure

```
wp-datatable/
├── wp-datatable.php                    # Main plugin file
├── src/
│   ├── Core/
│   │   ├── DataTableRegistry.php      # Registry untuk semua datatable
│   │   ├── AbstractDataTable.php      # Base class untuk datatable
│   │   └── DataTableInterface.php     # Interface contract
│   │
│   ├── Templates/
│   │   ├── dual-panel/                # Dual panel templates (current dev)
│   │   │   ├── DashboardTemplate.php      # Main orchestrator
│   │   │   ├── PanelLayoutTemplate.php    # Dual panel layout
│   │   │   ├── TabSystemTemplate.php      # Tab navigation
│   │   │   ├── StatsBoxTemplate.php       # Statistics container
│   │   │   └── FiltersTemplate.php        # Filter controls
│   │   │
│   │   └── single-panel/              # Single panel templates (future)
│   │       ├── DashboardTemplate.php      # Main orchestrator
│   │       ├── PanelLayoutTemplate.php    # Single panel layout
│   │       ├── StatsBoxTemplate.php       # Statistics (simplified)
│   │       └── FiltersTemplate.php        # Filter controls (simplified)
│   │
│   └── Controllers/
│       ├── AssetController.php            # Main orchestrator
│       └── Assets/
│           ├── AssetStrategyInterface.php # Interface
│           ├── BaseAssets.php             # Shared/common assets
│           ├── DualPanelAssets.php        # Dual panel specific
│           ├── SinglePanelAssets.php      # Single panel specific
│           └── PremiumAssets.php          # Premium features (future)
│
├── assets/
│   ├── css/
│   │   ├── core.css                   # Base styles
│   │   ├── dual-panel.css             # Dual panel specific
│   │   └── single-panel.css           # Single panel specific
│   │
│   └── js/
│       ├── dual-panel/
│       │   ├── panel-manager.js       # Panel open/close, AJAX
│       │   ├── tab-manager.js         # Tab switching
│       │   └── auto-refresh.js        # Auto-refresh capability
│       │
│       └── single-panel/
│           └── datatable.js           # Simple datatable JS
│
└── README.md                          # Documentation
```

---

## 🎨 Naming Conventions

### Namespace
```php
namespace WPDataTable\Templates\DualPanel;   // Dual panel templates
namespace WPDataTable\Templates\SinglePanel;  // Single panel templates
namespace WPDataTable\Core;                   // Core classes
namespace WPDataTable\Controllers;            // Controllers
namespace WPDataTable\Controllers\Assets;     // Asset strategies
```

### CSS Classes
```css
/* Use wpdt- prefix (wp-datatable) */
.wpdt-datatable-container { }
.wpdt-left-panel { }
.wpdt-right-panel { }
.wpdt-panel-full { }
.wpdt-statistics-container { }
.wpdt-filters-container { }
```

### Hook Names
```php
// Use wpdt_ prefix (clean, no backward compat)
do_action('wpdt_left_panel_content', $config);
do_action('wpdt_right_panel_content', $config);
do_action('wpdt_panel_footer', $config);

apply_filters('wpdt_datatable_tabs', $tabs, $entity);
apply_filters('wpdt_datatable_filters', $filters, $entity);
apply_filters('wpdt_datatable_stats', $stats, $entity);
```

---

## 🎨 File Header Standard

### Main Plugin File Header

```php
<?php
/**
 * Plugin Name: WP DataTable
 * Plugin URI:
 * Description: Reusable DataTable framework untuk WordPress plugins dengan dual & single panel layouts
 * Version: 0.1.0
 * Author: arisciwek
 * Author URI:
 * License: GPL v2 or later
 *
 * @package     WP_DataTable
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/wp-datatable.php
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial development version
 * - Core framework setup
 * - Plugin structure created
 */
```

### Class File Header (Dual Panel Template Example)

```php
<?php
/**
 * Dashboard Template - Dual Panel
 *
 * Main dashboard orchestrator untuk dual panel layout.
 * Ported dari wp-app-core dengan adaptasi namespace & hooks.
 *
 * @package     WP_DataTable
 * @subpackage  Templates\DualPanel
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Templates/dual-panel/DashboardTemplate.php
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Ported dari wp-app-core/src/Views/DataTable/Templates/DashboardTemplate.php
 * - Updated namespace: WPAppCore → WPDataTable\Templates\DualPanel
 * - Updated hooks: wpapp_ → wpdt_
 * - Updated CSS classes: wpapp- → wpdt-
 *
 * Original Source: wp-app-core v1.1.0
 */
```

### Asset File Header (JS Example)

```php
/**
 * Panel Manager - Dual Panel
 *
 * Handles panel open/close, AJAX loading, smooth transitions.
 * Ported dari wp-app-core dengan adaptasi selectors & hooks.
 *
 * @package     WP_DataTable
 * @subpackage  Assets\JS\DualPanel
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/assets/js/dual-panel/panel-manager.js
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Ported dari wp-app-core/assets/js/datatable/wpapp-panel-manager.js
 * - Updated selectors: wpapp- → wpdt-
 * - Updated events: wpapp: → wpdt:
 * - Updated script handle references
 *
 * Original Source: wp-app-core v1.1.0
 */
```

---

## 📂 Standard Files (Follow wp-customer Pattern)

Semua file berikut WAJIB ada, mengikuti pattern dari wp-customer (nama file & path sama, isi disesuaikan):

### includes/ (7 files)
1. `class-autoloader.php` - PSR-4 autoloader
2. `class-activator.php` - Plugin activation
3. `class-deactivator.php` - Plugin deactivation
4. `class-loader.php` - Hook management
5. `class-init-hooks.php` - WordPress init hooks
6. `class-role-manager.php` - Custom capabilities
7. `class-upgrade.php` - Version upgrades

### src/Controllers/ (4 files)
1. `MenuManager.php` - Admin menu registration
2. `SettingsController.php` - Settings CRUD
3. `AssetController.php` - Asset management (DataTable specific)
4. `Assets/` folder - Asset strategies (DataTable specific)

### src/Models/Settings/ (2 files)
1. `SettingsModel.php` - Settings database model
2. `PermissionModel.php` - Permission checks

### src/Core/ (3 files - DataTable Specific)
1. `DataTableRegistry.php` - Registry pattern
2. `AbstractDataTable.php` - Base class
3. `DataTableInterface.php` - Interface contract

**Total Standard Files: 14 files**
- 7 includes files (following wp-customer)
- 2 standard controller files (following wp-customer)
- 2 standard model files (following wp-customer)
- 3 core files (DataTable specific)

---

## 📋 Porting Guidelines

### IMPORTANT: Two Types of Copying

**Type A: Copy from wp-app-core (Templates & Assets)**
- Full copy of file content
- Find & Replace untuk namespace, hooks, CSS classes
- Preserve all functionality
- Example: DashboardTemplate.php, panel-manager.js

**Type B: Copy Pattern from wp-customer (Standard Files)**
- Copy structure/pattern only
- Adapt content untuk wp-datatable context
- Keep same file name & path
- Example: class-autoloader.php, MenuManager.php

**DO (for wp-app-core files):**
✅ COPY file asli dari wp-app-core
✅ Update header dengan format standar
✅ Update namespace (Find & Replace)
✅ Update hooks (Find & Replace)
✅ Update CSS classes (Find & Replace)
✅ Test functionality
✅ Document changes in changelog

**DO (for wp-customer files):**
✅ COPY pattern/structure dari wp-customer
✅ Adapt content untuk wp-datatable
✅ Keep same file name & path
✅ Update header dengan format standar
✅ Update class names (Customer → DataTable)
✅ Update namespaces (WPCustomer → WPDataTable)
✅ Test functionality

**DON'T:**
❌ Rewrite from scratch
❌ Skip copying original logic
❌ Remove functionality tanpa alasan
❌ Forget to update paths in header
❌ Skip changelog entry
❌ Change file names from wp-customer pattern
❌ Skip standard files (all 14 files required)

### Find & Replace Checklist

**PHP Files:**
```bash
# Namespace
WPAppCore\Views\DataTable\Templates → WPDataTable\Templates\DualPanel

# Hooks (action)
do_action('wpapp_ → do_action('wpdt_

# Hooks (filter)
apply_filters('wpapp_ → apply_filters('wpdt_

# CSS Classes
wpapp- → wpdt-

# Constants (if any)
WP_APP_CORE → WP_DATATABLE
WPAPP_ → WPDT_
```

**CSS Files:**
```bash
# Classes
.wpapp- → .wpdt-

# IDs
#wpapp- → #wpdt-

# Comments referencing wp-app-core
wp-app-core → wp-datatable
wpapp → wpdt
```

**JS Files:**
```bash
# Selectors
.wpapp- → .wpdt-
#wpapp- → #wpdt-

# Events
wpapp: → wpdt:

# Object names
wpAppPanelManager → wpdtPanelManager
wpStateMachineData → wpdtData (or context-specific)

# Script handles
'wpapp- → 'wpdt-
```

---

## 📅 Implementation Phases

### Phase 1: Core Framework (Week 1)

**Goal:** Setup plugin structure & core classes

**Tasks:**

**1. Plugin Structure:**
- [x] Create plugin folder: `/wp-datatable/`
- [x] Create main file: `wp-datatable.php`
  - [x] Copy header format dari contoh di atas
  - [x] Version: `0.1.0`
  - [x] Author: `arisciwek`
  - [x] Path: `/wp-datatable/wp-datatable.php`
- [x] Create folder structure:
  ```
  wp-datatable/
  ├── wp-datatable.php                    # Main plugin file
  │
  ├── includes/                           # Standard includes (follow wp-customer)
  │   ├── class-autoloader.php
  │   ├── class-activator.php
  │   ├── class-deactivator.php
  │   ├── class-loader.php
  │   ├── class-init-hooks.php
  │   ├── class-role-manager.php
  │   └── class-upgrade.php
  │
  ├── src/
  │   ├── Core/                           # Core DataTable framework
  │   │   ├── DataTableRegistry.php
  │   │   ├── AbstractDataTable.php
  │   │   └── DataTableInterface.php
  │   │
  │   ├── Templates/                      # Template classes
  │   │   ├── dual-panel/
  │   │   │   ├── DashboardTemplate.php
  │   │   │   ├── PanelLayoutTemplate.php
  │   │   │   ├── TabSystemTemplate.php
  │   │   │   ├── StatsBoxTemplate.php
  │   │   │   └── FiltersTemplate.php
  │   │   │
  │   │   └── single-panel/
  │   │       ├── DashboardTemplate.php
  │   │       ├── PanelLayoutTemplate.php
  │   │       ├── StatsBoxTemplate.php
  │   │       └── FiltersTemplate.php
  │   │
  │   ├── Controllers/                    # Controllers
  │   │   ├── MenuManager.php             # Standard (follow wp-customer)
  │   │   ├── SettingsController.php      # Standard (follow wp-customer)
  │   │   ├── AssetController.php         # DataTable specific
  │   │   └── Assets/                     # Asset strategies
  │   │       ├── AssetStrategyInterface.php
  │   │       ├── BaseAssets.php
  │   │       ├── DualPanelAssets.php
  │   │       ├── SinglePanelAssets.php
  │   │       └── PremiumAssets.php
  │   │
  │   └── Models/                         # Models
  │       └── Settings/                   # Standard (follow wp-customer)
  │           ├── SettingsModel.php
  │           └── PermissionModel.php
  │
  ├── assets/                             # Frontend assets
  │   ├── css/
  │   │   ├── core.css
  │   │   ├── dual-panel.css
  │   │   └── single-panel.css
  │   │
  │   └── js/
  │       ├── dual-panel/
  │       │   ├── panel-manager.js
  │       │   ├── tab-manager.js
  │       │   └── auto-refresh.js
  │       │
  │       └── single-panel/
  │           └── datatable.js
  │
  └── languages/                          # Translations
      └── wp-datatable-id_ID.mo
  ```

**2. Constants & Main Plugin Class:**
- [x] Define constants in `wp-datatable.php`:
  ```php
  define('WP_DATATABLE_VERSION', '0.1.0');
  define('WP_DATATABLE_FILE', __FILE__);
  define('WP_DATATABLE_PATH', plugin_dir_path(__FILE__));
  define('WP_DATATABLE_URL', plugin_dir_url(__FILE__));
  define('WP_DATATABLE_DEVELOPMENT', true);
  ```
- [x] Create main plugin class `WPDataTable` in `wp-datatable.php`:
  - [x] Singleton pattern
  - [x] Load dependencies
  - [x] Initialize components

**3. Standard Includes Files (Follow wp-customer pattern):**

**3.1. includes/class-autoloader.php**
- [x] Copy pattern dari `/wp-customer/includes/class-autoloader.php`
- [x] Update header (format standar, v0.1.0)
- [x] Class name: `WPDataTableAutoloader`
- [x] Namespace: `WPDataTable\`
- [x] Base path: `WP_DATATABLE_PATH`
- [x] Test: Autoload working

**3.2. includes/class-activator.php**
- [x] Copy pattern dari `/wp-customer/includes/class-activator.php`
- [x] Update header (format standar, v0.1.0)
- [x] Class name: `WPDataTable_Activator`
- [x] Methods:
  - [x] `activate()` - Plugin activation
  - [x] Create necessary DB tables (if needed)
  - [x] Set default options
  - [x] Flush rewrite rules
- [x] Hook: `register_activation_hook()`

**3.3. includes/class-deactivator.php**
- [x] Copy pattern dari `/wp-customer/includes/class-deactivator.php`
- [x] Update header (format standar, v0.1.0)
- [x] Class name: `WPDataTable_Deactivator`
- [x] Methods:
  - [x] `deactivate()` - Plugin deactivation
  - [x] Flush rewrite rules
- [x] Hook: `register_deactivation_hook()`

**3.4. includes/class-loader.php**
- [x] Copy pattern dari `/wp-customer/includes/class-loader.php`
- [x] Update header (format standar, v0.1.0)
- [x] Class name: `WPDataTable_Loader`
- [x] Methods:
  - [x] `add_action()` - Register actions
  - [x] `add_filter()` - Register filters
  - [x] `run()` - Execute hooks
- [x] Purpose: Central hook management

**3.5. includes/class-init-hooks.php**
- [x] Copy pattern dari `/wp-customer/includes/class-init-hooks.php`
- [x] Update header (format standar, v0.1.0)
- [x] Class name: `WPDataTable_Init_Hooks`
- [x] Methods:
  - [x] `init()` - WordPress init action
  - [x] `admin_init()` - Admin init
  - [x] Load textdomain
  - [x] Register custom post types (if needed)
- [x] Hook to WordPress init

**3.6. includes/class-role-manager.php**
- [x] Copy pattern dari `/wp-customer/includes/class-role-manager.php`
- [x] Update header (format standar, v0.1.0)
- [x] Class name: `WPDataTable_Role_Manager`
- [x] Methods:
  - [x] `add_capabilities()` - Add custom capabilities
  - [x] `remove_capabilities()` - Remove on deactivation
- [x] Custom caps: `manage_datatables`, `view_datatables`

**3.7. includes/class-upgrade.php**
- [x] Copy pattern dari `/wp-customer/includes/class-upgrade.php`
- [x] Update header (format standar, v0.1.0)
- [x] Class name: `WPDataTable_Upgrade`
- [x] Methods:
  - [x] `check_version()` - Check if upgrade needed
  - [x] `upgrade_to_010()` - Version-specific upgrades
  - [x] Update version option
- [x] Future: Handle DB migrations

**4. Standard Controller Files (Follow wp-customer pattern):**

**4.1. src/Controllers/MenuManager.php**
- [x] Copy pattern dari `/wp-customer/src/Controllers/MenuManager.php`
- [x] Update header (format standar, v0.1.0)
- [x] Namespace: `WPDataTable\Controllers`
- [x] Class name: `MenuManager`
- [x] Methods:
  - [x] `register_menus()` - Register admin menus
  - [x] `render_dashboard()` - Main settings page
- [x] Menu slug: `wp-datatable`
- [x] Hook: `admin_menu`

**4.2. src/Controllers/SettingsController.php**
- [x] Copy pattern dari `/wp-customer/src/Controllers/SettingsController.php`
- [x] Update header (format standar, v0.1.0)
- [x] Namespace: `WPDataTable\Controllers`
- [x] Class name: `SettingsController`
- [x] Methods:
  - [x] `register_settings()` - Register settings
  - [x] `save_settings()` - Handle save
  - [x] `get_settings()` - Retrieve settings
- [x] Settings group: `wpdt_settings`

**5. Standard Model Files (Follow wp-customer pattern):**

**5.1. src/Models/Settings/SettingsModel.php**
- [x] Copy pattern dari `/wp-customer/src/Models/Settings/SettingsModel.php`
- [x] Update header (format standar, v0.1.0)
- [x] Namespace: `WPDataTable\Models\Settings`
- [x] Class name: `SettingsModel`
- [x] Methods:
  - [x] `get_option()` - Get setting value
  - [x] `update_option()` - Update setting
  - [x] `get_all()` - Get all settings
- [x] Option name: `wpdt_settings`

**5.2. src/Models/Settings/PermissionModel.php**
- [x] Copy pattern dari `/wp-customer/src/Models/Settings/PermissionModel.php`
- [x] Update header (format standar, v0.1.0)
- [x] Namespace: `WPDataTable\Models\Settings`
- [x] Class name: `PermissionModel`
- [x] Methods:
  - [x] `can_manage_datatables()` - Check permission
  - [x] `can_view_datatables()` - Check permission
  - [x] `get_user_capabilities()` - Get caps
- [x] Hook to WordPress capability checks

**6. Core Classes (NEW - Not Ported):**
- [x] Create `src/Core/DataTableRegistry.php`:
  - [x] Header dengan format standar
  - [x] Version: `0.1.0`
  - [x] Namespace: `WPDataTable\Core`
  - [x] Implement registry pattern
- [x] Create `src/Core/AbstractDataTable.php`:
  - [x] Header dengan format standar
  - [x] Version: `0.1.0`
  - [x] Namespace: `WPDataTable\Core`
  - [x] Abstract methods untuk template pattern
- [x] Create `src/Core/DataTableInterface.php`:
  - [x] Header dengan format standar
  - [x] Version: `0.1.0`
  - [x] Namespace: `WPDataTable\Core`
  - [x] Interface contract

**7. Testing Phase 1:**
- [ ] Verify all standard files exist (following wp-customer pattern):
  - [ ] All 7 includes files created
  - [ ] All 2 standard controller files created
  - [ ] All 2 standard model files created
  - [ ] All 3 core files created
- [ ] Activate plugin tanpa error
- [ ] Test autoloader working
- [ ] Test core class instantiation
- [ ] Test activation hook (check options set)
- [ ] Test deactivation hook
- [ ] Test loader hook management
- [ ] Test role/capability creation
- [ ] Test settings model CRUD
- [ ] Test permission model checks

**Success Criteria:**
- ✅ Plugin dapat diaktifkan tanpa error
- ✅ All 14 standard files exist & properly structured
- ✅ All headers follow standard format (v0.1.0, arisciwek, correct paths)
- ✅ Autoloader working (can load classes from src/)
- ✅ Core classes dapat diinstantiate
- ✅ Activation creates default options
- ✅ Deactivation cleans up properly
- ✅ Role manager adds custom capabilities
- ✅ Settings can be saved/retrieved
- ✅ No PHP warnings/errors
- ✅ Following wp-customer pattern exactly

---

### Phase 2: Dual Panel Templates (Week 2-3)

**Goal:** COPY & adapt templates dari wp-app-core

**Source Files Location:**
```
/wp-app-core/src/Views/DataTable/Templates/
├── DashboardTemplate.php
├── PanelLayoutTemplate.php
├── TabSystemTemplate.php
├── StatsBoxTemplate.php
└── FiltersTemplate.php
```

**Tasks:**

**Week 2 - Day 1-2: DashboardTemplate**
- [x] **COPY** `wp-app-core/.../DashboardTemplate.php` to `src/Templates/dual-panel/DashboardTemplate.php`
- [x] Update file header:
  - [ ] Change to format standar (lihat contoh di atas)
  - [ ] Version: `0.1.0`
  - [ ] Author: `arisciwek`
  - [ ] Path: `/wp-datatable/src/Templates/dual-panel/DashboardTemplate.php`
  - [ ] Add changelog: "Ported dari wp-app-core v1.1.0"
- [ ] Find & Replace:
  - [ ] `namespace WPAppCore\Views\DataTable\Templates;` → `namespace WPDataTable\Templates\DualPanel;`
  - [ ] `do_action('wpapp_` → `do_action('wpdt_`
  - [ ] `apply_filters('wpapp_` → `apply_filters('wpdt_`
  - [ ] `wpapp-` → `wpdt-` (CSS classes)
  - [ ] `WP_APP_CORE` → `WP_DATATABLE` (constants)
- [ ] Update use statements:
  - [ ] `use WPAppCore\...` → `use WPDataTable\...`
- [ ] Test: Render tanpa error

**Week 2 - Day 2-3: PanelLayoutTemplate**
- [ ] **COPY** `wp-app-core/.../PanelLayoutTemplate.php` to `src/Templates/dual-panel/PanelLayoutTemplate.php`
- [ ] Update file header (same format as above)
  - [ ] Version: `0.1.0`
  - [ ] Changelog: "Ported dari wp-app-core, updated namespace & hooks"
- [ ] Find & Replace (same checklist as DashboardTemplate)
- [ ] Test: Left/right panel rendering
- [ ] Test: Panel sliding animation
- [ ] Test: 45% / 55% split

**Week 2 - Day 3-4: TabSystemTemplate**
- [ ] **COPY** `wp-app-core/.../TabSystemTemplate.php` to `src/Templates/dual-panel/TabSystemTemplate.php`
- [ ] Update file header (same format)
  - [ ] Version: `0.1.0`
  - [ ] Changelog entry
- [ ] Find & Replace (same checklist)
- [ ] Test: Tab registration via filter
- [ ] Test: Tab navigation (click)
- [ ] Test: Tab navigation (keyboard arrows)
- [ ] Test: Priority-based sorting
- [ ] Test: Hash navigation (#entity-123&tab=details)
- [ ] Test: Direct inclusion pattern
- [ ] Test: AJAX pattern

**Week 2 - Day 5: StatsBoxTemplate**
- [ ] **COPY** `wp-app-core/.../StatsBoxTemplate.php` to `src/Templates/dual-panel/StatsBoxTemplate.php`
- [ ] Update file header (same format)
  - [ ] Version: `0.1.0`
  - [ ] Note: "Pure infrastructure - container + hook only"
- [ ] Find & Replace (same checklist)
- [ ] Test: Stats rendering via action hook
- [ ] Test: Empty state (no stats registered)

**Week 3 - Day 1-2: FiltersTemplate**
- [ ] **COPY** `wp-app-core/.../FiltersTemplate.php` to `src/Templates/dual-panel/FiltersTemplate.php`
- [ ] Update file header (same format)
  - [ ] Version: `0.1.0`
  - [ ] Changelog entry
- [ ] Find & Replace (same checklist)
- [ ] Test: Filter registration via filter
- [ ] Test: Select filter rendering
- [ ] Test: Search filter rendering
- [ ] Test: Date range filter (placeholder)
- [ ] Test: Multiple filters

**Week 3 - Day 3: Integration Testing**
- [ ] Test all 5 templates together
- [ ] Test with stats enabled
- [ ] Test with stats disabled
- [ ] Test with tabs enabled
- [ ] Test with tabs disabled
- [ ] Test with filters
- [ ] Test without filters
- [ ] Test error states

**Week 3 - Day 4-5: Documentation**
- [ ] Document dual panel usage
- [ ] Create example integration
- [ ] Document all hooks
- [ ] Document template methods

**Success Criteria:**
- ✅ All 5 templates COPIED (not rewritten)
- ✅ All headers follow standard format
- ✅ All version numbers: `0.1.0`
- ✅ All namespaces updated correctly
- ✅ All hooks updated: `wpapp_` → `wpdt_`
- ✅ All CSS classes updated: `wpapp-` → `wpdt-`
- ✅ No references to `WPAppCore` namespace
- ✅ No references to `wpapp_` hooks
- ✅ Templates working end-to-end
- ✅ Integration tests pass
- ✅ Documentation complete

---

### Phase 3: Dual Panel Assets (Week 3-4)

**Goal:** COPY & adapt CSS/JS dari wp-app-core, implement Strategy Pattern

**Source Files Location:**
```
/wp-app-core/assets/
├── css/datatable/
│   └── wpapp-datatable.css
└── js/datatable/
    ├── wpapp-panel-manager.js
    ├── wpapp-tab-manager.js
    └── wpapp-datatable-auto-refresh.js
```

**Tasks:**

**Week 3 - Day 1: CSS Port**
- [ ] **COPY** `wp-app-core/assets/css/datatable/wpapp-datatable.css` to temporary location
- [ ] Analyze CSS, split into:
  - [ ] **Common styles** → `assets/css/core.css`
    - Base variables
    - Common utility classes
    - WordPress admin overrides
  - [ ] **Dual panel styles** → `assets/css/dual-panel.css`
    - Panel layout (45% / 55%)
    - Sliding animations
    - Tab system styles
    - Panel-specific components
- [ ] Update `core.css`:
  - [ ] Add file header (format standar, version `0.1.0`)
  - [ ] Path: `/wp-datatable/assets/css/core.css`
  - [ ] Changelog: "Extracted from wp-app-core wpapp-datatable.css"
  - [ ] Find & Replace: `.wpapp-` → `.wpdt-`
  - [ ] Find & Replace: `#wpapp-` → `#wpdt-`
- [ ] Update `dual-panel.css`:
  - [ ] Add file header (format standar, version `0.1.0`)
  - [ ] Path: `/wp-datatable/assets/css/dual-panel.css`
  - [ ] Changelog: "Ported from wp-app-core, dual panel specific styles"
  - [ ] Find & Replace: same as core.css
- [ ] Test: Full width pattern (negative margins)
- [ ] Test: Responsive behavior
- [ ] Test: Panel transitions

**Week 3 - Day 2: JS Port - Panel Manager**
- [ ] **COPY** `wp-app-core/.../wpapp-panel-manager.js` to `assets/js/dual-panel/panel-manager.js`
- [ ] Update file header:
  - [ ] Use JS comment format (lihat contoh di atas)
  - [ ] Version: `0.1.0`
  - [ ] Author: `arisciwek`
  - [ ] Path: `/wp-datatable/assets/js/dual-panel/panel-manager.js`
  - [ ] Changelog: "Ported from wp-app-core, updated selectors & events"
- [ ] Find & Replace:
  - [ ] `.wpapp-` → `.wpdt-` (selectors)
  - [ ] `#wpapp-` → `#wpdt-` (selectors)
  - [ ] `wpapp:` → `wpdt:` (custom events)
  - [ ] `wpAppPanelManager` → `wpdtPanelManager` (object names)
  - [ ] `'wpapp-` → `'wpdt-` (script handles)
- [ ] Test: Panel open on row click
- [ ] Test: Panel close on button click
- [ ] Test: AJAX loading
- [ ] Test: Smooth transitions
- [ ] Test: Multiple panels on same page

**Week 3 - Day 3: JS Port - Tab Manager**
- [ ] **COPY** `wp-app-core/.../wpapp-tab-manager.js` to `assets/js/dual-panel/tab-manager.js`
- [ ] Update file header (same format as panel-manager.js)
  - [ ] Version: `0.1.0`
  - [ ] Changelog entry
- [ ] Find & Replace (same checklist as panel-manager.js)
- [ ] Test: Tab switching (click)
- [ ] Test: Tab switching (keyboard arrows)
- [ ] Test: Hash navigation
- [ ] Test: Browser back/forward
- [ ] Test: AJAX tab content loading
- [ ] Test: Multiple tab systems on same page

**Week 3 - Day 4: JS Port - Auto Refresh**
- [ ] **COPY** `wp-app-core/.../wpapp-datatable-auto-refresh.js` to `assets/js/dual-panel/auto-refresh.js`
- [ ] Update file header (same format)
  - [ ] Version: `0.1.0`
  - [ ] Changelog entry
- [ ] Find & Replace (same checklist)
- [ ] Test: Auto-refresh interval
- [ ] Test: Manual refresh button
- [ ] Test: Refresh indicator
- [ ] Test: Pause on user interaction
- [ ] Test: Resume after inactivity

**Week 4 - Day 1-2: Asset Strategy Pattern**
- [x] Create `Controllers/Assets/` folder
- [x] Create `AssetStrategyInterface.php`:
  - [x] File header (format standar, version `0.1.0`)
  - [x] Methods: `enqueue_styles()`, `enqueue_scripts()`, `get_localize_data()`, `should_load()`
- [x] Create `BaseAssets.php`:
  - [x] File header (format standar, version `0.1.0`)
  - [x] Abstract class implementing interface
  - [x] Common asset loading (DataTables, jQuery, core.css)
  - [x] Common localize data (ajaxUrl, nonce, i18n)
- [x] Create `DualPanelAssets.php`:
  - [x] File header (format standar, version `0.1.0`)
  - [x] Extends BaseAssets
  - [x] Enqueue dual-panel.css
  - [x] Enqueue panel-manager.js
  - [x] Enqueue tab-manager.js
  - [x] Enqueue auto-refresh.js
  - [x] Localize with dual panel config
  - [x] `should_load()`: Check `wpdt_use_dual_panel` filter

**Week 4 - Day 3: Asset Controller**
- [x] Create `AssetController.php`:
  - [x] File header (format standar, version `0.1.0`)
  - [x] Register all strategies
  - [x] Hook to `admin_enqueue_scripts`
  - [x] Detect which layout is active
  - [x] Load only applicable strategies
  - [x] Singleton pattern implementation
- [ ] Test: Conditional loading
- [ ] Test: Only dual panel assets load when dual panel active
- [ ] Test: Multiple strategies can coexist

**Week 4 - Day 4-5: Integration & Testing**
- [ ] Test complete asset pipeline:
  - [ ] core.css loads on all datatable pages
  - [ ] dual-panel.css loads only for dual panel
  - [ ] All 3 JS files load for dual panel
  - [ ] Localized data present in JS
- [ ] Test asset loading order (dependencies)
- [ ] Test no asset conflicts
- [ ] Performance test (load time)
- [ ] Documentation: Asset system

**Success Criteria:**
- ✅ All CSS/JS files COPIED (not rewritten)
- ✅ All headers follow standard format
- ✅ All version numbers: `0.1.0`
- ✅ All selectors updated: `wpapp-` → `wpdt-`
- ✅ All events updated: `wpapp:` → `wpdt:`
- ✅ Strategy pattern implemented
- ✅ Conditional loading working
- ✅ JS events working (panel, tabs, refresh)
- ✅ Auto-asset loading (Plug & Play) working
- ✅ No console errors
- ✅ Smooth animations
- ✅ Documentation complete

---

### Phase 4: Single Panel Templates (Week 4-5)

**Goal:** Create simplified templates untuk single panel layout

**Tasks:**

**Templates:**
- [x] Create `Templates/single-panel/` folder
- [x] Implement `DashboardTemplate.php`:
  - [x] Namespace: `WPDataTable\Templates\SinglePanel`
  - [x] Simplified orchestration (no tabs)
  - [x] Stats & filters support
- [x] Implement `PanelLayoutTemplate.php`:
  - [x] Full width panel
  - [x] Simple layout (no left/right split)
  - [x] Hook: `wpdt_panel_content`
- [x] Implement `StatsBoxTemplate.php`:
  - [x] Reuse or simplify dari dual panel
- [x] Implement `FiltersTemplate.php`:
  - [x] Reuse or simplify dari dual panel

**Assets:**
- [x] Create `assets/css/single-panel.css`:
  - [x] Full width layout styles
  - [x] Simplified components
- [x] Create `assets/js/single-panel/datatable.js`:
  - [x] Simple DataTable initialization
  - [x] Filter handling
  - [x] Event-driven refresh capability
- [x] Implement `SinglePanelAssets.php` strategy:
  - [x] Enqueue single-panel.css
  - [x] Enqueue datatable.js
  - [x] Localize with single panel data
- [x] Register SinglePanelAssets in AssetController

**Success Criteria:**
- ✅ Single panel templates created (4 files)
- ✅ Simple & clean layout
- ✅ Asset strategy implemented
- ✅ Conditional asset loading ready
- [ ] Documentation (pending Phase 7)
- [ ] Testing (pending Phase 5)

---

### Phase 5: Testing & Integration (Week 5-6)

**Goal:** Test dengan plugin baru (belum pakai wp-app-core)

**Tasks:**

**Create Test Plugin:**
- [x] Create test plugin: `wp-datatable-test`
- [x] Test Dual Panel:
  - [x] Create entity dengan master-detail
  - [x] Register tabs via `wpdt_datatable_tabs`
  - [x] Register stats via `wpdt_statistics_content`
  - [x] Create menu page for dual panel test
  - [ ] Test panel open/close (manual testing via browser)
  - [ ] Test tab switching (manual testing via browser)
  - [ ] Test hash navigation (manual testing via browser)
- [x] Test Single Panel:
  - [x] Create simple listing entity
  - [x] Register stats & filters
  - [x] Create menu page for single panel test
  - [ ] Test full width layout (manual testing via browser)
  - [ ] Test filter functionality (manual testing via browser)

**Edge Cases:**
- [ ] Test with no tabs registered
- [ ] Test with no stats registered
- [ ] Test with no filters registered
- [ ] Test with multiple entities on same page
- [ ] Test browser back/forward navigation
- [ ] Test keyboard navigation (arrow keys)
- [ ] Test mobile responsive

**Performance:**
- [ ] Test asset loading (only needed assets)
- [ ] Test multiple DataTables on same page
- [ ] Test with large datasets (1000+ rows)

**Success Criteria:**
- ✅ All test cases pass
- ✅ No JS errors in console
- ✅ No PHP errors
- ✅ Smooth animations
- ✅ Mobile responsive

---

### Phase 6: Premium Assets (Future)

**Goal:** Prepare untuk membership/premium features

**Tasks:**
- [ ] Implement `PremiumAssets.php` strategy:
  - [ ] Check membership status via filter
  - [ ] Enqueue premium CSS
  - [ ] Enqueue premium JS (advanced-filters, export-enhanced)
  - [ ] Conditional loading based on tier
- [ ] Create premium asset files:
  - [ ] `assets/css/premium.css`
  - [ ] `assets/css/themes/premium-dark.css`
  - [ ] `assets/js/premium/advanced-filters.js`
  - [ ] `assets/js/premium/export-enhanced.js`
  - [ ] `assets/js/premium/real-time-sync.js`
- [ ] Add membership hooks:
  - [ ] `wpdt_has_premium_access` filter
  - [ ] `wpdt_membership_tier` filter
- [ ] Documentation: Premium features integration

**Success Criteria:**
- ✅ Premium assets load only for premium users
- ✅ Tier-based feature access
- ✅ Graceful degradation for free users

---

### Phase 7: Documentation (Week 7)

**Goal:** Complete documentation untuk developers

**Tasks:**

**Main README.md:**
- [ ] Overview & features
- [ ] Installation instructions
- [ ] Quick start guide
- [ ] Dual panel example
- [ ] Single panel example
- [ ] Hook reference
- [ ] FAQ

**API Documentation:**
- [ ] Core classes documentation
- [ ] Template classes documentation
- [ ] Asset Controller documentation
- [ ] Hook reference (complete list)
- [ ] Filter reference (complete list)

**Guides:**
- [ ] Step-by-step: Create dual panel datatable
- [ ] Step-by-step: Create single panel datatable
- [ ] Step-by-step: Register custom tab
- [ ] Step-by-step: Add custom filter
- [ ] Step-by-step: Add custom stats
- [ ] Migration guide (wp-app-core → wp-datatable)

**Examples:**
- [ ] Example plugin dengan dual panel
- [ ] Example plugin dengan single panel
- [ ] Example premium integration

**Success Criteria:**
- ✅ Documentation lengkap & clear
- ✅ All examples tested & working
- ✅ Migration guide accurate

---

## 🎯 Success Criteria (Overall)

**v1.0 akan dianggap sukses jika:**

1. ✅ Plugin lain bisa register datatable via Registry
2. ✅ Dual panel layout works 100% seperti wp-app-core
3. ✅ Single panel layout works untuk simple listing
4. ✅ Tab system works dengan dual pattern (Direct + AJAX)
5. ✅ Hook system compatible & well-documented
6. ✅ Auto-asset loading works (Plug & Play)
7. ✅ Asset Strategy Pattern flexible & extensible
8. ✅ Ready untuk premium/membership features
9. ✅ Battle-tested dengan minimal 1 test plugin
10. ✅ Documentation lengkap dengan examples

---

## 🔧 Technical Specifications

### Core Classes

**DataTableRegistry:**
```php
namespace WPDataTable\Core;

class DataTableRegistry {
    public static function register(AbstractDataTable $table): void;
    public static function unregister(string $name): void;
    public static function get(string $name): ?AbstractDataTable;
    public static function all(): array;
}
```

**AbstractDataTable:**
```php
namespace WPDataTable\Core;

abstract class AbstractDataTable implements DataTableInterface {
    abstract public function getName(): string;
    abstract public function getColumns(): array;
    abstract public function getData(array $args): array;
    public function getBulkActions(): array { return []; }
    public function getFilters(): array { return []; }
}
```

### Template Classes

**DualPanel\DashboardTemplate:**
```php
namespace WPDataTable\Templates\DualPanel;

class DashboardTemplate {
    public static function render(array $config): void;
    private static function validate_config(array $config): array;
    private static function ensure_assets_loaded(): void;
}
```

**DualPanel\PanelLayoutTemplate:**
```php
namespace WPDataTable\Templates\DualPanel;

class PanelLayoutTemplate {
    public static function render(array $config): void;
    private static function render_left_panel(array $config): void;
    private static function render_right_panel(array $config): void;
}
```

### Asset Strategy

**AssetStrategyInterface:**
```php
namespace WPDataTable\Controllers\Assets;

interface AssetStrategyInterface {
    public function enqueue_styles(): void;
    public function enqueue_scripts(): void;
    public function get_localize_data(): array;
    public function should_load(): bool;
}
```

---

## 📊 Hook Reference

### Dual Panel Hooks

**Panel Content:**
- `wpdt_left_panel_content` (action) - Left panel main content
- `wpdt_right_panel_content` (action) - Right panel content (no tabs)
- `wpdt_right_panel_footer` (action) - Right panel footer

**Tabs:**
- `wpdt_datatable_tabs` (filter) - Register tabs
- `wpdt_before_tab_template` (action) - Before tab template
- `wpdt_after_tab_template` (action) - After tab template
- `wpdt_no_tabs_content` (action) - No tabs registered

**Stats & Filters:**
- `wpdt_datatable_stats` (filter) - Register statistics
- `wpdt_datatable_filters` (filter) - Register filters
- `wpdt_statistics_content` (action) - Render statistics content

**Assets:**
- `wpdt_use_dual_panel` (filter) - Signal dual panel usage
- `wpdt_asset_strategies` (filter) - Register custom strategies
- `wpdt_is_datatable_page` (filter) - Check if datatable page

### Single Panel Hooks

**Panel Content:**
- `wpdt_panel_content` (action) - Full panel content

**Stats & Filters:**
- `wpdt_datatable_stats` (filter) - Register statistics (same as dual)
- `wpdt_datatable_filters` (filter) - Register filters (same as dual)
- `wpdt_statistics_content` (action) - Render statistics (same as dual)

**Assets:**
- `wpdt_use_single_panel` (filter) - Signal single panel usage

### Premium Hooks (Future)

- `wpdt_has_premium_access` (filter) - Check premium access
- `wpdt_membership_tier` (filter) - Get membership tier

---

## 🚀 Usage Examples

### Dual Panel Example

```php
use WPDataTable\Templates\DualPanel\DashboardTemplate;

// Signal dual panel usage
add_filter('wpdt_use_dual_panel', '__return_true');

// Render dashboard
DashboardTemplate::render([
    'entity' => 'state_machine',
    'title' => 'State Machines',
    'ajax_action' => 'get_machine_details',
    'has_stats' => true,
    'has_tabs' => true,
]);

// Register tabs
add_filter('wpdt_datatable_tabs', function($tabs, $entity) {
    if ($entity !== 'state_machine') return $tabs;

    return [
        'states' => [
            'title' => 'States',
            'priority' => 10
        ],
        'transitions' => [
            'title' => 'Transitions',
            'priority' => 20
        ]
    ];
}, 10, 2);

// Register left panel content
add_action('wpdt_left_panel_content', function($config) {
    if ($config['entity'] !== 'state_machine') return;
    include __DIR__ . '/views/machines/datatable.php';
});
```

### Single Panel Example

```php
use WPDataTable\Templates\SinglePanel\DashboardTemplate;

// Signal single panel usage
add_filter('wpdt_use_single_panel', '__return_true');

// Render dashboard
DashboardTemplate::render([
    'entity' => 'activity_log',
    'title' => 'Activity Logs',
    'has_stats' => true,
    'has_filters' => true,
]);

// Register panel content
add_action('wpdt_panel_content', function($config) {
    if ($config['entity'] !== 'activity_log') return;
    include __DIR__ . '/views/logs/datatable.php';
});
```

---

## 📝 Notes

### Design Decisions

1. **No Backward Compatibility**
   - Clean start, no legacy baggage
   - Pure WPDataTable namespace
   - wpdt_ hooks dari awal
   - Fresh start untuk best practices

2. **Separated Folders**
   - dual-panel/ dan single-panel/ terpisah
   - Clear separation of concerns
   - Independent evolution
   - Easier maintenance

3. **Strategy Pattern for Assets**
   - Flexible & extensible
   - Easy to add PremiumAssets
   - Conditional loading
   - Better performance

4. **Hook-Based Extensibility**
   - WordPress best practices
   - Plugin lain easy to integrate
   - No tight coupling
   - Future-proof

### References

- Discussion: `/DISKUSI/task-7106.md`
- Source: `wp-app-core/src/Views/DataTable/`
- Inspiration: wpDataTables plugin (structure only)

---

## ✅ Checklist Summary

**Phase 1: Core Framework**
- [x] Plugin structure
- [x] Core classes (Registry, Abstract, Interface)
- [ ] Unit tests
- [ ] Documentation

**Phase 2: Dual Panel Templates**
- [x] Port 5 templates dari wp-app-core
- [x] Update namespace & hooks
- [ ] Integration tests
- [ ] Documentation

**Phase 3: Dual Panel Assets**
- [x] Port CSS/JS dari wp-app-core
- [x] Clean all wpApp/WPApp references
- [x] Update all config objects (wpAppConfig → wpdtConfig)
- [x] Implement Strategy Pattern (AssetStrategyInterface, BaseAssets, DualPanelAssets)
- [x] Asset Controller
- [ ] Test asset loading

**Phase 4: Single Panel**
- [x] Create templates (4 files)
- [x] Create assets (CSS + JS)
- [x] Implement SinglePanelAssets strategy
- [x] Register in AssetController
- [ ] Test functionality (pending Phase 5)
- [ ] Documentation (pending Phase 7)

**Phase 5: Testing**
- [x] Create test plugin (wp-datatable-test)
- [x] Test plugin activated
- [x] Dual panel test page created
- [x] Single panel test page created
- [ ] Manual browser testing (dual panel)
- [ ] Manual browser testing (single panel)
- [ ] Edge cases & performance

**Phase 6: Premium (Future)**
- [ ] Premium strategy
- [ ] Premium assets
- [ ] Membership hooks
- [ ] Documentation

**Phase 7: Documentation**
- [ ] README & guides
- [ ] API docs
- [ ] Examples
- [ ] Migration guide

---

**Author:** arisciwek
**Status:** Ready for Implementation
**Next Action:** Begin Phase 1 - Core Framework
**Estimated Timeline:** 7 weeks for v1.0
**Last Updated:** 2025-11-08
