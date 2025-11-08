<?php
/**
 * File: class-activator.php
 * Path: /wp-datatable/includes/class-activator.php
 * Description: Handles plugin activation
 *
 * @package     WP_DataTable
 * @subpackage  Includes
 * @version     0.1.0
 * @author      arisciwek
 *
 * Description: Menangani proses aktivasi plugin.
 *              Termasuk di dalamnya:
 *              - Menambahkan versi plugin ke options table
 *              - Setup default settings
 *              - Setup permission dan capabilities
 *              - Flush rewrite rules
 *
 * Dependencies:
 * - WordPress Options API
 * - class-role-manager.php untuk capabilities
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial development version
 * - Copied pattern from wp-customer
 * - Adapted for WP DataTable plugin
 * - Simplified (no database tables in v0.1.0)
 */

// Load RoleManager
require_once WP_DATATABLE_PATH . 'includes/class-role-manager.php';

class WPDataTable_Activator {
    private static function logError($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("WPDataTable_Activator Error: {$message}");
        }
    }

    public static function activate() {
        try {
            // Load textdomain first
            load_textdomain('wp-datatable', WP_DATATABLE_PATH . 'languages/wp-datatable-id_ID.mo');

            // 1. Setup capabilities
            try {
                $role_manager = new WPDataTable_Role_Manager();
                $role_manager->addCapabilities();

                // Clear WordPress user/role caches to ensure capabilities load immediately
                global $wpdb;
                wp_cache_delete('alloptions', 'options');

                // Clear user meta cache for all users
                $user_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->users}");
                foreach ($user_ids as $user_id) {
                    clean_user_cache($user_id);
                    wp_cache_delete($user_id, 'users');
                    wp_cache_delete($user_id, 'user_meta');
                }

            } catch (\Exception $e) {
                self::logError('Error adding capabilities: ' . $e->getMessage());
            }

            // 2. Add version
            self::addVersion();

            // 3. Setup default settings
            self::setupDefaults();

            // 4. Flush rewrite rules
            flush_rewrite_rules();

        } catch (\Exception $e) {
            self::logError('Critical error during activation: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Setup default settings
     */
    private static function setupDefaults() {
        try {
            // Check if settings already exist
            if (!get_option('wpdt_settings')) {
                $default_settings = [
                    'enable_dual_panel' => true,
                    'enable_single_panel' => true,
                    'default_layout' => 'dual-panel',
                    'enable_auto_refresh' => true,
                    'enable_export' => false,
                    'items_per_page' => 20,
                ];

                add_option('wpdt_settings', $default_settings);
            }
        } catch (\Exception $e) {
            self::logError('Error setting up defaults: ' . $e->getMessage());
        }
    }

    /**
     * Add version to options
     */
    private static function addVersion() {
        add_option('wpdt_version', WP_DATATABLE_VERSION);
    }
}
