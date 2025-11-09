# Architecture Overview

**Version**: 0.1.0
**Last Updated**: 2025-11-09

---

## 📐 System Architecture

WP DataTable uses a modular, event-driven architecture with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────┐
│                    WordPress Admin                       │
└─────────────────────┬───────────────────────────────────┘
                      │
          ┌───────────┴───────────┐
          │                       │
    ┌─────▼──────┐        ┌──────▼─────┐
    │  Strategy  │        │   Assets   │
    │  Manager   │        │  Manager   │
    └─────┬──────┘        └──────┬─────┘
          │                      │
          │  ┌───────────────────┘
          │  │
    ┌─────▼──▼──────────────────────────┐
    │      Dashboard Template           │
    │  ┌──────────┐  ┌──────────────┐  │
    │  │ Layout   │  │ Tab System   │  │
    │  │ Template │  │ Template     │  │
    │  └──────────┘  └──────────────┘  │
    └───────────────────────────────────┘
              │
    ┌─────────┴─────────┐
    │                   │
┌───▼────────┐   ┌──────▼──────┐
│ PHP Server │   │ JavaScript  │
│   Side     │   │  Frontend   │
└────────────┘   └─────────────┘
```

---

## 🏗️ Core Components

### 1. Strategy Manager

**Purpose**: Determines which layout strategy to use (Dual Panel or Single Panel)

**Location**: `src/Strategies/StrategyManager.php`

**Responsibilities**:
- Register layout strategies
- Select appropriate strategy based on context
- Route rendering to correct strategy

**Key Methods**:
```php
public function register_strategy(string $name, LayoutStrategyInterface $strategy): void
public function get_strategy(string $name): ?LayoutStrategyInterface
public function render(array $config): void
```

---

### 2. Assets Manager

**Purpose**: Manage CSS and JavaScript assets for DataTable and layouts

**Location**: `src/Assets/AssetManager.php`

**Responsibilities**:
- Enqueue base assets (DataTables library, jQuery, etc)
- Enqueue layout-specific assets (dual-panel, single-panel)
- Manage script dependencies
- Localize script configurations

**Asset Hierarchy**:
```
Base Assets (Always Loaded)
├── DataTables Library
├── jQuery
└── Common Styles

Layout Assets (Conditional)
├── Dual Panel
│   ├── panel-manager.js
│   ├── tab-manager.js
│   └── dual-panel.css
└── Single Panel
    ├── single-panel.js
    └── single-panel.css
```

**Key Methods**:
```php
public function enqueue_base_assets(): void
public function enqueue_dual_panel_assets(): void
public function enqueue_single_panel_assets(): void
```

---

### 3. Dashboard Template

**Purpose**: Main entry point for rendering DataTable dashboards

**Location**:
- Dual Panel: `src/Templates/DualPanel/DashboardTemplate.php`
- Single Panel: `src/Templates/SinglePanel/DashboardTemplate.php`

**Responsibilities**:
- Validate configuration
- Apply filters for customization
- Render layout structure
- Include child templates (Layout, TabSystem, etc)

**Usage**:
```php
use WPDataTable\Templates\DualPanel\DashboardTemplate;

DashboardTemplate::render([
    'entity' => 'customer',
    'title' => 'Customers',
    'has_tabs' => true,
    'has_stats' => true,
    'ajax_action' => 'get_customer_details'
]);
```

---

### 4. Layout Templates

**Purpose**: Render the physical layout structure (HTML/CSS)

**Locations**:
- Dual Panel: `src/Templates/DualPanel/LayoutTemplate.php`
- Single Panel: `src/Templates/SinglePanel/LayoutTemplate.php`

**Dual Panel Structure**:
```html
<div class="wpdt-datatable-layout" data-entity="customer">
    <!-- Left Panel -->
    <div class="wpdt-left-panel">
        <div class="wpdt-datatable-header">
            <!-- Statistics Cards -->
        </div>
        <div class="wpdt-datatable-content">
            <!-- DataTable -->
        </div>
    </div>

    <!-- Right Panel (Detail Panel) -->
    <div class="wpdt-right-panel hidden">
        <div class="wpdt-panel-header">
            <!-- Title & Close Button -->
        </div>
        <div class="wpdt-panel-content">
            <!-- Tabs & Content -->
        </div>
    </div>
