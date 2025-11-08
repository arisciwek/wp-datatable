<?php
/**
 * Stats Box Template - Dual Panel
 *
 * Provides reusable statistics cards/boxes untuk dashboard.
 * Pure infrastructure pattern (container + hook only).
 *
 * @package     WP_DataTable
 * @subpackage  Templates\DualPanel
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Templates/dual-panel/StatsBoxTemplate.php
 *
 * Description: Statistics container untuk dashboard pages.
 *              Pure infrastructure - hanya menyediakan container wrapper.
 *              Plugins render their own HTML dengan plugin-specific CSS classes.
 *
 * Pattern: Pure Hook-Based Infrastructure
 * - Template provides container only
 * - Plugins hook in to render their own stats HTML
 * - Each plugin uses their own CSS classes (agency-, customer-, etc.)
 * - No dual rendering mechanism (kept simple)
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Ported dari wp-app-core/src/Views/DataTable/Templates/StatsBoxTemplate.php
 * - Updated namespace: WPAppCore\Views\DataTable\Templates → WPDataTable\Templates\DualPanel
 * - Updated hooks: wpapp_ → wpdt_
 * - Updated CSS classes: wpapp- → wpdt-
 * - Updated text domain: wp-app-core → wp-datatable
 * - Preserved pure infrastructure pattern (container + hook only)
 *
 * Original Source: wp-app-core v1.2.0 (2025-10-29)
 *
 * Usage:
 * ```php
 * // In plugin controller:
 * add_action('wpdt_statistics_cards_content', function($entity) {
 *     if ($entity !== 'agency') return;
 *     echo '<div class="agency-statistics-cards">';
 *     echo '<div class="agency-stat-card">Custom Card</div>';
 *     echo '</div>';
 * }, 10);
 * ```
 */

namespace WPDataTable\Templates\DualPanel;

defined('ABSPATH') || exit;

class StatsBoxTemplate {

    /**
     * Render statistics container with hook
     *
     * Provides empty container for plugins to inject statistics.
     * Each plugin renders their own HTML with plugin-specific CSS classes.
     *
     * Pattern: Infrastructure (container + hook), not implementation
     *
     * @param string $entity Entity name
     * @return void
     */
    public static function render($entity) {
        ?>
        <!-- Statistics Container (Global Scope) -->
        <div class="wpdt-statistics-container">
            <?php
            /**
             * Action: Statistics cards content
             *
             * Plugins should hook here to render custom statistics cards
             * Each plugin renders their own HTML with their own CSS classes
             *
             * IMPORTANT: Use plugin-specific CSS classes (e.g., agency-, customer-)
             *
             * @param string $entity Entity name
             *
             * @example
             * add_action('wpdt_statistics_cards_content', function($entity) {
             *     if ($entity !== 'agency') return;
             *     echo '<div class="agency-statistics-cards">';
             *     echo '<div class="agency-stat-card">Custom Card</div>';
             *     echo '</div>';
             * }, 10);
             */
            do_action('wpdt_statistics_cards_content', $entity);
            ?>
        </div>
        <?php
    }
}
