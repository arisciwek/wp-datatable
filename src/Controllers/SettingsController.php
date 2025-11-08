<?php
/**
 * File: SettingsController.php
 * Path: /wp-datatable/src/Controllers/SettingsController.php
 * Description: Controller untuk mengelola halaman pengaturan plugin
 *
 * @package     WP_DataTable
 * @subpackage  Admin/Controllers
 * @version     0.1.0
 * @author      arisciwek
 *
 * Description: Menangani pengaturan plugin WP DataTable.
 *              Saat ini hanya menangani pengaturan dasar.
 *              Future: akan menambahkan tabs untuk different settings sections.
 *
 * Dependencies:
 * - SettingsModel (untuk akses data pengaturan)
 * - WordPress Settings API
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial development version
 * - Copied pattern from wp-customer
 * - Adapted for WP DataTable plugin
 * - Simplified settings structure (basic settings only in v0.1.0)
 * - Ready for future enhancements (tabs, AJAX handlers, etc.)
 */

namespace WPDataTable\Controllers;

use WPDataTable\Models\Settings\SettingsModel;

class SettingsController {
    private $settings_model;

    public function __construct() {
        $this->settings_model = new SettingsModel();
    }

    public function init() {
        add_action('admin_init', [$this, 'register_settings']);
        // Future: register AJAX handlers here
        // $this->register_ajax_handlers();
    }

    /**
     * Register plugin settings with WordPress Settings API
     */
    public function register_settings() {
        // General Settings
        register_setting(
            'wpdt_settings',
            'wpdt_settings',
            array(
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => array(
                    'enable_dual_panel' => true,
                    'enable_single_panel' => true,
                    'default_layout' => 'dual-panel',
                    'enable_auto_refresh' => true,
                    'enable_export' => false,
                    'items_per_page' => 20,
                )
            )
        );

        /**
         * Future: Additional settings groups
         *
         * Example - Development Settings:
         *
         * register_setting(
         *     'wpdt_development_settings',
         *     'wpdt_development_settings',
         *     array(
         *         'sanitize_callback' => [$this, 'sanitize_development_settings'],
         *         'default' => array(
         *             'enable_development' => 0,
         *             'clear_data_on_deactivate' => 0
         *         )
         *     )
         * );
         */
    }

    /**
     * Sanitize general settings
     *
     * @param array $input Raw input from settings form
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        // Layout settings
        $sanitized['enable_dual_panel'] = isset($input['enable_dual_panel']) ? 1 : 0;
        $sanitized['enable_single_panel'] = isset($input['enable_single_panel']) ? 1 : 0;
        $sanitized['default_layout'] = in_array($input['default_layout'], ['dual-panel', 'single-panel'])
            ? sanitize_text_field($input['default_layout'])
            : 'dual-panel';

        // Feature settings
        $sanitized['enable_auto_refresh'] = isset($input['enable_auto_refresh']) ? 1 : 0;
        $sanitized['enable_export'] = isset($input['enable_export']) ? 1 : 0;

        // Numeric settings
        $sanitized['items_per_page'] = absint($input['items_per_page']);
        if ($sanitized['items_per_page'] < 5) {
            $sanitized['items_per_page'] = 5;
        }
        if ($sanitized['items_per_page'] > 100) {
            $sanitized['items_per_page'] = 100;
        }

        return $sanitized;
    }

    /**
     * Render settings page
     */
    public function renderPage() {
        if (!current_user_can('manage_datatables')) {
            wp_die(__('Anda tidak memiliki izin untuk mengakses halaman ini.', 'wp-datatable'));
        }

        // Get current settings
        $settings = $this->settings_model->get_settings();

        // Future: Support for tabs
        // $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        // $this->loadTabView($current_tab, $settings);

        // For now, load simple settings page
        $this->renderSimpleSettingsPage($settings);
    }

    /**
     * Render simple settings page (v0.1.0)
     *
     * @param array $settings Current settings
     */
    private function renderSimpleSettingsPage($settings) {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Pengaturan DataTable', 'wp-datatable'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('wpdt_settings');
                do_settings_sections('wpdt_settings');
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Layout Settings', 'wp-datatable'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="wpdt_settings[enable_dual_panel]" value="1"
                                        <?php checked($settings['enable_dual_panel'], 1); ?>>
                                    <?php esc_html_e('Enable Dual Panel Layout', 'wp-datatable'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="wpdt_settings[enable_single_panel]" value="1"
                                        <?php checked($settings['enable_single_panel'], 1); ?>>
                                    <?php esc_html_e('Enable Single Panel Layout', 'wp-datatable'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="default_layout"><?php esc_html_e('Default Layout', 'wp-datatable'); ?></label>
                        </th>
                        <td>
                            <select name="wpdt_settings[default_layout]" id="default_layout">
                                <option value="dual-panel" <?php selected($settings['default_layout'], 'dual-panel'); ?>>
                                    <?php esc_html_e('Dual Panel', 'wp-datatable'); ?>
                                </option>
                                <option value="single-panel" <?php selected($settings['default_layout'], 'single-panel'); ?>>
                                    <?php esc_html_e('Single Panel', 'wp-datatable'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="items_per_page"><?php esc_html_e('Items Per Page', 'wp-datatable'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="wpdt_settings[items_per_page]" id="items_per_page"
                                value="<?php echo esc_attr($settings['items_per_page']); ?>"
                                min="5" max="100" step="5">
                            <p class="description">
                                <?php esc_html_e('Number of items to display per page (5-100)', 'wp-datatable'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Feature Settings', 'wp-datatable'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="wpdt_settings[enable_auto_refresh]" value="1"
                                        <?php checked($settings['enable_auto_refresh'], 1); ?>>
                                    <?php esc_html_e('Enable Auto Refresh', 'wp-datatable'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="wpdt_settings[enable_export]" value="1"
                                        <?php checked($settings['enable_export'], 1); ?>>
                                    <?php esc_html_e('Enable Export Features', 'wp-datatable'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Future: Load tab-based views
     *
     * private function loadTabView($tab, $settings) {
     *     $allowed_tabs = [
     *         'general' => 'tab-general.php',
     *         'advanced' => 'tab-advanced.php',
     *         'permissions' => 'tab-permissions.php',
     *     ];
     *
     *     $tab = isset($allowed_tabs[$tab]) ? $tab : 'general';
     *     $tab_file = WP_DATATABLE_PATH . 'src/Views/templates/settings/' . $allowed_tabs[$tab];
     *
     *     if (file_exists($tab_file)) {
     *         require_once $tab_file;
     *     }
     * }
     */
}