</div>
```

---

### 5. Tab System Template

**Purpose**: Render tab navigation and content containers

**Location**: `src/Templates/DualPanel/TabSystemTemplate.php`

**Responsibilities**:
- Create tab navigation buttons
- Create tab content containers
- Support lazy-loading attributes
- Handle active tab state

**Output Structure**:
```html
<!-- Tab Navigation -->
<div class="nav-tab-wrapper wpdt-tab-wrapper">
    <a href="#" class="nav-tab nav-tab-active" data-tab="info">
        Info
    </a>
    <a href="#" class="nav-tab" data-tab="staff">
        Staff
    </a>
</div>

<!-- Tab Containers -->
<div id="info" class="wpdt-tab-content active">
    <!-- Tab content here -->
</div>
<div id="staff" class="wpdt-tab-content">
    <!-- Tab content here -->
</div>
```

---

## 🔄 Request Flow

### Initial Page Load

```
1. WordPress Admin Page Load
   ↓
2. Plugin registers menu page
   ↓
3. Controller::render() called
   ↓
4. DashboardTemplate::render($config)
   ↓
5. Strategy Manager selects strategy
   ↓
6. Layout Template renders HTML
   ↓
7. Tab System Template renders tabs
   ↓
8. Assets Manager enqueues JS/CSS
   ↓
9. JavaScript initializes:
   - Panel Manager
   - Tab Manager
   - DataTable
   ↓
10. DataTable AJAX loads initial data
```

---

### Panel Open Flow (Dual Panel)

```
1. User clicks row/button with .wpdt-panel-trigger
   ↓
2. panel-manager.js detects click
   ↓
3. Extract data-id and data-entity
   ↓
4. Verify entity matches current context
   ↓
