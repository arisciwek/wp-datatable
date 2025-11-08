<?php
/**
 * File: MenuManager.php
 * Path: /wp-datatable/src/Controllers/MenuManager.php
 *
 * @package     WP_DataTable
 * @subpackage  Admin/Controllers
 * @version     0.1.0
 * @author      arisciwek
 *
 * Description: Menangani registrasi admin menu untuk WP DataTable plugin.
 *              Saat ini hanya menangani settings menu.
 *              Future: akan menambahkan menu untuk datatable management.
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial development version
 * - Copied pattern from wp-customer
 * - Adapted for WP DataTable plugin
 * - Simplified menu structure (only settings in v0.1.0)
 * - Ready for future menu additions
 */

namespace WPDataTable\Controllers;

use WPDataTable\Controllers\SettingsController;

class MenuManager {
    private $plugin_name;
    private $version;
    private $settings_controller;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings_controller = new SettingsController();
    }

    public function init() {
        add_action('admin_menu', [$this, 'registerMenus']);
        $this->settings_controller->init();
    }

    public function registerMenus() {
        // Main menu: WP DataTable Settings
        add_menu_page(
            __('DataTable Settings', 'wp-datatable'),
            __('DataTable', 'wp-datatable'),
            'manage_datatables',
            'wp-datatable',
            [$this->settings_controller, 'renderPage'],
            'dashicons-list-view',
            58
        );

        // Submenu: Settings (rename first menu item)
        add_submenu_page(
            'wp-datatable',
            __('Pengaturan DataTable', 'wp-datatable'),
            __('Pengaturan', 'wp-datatable'),
            'manage_datatables',
            'wp-datatable',
            [$this->settings_controller, 'renderPage']
        );

        /**
         * Future menu items will be added here:
         *
         * Example - DataTable Registry/Management menu:
         *
         * add_submenu_page(
         *     'wp-datatable',
         *     __('Registered DataTables', 'wp-datatable'),
         *     __('Manage DataTables', 'wp-datatable'),
         *     'configure_datatables',
         *     'wp-datatable-registry',
         *     [$this->registry_controller, 'renderPage']
         * );
         */
    }
}
