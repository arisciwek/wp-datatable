<?php
/**
 * File: class-upgrade.php
 * Path: /wp-datatable/includes/class-upgrade.php
 * Description: Handles plugin upgrades and migrations
 *
 * @package     WP_DataTable
 * @subpackage  Includes
 * @version     0.1.0
 * @author      arisciwek
 *
 * Description: Menangani upgrade plugin saat versi berubah.
 *              Ensures backward compatibility dan data migration.
 *              Saat ini versi 0.1.0 belum memiliki upgrade routine,
 *              tetapi struktur sudah disiapkan untuk future upgrades.
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial development version
 * - Copied pattern from wp-customer
 * - Adapted for WP DataTable plugin
 * - Setup version checking mechanism
 * - Ready for future upgrade routines
 */

class WPDataTable_Upgrade {
    /**
     * Version option name in database
     */
    const VERSION_OPTION = 'wpdt_version';

    /**
     * Check and run upgrades if needed
     */
    public static function check_and_upgrade() {
        $current_version = get_option(self::VERSION_OPTION, '0.0.0');
        $new_version = WP_DATATABLE_VERSION;

        // Skip if same version
        if (version_compare($current_version, '=', $new_version)) {
            return;
        }

        self::log("Upgrading from {$current_version} to {$new_version}");

        // Run upgrade routines based on version
        // Future upgrade routines will be added here as needed
        // Example:
        // if (version_compare($current_version, '0.2.0', '<')) {
        //     self::upgrade_to_0_2_0();
        // }

        // Update version in database
        update_option(self::VERSION_OPTION, $new_version);
        self::log("Upgrade completed to version {$new_version}");
    }

    /**
     * Future upgrade routine template
     *
     * Example for v0.2.0:
     *
     * private static function upgrade_to_0_2_0() {
     *     self::log("Running upgrade to 0.2.0");
     *
     *     try {
     *         // Perform upgrade tasks here
     *         // Example: migrate settings, update database schema, etc.
     *
     *         self::log("Upgrade to 0.2.0 completed");
     *         return true;
     *     } catch (\Exception $e) {
     *         self::log("Error in upgrade to 0.2.0: " . $e->getMessage());
     *         return false;
     *     }
     * }
     */

    /**
     * Log upgrade messages
     */
    private static function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("WPDataTable_Upgrade: {$message}");
        }
    }
}