5. Update URL hash (#customer-123)
   ↓
6. showPanel() animation starts
   ↓
7. AJAX request to ajax_action (get_customer_details)
   ↓
8. Server returns { title, tabs: {info: html, staff: html} }
   ↓
9. updatePanelContent() injects HTML
   ↓
10. tab-manager.js reinitializes
   ↓
11. Trigger wpdt:panel-opened event
```

---

### Tab Switch Flow

```
1. User clicks tab navigation
   ↓
2. tab-manager.js detects click
   ↓
3. Check if tab has .wpdt-tab-autoload class
   ↓
4. If NOT autoload:
   - Simple show/hide tabs
   - Trigger wpdt:tab-switched event
   ↓
5. If autoload AND not loaded:
   - Get data-{entity}-id attribute
   - Get data-load-action attribute
   - Get data-content-target selector
   - Show loading spinner
   - AJAX to load_action
   - Inject response.data.html to content-target
   - Mark tab as .loaded
   - Trigger wpdt:tab-data-loaded event
   ↓
6. Trigger wpdt:tab-switched event
```

---

## 🎯 Data Flow

### Server-Side Data Flow

```
Controller
    ↓
DataTableModel (extends DataTableModel base)
    ↓
QueryBuilder (optional) or Direct SQL
    ↓
Database
    ↓
format_row() - Format each row
    ↓
Return to Controller
    ↓
wp_send_json() to Frontend
```

---

### Client-Side Data Flow

```
DataTable AJAX Request
    ↓
Server processes (Controller → Model)
    ↓
JSON Response:
{
    draw: 1,
    recordsTotal: 100,
    recordsFiltered: 100,
    data: [
        {
            DT_RowId: "customer-1",
            DT_RowData: { id: 1, entity: "customer" },
            code: "C001",
            name: "John Doe",
            ...
        }
    ]
}
    ↓
DataTable renders rows
    ↓
createdRow callback copies DT_RowData to DOM attributes
    ↓
Row HTML:
<tr id="customer-1" data-id="1" data-entity="customer">
    <td>C001</td>
    <td>John Doe</td>
    ...
</tr>
```

---

## 🔌 Extension Points

### PHP Hooks & Filters

**Strategy Selection**:
```php
// Signal to use dual panel
add_filter('wpdt_use_dual_panel', function($use) {
    if (isset($_GET['page']) && $_GET['page'] === 'my-entity') {
        return true;
    }
    return $use;
});
```

**Tab Registration**:
```php
add_filter('wpdt_datatable_tabs', function($tabs, $entity) {
    if ($entity !== 'customer') return $tabs;

    return [
        'info' => [
            'title' => 'Info',
            'template' => '/path/to/template.php',
            'priority' => 10
        ]
    ];
}, 10, 2);
```

**Content Rendering**:
```php
add_action('wpdt_left_panel_content', function($config) {
    if ($config['entity'] !== 'customer') return;

    include 'my-datatable.php';
}, 10, 1);
```

---

### JavaScript Events

**Panel Events**:
```javascript
// Before panel opens
$(document).on('wpdt:panel-opening', function(e, data) {
    console.log('Opening:', data.entity, data.id);
});

// After panel opened
$(document).on('wpdt:panel-opened', function(e, data) {
    console.log('Opened:', data.entity, data.id);
});

// Before panel closes
$(document).on('wpdt:panel-closing', function(e, data) {
    console.log('Closing:', data.entity, data.id);
});

// After panel closed
$(document).on('wpdt:panel-closed', function(e, data) {
    console.log('Closed:', data.entity);
});
```

**Tab Events**:
```javascript
// Tab switched
$(document).on('wpdt:tab-switched', function(e, data) {
    console.log('Tab switched to:', data.tabId);

    // Initialize nested DataTable
    if (data.tabId === 'employees') {
        initEmployeesDataTable();
    }
});

// Tab data loaded (lazy-load)
$(document).on('wpdt:tab-data-loaded', function(e, data) {
    console.log('Tab data loaded:', data.tabId);
});
```

---

## 🧩 Component Dependencies

```
DashboardTemplate
├── StrategyManager (selects strategy)
├── AssetManager (enqueues assets)
├── LayoutTemplate
│   ├── Statistics partial (optional)
│   ├── DataTable partial
│   └── Panel structure
└── TabSystemTemplate
    ├── Tab navigation
    └── Tab containers
        └── Plugin templates (via hooks)

Frontend (JavaScript)
├── panel-manager.js
│   ├── Handles row/button clicks
│   ├── AJAX for detail panel
│   ├── Panel animations
│   └── Hash navigation
└── tab-manager.js
    ├── Tab switching
    ├── Lazy-load AJAX
    └── Event triggering
```

---

## 📦 File Structure

```
wp-datatable/
├── src/
│   ├── Assets/
│   │   └── AssetManager.php
│   ├── Strategies/
│   │   ├── LayoutStrategyInterface.php
│   │   ├── StrategyManager.php
│   │   ├── DualPanelStrategy.php
│   │   └── SinglePanelStrategy.php
│   └── Templates/
│       ├── DualPanel/
│       │   ├── DashboardTemplate.php
│       │   ├── LayoutTemplate.php
│       │   └── TabSystemTemplate.php
│       └── SinglePanel/
│           ├── DashboardTemplate.php
│           └── LayoutTemplate.php
├── assets/
│   ├── css/
│   │   ├── dual-panel.css
│   │   └── single-panel.css
│   └── js/
│       ├── dual-panel/
│       │   ├── panel-manager.js
│       │   └── tab-manager.js
│       └── single-panel/
│           └── single-panel.js
└── docs/
    └── (this documentation)
```

---

## 🎨 Design Patterns Used

### 1. Strategy Pattern
Used for selecting layout strategy (Dual Panel vs Single Panel)

### 2. Template Method Pattern
DashboardTemplate provides structure, child templates fill details

### 3. Observer Pattern
JavaScript event system (wpdt:* events)

### 4. Dependency Injection
AssetManager, StrategyManager injected where needed

### 5. Lazy Loading Pattern
Tab content loaded on-demand via AJAX

---

**Next**: [Core Concepts](core-concepts.md) →
**Back**: [Documentation Index](../README.md)
