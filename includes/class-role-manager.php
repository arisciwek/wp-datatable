<?php
/**
 * Role Manager Class
 *
 * @package     WP_DataTable
 * @subpackage  Includes
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/includes/class-role-manager.php
 *
 * Description: Centralized capability management for WP DataTable plugin.
 *              Manages custom capabilities yang dapat ditambahkan ke roles.
 *
 * Capabilities:
 * - manage_datatables: Can configure datatables settings
 * - view_datatables: Can view datatables
 * - configure_datatables: Can configure datatable options
 *
 * Usage:
 * - Get all capabilities: WPDataTable_Role_Manager::getCapabilities()
 * - Add capabilities: $role_manager->addCapabilities()
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial development version
 * - Copied pattern from wp-customer
 * - Adapted for WP DataTable plugin
 * - Focus on capabilities (no custom roles in v0.1.0)
 */

defined('ABSPATH') || exit;

class WPDataTable_Role_Manager {
    /**
     * Get all custom capabilities
     *
     * @return array Array of capability_slug => capability_name pairs
     */
    public static function getCapabilities(): array {
        return [
            'manage_datatables' => __('Manage DataTables', 'wp-datatable'),
            'view_datatables' => __('View DataTables', 'wp-datatable'),
            'configure_datatables' => __('Configure DataTables', 'wp-datatable'),
        ];
    }

    /**
     * Get only capability slugs
     *
     * @return array Array of capability slugs
     */
    public static function getCapabilitySlugs(): array {
        return array_keys(self::getCapabilities());
    }

    /**
     * Add capabilities to administrator role
     */
    public function addCapabilities() {
        $admin_role = get_role('administrator');

        if ($admin_role) {
            foreach (self::getCapabilitySlugs() as $cap) {
                $admin_role->add_cap($cap);
            }
        }
    }

    /**
     * Remove capabilities from all roles
     */
    public function removeCapabilities() {
        $capabilities = self::getCapabilitySlugs();

        foreach (get_editable_roles() as $role_name => $role_info) {
            $role = get_role($role_name);
            if (!$role) continue;

            foreach ($capabilities as $cap) {
                $role->remove_cap($cap);
            }
        }
    }

    /**
     * Check if user has datatable capability
     *
     * @param string $capability Capability to check
     * @param int|null $user_id User ID (default: current user)
     * @return bool True if user has capability
     */
    public static function userCan(string $capability, ?int $user_id = null): bool {
        if ($user_id === null) {
            return current_user_can($capability);
        }

        $user = get_user_by('id', $user_id);
        return $user && $user->has_cap($capability);
    }
}
