<?php
/**
 * DataTable Registry Class
 *
 * @package     WP_DataTable
 * @subpackage  Core
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Core/DataTableRegistry.php
 *
 * Description: Central registry untuk managing semua datatables.
 *              Menggunakan Singleton pattern untuk global access.
 *              Plugins dapat register datatables via WordPress hooks.
 *
 * Features:
 * - Register/unregister datatables
 * - Get datatable by ID
 * - List all registered datatables
 * - Permission checks
 * - Hook integration for plugins
 *
 * Usage Example:
 * ```php
 * // Register a datatable from your plugin
 * add_action('wpdt_register_datatables', function($registry) {
 *     $registry->register(new MyCustomerDataTable());
 * });
 *
 * // Get a datatable
 * $registry = DataTableRegistry::getInstance();
 * $datatable = $registry->get('my_customers');
 * ```
 *
 * Hook Flow:
 * 1. wp_datatable plugin initializes
 * 2. Fires 'wpdt_register_datatables' hook
 * 3. Other plugins register their datatables
 * 4. Registry maintains list of all datatables
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial development version
 * - Singleton pattern implementation
 * - Register/get/list functionality
 * - Permission checking
 * - WordPress hooks integration
 * - Ready for cross-plugin usage
 */

namespace WPDataTable\Core;

defined('ABSPATH') || exit;

class DataTableRegistry {
    /**
     * Singleton instance
     *
     * @var DataTableRegistry|null
     */
    private static $instance = null;

    /**
     * Registered datatables
     *
     * @var array Array of DataTableInterface instances keyed by ID
     */
    private $datatables = [];

    /**
     * Private constructor for Singleton pattern
     */
    private function __construct() {
        // Initialize registry
        $this->init();
    }

    /**
     * Get singleton instance
     *
     * @return DataTableRegistry Singleton instance
     */
    public static function getInstance(): DataTableRegistry {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize registry
     *
     * Fires WordPress hook untuk allow plugins to register datatables.
     */
    private function init(): void {
        /**
         * Fire hook untuk plugin registration
         *
         * Other plugins should hook into this action to register their datatables.
         *
         * @param DataTableRegistry $this Registry instance
         *
         * @example
         * ```php
         * add_action('wpdt_register_datatables', function($registry) {
         *     $registry->register(new MyDataTable());
         * });
         * ```
         */
        do_action('wpdt_register_datatables', $this);
    }

    /**
     * Register a datatable
     *
     * Add a datatable instance to the registry.
     * ID must be unique - akan overwrite jika sudah ada.
     *
     * @param DataTableInterface $datatable DataTable instance
     * @return bool True if registered successfully
     *
     * @throws \InvalidArgumentException If datatable ID is empty
     */
    public function register(DataTableInterface $datatable): bool {
        $id = $datatable->get_id();

        if (empty($id)) {
            throw new \InvalidArgumentException('DataTable ID cannot be empty');
        }

        // Log warning if overwriting
        if (isset($this->datatables[$id])) {
            error_log(sprintf(
                '[WP DataTable] Warning: Overwriting existing datatable with ID: %s',
                $id
            ));
        }

        $this->datatables[$id] = $datatable;

        error_log(sprintf(
            '[WP DataTable] Registered datatable: %s (Entity: %s)',
            $id,
            $datatable->get_entity_name()
        ));

        return true;
    }

    /**
     * Unregister a datatable
     *
     * Remove a datatable from registry by ID.
     *
     * @param string $id DataTable ID
     * @return bool True if unregistered successfully
     */
    public function unregister(string $id): bool {
        if (!isset($this->datatables[$id])) {
            return false;
        }

        unset($this->datatables[$id]);

        error_log(sprintf(
            '[WP DataTable] Unregistered datatable: %s',
            $id
        ));

        return true;
    }

    /**
     * Get a datatable by ID
     *
     * @param string $id DataTable ID
     * @return DataTableInterface|null DataTable instance or null if not found
     */
    public function get(string $id): ?DataTableInterface {
        return $this->datatables[$id] ?? null;
    }

    /**
     * Check if datatable is registered
     *
     * @param string $id DataTable ID
     * @return bool True if registered
     */
    public function has(string $id): bool {
        return isset($this->datatables[$id]);
    }

    /**
     * Get all registered datatables
     *
     * @return array Array of DataTableInterface instances keyed by ID
     */
    public function getAll(): array {
        return $this->datatables;
    }

    /**
     * Get all datatables that current user can access
     *
     * Filter datatables berdasarkan permission checks.
     *
     * @return array Array of DataTableInterface instances user can access
     */
    public function getAllAccessible(): array {
        $accessible = [];

        foreach ($this->datatables as $id => $datatable) {
            if ($datatable->can_access()) {
                $accessible[$id] = $datatable;
            }
        }

        return $accessible;
    }

    /**
     * Get count of registered datatables
     *
     * @return int Number of registered datatables
     */
    public function count(): int {
        return count($this->datatables);
    }

    /**
     * Get datatables grouped by entity
     *
     * Organize datatables by entity name.
     * Useful untuk UI listing.
     *
     * @return array Datatables grouped by entity name
     *
     * @example
     * ```php
     * [
     *     'customer' => [DataTable1, DataTable2],
     *     'agency' => [DataTable3],
     * ]
     * ```
     */
    public function getByEntity(): array {
        $grouped = [];

        foreach ($this->datatables as $id => $datatable) {
            $entity = $datatable->get_entity_name();
            if (!isset($grouped[$entity])) {
                $grouped[$entity] = [];
            }
            $grouped[$entity][$id] = $datatable;
        }

        return $grouped;
    }

    /**
     * Get datatables by layout type
     *
     * Filter datatables by layout (dual-panel or single-panel).
     *
     * @param string $layout Layout type ('dual-panel' or 'single-panel')
     * @return array Array of matching datatables
     */
    public function getByLayout(string $layout): array {
        $filtered = [];

        foreach ($this->datatables as $id => $datatable) {
            $config = $datatable->get_config();
            if (($config['layout'] ?? 'single-panel') === $layout) {
                $filtered[$id] = $datatable;
            }
        }

        return $filtered;
    }

    /**
     * Clear all registered datatables
     *
     * Remove all datatables from registry.
     * Useful for testing.
     *
     * @return void
     */
    public function clear(): void {
        $this->datatables = [];
        error_log('[WP DataTable] Registry cleared');
    }

    /**
     * Get registry info for debugging
     *
     * @return array Registry information
     */
    public function getInfo(): array {
        $info = [
            'total_datatables' => $this->count(),
            'accessible_datatables' => count($this->getAllAccessible()),
            'datatables' => []
        ];

        foreach ($this->datatables as $id => $datatable) {
            $config = $datatable->get_config();
            $info['datatables'][$id] = [
                'id' => $id,
                'entity' => $datatable->get_entity_name(),
                'layout' => $config['layout'] ?? 'single-panel',
                'can_access' => $datatable->can_access(),
                'text_domain' => $datatable->get_text_domain(),
            ];
        }

        return $info;
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
