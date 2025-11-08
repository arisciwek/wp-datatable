<?php
/**
 * Asset Strategy Interface
 *
 * @package     WP_DataTable
 * @subpackage  Controllers/Assets
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Controllers/Assets/AssetStrategyInterface.php
 *
 * Description: Interface contract untuk asset loading strategies.
 *              Defines methods yang harus diimplementasi oleh semua asset strategies.
 *              Enables flexible, conditional asset loading based on layout type.
 *
 * Contract Methods:
 * - enqueue_styles(): Enqueue CSS files
 * - enqueue_scripts(): Enqueue JS files
 * - get_localize_data(): Get data untuk wp_localize_script()
 * - should_load(): Determine if strategy should load on current page
 *
 * Strategy Pattern Benefits:
 * - Conditional loading (only load needed assets)
 * - Easy to add new strategies (SinglePanel, Premium)
 * - Separation of concerns
 * - Testable asset loading logic
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial implementation
 * - Define core contract methods
 * - Support for dual-panel, single-panel, premium strategies
 * - Ready for extensibility
 */

namespace WPDataTable\Controllers\Assets;

defined('ABSPATH') || exit;

interface AssetStrategyInterface {
    /**
     * Enqueue CSS stylesheets
     *
     * Load CSS files required for this strategy.
     * Called by AssetController when should_load() returns true.
     *
     * @return void
     *
     * @example
     * ```php
     * public function enqueue_styles(): void {
     *     wp_enqueue_style(
     *         'wpdt-dual-panel',
     *         WP_DATATABLE_URL . 'assets/css/dual-panel.css',
     *         [],
     *         WP_DATATABLE_VERSION
     *     );
     * }
     * ```
     */
    public function enqueue_styles(): void;

    /**
     * Enqueue JavaScript files
     *
     * Load JS files required for this strategy.
     * Called by AssetController when should_load() returns true.
     *
     * @return void
     *
     * @example
     * ```php
     * public function enqueue_scripts(): void {
     *     wp_enqueue_script(
     *         'wpdt-panel-manager',
     *         WP_DATATABLE_URL . 'assets/js/dual-panel/panel-manager.js',
     *         ['jquery'],
     *         WP_DATATABLE_VERSION,
     *         true
     *     );
     * }
     * ```
     */
    public function enqueue_scripts(): void;

    /**
     * Get data untuk wp_localize_script()
     *
     * Return array of data yang akan di-pass ke JavaScript.
     * Typically includes: ajaxUrl, nonce, config options, i18n strings.
     *
     * @return array Localized data
     *
     * @example
     * ```php
     * public function get_localize_data(): array {
     *     return [
     *         'ajaxUrl' => admin_url('admin-ajax.php'),
     *         'nonce' => wp_create_nonce('wpdt_nonce'),
     *         'debug' => defined('WP_DEBUG') && WP_DEBUG,
     *         'i18n' => [
     *             'loading' => __('Loading...', 'wp-datatable'),
     *             'error' => __('Error loading data', 'wp-datatable'),
     *         ]
     *     ];
     * }
     * ```
     */
    public function get_localize_data(): array;

    /**
     * Determine if this strategy should load
     *
     * Return true jika strategy ini harus load pada current page/context.
     * Used by AssetController untuk conditional loading.
     *
     * @return bool True if should load
     *
     * @example
     * ```php
     * public function should_load(): bool {
     *     // Check if dual-panel usage is detected
     *     return apply_filters('wpdt_use_dual_panel', false);
     * }
     * ```
     */
    public function should_load(): bool;

    /**
     * Get strategy name/identifier
     *
     * Return unique identifier untuk strategy ini.
     * Used for debugging and logging.
     *
     * @return string Strategy name
     *
     * @example
     * ```php
     * public function get_name(): string {
     *     return 'dual-panel';
     * }
     * ```
     */
    public function get_name(): string;
}
