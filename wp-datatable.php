<?php
/**
 * Plugin Name: WP DataTable
 * Plugin URI:
 * Description: Reusable DataTable framework untuk WordPress plugins dengan dual & single panel layouts
 * Version: 0.1.0
 * Author: arisciwek
 * Author URI:
 * License: GPL v2 or later
 * Update URI: false
 *
 * @package     WP_DataTable
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/wp-datatable.php
 *
 * Description: Update URI: false disables WordPress.org update checks.
 *              This is a local development plugin, not from repository.
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial development version
 * - Core framework setup
 * - Plugin structure created
 * - Standard files following wp-customer pattern
 * - Disabled auto-update checks (Update URI: false)
 */

defined('ABSPATH') || exit;

// Define plugin constants first, before anything else
define('WP_DATATABLE_VERSION', '0.1.0');
define('WP_DATATABLE_FILE', __FILE__);
define('WP_DATATABLE_PATH', plugin_dir_path(__FILE__));
define('WP_DATATABLE_URL', plugin_dir_url(__FILE__));
define('WP_DATATABLE_DEVELOPMENT', true);

// Disable update checks for this local development plugin
add_filter('site_transient_update_plugins', function($value) {
    if (isset($value->response[plugin_basename(__FILE__)])) {
        unset($value->response[plugin_basename(__FILE__)]);
    }
    return $value;
});

add_filter('auto_update_plugin', function($update, $item) {
    if (isset($item->slug) && $item->slug === 'wp-datatable') {
        return false;
    }
    return $update;
}, 10, 2);

/**
 * Main plugin class
 *
 * Note: Using WP_DataTable (with underscore) to avoid conflict
 * with WPDataTable class from wpdatatables plugin
 */
class WP_DataTable {
    /**
     * Single instance of the class
     */
    private static $instance = null;

    private $loader;
    private $plugin_name;
    private $version;

    /**
     * Get single instance of WP_DataTable
     */
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->plugin_name = 'wp-datatable';
        $this->version = WP_DATATABLE_VERSION;

        // Register autoloader first
        require_once WP_DATATABLE_PATH . 'includes/class-autoloader.php';
        $autoloader = new WPDataTableAutoloader('WPDataTable\\', WP_DATATABLE_PATH);
        $autoloader->register();

        // Load textdomain immediately
        load_textdomain('wp-datatable', WP_DATATABLE_PATH . 'languages/wp-datatable-id_ID.mo');

        // Load dependencies
        $this->load_dependencies();

        // Define hooks
        $this->define_hooks();
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Load core classes
        require_once WP_DATATABLE_PATH . 'includes/class-loader.php';
        require_once WP_DATATABLE_PATH . 'includes/class-init-hooks.php';

        // Initialize loader
        $this->loader = new WPDataTable_Loader();
    }

    /**
     * Define hooks
     */
    private function define_hooks() {
        // Initialize hooks handler
        $init_hooks = new WPDataTable_Init_Hooks($this->plugin_name, $this->version);
        $this->loader->add_action('init', $init_hooks, 'init');

        // Admin hooks
        if (is_admin()) {
            // Menu Manager
            $menu_manager = new \WPDataTable\Controllers\MenuManager($this->plugin_name, $this->version);
            $menu_manager->init();

            // Asset Controller (Singleton pattern)
            $asset_controller = \WPDataTable\Controllers\Assets\AssetController::get_instance();
            $asset_controller->init();
            // Asset controller hooks to admin_enqueue_scripts internally
        }
    }

    /**
     * Run the loader
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Get plugin name
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Get version
     */
    public function get_version() {
        return $this->version;
    }
}

/**
 * Activation hook
 */
function activate_wp_datatable() {
    require_once WP_DATATABLE_PATH . 'includes/class-activator.php';
    WPDataTable_Activator::activate();
}
register_activation_hook(__FILE__, 'activate_wp_datatable');

/**
 * Deactivation hook
 */
function deactivate_wp_datatable() {
    require_once WP_DATATABLE_PATH . 'includes/class-deactivator.php';
    WPDataTable_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_wp_datatable');

/**
 * Run the plugin
 */
function run_wp_datatable() {
    $plugin = WP_DataTable::getInstance();
    $plugin->run();
}

// Initialize on plugins_loaded to ensure consistent loading order
add_action('plugins_loaded', 'run_wp_datatable', 10);
