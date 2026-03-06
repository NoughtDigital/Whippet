<?php
/**
 * Main Plugin Class
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class
 */
class Plugin {

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	const VERSION = '1.0.2';

	/**
	 * Singleton instance
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Admin instance
	 *
	 * @var Admin
	 */
	public $admin;

	/**
	 * Assets instance
	 *
	 * @var Assets
	 */
	public $assets;

	/**
	 * Get singleton instance
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->define_constants();
		$this->init_hooks();
		$this->load_dependencies();
	}

	/**
	 * Define plugin constants
	 */
	private function define_constants() {
		define( 'WHIPPET_VERSION', self::VERSION );
		define( 'WHIPPET_PATH', plugin_dir_path( dirname( dirname( __FILE__ ) ) ) );
		define( 'WHIPPET_URL', plugin_dir_url( dirname( dirname( __FILE__ ) ) ) );
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		register_activation_hook( WHIPPET_PATH . 'whippet.php', array( $this, 'activate' ) );
		register_deactivation_hook( WHIPPET_PATH . 'whippet.php', array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize plugin
	 */
	public function init() {
		$this->load_textdomain();
		
		if ( is_admin() ) {
			$this->admin = new Admin();
			$this->assets = new Assets();
		}
	}

	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies() {
		require_once WHIPPET_PATH . 'inc/settings.php';
		require_once WHIPPET_PATH . 'inc/functions.php';
		require_once WHIPPET_PATH . 'inc/script-manager.php';
		require_once WHIPPET_PATH . 'inc/import-export.php';
		require_once WHIPPET_PATH . 'inc/tutorials.php';
		require_once WHIPPET_PATH . 'analytics/load.php';
		require_once WHIPPET_PATH . 'fonts/load.php';
		require_once WHIPPET_PATH . 'nazy-load/load.php';
		require_once WHIPPET_PATH . 'pages/load.php';
		require_once WHIPPET_PATH . 'scripts/load.php';
	}

	/**
	 * Load plugin textdomain
	 */
	private function load_textdomain() {
		load_plugin_textdomain( 'whippet', false, dirname( plugin_basename( WHIPPET_PATH . 'whippet.php' ) ) . '/languages/' );
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Create necessary database tables or options
		if ( ! get_option( 'whippet_version' ) ) {
			add_option( 'whippet_version', self::VERSION );
		}

		// Check and create database tables
		if ( function_exists( 'whippet_check_db' ) ) {
			whippet_check_db();
		}

	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
	}
}

