<?php
/**
 * Plugin Name: WP DataTable Test
 * Plugin URI:
 * Description: Test plugin untuk wp-datatable framework - testing dual panel dan single panel layouts
 * Version: 0.1.0
 * Author: arisciwek
 *
 * @package WP_DataTable_Test
 */

defined('ABSPATH') || exit;

class WP_DataTable_Test {
    private static $instance = null;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Register admin menu
        add_action('admin_menu', [$this, 'register_menus']);

        // Signal asset loading EARLY (before admin_enqueue_scripts)
        add_filter('wpdt_use_dual_panel', [$this, 'should_use_dual_panel']);
        add_filter('wpdt_use_single_panel', [$this, 'should_use_single_panel']);

        // Register dual panel hooks
        add_action('wpdt_left_panel_content', [$this, 'render_dual_panel_table'], 10);
        add_filter('wpdt_datatable_tabs', [$this, 'register_dual_panel_tabs'], 10, 2);
        add_action('wpdt_statistics_content', [$this, 'render_dual_panel_stats'], 10);

        // Register single panel hooks
        add_action('wpdt_panel_content', [$this, 'render_single_panel_table'], 10);
        add_filter('wpdt_datatable_filters', [$this, 'register_single_panel_filters'], 10, 2);
        add_action('wpdt_statistics_content', [$this, 'render_single_panel_stats'], 10);

        // Register AJAX handlers for panel content
        add_action('wp_ajax_wpdt_test_get_panel_content', [$this, 'ajax_get_panel_content']);

        // AUTO-WIRE AJAX HANDLERS (required for edit/delete functionality)
        add_action('wp_ajax_wpdt_test_get_edit_form', [$this, 'ajax_get_edit_form']);
        add_action('wp_ajax_wpdt_test_update', [$this, 'ajax_update']);
        add_action('wp_ajax_wpdt_test_delete', [$this, 'ajax_delete']);

        // Inject auto-wire config into DualPanelAssets via filter
        add_filter('wpdt_localize_data', [$this, 'inject_autowire_config']);

