<?php
/**
 * Plugin Deactivator Class
 *
 * @package     WP_DataTable
 * @subpackage  Includes
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/includes/class-deactivator.php
 *
 * Description: Menangani proses deaktivasi plugin:
 *              - Cache cleanup
 *              - Settings cleanup (hanya dalam mode development)
 *              - Capabilities removal (hanya dalam mode development)
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

class WPDataTable_Deactivator {
    private static function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[WPDataTable_Deactivator] {$message}");
        }
    }

    /**
     * Check if data should be cleared on deactivation
     */
    private static function should_clear_data() {
        return defined('WP_DATATABLE_DEVELOPMENT') && WP_DATATABLE_DEVELOPMENT;
    }

    /**
     * Main deactivation method
     */
    public static function deactivate() {
        $should_clear_data = self::should_clear_data();

        try {
            // Only proceed with data cleanup if in development mode
            if (!$should_clear_data) {
                self::debug("Skipping data cleanup - not in development mode");
                flush_rewrite_rules();
                return;
            }

            // Remove capabilities (only in development mode)
            self::remove_capabilities();

            // Cleanup settings (only in development mode)
            self::cleanup_settings();

            // Flush rewrite rules
            flush_rewrite_rules();

            self::debug("Plugin deactivation complete");

        } catch (\Exception $e) {
            self::debug("Error during deactivation: " . $e->getMessage());
        }
    }

    /**
     * Remove plugin capabilities
     */
    private static function remove_capabilities() {
        try {
            $role_manager = new WPDataTable_Role_Manager();

            // Get all capabilities
            $capabilities = [
                'manage_datatables',
                'view_datatables',
                'configure_datatables',
            ];

            // Remove capabilities from all roles
            foreach (get_editable_roles() as $role_name => $role_info) {
                $role = get_role($role_name);
                if (!$role) continue;

                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }

            self::debug("Capabilities removed successfully");

        } catch (\Exception $e) {
            self::debug("Error removing capabilities: " . $e->getMessage());
        }
    }

    /**
     * Cleanup plugin settings
     */
    private static function cleanup_settings() {
        try {
            delete_option('wpdt_settings');
            delete_option('wpdt_version');
            delete_transient('wpdt_cache');

            self::debug("Settings and transients cleared");

        } catch (\Exception $e) {
            self::debug("Error cleaning up settings: " . $e->getMessage());
        }
    }
}
