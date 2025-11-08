<?php
/**
 * Init Hooks Class
 *
 * @package     WP_DataTable
 * @subpackage  Includes
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/includes/class-init-hooks.php
 *
 * Description: Mendefinisikan semua hooks dan filters yang dibutuhkan
 *              oleh plugin saat inisialisasi.
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial development version
 * - Copied pattern from wp-customer
 * - Adapted for WP DataTable plugin
 * - Simplified (no shortcodes or templates in v0.1.0)
 */

class WPDataTable_Init_Hooks {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Main init method
     */
    public function init() {
        // Load textdomain
        $this->load_textdomain();

        // Register custom actions (future enhancement)
        // add_filter('query_vars', [$this, 'add_query_vars']);
    }

    /**
     * Load plugin textdomain untuk i18n/l10n
     */
    public function load_textdomain() {
        $rel_path = dirname(plugin_basename(__FILE__), 2) . '/languages/';
        load_plugin_textdomain(
            'wp-datatable',
            false,
            $rel_path
        );
    }

    /**
     * Add custom query vars (future enhancement)
     */
    public function add_query_vars($vars) {
        // Future: add custom query vars if needed
        return $vars;
    }
}
