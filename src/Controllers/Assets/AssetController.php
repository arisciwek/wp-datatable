<?php
/**
 * Asset Controller
 *
 * @package     WP_DataTable
 * @subpackage  Controllers/Assets
 * @version     0.1.0
 * @author      arisciwek
 *
 * Path: /wp-datatable/src/Controllers/Assets/AssetController.php
 *
 * Description: Central orchestrator untuk asset loading strategies.
 *              Manages registration dan execution of asset strategies.
 *              Implements Strategy Pattern untuk flexible asset loading.
 *
 * Responsibilities:
 * - Register asset strategies (DualPanel, SinglePanel, Premium)
 * - Execute strategies based on should_load() detection
 * - Hook into WordPress asset enqueue system
 * - Provide extensibility via wpdt_register_asset_strategies hook
 *
 * Strategy Pattern Benefits:
 * - Conditional loading (only load needed assets)
 * - Easy to add new strategies
 * - Separation of concerns
 * - Testable asset loading logic
 *
 * Usage:
 * ```php
 * // In main plugin file
 * $asset_controller = AssetController::get_instance();
 * $asset_controller->init();
 * ```
 *
 * Extensibility:
 * ```php
 * // Plugins can register custom strategies
 * add_action('wpdt_register_asset_strategies', function($controller) {
 *     $controller->register_strategy(new CustomAssets());
 * });
 * ```
 *
 * Changelog:
 * 0.1.0 - 2025-11-08
 * - Initial implementation
 * - Singleton pattern
 * - Strategy registration and execution
 * - WordPress hook integration
 * - Extensibility via actions
 */

namespace WPDataTable\Controllers\Assets;

defined('ABSPATH') || exit;

class AssetController {
    /**
     * Singleton instance
     *
     * @var AssetController|null
     */
    private static $instance = null;

    /**
     * Registered asset strategies
     *
     * @var array<string, AssetStrategyInterface>
     */
    private $strategies = [];

    /**
     * Get singleton instance
     *
     * @return AssetController
     */
    public static function get_instance(): AssetController {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - private untuk Singleton
     *
     * Setup WordPress hooks for asset enqueuing.
     */
    private function __construct() {
        // Hook into WordPress admin asset enqueue
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        /**
         * Action: After AssetController initialized
         *
         * @param AssetController $this Controller instance
         */
        do_action('wpdt_asset_controller_init', $this);
    }

    /**
     * Initialize controller
     *
     * Register default strategies dan allow plugins to register custom strategies.
     * Should be called once during plugin initialization.
     *
     * @return void
     */
    public function init(): void {
        // Register default strategies
        $this->register_default_strategies();

        /**
         * Action: Register custom asset strategies
         *
         * Allows plugins to register their own asset loading strategies.
         *
         * @param AssetController $this Controller instance
         *
         * @example
         * add_action('wpdt_register_asset_strategies', function($controller) {
         *     $controller->register_strategy(new PremiumAssets());
         * });
         */
        do_action('wpdt_register_asset_strategies', $this);

        /**
         * Action: After all strategies registered
         *
         * @param AssetController $this Controller instance
         */
        do_action('wpdt_asset_strategies_registered', $this);
    }

    /**
     * Register default asset strategies
     *
     * Register built-in strategies:
     * - DualPanelAssets: Dual panel layout
     * - SinglePanelAssets: Single panel layout
     * - PremiumAssets: Premium features (future)
     *
     * @return void
     */
    private function register_default_strategies(): void {
        // Dual Panel Strategy
        $this->register_strategy(new DualPanelAssets());

        // Single Panel Strategy
        $this->register_strategy(new SinglePanelAssets());

        // Future strategies
        // $this->register_strategy(new PremiumAssets());

        /**
         * Action: After default strategies registered
         *
         * @param AssetController $this Controller instance
         */
        do_action('wpdt_default_strategies_registered', $this);
    }

    /**
     * Register asset strategy
     *
     * Add new strategy to the registry.
     * Strategy will be executed if should_load() returns true.
     *
     * @param AssetStrategyInterface $strategy Strategy to register
     * @return bool True if registered successfully
     */
    public function register_strategy(AssetStrategyInterface $strategy): bool {
        $name = $strategy->get_name();

        // Check if strategy already registered
        if (isset($this->strategies[$name])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    '[WP DataTable] Strategy "%s" already registered. Skipping.',
                    $name
                ));
            }
            return false;
        }

        // Register strategy
        $this->strategies[$name] = $strategy;

        /**
         * Action: After strategy registered
         *
         * @param AssetStrategyInterface $strategy Registered strategy
         * @param AssetController $this Controller instance
         */
        do_action('wpdt_asset_strategy_registered', $strategy, $this);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[WP DataTable] Strategy "%s" registered successfully.',
                $name
            ));
        }

        return true;
    }

    /**
     * Enqueue assets
     *
     * WordPress hook callback.
     * Loop through registered strategies and enqueue assets
     * for strategies that should load.
     *
     * @return void
     */
    public function enqueue_assets(): void {
        /**
         * Action: Before enqueuing assets
         *
         * @param AssetController $this Controller instance
         */
        do_action('wpdt_before_enqueue_assets', $this);

        $loaded_strategies = [];

        // Loop through strategies
        foreach ($this->strategies as $name => $strategy) {
            // Check if strategy should load
            if (!$strategy->should_load()) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf(
                        '[WP DataTable] Strategy "%s" should_load() returned false. Skipping.',
                        $name
                    ));
                }
                continue;
            }

            // Enqueue styles and scripts
            try {
                $strategy->enqueue_styles();
                $strategy->enqueue_scripts();

                $loaded_strategies[] = $name;

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf(
                        '[WP DataTable] Strategy "%s" assets enqueued successfully.',
                        $name
                    ));
                }
            } catch (\Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf(
                        '[WP DataTable] Error enqueuing assets for strategy "%s": %s',
                        $name,
                        $e->getMessage()
                    ));
                }
            }
        }

        /**
         * Action: After assets enqueued
         *
         * @param array $loaded_strategies Names of loaded strategies
         * @param AssetController $this Controller instance
         */
        do_action('wpdt_after_enqueue_assets', $loaded_strategies, $this);
    }

    /**
     * Get registered strategies
     *
     * Return all registered strategies.
     * Useful for debugging and testing.
     *
     * @return array<string, AssetStrategyInterface> Registered strategies
     */
    public function get_strategies(): array {
        return $this->strategies;
    }

    /**
     * Get specific strategy by name
     *
     * @param string $name Strategy name
     * @return AssetStrategyInterface|null Strategy or null if not found
     */
    public function get_strategy(string $name): ?AssetStrategyInterface {
        return $this->strategies[$name] ?? null;
    }

    /**
     * Check if strategy is registered
     *
     * @param string $name Strategy name
     * @return bool True if registered
     */
    public function has_strategy(string $name): bool {
        return isset($this->strategies[$name]);
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }
}
