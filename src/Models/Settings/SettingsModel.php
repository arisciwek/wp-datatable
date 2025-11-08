<?php
/**
 * File: SettingsModel.php
 * Path: /wp-datatable/src/Models/Settings/SettingsModel.php
 * Description: Model untuk mengelola pengaturan umum plugin
 *
 * @package     WP_DataTable
 * @subpackage  Models/Settings
 * @version     0.1.0
 * @author      arisciwek
 *
 * Description: Menangani semua operasi database untuk pengaturan plugin.
 *              Includes caching, sanitization, dan default values.
 *
 * Dependencies:
 * - WordPress Options API
 * - WordPress Cache API
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial development version
 * - Copied pattern from wp-customer
 * - Adapted for WP DataTable plugin
 * - Simplified settings structure (basic settings only in v0.1.0)
 * - Added caching support
 * - Added sanitization methods
 */

namespace WPDataTable\Models\Settings;

class SettingsModel {
    /**
     * Option name in wp_options table
     */
    private $option_name = 'wpdt_settings';

    /**
     * Default settings values
     */
    private $default_settings = [
        'enable_dual_panel' => true,
        'enable_single_panel' => true,
        'default_layout' => 'dual-panel',
        'enable_auto_refresh' => true,
        'enable_export' => false,
        'items_per_page' => 20,
    ];

    /**
     * Get all settings with default values
     *
     * @return array Settings array
     */
    public function get_settings(): array {
        $cache_key = 'wpdt_settings';
        $cache_group = 'wp_datatable';

        // Try to get from cache first
        $settings = wp_cache_get($cache_key, $cache_group);

        if (false === $settings) {
            // Not in cache, get from database
            $settings = get_option($this->option_name, []);

            // Parse with defaults to ensure all keys exist
            $settings = wp_parse_args($settings, $this->default_settings);

            // Store in cache for next time
            wp_cache_set($cache_key, $settings, $cache_group);
        }

        return $settings;
    }

    /**
     * Save settings dengan validasi
     *
     * @param array $input New settings values
     * @return bool True if saved successfully
     */
    public function save_settings(array $input): bool {
        if (empty($input)) {
            return false;
        }

        // Clear cache first
        wp_cache_delete('wpdt_settings', 'wp_datatable');

        // Sanitize input
        $sanitized = $this->sanitize_settings($input);

        // Only update if we have valid data
        if (!empty($sanitized)) {
            $result = update_option($this->option_name, $sanitized);

            // Re-cache the new values if update successful
            if ($result) {
                wp_cache_set(
                    'wpdt_settings',
                    $sanitized,
                    'wp_datatable'
                );
            }

            return $result;
        }

        return false;
    }

    /**
     * Sanitize all setting values
     *
     * @param array|null $settings Raw settings from input
     * @return array Sanitized settings
     */
    public function sanitize_settings(?array $settings = []): array {
        // If settings is null, use empty array
        if ($settings === null) {
            $settings = [];
        }

        $sanitized = [];

        // Sanitize layout settings
        if (isset($settings['enable_dual_panel'])) {
            $sanitized['enable_dual_panel'] = (bool) $settings['enable_dual_panel'];
        }

        if (isset($settings['enable_single_panel'])) {
            $sanitized['enable_single_panel'] = (bool) $settings['enable_single_panel'];
        }

        if (isset($settings['default_layout'])) {
            $allowed_layouts = ['dual-panel', 'single-panel'];
            $sanitized['default_layout'] = in_array($settings['default_layout'], $allowed_layouts)
                ? $settings['default_layout']
                : 'dual-panel';
        }

        // Sanitize feature settings
        if (isset($settings['enable_auto_refresh'])) {
            $sanitized['enable_auto_refresh'] = (bool) $settings['enable_auto_refresh'];
        }

        if (isset($settings['enable_export'])) {
            $sanitized['enable_export'] = (bool) $settings['enable_export'];
        }

        // Sanitize numeric settings
        if (isset($settings['items_per_page'])) {
            $sanitized['items_per_page'] = absint($settings['items_per_page']);
            // Ensure value is within valid range (5-100)
            if ($sanitized['items_per_page'] < 5) {
                $sanitized['items_per_page'] = 5;
            }
            if ($sanitized['items_per_page'] > 100) {
                $sanitized['items_per_page'] = 100;
            }
        }

        // Merge with default settings to ensure all required keys exist
        return wp_parse_args($sanitized, $this->default_settings);
    }

    /**
     * Get specific setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Setting value
     */
    public function get_setting($key, $default = null) {
        $settings = $this->get_settings();
        return $settings[$key] ?? $default;
    }

    /**
     * Update specific setting value
     *
     * @param string $key Setting key
     * @param mixed $value New value
     * @return bool True if updated successfully
     */
    public function update_setting($key, $value): bool {
        $settings = $this->get_settings();
        $settings[$key] = $value;
        return $this->save_settings($settings);
    }

    /**
     * Reset settings to defaults
     *
     * @return bool True if reset successfully
     */
    public function reset_to_defaults(): bool {
        // Clear cache first
        wp_cache_delete('wpdt_settings', 'wp_datatable');

        // Update with default settings
        $result = update_option($this->option_name, $this->default_settings);

        // Re-cache the defaults if update successful
        if ($result) {
            wp_cache_set(
                'wpdt_settings',
                $this->default_settings,
                'wp_datatable'
            );
        }

        return $result;
    }

    /**
     * Delete all plugin settings
     *
     * @return bool True if deleted successfully
     */
    public function delete_settings(): bool {
        // Clear cache first
        wp_cache_delete('wpdt_settings', 'wp_datatable');

        return delete_option($this->option_name);
    }

    /**
     * Get default settings
     *
     * @return array Default settings array
     */
    public function get_defaults(): array {
        return $this->default_settings;
    }
}