        // Define tab structure for dual panel
        add_filter('wpdt_datatable_tabs', [$this, 'define_panel_tabs'], 10, 2);
    }

    /**
     * Check if should use dual panel
     */
    public function should_use_dual_panel($use) {
        if (isset($_GET['page']) && $_GET['page'] === 'wpdt-test-dual') {
            return true;
        }
        return $use;
    }

    /**
     * Check if should use single panel
     */
    public function should_use_single_panel($use) {
        if (isset($_GET['page']) && $_GET['page'] === 'wpdt-test-single') {
            return true;
        }
        return $use;
    }

    /**
     * Register admin menus
     */
    public function register_menus() {
        // Dual panel test page
        add_menu_page(
            'Dual Panel Test',
            'Dual Panel Test',
            'manage_options',
            'wpdt-test-dual',
            [$this, 'render_dual_panel_page'],
            'dashicons-list-view',
            59
        );

        // Single panel test page
        add_menu_page(
            'Single Panel Test',
            'Single Panel Test',
            'manage_options',
            'wpdt-test-single',
            [$this, 'render_single_panel_page'],
            'dashicons-editor-table',
            60
        );
    }

    /**
     * Render dual panel test page
     */
    public function render_dual_panel_page() {
        // Render dashboard template (filter already set in constructor)
        \WPDataTable\Templates\DualPanel\DashboardTemplate::render([
            'entity' => 'test_dual',
            'title' => 'Dual Panel Test',
            'description' => 'Testing dual panel layout dengan tabs, stats, dan filters',
            'has_stats' => true,
            'has_tabs' => true,
            'has_filters' => false,
            'ajax_action' => 'wpdt_test_get_panel_content',
        ]);
    }

    /**
     * Render single panel test page
     */
    public function render_single_panel_page() {
        // Render dashboard template (filter already set in constructor)
        \WPDataTable\Templates\SinglePanel\DashboardTemplate::render([
            'entity' => 'test_single',
            'title' => 'Single Panel Test',
            'description' => 'Testing single panel layout dengan stats dan filters',
            'has_stats' => true,
            'has_filters' => true,
        ]);
    }

    /**
     * AJAX handler for panel content
     */
    public function ajax_get_panel_content() {
        check_ajax_referer('wpdt_nonce', 'nonce');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $entity = isset($_POST['entity']) ? sanitize_text_field($_POST['entity']) : '';

        if (!$id || $entity !== 'test_dual') {
            wp_send_json_error(['message' => 'Invalid request']);
        }

        // Tab 1: Info (Detail Information)
        ob_start();
        ?>
        <div class="wpdt-panel-detail">
            <h3>Detail Information</h3>
            <table class="form-table">
                <tr>
                    <th>ID</th>
                    <td><?php echo esc_html($id); ?></td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td>Test Item <?php echo esc_html($id); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <?php if ($id < 3): ?>
                            <span class="wpdt-badge wpdt-badge-success">Active</span>
                        <?php else: ?>
                            <span class="wpdt-badge wpdt-badge-error">Inactive</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Created</th>
                    <td>2025-11-08 12:00:00</td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td>This is a test item for dual panel demonstration with multiple tabs.</td>
                </tr>
            </table>
        </div>
        <?php
        $tab_info = ob_get_clean();

        // Tab 2: History (DataTable with action buttons per row)
        ob_start();
        ?>
        <div class="wpdt-panel-detail">
            <h3>History Log</h3>
            <table id="test-history-datatable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Action</th>
                        <th>User</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2025-12-28 14:30:00</td>
                        <td><span class="wpdt-badge wpdt-badge-info">Updated</span></td>
                        <td>admin</td>
                        <td>Updated status to active</td>
                        <td>
                            <button type="button" class="button button-small wpdt-edit-btn" data-id="<?php echo esc_attr($id); ?>" data-entity="test_dual" title="Edit">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="button button-small wpdt-delete-btn" data-id="<?php echo esc_attr($id); ?>" data-entity="test_dual" title="Delete">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>2025-12-28 10:15:00</td>
                        <td><span class="wpdt-badge wpdt-badge-success">Created</span></td>
                        <td>admin</td>
                        <td>Item created</td>
                        <td>
                            <button type="button" class="button button-small wpdt-edit-btn" data-id="<?php echo esc_attr($id); ?>" data-entity="test_dual" title="Edit">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="button button-small wpdt-delete-btn" data-id="<?php echo esc_attr($id); ?>" data-entity="test_dual" title="Delete">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>2025-12-27 16:45:00</td>
                        <td><span class="wpdt-badge wpdt-badge-warning">Modified</span></td>
                        <td>editor</td>
                        <td>Changed description</td>
                        <td>
                            <button type="button" class="button button-small wpdt-edit-btn" data-id="<?php echo esc_attr($id); ?>" data-entity="test_dual" title="Edit">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="button button-small wpdt-delete-btn" data-id="<?php echo esc_attr($id); ?>" data-entity="test_dual" title="Delete">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Initialize DataTable when tab is switched to history
            $(document).on('wpdt:tab-switched', function(e, data) {
                console.log('[Test] Tab switched event:', data);

                if (data.tabId === 'history' && !$.fn.DataTable.isDataTable('#test-history-datatable')) {
                    console.log('[Test] Initializing history DataTable...');

                    $('#test-history-datatable').DataTable({
                        pageLength: 5,
                        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
                        order: [[0, 'desc']],
                        columnDefs: [
                            {
                                targets: -1, // Last column (Actions)
                                orderable: false, // Disable sorting
                                searchable: false, // Disable search
                                width: '120px' // Fixed width for action buttons
                            }
                        ],
                        language: {
                            emptyTable: "No history records found",
                            info: "Showing _START_ to _END_ of _TOTAL_ records",
                            infoEmpty: "Showing 0 to 0 of 0 records",
                            lengthMenu: "Show _MENU_ records",
                            search: "Search:",
                            paginate: {
                                first: "First",
                                last: "Last",
                                next: "Next",
                                previous: "Previous"
                            }
                        }
                    });

                    console.log('[Test] History DataTable initialized successfully');
                }
            });
        });
        </script>
        <?php
        $tab_history = ob_get_clean();

        // Return response with tabs
        // Note: tabs must be object with tab ID as key, not array
        // Tab IDs must match the IDs rendered by TabSystemTemplate (details, history, etc.)
        wp_send_json_success([
            'title' => 'Test Item #' . $id,
            'tabs' => [
                'details' => $tab_info,  // First tab is always "details"
                'history' => $tab_history,
            ],
        ]);
    }

    /**
     * Render dual panel datatable (left panel)
     */
    public function render_dual_panel_table($config) {
        if ($config['entity'] !== 'test_dual') return;
        ?>
        <table id="test-dual-datatable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr class="wpdt-clickable-row" data-id="1" data-entity="test_dual">
                    <td>1</td>
                    <td>Test Item 1</td>
                    <td><span class="wpdt-badge wpdt-badge-success">Active</span></td>
                    <td>
                        <button type="button" class="button button-small wpdt-panel-trigger" data-id="1" data-entity="test_dual" title="View">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                        <button type="button" class="button button-small wpdt-edit-btn" data-id="1" data-entity="test_dual" title="Edit">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="button button-small wpdt-delete-btn" data-id="1" data-entity="test_dual" title="Delete">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>
                <tr class="wpdt-clickable-row" data-id="2" data-entity="test_dual">
                    <td>2</td>
                    <td>Test Item 2</td>
                    <td><span class="wpdt-badge wpdt-badge-success">Active</span></td>
                    <td>
                        <button type="button" class="button button-small wpdt-panel-trigger" data-id="2" data-entity="test_dual" title="View">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                        <button type="button" class="button button-small wpdt-edit-btn" data-id="2" data-entity="test_dual" title="Edit">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="button button-small wpdt-delete-btn" data-id="2" data-entity="test_dual" title="Delete">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>
                <tr class="wpdt-clickable-row" data-id="3" data-entity="test_dual">
                    <td>3</td>
                    <td>Test Item 3</td>
                    <td><span class="wpdt-badge wpdt-badge-error">Inactive</span></td>
                    <td>
                        <button type="button" class="button button-small wpdt-panel-trigger" data-id="3" data-entity="test_dual" title="View">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                        <button type="button" class="button button-small wpdt-edit-btn" data-id="3" data-entity="test_dual" title="Edit">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="button button-small wpdt-delete-btn" data-id="3" data-entity="test_dual" title="Delete">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
        <script>
        jQuery(document).ready(function($) {
            // Initialize DataTable
            $('#test-dual-datatable').DataTable({
                pageLength: 10,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries"
                }
            });

            // Bind row click to open panel
            $(document).on('click', '.wpdt-clickable-row', function(e) {
                e.preventDefault();
                var $row = $(this);
                var itemId = $row.data('id');

                if (!itemId) {
                    console.warn('[Test] No item ID found on row');
                    return;
                }

                console.log('[Test] Row clicked, ID:', itemId);

                // Call panel manager's openPanel method (expects just ID)
                if (window.wpdtPanelManager) {
                    console.log('[Test] Calling wpdtPanelManager.openPanel with ID:', itemId);
                    window.wpdtPanelManager.openPanel(itemId);
                } else {
                    console.error('[Test] Panel manager not initialized!');
                    console.log('[Test] Available global objects:', Object.keys(window).filter(k => k.includes('wpdt')));
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Register tabs untuk dual panel
     */
    public function register_dual_panel_tabs($tabs, $entity) {
        if ($entity !== 'test_dual') return $tabs;

        return [
            'details' => [
                'title' => 'Details',
                'priority' => 10,
                // No template path = hook-based AJAX pattern
            ],
            'settings' => [
                'title' => 'Settings',
                'priority' => 20,
            ],
            'history' => [
                'title' => 'History',
                'priority' => 30,
            ],
        ];
    }

    /**
     * Render stats untuk dual panel
     */
    public function render_dual_panel_stats($config) {
        if ($config['entity'] !== 'test_dual') return;
        ?>
        <div class="wpdt-stat-box">
            <div class="wpdt-stat-value">3</div>
            <div class="wpdt-stat-label">Total Items</div>
        </div>
        <div class="wpdt-stat-box">
            <div class="wpdt-stat-value">2</div>
            <div class="wpdt-stat-label">Active</div>
        </div>
        <div class="wpdt-stat-box">
            <div class="wpdt-stat-value">1</div>
            <div class="wpdt-stat-label">Inactive</div>
        </div>
        <?php
    }

    /**
     * Render single panel datatable
     */
    public function render_single_panel_table($config) {
        if ($config['entity'] !== 'test_single') return;
        ?>
        <table id="test-single-datatable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Log Entry 1</td>
                    <td>Info</td>
                    <td><span class="wpdt-badge wpdt-badge-success">Success</span></td>
                    <td>2025-11-08</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Log Entry 2</td>
                    <td>Warning</td>
                    <td><span class="wpdt-badge wpdt-badge-warning">Warning</span></td>
                    <td>2025-11-08</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Log Entry 3</td>
                    <td>Error</td>
                    <td><span class="wpdt-badge wpdt-badge-error">Error</span></td>
                    <td>2025-11-07</td>
                </tr>
            </tbody>
        </table>
        <script>
        jQuery(document).ready(function($) {
            var table = $('#test-single-datatable').DataTable({
                pageLength: 20,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries"
                }
            });

            // Register dengan single panel manager untuk auto-refresh
            if (window.wpdtSinglePanel) {
                window.wpdtSinglePanel.registerDataTable('test_single', table);
            }
        });
        </script>
        <?php
    }

    /**
     * Register filters untuk single panel
     */
    public function register_single_panel_filters($filters, $entity) {
        if ($entity !== 'test_single') return $filters;

        return [
            'category' => [
                'type' => 'select',
                'label' => 'Category',
                'options' => [
                    'info' => 'Info',
                    'warning' => 'Warning',
                    'error' => 'Error',
                ],
                'default' => '',
            ],
            'search' => [
                'type' => 'search',
                'label' => 'Search',
                'placeholder' => 'Search logs...',
            ],
            'date_range' => [
                'type' => 'date_range',
                'label' => 'Date Range',
            ],
        ];
    }

    /**
     * Render stats untuk single panel
     */
    public function render_single_panel_stats($config) {
        if ($config['entity'] !== 'test_single') return;
        ?>
        <div class="wpdt-stat-box">
            <div class="wpdt-stat-value">3</div>
            <div class="wpdt-stat-label">Total Logs</div>
        </div>
        <div class="wpdt-stat-box">
            <div class="wpdt-stat-value">1</div>
            <div class="wpdt-stat-label">Errors</div>
        </div>
        <div class="wpdt-stat-box">
            <div class="wpdt-stat-value">1</div>
            <div class="wpdt-stat-label">Warnings</div>
        </div>
        <div class="wpdt-stat-box">
            <div class="wpdt-stat-value">1</div>
            <div class="wpdt-stat-label">Info</div>
        </div>
        <?php
    }

    /**
     * Define panel tabs for test_dual entity
     *
     * @param array $tabs Existing tabs
     * @param string $entity Entity name
     * @return array Modified tabs
     */
    public function define_panel_tabs($tabs, $entity) {
        // Only define tabs for our test entity
        if ($entity !== 'test_dual') {
            return $tabs;
        }

        return [
            'details' => [
                'title' => 'Details',
                'icon' => 'dashicons-info',
                'priority' => 10,
            ],
            'history' => [
                'title' => 'History',
                'icon' => 'dashicons-backup',
                'priority' => 20,
            ],
        ];
    }

    /**
     * Inject auto-wire config into DualPanelAssets localize data
     *
     * This filter allows consumer plugins to inject their entity config
     * into wpdtConfig without overwriting base config from DualPanelAssets.
     *
     * @param array $data Existing localize data from DualPanelAssets
     * @return array Modified data with test_dual entity config
     */
    public function inject_autowire_config($data) {
        // Only inject on our test page
        if (!isset($_GET['page']) || $_GET['page'] !== 'wpdt-test-dual') {
            return $data;
        }

        // Inject test_dual entity config
        $data['test_dual'] = [
            'action_buttons' => [
                'edit' => [
                    'enabled' => true,
                    'ajax_action' => 'wpdt_test_get_edit_form',
                    'submit_action' => 'wpdt_test_update',
                    'modal_title' => 'Edit Test Item',
                    'success_message' => 'Item updated successfully!',
                    'modal_size' => 'medium',
                ],
                'delete' => [
                    'enabled' => true,
                    'ajax_action' => 'wpdt_test_delete',
                    'confirm_title' => 'Delete Test Item',
                    'confirm_message' => 'Are you sure you want to delete this item?',
                    'success_message' => 'Item deleted successfully!',
                ],
            ],
        ];

        return $data;
    }

    /**
     * AJAX: Get edit form HTML
     */
    public function ajax_get_edit_form() {
        check_ajax_referer('wpdt_nonce', 'nonce');

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$id) {
            wp_send_json_error(['message' => 'Invalid ID']);
        }

        // Simulate getting data (in real app, fetch from database)
        $data = [
            1 => ['name' => 'Test Item 1', 'status' => 'active'],
            2 => ['name' => 'Test Item 2', 'status' => 'active'],
            3 => ['name' => 'Test Item 3', 'status' => 'inactive'],
        ];

        $item = $data[$id] ?? null;
        if (!$item) {
            wp_send_json_error(['message' => 'Item not found']);
        }

        // Render form
        ob_start();
        ?>
        <form id="wpdt-test-edit-form" method="post">
            <?php wp_nonce_field('wpdt_test_update', 'wpdt_test_nonce'); ?>
            <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">

            <table class="form-table">
                <tr>
                    <th><label for="name">Name *</label></th>
                    <td>
                        <input type="text" id="name" name="name" class="regular-text"
                               value="<?php echo esc_attr($item['name']); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="status">Status *</label></th>
                    <td>
                        <select id="status" name="status" required>
                            <option value="active" <?php selected($item['status'], 'active'); ?>>Active</option>
                            <option value="inactive" <?php selected($item['status'], 'inactive'); ?>>Inactive</option>
                        </select>
                    </td>
                </tr>
            </table>
        </form>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * AJAX: Update item
     */
    public function ajax_update() {
        check_ajax_referer('wpdt_test_update', 'wpdt_test_nonce');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if (!$id || !$name || !$status) {
            wp_send_json_error(['message' => 'Missing required fields']);
        }

        // Simulate update (in real app, update database)
        // For demo purposes, just return success
        wp_send_json_success([
            'message' => 'Item #' . $id . ' updated successfully!',
            'data' => [
                'id' => $id,
                'name' => $name,
                'status' => $status,
            ],
        ]);
    }

    /**
     * AJAX: Delete item
     */
    public function ajax_delete() {
        check_ajax_referer('wpdt_nonce', 'nonce');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            wp_send_json_error(['message' => 'Invalid ID']);
        }

        // Simulate delete (in real app, delete from database)
        // For demo purposes, just return success
        wp_send_json_success([
            'message' => 'Item #' . $id . ' deleted successfully!',
        ]);
    }
}

// Initialize plugin
WP_DataTable_Test::getInstance();
