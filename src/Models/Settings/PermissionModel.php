<?php
/**
 * Permission Model Class
 *
 * @package     WP_DataTable
 * @subpackage  Models/Settings
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Models/Settings/PermissionModel.php
 *
 * Description: Model untuk mengelola hak akses plugin.
 *              Menangani capabilities untuk WordPress roles.
 *              Saat ini hanya menangani capabilities dasar.
 *
 * Dependencies:
 * - WordPress Roles API
 * - WPDataTable_Role_Manager
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial development version
 * - Copied pattern from wp-customer
 * - Adapted for WP DataTable plugin
 * - Simplified permissions structure (basic capabilities only in v0.1.0)
 * - No custom roles yet (only managing existing WP roles)
 * - Ready for future enhancements
 */

namespace WPDataTable\Models\Settings;

class PermissionModel {
    /**
     * Available capabilities untuk WP DataTable plugin
     *
     * @var array
     */
    private $available_capabilities = [
        'manage_datatables' => 'Manage DataTables',
        'view_datatables' => 'View DataTables',
        'configure_datatables' => 'Configure DataTables',
    ];

    /**
     * Get all available capabilities
     *
     * @return array Array of capability_slug => capability_name
     */
    public function getAllCapabilities(): array {
        return $this->available_capabilities;
    }

    /**
     * Get capability descriptions for tooltips/help text
     *
     * @return array Associative array of capability => description
     */
    public function getCapabilityDescriptions(): array {
        return [
            'manage_datatables' => __('Memungkinkan mengelola pengaturan datatables plugin', 'wp-datatable'),
            'view_datatables' => __('Memungkinkan melihat datatables yang terdaftar', 'wp-datatable'),
            'configure_datatables' => __('Memungkinkan mengkonfigurasi opsi datatables', 'wp-datatable'),
        ];
    }

    /**
     * Check if a role has a specific capability
     *
     * @param string $role_name Role slug
     * @param string $capability Capability slug
     * @return bool True if role has capability
     */
    public function roleHasCapability(string $role_name, string $capability): bool {
        $role = get_role($role_name);
        if (!$role) {
            error_log("WPDataTable: Role not found: $role_name");
            return false;
        }
        return $role->has_cap($capability);
    }

    /**
     * Add all capabilities to administrator role
     *
     * @return void
     */
    public function addCapabilities(): void {
        // Set administrator capabilities
        $admin = get_role('administrator');
        if ($admin) {
            foreach (array_keys($this->available_capabilities) as $cap) {
                $admin->add_cap($cap);
            }
        }

        /**
         * Future: Add capabilities to other roles
         *
         * Example - Editor role with limited access:
         *
         * $editor = get_role('editor');
         * if ($editor) {
         *     $editor->add_cap('view_datatables');
         *     $editor->add_cap('configure_datatables');
         * }
         */
    }

    /**
     * Reset permissions to default settings
     *
     * @return bool True if reset successful
     */
    public function resetToDefault(): bool {
        try {
            error_log('[WPDataTable_PermissionModel] resetToDefault() START');

            // Get all editable roles
            $all_roles = get_editable_roles();
            error_log('[WPDataTable_PermissionModel] Processing ' . count($all_roles) . ' roles');

            foreach ($all_roles as $role_name => $role_info) {
                $role = get_role($role_name);
                if (!$role) {
                    continue;
                }

                // Remove all datatable capabilities first
                foreach (array_keys($this->available_capabilities) as $cap) {
                    $role->remove_cap($cap);
                }

                // Add capabilities back based on role
                if ($role_name === 'administrator') {
                    // Administrator gets all capabilities
                    foreach (array_keys($this->available_capabilities) as $cap) {
                        $role->add_cap($cap);
                    }
                    error_log('[WPDataTable_PermissionModel] Added all capabilities to administrator');
                }

                /**
                 * Future: Add default capabilities for other roles
                 *
                 * Example:
                 *
                 * if ($role_name === 'editor') {
                 *     $role->add_cap('view_datatables');
                 *     $role->add_cap('configure_datatables');
                 * }
                 */
            }

            // Clear user cache to ensure changes take effect
            wp_cache_flush();

            error_log('[WPDataTable_PermissionModel] resetToDefault() END - SUCCESS');
            return true;

        } catch (\Exception $e) {
            error_log('[WPDataTable_PermissionModel] EXCEPTION in resetToDefault(): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update capabilities for a specific role
     *
     * @param string $role_name Role slug
     * @param array $capabilities Array of capability => enabled pairs
     * @return bool True if updated successfully
     */
    public function updateRoleCapabilities(string $role_name, array $capabilities): bool {
        // Don't allow modifying administrator role
        if ($role_name === 'administrator') {
            return false;
        }

        $role = get_role($role_name);
        if (!$role) {
            return false;
        }

        // Remove all datatable capabilities first
        foreach (array_keys($this->available_capabilities) as $cap) {
            $role->remove_cap($cap);
        }

        // Add new capabilities
        foreach ($capabilities as $cap => $enabled) {
            if ($enabled && isset($this->available_capabilities[$cap])) {
                $role->add_cap($cap);
            }
        }

        // Clear cache
        wp_cache_flush();

        return true;
    }

    /**
     * Get capabilities for a specific role
     *
     * @param string $role_name Role slug
     * @return array Array of capability => bool pairs
     */
    public function getRoleCapabilities(string $role_name): array {
        $role = get_role($role_name);
        if (!$role) {
            return [];
        }

        $result = [];
        foreach (array_keys($this->available_capabilities) as $cap) {
            $result[$cap] = $role->has_cap($cap);
        }

        return $result;
    }

    /**
     * Remove all plugin capabilities from all roles
     *
     * @return bool True if removed successfully
     */
    public function removeAllCapabilities(): bool {
        try {
            $all_roles = get_editable_roles();

            foreach ($all_roles as $role_name => $role_info) {
                $role = get_role($role_name);
                if (!$role) {
                    continue;
                }

                foreach (array_keys($this->available_capabilities) as $cap) {
                    $role->remove_cap($cap);
                }
            }

            // Clear cache
            wp_cache_flush();

            return true;

        } catch (\Exception $e) {
            error_log('[WPDataTable_PermissionModel] Error removing capabilities: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get default capabilities for administrator role
     *
     * @return array Array of capability => bool pairs
     */
    private function getDefaultAdminCapabilities(): array {
        $defaults = [];
        foreach (array_keys($this->available_capabilities) as $cap) {
            $defaults[$cap] = true;
        }
        return $defaults;
    }
}
