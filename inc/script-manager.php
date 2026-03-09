<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whippet pre-configuration
 * ============================================================================
 */

// Database check is handled in Plugin::activate()

global $whippet_db_version;
$whippet_db_version = '1.4';

/**
 * Created certain tables
 *
 * @param  string $table Table name.
 */
function whippet_create_db( $table ) {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . $table;
	$sql = '';

	switch ( $table ) {
		case 'whippet_disabled':
			$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				handler_type tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=css, 1=js',
				handler_name varchar(128) DEFAULT '' NOT NULL,
				url varchar(255) DEFAULT '' NOT NULL,
				regex TEXT NOT NULL DEFAULT '',
				PRIMARY KEY (id)
			) $charset_collate;";
			break;
		case 'whippet_enabled':
			$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				handler_type tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=css, 1=js',
				handler_name varchar(128) DEFAULT '' NOT NULL,
				content_type varchar(64) DEFAULT '' NOT NULL,
				url varchar(255) DEFAULT '' NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";
			break;
		case 'whippet_p_disabled':
			$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				name varchar(128) DEFAULT '' NOT NULL,
				url varchar(255) DEFAULT '' NOT NULL,
				regex TEXT NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";
			break;
		case 'whippet_p_enabled':
			$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				name varchar(128) DEFAULT '' NOT NULL,
				content_type varchar(64) DEFAULT '' NOT NULL,
				url varchar(255) DEFAULT '' NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";
			break;
	}

	if ( ! empty( $sql ) ) {
		dbDelta( $sql );
	}
}

/**
 * Install required tabled:
 * whippet_disabled, whippet_enabled
 */
function whippet_check_db() {
	global $whippet_db_version;
	$updated = false;
	$current_db_version = get_option( 'whippet_db_version', '1.0' );

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	if ( version_compare( $current_db_version, 1.1 , '<' ) ) {
		whippet_create_db( 'whippet_disabled' );
		whippet_create_db( 'whippet_enabled' );
		$updated = true;
	}

	if ( version_compare( $current_db_version, 1.2 , '<' ) ) {
		whippet_create_db( 'whippet_p_disabled' );
		whippet_create_db( 'whippet_p_enabled' );
		$updated = true;
	}

	if ( version_compare( $current_db_version, 1.3 , '<' ) ) {
		$updated = true;
	}

	if ( version_compare( $current_db_version, 1.4 , '<' ) ) {
		whippet_create_db( 'whippet_disabled' );
		$updated = true;
	}

	if ( $updated ) {
		update_option( 'whippet_db_version', $whippet_db_version );
	}
}

/**
 * Whippet actual functionalty
 * ============================================================================
 */
class Whippet {
	/**
	 * Stores current content type
	 *
	 * @var string
	 */
	private $content_type = '';

	/**
	 * Stores entire entered by user selection for CSS/JS
	 *
	 * @var array
	 */
	private $whippet_data = array();

	/**
	 * Stores entire entered by user selection for plugins
	 *
	 * @var array
	 */
	private $whippet_data_plugins = array();

	/**
	 * Stores list of all available assets (used in rendering panel)
	 *
	 * @var array
	 */
	private $collection = array();

	/**
	 * Stores list of asset dependencies
	 *
	 * @var array
	 */
	private $dependency_collection = array();

	/**
	 * Stores list of all plugins (regular + must-use).
	 *
	 * @var array
	 */
	private $all_plugins = array();

	/**
	 * Stores list of all content types defined in WordPress
	 *
	 * @var array
	 */
	private $all_content_types = array();

	/**
	 * List of all assets
	 *
	 * @var array
	 */
	private $all_assets = array();

	/**
	 * List of plugins not visible on the list
	 *
	 * @var array
	 */
	private $whitelist_plugins = array(
		'whippet'
	);

	/**
	 * List of CSS/JS not visible on the list
	 *
	 * @var array
	 */
	private $whitelist_assets = array(
		'js' => array( 'admin-bar' ),
		'css' => array( 'admin-bar', 'dashicons' ),
	);

	/**
	 * List of already rendered plugins (to omit conflict of existence in lower sections)
	 *
	 * @var array
	 */
	private $already_listed_plugins = array();

	/**
	 * Version number, to keep latest assets
	 *
	 * @var string
	 */
	protected $version = '1.0.2';

	/**
	 * Prefix for content-type scoped asset disable rules.
	 *
	 * @var string
	 */
	private $asset_content_type_scope_prefix = '@content_type:';

	/**
	 * Initilize entire machine
	 */
	function __construct() {

		if ( ! defined( 'WHIPPET_DISABLE_ON_FRONTEND' ) ) {
			add_action( 'init', array( $this, 'update_configuration' ), 1 );
		} elseif ( defined( 'WHIPPET_ENABLE_ON_BACKEND' ) ) {
			add_action( 'admin_init', array( $this, 'update_configuration' ), 1 );
		}
		add_action( 'init', array( $this, 'load_configuration' ), 2 );
		add_action( 'init', array( $this, 'read_plugins_list' ), 3 );
		add_action( 'init', array( $this, 'conditionally_remove_emoji' ), 4 );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'template_redirect', array( $this, 'detect_content_type' ) );

		if ( ! defined( 'WHIPPET_DISABLE_ON_FRONTEND' ) && ! is_admin() ) {
			add_action( 'wp_head', array( $this, 'collect_assets' ), 10000 );
			add_action( 'wp_footer', array( $this, 'collect_assets' ), 10000 );
			add_filter( 'script_loader_src', array( $this, 'unload_assets' ), 10, 2 );
			add_filter( 'style_loader_src', array( $this, 'unload_assets' ), 10, 2 );

			if ( ! defined( 'DISABLE_WHIPPET_PANEL' ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'append_asset' ) );
				add_action( 'wp_footer', array( $this, 'render_panel' ), 10000 + 1 );
			}
		}

		if ( defined( 'WHIPPET_ENABLE_ON_BACKEND' ) && is_admin() ) {
			add_action( 'admin_head', array( $this, 'collect_assets' ), 10000 );
			add_action( 'admin_footer', array( $this, 'collect_assets' ), 10000 );
			add_filter( 'script_loader_src', array( $this, 'unload_assets' ), 10, 2 );
			add_filter( 'style_loader_src', array( $this, 'unload_assets' ), 10, 2 );

			if ( ! defined( 'DISABLE_WHIPPET_PANEL' ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'append_asset' ) );
				add_action( 'admin_footer', array( $this, 'render_panel' ), 10000 + 1 );
			}
		}

		if ( ! defined( 'DISABLE_WHIPPET_PANEL' ) ) {
			add_action( 'admin_bar_menu', array( $this, 'add_node_to_admin_bar' ), 1000 );
		}
	}

	/**
	 * Conditionally remove emoji script and styles
	 */
	public function conditionally_remove_emoji() {
		if ( ! $this->get_visibility_asset( 'js', 'wp-emoji' ) ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
		}
	}

	/**
	 * Check whether asset should be disabled or not.
	 *
	 * @param  string $url 		Handler URL.
	 * @param  string $handle 	Asset handle name.
	 * @return mixed
	 */
	public function unload_assets( $url, $handle ) {
		if ( strpos( $handle, 'wp-polyfill' ) === 0 ) {
			return $url;
		}

		$type = ( current_filter() === 'script_loader_src' ) ? 'js' : 'css';

		return $this->get_visibility_asset( $type, $handle ) ? $url : false;
	}

	/**
	 * Sniff whether Emoji has already been disable by 3rd part extensions
	 *
	 * @return bool State
	 */
	private function detect_emoji_visibility() {
		return (bool) has_action( 'wp_head', 'print_emoji_detection_script' );
	}

	/**
	 * Read list of all plugins (regular + must-use).
	 *
	 * @return void
	 */
	public function read_plugins_list() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->all_plugins = get_plugins();
		if ( $this->get_mu_plugins_available() ) {
			$this->all_plugins = array_merge( $this->all_plugins, $this->get_mu_plugin_list() );
		}
	}

	/**
	 * Whether the must-use plugin directory is available and readable.
	 *
	 * @return bool
	 */
	private function get_mu_plugins_available() {
		return defined( 'WPMU_PLUGIN_DIR' ) && is_dir( WPMU_PLUGIN_DIR ) && is_readable( WPMU_PLUGIN_DIR );
	}

	/**
	 * Get plugin list from the must-use plugins directory (same format as get_plugins()).
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_mu_plugin_list() {
		$mu_plugins = array();
		if ( ! $this->get_mu_plugins_available() || ! function_exists( 'get_plugin_data' ) ) {
			return $mu_plugins;
		}

		$mu_dir = trailingslashit( WPMU_PLUGIN_DIR );
		$dir_handle = opendir( WPMU_PLUGIN_DIR );
		if ( ! is_resource( $dir_handle ) ) {
			return $mu_plugins;
		}

		while ( false !== ( $entry = readdir( $dir_handle ) ) ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}
			$full_path = $mu_dir . $entry;
			if ( is_dir( $full_path ) ) {
				$plugin_file = $this->get_mu_plugin_php_file( $full_path, $entry );
				if ( $plugin_file ) {
					$plugin_data = $this->get_mu_plugin_data( $plugin_file );
					if ( ! empty( $plugin_data ) ) {
						$mu_plugins[ $entry . '/' . basename( $plugin_file ) ] = $plugin_data;
					}
				}
			} elseif ( is_file( $full_path ) && preg_match( '/\.php$/i', $entry ) ) {
				$plugin_data = $this->get_mu_plugin_data( $full_path );
				if ( ! empty( $plugin_data ) ) {
					$mu_plugins[ $entry ] = $plugin_data;
				}
			}
		}
		closedir( $dir_handle );

		return $mu_plugins;
	}

	/**
	 * Find the main PHP file in a must-use plugin directory.
	 *
	 * @param string $dir_path Full path to plugin directory.
	 * @param string $dir_name Directory name (plugin slug).
	 * @return string|false Full path to main plugin file or false.
	 */
	private function get_mu_plugin_php_file( $dir_path, $dir_name ) {
		$likely = trailingslashit( $dir_path ) . $dir_name . '.php';
		if ( is_file( $likely ) && is_readable( $likely ) ) {
			return $likely;
		}
		$dir_handle = @opendir( $dir_path );
		if ( ! is_resource( $dir_handle ) ) {
			return false;
		}
		while ( false !== ( $entry = readdir( $dir_handle ) ) ) {
			if ( '.' === $entry || '..' === $entry || ! preg_match( '/\.php$/i', $entry ) ) {
				continue;
			}
			closedir( $dir_handle );
			return trailingslashit( $dir_path ) . $entry;
		}
		closedir( $dir_handle );
		return false;
	}

	/**
	 * Get plugin header data for a must-use plugin file (WP 6.2+ safe).
	 *
	 * @param string $plugin_file Full path to plugin PHP file.
	 * @return array<string, string>
	 */
	private function get_mu_plugin_data( $plugin_file ) {
		if ( ! is_file( $plugin_file ) || ! is_readable( $plugin_file ) ) {
			return array();
		}
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$data = get_plugin_data( $plugin_file, false, false );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Base URL for must-use plugins directory (for asset URL detection). Empty string if not available.
	 *
	 * @return string
	 */
	private function get_mu_plugins_base_url() {
		if ( ! $this->get_mu_plugins_available() ) {
			return '';
		}
		return content_url( basename( WPMU_PLUGIN_DIR ) );
	}

	/**
	 * Get information regarding used assets
	 *
	 * @return bool
	 */
	public function collect_assets() {
		global $whippet_helper;

		/**
		 * Imitate full untouched list without dequeued assets
		 * Appends part of original table. Safe approach.
		 */
		$data_assets = array(
			'js' => wp_scripts(),
			'css' => wp_styles(),
		);

		/**
		 * Prepare list for Theme and Misc
		 */
		$plugins_url = plugins_url();
		$mu_plugins_url = $this->get_mu_plugins_base_url();
		$theme_root_uri = get_theme_root_uri();
		foreach ( $data_assets as $type => $data ) {
			$done = isset( $data->done ) && is_array( $data->done ) ? $data->done : array();
			$registered = isset( $data->registered ) && is_array( $data->registered ) ? $data->registered : array();
			foreach ( $done as $el ) {
				if ( ! in_array( $el, $this->whitelist_assets[ $type ], true ) ) {
					$reg = isset( $registered[ $el ] ) ? $registered[ $el ] : null;
					$src = ( $reg && is_object( $reg ) && isset( $reg->src ) ) ? $reg->src : '';
					if ( ! is_string( $src ) || '' === $src ) {
						continue;
					}
					$url = $this->prepare_correct_url( $src );

					if ( $plugins_url && is_string( $url ) && false !== strpos( $url, $plugins_url ) ) {
						$resource_name = 'plugins';
						$plugin_path = str_replace( $plugins_url, '', $url );
						$plugin_path = ltrim( $plugin_path, '/' );
						$plugin_path_parts = explode( '/', $plugin_path );
						$plugin_dir = isset( $plugin_path_parts[0] ) ? $plugin_path_parts[0] : '';

					} elseif ( $mu_plugins_url && is_string( $url ) && false !== strpos( $url, $mu_plugins_url ) ) {
						$resource_name = 'plugins';
						$plugin_path = str_replace( $mu_plugins_url, '', $url );
						$plugin_path = ltrim( $plugin_path, '/' );
						$plugin_path_parts = explode( '/', $plugin_path );
						$plugin_dir = isset( $plugin_path_parts[0] ) ? $plugin_path_parts[0] : '';

					} elseif ( $theme_root_uri && is_string( $url ) && false !== strpos( $url, $theme_root_uri ) ) {
						$resource_name = 'theme';
						$plugin_dir = null;
					} else {
						$resource_name = 'misc';
						$plugin_dir = null;
					}

						$url_info = pathinfo( $url );
						$filename = isset( $url_info['basename'] ) ? $url_info['basename'] : '';
						$file_base = isset( $url_info['filename'] ) ? $url_info['filename'] : '';
						$file_extension = isset( $url_info['extension'] ) ? $url_info['extension'] : '';

						$deps = ( $reg && is_object( $reg ) && isset( $reg->deps ) && is_array( $reg->deps ) ) ? $reg->deps : array();
						$arr = array(
							'url_full' => $url,
							'filename' => $filename,
							'file_base' => $file_base,
							'is_external' => $this->check_if_external( $url ),
							'file_extension' => $file_extension,
							'state' => $this->get_visibility_asset( $type, $el ),
							'size' => $this->get_asset_size( $url ),
							'deps' => $deps,
						);

						if ( 'plugins' === $resource_name && '' !== $plugin_dir ) {
							$arr['plugin'] = '';
							$this->collection[ $resource_name ][ $plugin_dir ][ $type ][ $el ] = $arr;
						} elseif ( 'plugins' !== $resource_name ) {
							$this->collection[ $resource_name ][ $type ][ $el ] = $arr;
						}

						$this->dependency_collection[] = array(
							'name' => $el,
							'filename' => $filename,
							'file_extension' => $file_extension,
							'state' => $this->get_visibility_asset( $type, $el ),
							'deps' => $deps,
						);
					}
			}
		}
		global $wp_version;
		if ( version_compare( $wp_version, '4.2', '>=' ) ) {
			$url = '/wp-includes/js/wp-emoji-release.min.js';

			$this->collection['misc']['js']['wp-emoji'] = array(
				'url_full' => $url,
				'filename' => 'wp-emoji-release.min.js',
				'file_base' => 'wp-emoji-release.min',
				'is_external' => false,
				'file_extension' => 'js',
				'state' => $this->detect_emoji_visibility(),
				'size' => $this->get_asset_size( $url ),
				'deps' => array(),
			);

		}

		return false;
	}

	/**
	 * Checks whether URL is internal or external resource
	 *
	 * @param  string $url Input URL.
	 * @return bool      True if external
	 */
	private function check_if_external( $url ) {
		if ( ! is_string( $url ) ) {
			return false;
		}
		$components_dest = parse_url( $url );
		$components_base = parse_url( get_site_url() );
		if ( ! is_array( $components_dest ) || ! is_array( $components_base ) ) {
			return false;
		}
		$host_dest = isset( $components_dest['host'] ) ? $components_dest['host'] : '';
		$host_base = isset( $components_base['host'] ) ? $components_base['host'] : '';
		return '' !== $host_dest && 0 !== strcasecmp( $host_dest, $host_base );
	}

	/**
	 * Initialize interface translation
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'whippet', false, dirname( plugin_basename( WHIPPET_PATH . 'whippet.php' ) ) . '/languages' );
	}

	/**
	 * Adds notification after plugin activation how to use Whippet
	 */
	public function load_plugin() {
		if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
			if ( get_option( 'whippet_Issue_1' ) ) {
				delete_option( 'whippet_Issue_1' );
				deactivate_plugins( plugin_basename( __FILE__ ) );
				add_action( 'admin_notices', array( $this, 'whippet_null' ) );
			} elseif ( get_option( 'whippet_Issue_2' ) ) {
				delete_option( 'whippet_Issue_2' );
				deactivate_plugins( plugin_basename( __FILE__ ) );
				add_action( 'admin_notices', array( $this, 'whippet_error' ) );
			} elseif ( get_option( 'whippet_Issue_3' ) ) {
				delete_option( 'whippet_Issue_3' );
				deactivate_plugins( plugin_basename( __FILE__ ) );
				add_action( 'admin_notices', array( $this, 'whippet_error_mu_1' ) );
			} elseif ( get_option( 'whippet_Issue_4' ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				add_action( 'admin_notices', array( $this, 'whippet_error_mu_2' ) );
			}
		}

		if ( is_admin() && 'Whippet' === get_option( 'Activated_Plugin' ) ) {
			delete_option( 'Activated_Plugin' );
		}
	}

	/**
	 * Loads functionality that allows to enable/disable js/css without site reload
	 */
	public function append_asset( $hook_suffix = null ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( is_admin() ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			$allowed_screens = array(
				'tools_page_whippet',
				'tools_page_whippet-analytics',
				'tools_page_whippet-import-export',
				'tools_page_whippet-tutorials',
			);

			if ( $screen && ! in_array( $screen->id, $allowed_screens, true ) ) {
				return;
			}
		}

		add_action( is_admin() ? 'admin_head' : 'wp_head', array( $this, 'add_font_preconnect_panel' ), 1 );

		wp_enqueue_style( 'whippet-figtree-font', 'https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap', array(), null );
		wp_enqueue_style( 'whippet', untrailingslashit( plugins_url( '../dist/css/style-whippet.css', __FILE__ ) ), array( 'whippet-figtree-font' ), $this->version, false );
		wp_enqueue_script( 'whippet', untrailingslashit( plugins_url( '../dist/js/app.js', __FILE__ ) ), array(), $this->version, true );
	}

	/**
	 * Get asset type based on name/ID
	 *
	 * @param  int|string $input Handler type.
	 * @return int|string        Reversed handler type.
	 */
	private function get_handler_type( $input ) {
		$data = array(
			'css' => 0,
			'js' => 1,
		);

		if ( is_numeric( $input ) ) {
			$data = array_flip( $data );
		}

		return $data[ $input ];
	}

	/**
	 * Build a stored scope value for a content type asset rule.
	 *
	 * @param string $content_type Content type slug.
	 * @return string
	 */
	private function get_asset_content_type_scope_value( $content_type ) {
		$content_type = sanitize_key( $content_type );
		if ( '' === $content_type ) {
			return '';
		}

		return $this->asset_content_type_scope_prefix . $content_type;
	}

	/**
	 * Parse a stored asset scope value into a content type slug.
	 *
	 * @param string $value Stored scope value.
	 * @return string
	 */
	private function get_asset_content_type_from_scope_value( $value ) {
		$value = is_string( $value ) ? $value : '';
		if ( 0 !== strpos( $value, $this->asset_content_type_scope_prefix ) ) {
			return '';
		}

		return sanitize_key( substr( $value, strlen( $this->asset_content_type_scope_prefix ) ) );
	}

	/**
	 * Execute action once checkbox is changed
	 */
	public function update_configuration() {
		global $wpdb;

		if ( ! current_user_can( 'manage_options' ) ||
			! isset( $_POST['whippetUpdate'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['whippetUpdate'] ) ), 'whippet' ) ||
			! isset( $_POST['currentURL'] ) ||
			empty( $_POST['currentURL'] ) ) {
			return false;
		}

		$all_assets_raw = isset( $_POST['allAssets'] ) ? wp_unslash( $_POST['allAssets'] ) : '[]';
		$all_assets = json_decode( $all_assets_raw );
		if ( ! is_array( $all_assets ) ) {
			$all_assets = array();
		}
		$all_assets = array_filter( array_map( 'sanitize_text_field', $all_assets ) );

		/**
		 * Clearing old configuration
		 * Removing all selected plugins (list of visible passed in array).
		 * Forget about phpcs warning. It's safe & prepared SQL
		 *
		 * 1. Clear disable everywhere
		 * 2. Clear enable content types & enable here
		 */

		/**
		 * CSS/JS
		 */
		$current_url = esc_url_raw( wp_unslash( $_POST['currentURL'] ) );
		if ( ! empty( $all_assets ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $all_assets ), '%s' ) );
			$sql = $wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}whippet_disabled WHERE handler_name IN ($placeholders)",
				$all_assets
			);
			$wpdb->query( $sql );

			$sql = $wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}whippet_enabled WHERE handler_name IN ($placeholders)",
				$all_assets
			);
			$wpdb->query( $sql );
		}


		/**
		 * Inserting new configuration (CSS/JS)
		 */
		if ( isset( $_POST['disabled'] ) && ! empty( $_POST['disabled'] ) && is_array( $_POST['disabled'] ) ) {
			foreach ( $_POST['disabled'] as $type => $assets ) {
				if ( ! empty( $assets ) && is_array( $assets ) ) {
					$type = sanitize_text_field( $type );
					foreach ( $assets as $handle => $where ) {
						if ( ! empty( $where ) && is_array( $where ) ) {
							$handle = sanitize_text_field( $handle );
							$rules = array();

							if ( ! empty( $where['type'] ) ) {
								$legacy_place = sanitize_text_field( $where['type'] );
								if ( 'everywhere' === $legacy_place ) {
									$rules[] = array( 'url' => '', 'regex' => '' );
								} elseif ( 'here' === $legacy_place ) {
									$rules[] = array( 'url' => $current_url, 'regex' => '' );
								}
							}

							if ( ! empty( $where['everywhere'] ) ) {
								$rules[] = array( 'url' => '', 'regex' => '' );
							}

							if ( ! empty( $where['here'] ) ) {
								$rules[] = array( 'url' => $current_url, 'regex' => '' );
							}

							foreach ( array( 'post', 'page' ) as $content_type ) {
								if ( ! empty( $where[ $content_type ] ) ) {
									$scope_value = $this->get_asset_content_type_scope_value( $content_type );
									if ( '' !== $scope_value ) {
										$rules[] = array(
											'url' => $scope_value,
											'regex' => '',
										);
									}
								}
							}

							$regex = isset( $where['regex'] ) ? sanitize_text_field( wp_unslash( $where['regex'] ) ) : '';
							if ( '' !== trim( $regex ) ) {
								$rules[] = array(
									'url' => '',
									'regex' => $regex,
								);
							}

							if ( empty( $rules ) ) {
								continue;
							}

							foreach ( $rules as $rule ) {
								$wpdb->insert(
									$wpdb->prefix . 'whippet_disabled',
									array(
										'handler_type' => $this->get_handler_type( $type ),
										'handler_name' => $handle,
										'url' => $rule['url'],
										'regex' => $rule['regex'],
									),
									array( '%d', '%s', '%s', '%s' )
								);
							}
						}
					}
				}
			}
		}

		if ( isset( $_POST['enabled'] ) && ! empty( $_POST['enabled'] ) && is_array( $_POST['enabled'] ) ) {
			foreach ( $_POST['enabled'] as $type => $assets ) {
				if ( ! empty( $assets ) && is_array( $assets ) ) {
					$type = sanitize_text_field( $type );
					foreach ( $assets as $handle => $content_types ) {
						if ( ! empty( $content_types ) && is_array( $content_types ) ) {
							$handle = sanitize_text_field( $handle );
							foreach ( $content_types as $content_type => $val ) {
								$content_type = sanitize_text_field( $content_type );
								$url_val = ( 'here' === $content_type ? $current_url : ( 'regex' === $content_type ? sanitize_text_field( wp_unslash( $val ) ) : '' ) );
								if ( 'regex' === $content_type && '' === trim( $url_val ) ) {
									continue;
								}
								$wpdb->insert(
									$wpdb->prefix . 'whippet_enabled',
									array(
										'handler_type' => $this->get_handler_type( $type ),
										'handler_name' => $handle,
										'content_type' => $content_type,
										'url' => $url_val,
									),
									array( '%d', '%s', '%s', '%s' )
								);
							}
						}
					}
				}
			}
		}


		/**
		 * Plugins
		 */
		$wpdb->query( $wpdb->prepare( 
			"DELETE FROM {$wpdb->prefix}whippet_p_disabled WHERE (url = '' OR url = %s)",
			$current_url
		) );

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}whippet_p_enabled WHERE (url = '' OR url = %s OR content_type = 'regex')",
			$current_url
		) );

		/**
		 * Inserting new configuration
		 */
		if ( isset( $_POST['disabledPlugin'] ) && ! empty( $_POST['disabledPlugin'] ) && is_array( $_POST['disabledPlugin'] ) ) {
			foreach ( $_POST['disabledPlugin'] as $plugin => $where ) {
				if ( ! empty( $where ) ) {
					$plugin = sanitize_text_field( $plugin );
					$where = sanitize_text_field( $where );
					$regex = '';
					if ( 'regex' === $where && isset( $_POST['disabledPluginRegex'][ $plugin ] ) ) {
						$regex = sanitize_text_field( wp_unslash( $_POST['disabledPluginRegex'][ $plugin ] ) );
					}
					
					$wpdb->insert(
						$wpdb->prefix . 'whippet_p_disabled',
						array(
							'name' => $plugin,
							'url' => ( 'here' === $where ? $current_url : '' ),
							'regex' => $regex,
						),
						array( '%s', '%s', '%s' )
					);
				}
			}
		}

		if ( isset( $_POST['enabledPlugin'] ) && ! empty( $_POST['enabledPlugin'] ) && is_array( $_POST['enabledPlugin'] ) ) {
			foreach ( $_POST['enabledPlugin'] as $plugin => $content_types ) {
				if ( ! empty( $content_types ) && is_array( $content_types ) ) {
					$plugin = sanitize_text_field( $plugin );
					foreach ( $content_types as $content_type => $val ) {
						$content_type = sanitize_text_field( $content_type );
						$url_val = ( 'here' === $content_type ? $current_url : ( 'regex' === $content_type ? sanitize_text_field( wp_unslash( $val ) ) : '' ) );
						if ( 'regex' === $content_type && '' === trim( $url_val ) ) {
							continue;
						}
						$wpdb->insert(
							$wpdb->prefix . 'whippet_p_enabled',
							array(
								'name' => $plugin,
								'content_type' => $content_type,
								'url' => $url_val,
							),
							array( '%s', '%s', '%s' )
						);
					}
				}
			}
		}

		if ( isset( $_POST['whippet_delete_rules'] ) ) {
			$this->delete_global_view_rules( wp_unslash( $_POST['whippet_delete_rules'] ) );
		}

		if ( isset( $_POST['whippet_frontend_settings_present'] ) ) {
			update_option( 'whippet_scripts_display_archives', ! empty( $_POST['whippet_scripts_display_archives'] ) ? 1 : 0 );
			update_option( 'whippet_scripts_display_deps', ! empty( $_POST['whippet_scripts_display_deps'] ) ? 1 : 0 );
			update_option( 'whippet_scripts_testing_mode', ! empty( $_POST['whippet_scripts_testing_mode'] ) ? 1 : 0 );
			update_option( 'whippet_scripts_hide_disclaimer', ! empty( $_POST['whippet_scripts_hide_disclaimer'] ) ? 1 : 0 );

			$mu_mode_old = (bool) get_option( 'whippet_scripts_mu_mode', 0 );
			$mu_mode_new = ! empty( $_POST['whippet_scripts_mu_mode'] );
			if ( $mu_mode_new !== $mu_mode_old ) {
				update_option( 'whippet_scripts_mu_mode', $mu_mode_new ? 1 : 0 );
				if ( $mu_mode_new && function_exists( 'whippet_scripts_install_mu_plugin' ) ) {
					$install = whippet_scripts_install_mu_plugin();
					set_transient( 'whippet_mu_install_result', $install, 30 );
				}
			}
		}

		$mu_plugins = get_option( 'whippet_p_mu_plugins', array() );
		if ( ! is_array( $mu_plugins ) ) {
			$mu_plugins = array();
		}
		if ( isset( $_POST['disabledPlugin'] ) && is_array( $_POST['disabledPlugin'] ) ) {
			foreach ( $_POST['disabledPlugin'] as $p => $where ) {
				if ( '' !== sanitize_text_field( $where ) ) {
					$p = sanitize_text_field( $p );
					$mu_plugins[ $p ] = ! empty( $_POST['disabledPluginMu'][ $p ] ) ? 1 : 0;
				}
			}
			update_option( 'whippet_p_mu_plugins', $mu_plugins );
		}

		$http_referer = '';
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$http_referer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		}
		if ( empty( $http_referer ) || ! wp_http_validate_url( $http_referer ) ) {
			$redirect_url = home_url( $current_url );
			$http_referer = wp_http_validate_url( $redirect_url ) ? $redirect_url : admin_url( 'tools.php?page=whippet' );
		}

		if ( ! defined( 'whippet_CACHE_CONTROL' ) ) {
			if ( function_exists( 'w3tc_pgcache_flush' ) ) {
				w3tc_pgcache_flush();
			} elseif ( function_exists( 'wp_cache_clear_cache' ) ) {
				wp_cache_clear_cache();
			} elseif ( function_exists( 'rocket_clean_files' ) ) {
				rocket_clean_files( $http_referer );
			}
		}

		// Redirect to refresh plugins configuration.
		wp_safe_redirect( $http_referer );
		exit;
	}

	/**
	 * Generates Whippet item with dynamically generated subtrees in administration menu
	 *
	 * @param mixed $wp_admin_bar 	Admin bar object.
	 */
	public function add_node_to_admin_bar( $wp_admin_bar ) {
		/**
		 * Checks whether Whippet should appear on frontend/backend or not
		 */
		if (
			! current_user_can( 'manage_options' ) ||
			( defined( 'whippet_DISABLE_ON_FRONTEND' ) && ! is_admin() ) ||
			( ! defined( 'whippet_ENABLE_ON_BACKEND' ) && is_admin() )
		) {
			return;
		}

		$wp_admin_bar->add_menu( array(
			'id'     => 'whippet',
			'title'  => esc_html__( 'Whippet', 'whippet' ),
			'meta'	 => array( 'class' => 'whippet-object' ),
		) );
	}

	/**
	 * Checks whether asset is enabled/disabled
	 *
	 * @param  string $type   Handler type (CSS/JS).
	 * @param  string $plugin Handler name.
	 * @return bool          State
	 */
	private function get_visibility_asset( $type = '', $plugin = '' ) {
		$state = true;
		$current_url = $this->get_current_url();

		if ( isset( $this->whippet_data['disabled'][ $type ][ $plugin ] ) ) {
			$disabled = $this->whippet_data['disabled'][ $type ][ $plugin ];
			$is_disabled_scope = false;
			if ( ! empty( $disabled['everywhere'] ) ) {
				$is_disabled_scope = true;
			} elseif ( ! empty( $disabled['here'] ) && $current_url !== '' ) {
				$is_disabled_scope = true;
			} elseif ( '' !== $this->content_type && ! empty( $disabled[ $this->content_type ] ) ) {
				$is_disabled_scope = true;
			} elseif ( ! empty( $disabled['regex'] ) ) {
				$pattern = $disabled['regex'];
				$regex = '/' . str_replace( '/', '\/', $pattern ) . '/';
				if ( false !== @preg_match( $regex, '' ) && preg_match( $regex, $current_url ) ) {
					$is_disabled_scope = true;
				}
			}
			if ( $is_disabled_scope ) {
				$state = false;
				if ( isset( $this->whippet_data['enabled'][ $type ][ $plugin ][ $this->content_type ] ) ||
					isset( $this->whippet_data['enabled'][ $type ][ $plugin ]['here'] ) ) {
					$state = true;
				}
				if ( ! $state && isset( $this->whippet_data['enabled'][ $type ][ $plugin ]['regex'] ) ) {
					$pattern = $this->whippet_data['enabled'][ $type ][ $plugin ]['regex'];
					if ( '' !== trim( (string) $pattern ) ) {
						$regex = '/' . str_replace( '/', '\/', $pattern ) . '/';
						if ( false !== @preg_match( $regex, '' ) && preg_match( $regex, $current_url ) ) {
							$state = true;
						}
					}
				}
			}
		}

		return $state;
	}

	/**
	 * Checks whether plugin is enabled/disabled
	 *
	 * @param  string $plugin   Plugin type.
	 * @return bool          State
	 */
	private function get_visibility_plugin( $plugin = '' ) {
		$state = 1;

		if ( isset( $this->whippet_data_plugins['disabled'][ $plugin ] ) ) {

				if ( isset( $this->whippet_data_plugins['disabled'][ $plugin ]['regex'] ) ) {
				$pattern = $this->whippet_data_plugins['disabled'][ $plugin ]['regex'];
				$regex   = '/' . str_replace( '/', '\/', $pattern ) . '/';
				$matches = array();
				if ( false !== @preg_match( $regex, '' ) ) {
					preg_match( $regex, esc_url( $this->get_current_url() ), $matches );
				}
				$state = ( count( $matches ) ? 0 : 1 );
			} else {
				$state = 0;
			}

			if ( isset( $this->whippet_data_plugins['enabled'][ $plugin ][ $this->content_type ] ) ||
				isset( $this->whippet_data_plugins['enabled'][ $plugin ]['here'] ) ) {
				$state = 1;
			}
			if ( ! $state && isset( $this->whippet_data_plugins['enabled'][ $plugin ]['regex'] ) ) {
				$pattern = $this->whippet_data_plugins['enabled'][ $plugin ]['regex'];
				if ( '' !== trim( (string) $pattern ) ) {
					$regex = '/' . str_replace( '/', '\/', $pattern ) . '/';
					if ( false !== @preg_match( $regex, '' ) && preg_match( $regex, $this->get_current_url() ) ) {
						$state = 1;
					}
				}
			}
		}

		return $state;
	}

	/**
	 * Exception for address starting from "//example.com" instead of
	 * "http://example.com". WooCommerce likes such a format
	 *
	 * @param  string $url Incorrect URL.
	 * @return string      Correct URL.
	 */
	private function prepare_correct_url( $url ) {
		if ( ! is_string( $url ) ) {
			return '';
		}
		if ( isset( $url[0] ) && isset( $url[1] ) && '/' === $url[0] && '/' === $url[1] ) {
			return ( is_ssl() ? 'https:' : 'http:' ) . $url;
		}
		return $url;
	}

	/**
	 * Checks how heavy is file
	 *
	 * @param  string $src    URL.
	 * @return int          Size in KB.
	 */
	private function get_asset_size( $src ) {
		$weight = 0;
		if ( ! is_string( $src ) ) {
			return $weight;
		}
		$src_parts = explode( '?', $src );
		$src_path = isset( $src_parts[0] ) ? $src_parts[0] : $src;
		$home = get_theme_root() . '/../..';
		$src_relative = $home . str_replace( get_home_url(), '', $this->prepare_correct_url( $src_path ) );

		if ( $src_relative && file_exists( $src_relative ) ) {
			$weight = round( filesize( $src_relative ) / 1024, 1 );
		}

		return $weight;
	}

	/**
	 * Detect current content type
	 */
	public function detect_content_type() {
		if ( is_singular() ) {
			$this->content_type = get_post_type();
		} elseif ( get_option( 'whippet_scripts_display_archives', 0 ) ) {
			if ( is_category() ) {
				$this->content_type = 'category';
			} elseif ( is_tag() ) {
				$this->content_type = 'tag';
			} elseif ( is_author() ) {
				$this->content_type = 'author';
			} elseif ( is_date() ) {
				$this->content_type = 'date';
			} elseif ( is_search() ) {
				$this->content_type = 'search';
			} elseif ( is_404() ) {
				$this->content_type = '404';
			} elseif ( is_archive() ) {
				$this->content_type = 'archive';
			}
		}
	}

	/**
	 * Run in case someone updated Whippet by direct copy/paste
	 * instead of activation described in documentation
	 */
	private function verify_database_structure() {
		global $wpdb;
		$tables = array( 'whippet_disabled', 'whippet_enabled', 'whippet_p_disabled', 'whippet_p_enabled' );

		foreach ( $tables as $table ) {
			$table_name = $wpdb->prefix . $table;
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
			if ( $found !== $table_name ) {
				whippet_create_db( $table );
			}
		}
	}

	/**
	 * Reading saved configuration
	 */
	public function load_configuration() {
		global $wpdb;

		/**
		 * CSS/JS
		 */
		$out = array();

		// Verify that database structure is correct.
		whippet_check_db();

		$current_url = $this->get_current_url();
		$disabled_global = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}whippet_disabled WHERE url = '' AND (regex IS NULL OR regex = '')", ARRAY_A );
		$disabled_here  = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}whippet_disabled WHERE url = %s",
			$current_url
		), ARRAY_A );
		$disabled_content_types = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}whippet_disabled WHERE url LIKE %s",
				$this->asset_content_type_scope_prefix . '%'
			),
			ARRAY_A
		);
		$disabled_regex = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}whippet_disabled WHERE regex IS NOT NULL AND regex != ''", ARRAY_A );

		$enabled_posts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}whippet_enabled WHERE content_type != 'here' AND content_type != 'regex'", ARRAY_A );
		$enabled_here  = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}whippet_enabled WHERE content_type = 'here' AND url = %s",
			$current_url
		), ARRAY_A );
		$enabled_regex = $wpdb->get_results( "SELECT handler_type, handler_name, url FROM {$wpdb->prefix}whippet_enabled WHERE content_type = 'regex'", ARRAY_A );
		$enabled = array_merge( $enabled_here, $enabled_posts );

		if ( ! empty( $disabled_global ) ) {
			foreach ( $disabled_global as $row ) {
				$type = $this->get_handler_type( $row['handler_type'] );
				$out['disabled'][ $type ][ $row['handler_name'] ]['everywhere'] = true;
			}
		}

		if ( ! empty( $disabled_here ) ) {
			foreach ( $disabled_here as $row ) {
				$type = $this->get_handler_type( $row['handler_type'] );
				$out['disabled'][ $type ][ $row['handler_name'] ]['here'] = true;
			}
		}

		if ( ! empty( $disabled_content_types ) ) {
			foreach ( $disabled_content_types as $row ) {
				$type = $this->get_handler_type( $row['handler_type'] );
				$content_type = $this->get_asset_content_type_from_scope_value( isset( $row['url'] ) ? $row['url'] : '' );
				if ( '' !== $content_type ) {
					$out['disabled'][ $type ][ $row['handler_name'] ][ $content_type ] = true;
				}
			}
		}

		if ( ! empty( $disabled_regex ) ) {
			foreach ( $disabled_regex as $row ) {
				$type = $this->get_handler_type( $row['handler_type'] );
				$out['disabled'][ $type ][ $row['handler_name'] ]['regex'] = isset( $row['regex'] ) ? stripslashes( (string) $row['regex'] ) : '';
			}
		}

		if ( ! empty( $enabled ) ) {
			foreach ( $enabled as $row ) {
				$type = $this->get_handler_type( $row['handler_type'] );
				$out['enabled'][ $type ][ $row['handler_name'] ][ $row['content_type'] ] = true;
			}
		}

		if ( ! empty( $enabled_regex ) ) {
			foreach ( $enabled_regex as $row ) {
				$type = $this->get_handler_type( $row['handler_type'] );
				$name = isset( $row['handler_name'] ) ? trim( (string) $row['handler_name'] ) : '';
				if ( '' !== $name ) {
					$out['enabled'][ $type ][ $name ]['regex'] = isset( $row['url'] ) ? stripslashes( (string) $row['url'] ) : '';
				}
			}
		}

		$this->whippet_data = $out;


		/**
		 * Plugins
		 */
		$out = array();
		$current_url = esc_url( $this->get_current_url() );

		$disabled_global = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}whippet_p_disabled WHERE url = '' AND regex = ''", ARRAY_A );
		$disabled_here = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}whippet_p_disabled WHERE url = %s",
			$current_url
		), ARRAY_A );
		$disabled_regex = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}whippet_p_disabled WHERE regex != ''", ARRAY_A );

		$enabled_posts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}whippet_p_enabled WHERE content_type != 'here' AND content_type != 'regex'", ARRAY_A );
		$enabled_here = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}whippet_p_enabled WHERE content_type = 'here' AND url = %s",
			$current_url
		), ARRAY_A );
		$enabled_regex = $wpdb->get_results( "SELECT name, url FROM {$wpdb->prefix}whippet_p_enabled WHERE content_type = 'regex'", ARRAY_A );
		$enabled = array_merge( $enabled_here, $enabled_posts );

		if ( ! empty( $disabled_global ) ) {
			foreach ( $disabled_global as $row ) {
				$out['disabled'][ $row['name'] ]['everywhere'] = true;
			}
		}

		if ( ! empty( $disabled_here ) ) {
			foreach ( $disabled_here as $row ) {
				$out['disabled'][ $row['name'] ]['here'] = true;
			}
		}

		if ( ! empty( $disabled_regex ) ) {
			foreach ( $disabled_regex as $row ) {
				$out['disabled'][ $row['name'] ]['regex'] = stripslashes( (string) $row['regex'] );
			}
		}

		if ( ! empty( $enabled ) ) {
			foreach ( $enabled as $row ) {
				$out['enabled'][ $row['name'] ][ $row['content_type'] ] = true;
			}
		}

		if ( ! empty( $enabled_regex ) ) {
			foreach ( $enabled_regex as $row ) {
				$name = isset( $row['name'] ) ? trim( (string) $row['name'] ) : '';
				if ( '' !== $name ) {
					$out['enabled'][ $name ]['regex'] = isset( $row['url'] ) ? stripslashes( (string) $row['url'] ) : '';
				}
			}
		}

		$this->whippet_data_plugins = $out;
	}

	/**
	 * Add preconnect links and inline font styles for whippet panel
	 */
	public function add_font_preconnect_panel() {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		echo '<style id="whippet-figtree-panel-inline">' . "\n";
		echo '#whippet, .whippet-panel, .whippet-form, .whippet-wrapper, [class*="whippet"], [id*="whippet"], #whippet *, .whippet-panel *, .whippet-form *, .whippet-wrapper *, [class*="whippet"] *, [id*="whippet"] * { font-family: "Figtree", ui-sans-serif, system-ui, sans-serif !important; }' . "\n";
		echo '#whippet code, #whippet pre, .g-regex textarea, .whippet-panel code, .whippet-panel pre { font-family: "SF Mono", Monaco, "Cascadia Code", "Roboto Mono", Consolas, "Courier New", monospace !important; }' . "\n";
		echo '.whippet-mu-badge { font-size: 0.75em; opacity: 0.9; margin-left: 0.25em; } .whippet-mu-notice { margin: 0.5em 0; }' . "\n";
		echo '.whippet-mu-toggle-wrap { display: inline-block; margin-left: 0.5em; vertical-align: middle; }' . "\n";
		echo '.whippet-mu-cell { vertical-align: middle; }' . "\n";
		echo '#whippet .whippet-mu-cell .whippet-mu-switch-wrap { display: inline-flex; align-items: center; }' . "\n";
		echo '#whippet .whippet-mu-cell .whippet-mu-toggle-input { display: none !important; }' . "\n";
		echo '#whippet .whippet-mu-cell .whippet-mu-switch { appearance: none; -webkit-appearance: none; background: transparent; border: 0; box-shadow: none; cursor: pointer; display: inline-block; margin: 0; padding: 0; vertical-align: middle; }' . "\n";
		echo '#whippet .whippet-mu-cell .whippet-mu-switch-track { position: relative; display: block; width: 44px; height: 24px; background-color: #c3c4c7; border-radius: 24px; transition: background-color 0.2s ease; box-shadow: inset 0 0 0 1px rgba(15,23,42,0.08); }' . "\n";
		echo '#whippet .whippet-mu-cell .whippet-mu-switch-handle { position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; display: block; background-color: #fff; border-radius: 50%; transition: transform 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.2); }' . "\n";
		echo '#whippet .whippet-mu-cell .whippet-mu-switch.is-on .whippet-mu-switch-track { background-color: #2271b1; }' . "\n";
		echo '#whippet .whippet-mu-cell .whippet-mu-switch.is-on .whippet-mu-switch-handle { transform: translateX(20px); }' . "\n";
		echo '#whippet .whippet-mu-cell .whippet-mu-switch.is-disabled { cursor: not-allowed; opacity: 0.5; }' . "\n";
		echo '#whippet .whippet-mu-cell .whippet-mu-switch:focus-visible .whippet-mu-switch-track { outline: 2px solid #2271b1; outline-offset: 2px; }' . "\n";
		echo '.whippet-label { font-weight: 600; }' . "\n";
		echo '.whippet-mu-footer { font-size: 0.9em; margin-top: 0.5em; opacity: 0.9; }' . "\n";
		echo '#whippet .whippet-mu-global { display: flex; align-items: center; gap: 10px; padding: 10px 16px; margin: 0 0 12px; background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%); border: 1px solid #c7d2fe; border-radius: 8px; }' . "\n";
		echo '#whippet .whippet-mu-global-label { font-weight: 600; font-size: 13px; color: #1e293b; white-space: nowrap; }' . "\n";
		echo '#whippet .whippet-mu-global-desc { font-size: 11px; color: #64748b; flex: 1; }' . "\n";
		echo '#whippet .whippet-mu-global-toggle { position: relative; display: inline-block; width: 44px; min-width: 44px; height: 24px; flex-shrink: 0; }' . "\n";
		echo '#whippet .whippet-mu-global-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }' . "\n";
		echo '#whippet .whippet-mu-global-slider { position: absolute; cursor: pointer; inset: 0; background-color: #c3c4c7; border-radius: 24px; transition: background-color 0.2s ease; }' . "\n";
		echo '#whippet .whippet-mu-global-slider::before { content: ""; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: #fff; border-radius: 50%; transition: transform 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.2); }' . "\n";
		echo '#whippet .whippet-mu-global-toggle input:checked + .whippet-mu-global-slider { background-color: #2271b1; }' . "\n";
		echo '#whippet .whippet-mu-global-toggle input:checked + .whippet-mu-global-slider::before { transform: translateX(20px); }' . "\n";
		echo '#whippet .whippet-mu-global-status { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }' . "\n";
		echo '#whippet .whippet-mu-global-status.is-on { color: #166534; }' . "\n";
		echo '#whippet .whippet-mu-global-status.is-off { color: #991b1b; }' . "\n";
		echo '#whippet .whippet-input-wrapper { display: flex; align-items: center; justify-content: flex-start; }' . "\n";
		echo '#whippet .whippet-input-wrapper input[type="checkbox"] { appearance: none; -webkit-appearance: none; width: 38px; height: 21px; background: #94a3b8; background-image: none !important; border-radius: 21px; position: relative; cursor: pointer; flex-shrink: 0; transition: background 0.2s ease; border: none !important; box-shadow: none !important; vertical-align: middle; margin: 0; padding: 0; }' . "\n";
		echo '#whippet .whippet-input-wrapper input[type="checkbox"]::before { display: none !important; }' . "\n";
		echo '#whippet .whippet-input-wrapper input[type="checkbox"]::after { content: ""; position: absolute; width: 15px; height: 15px; background: #fff; border-radius: 50%; top: 3px; left: 3px; transition: left 0.18s ease; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.22); }' . "\n";
		echo '#whippet .whippet-input-wrapper input[type="checkbox"]:checked { background: var(--wa-accent, #6366f1); background-image: none !important; }' . "\n";
		echo '#whippet .whippet-input-wrapper input[type="checkbox"]:checked::after { left: 20px; }' . "\n";
		echo '#whippet .whippet-input-wrapper input[type="checkbox"]:focus { outline: 2px solid rgba(99, 102, 241, 0.35); outline-offset: 2px; box-shadow: none !important; }' . "\n";
		echo '#whippet .whippet-input-wrapper input[type="checkbox"]:disabled { cursor: not-allowed; opacity: 0.55; }' . "\n";
		echo '</style>' . "\n";
	}

	/**
	 * Print render panel
	 */
	public function render_panel() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		global $whippet_helper;

		$form_action = esc_url( home_url( $this->get_current_url() ) );
		$out = '<form id="whippet" class="whippet-panel" method="POST" action="' . $form_action . '" style="display: none;">';

		$mu_install_result = get_transient( 'whippet_mu_install_result' );
		if ( false !== $mu_install_result && isset( $mu_install_result['ok'], $mu_install_result['message'] ) ) {
			delete_transient( 'whippet_mu_install_result' );
			if ( ! get_option( 'whippet_scripts_hide_disclaimer', 0 ) ) {
				$out .= '<div class="notice notice-' . ( $mu_install_result['ok'] ? 'success' : 'error' ) . ' whippet-mu-notice"><p>' . esc_html( $mu_install_result['message'] ) . '</p></div>';
			}
		}

		$this->all_assets = array();
		$this->all_content_types = $this->get_public_post_types();
		$out .= '<div class="whippet-layout">';
		$out .= '<aside class="whippet-sidebar">';
		$out .= '<div class="whippet-sidebar__brand">';
		$out .= '<div class="whippet-sidebar__badge">' . esc_html__( 'Whippet', 'whippet' ) . '</div>';
		$out .= '<div class="whippet-sidebar__title">' . esc_html__( 'Script Manager', 'whippet' ) . '</div>';
		$out .= '</div>';
		$out .= '<div class="whippet-sidebar__nav">';
		$out .= '<button type="button" class="whippet-sidebar__link is-active" data-whippet-view-target="script-manager" aria-pressed="true"><span class="whippet-sidebar__icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 5h16v3H4zm0 5h16v3H4zm0 5h10v3H4z"/></svg></span><span>' . esc_html__( 'Script Manager', 'whippet' ) . '</span></button>';
		$out .= '<button type="button" class="whippet-sidebar__link" data-whippet-view-target="global" aria-pressed="false"><span class="whippet-sidebar__icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm0 2a8 8 0 0 1 7.75 6H15a3 3 0 0 0-6 0H4.25A8 8 0 0 1 12 4Zm0 16a8 8 0 0 1-7.75-6H9a3 3 0 0 0 6 0h4.75A8 8 0 0 1 12 20Zm0-7a1 1 0 1 1 1-1 1 1 0 0 1-1 1Z"/></svg></span><span>' . esc_html__( 'Global View', 'whippet' ) . '</span></button>';
		$out .= '<button type="button" class="whippet-sidebar__link" data-whippet-view-target="settings" aria-pressed="false"><span class="whippet-sidebar__icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M19.14 12.94a7.48 7.48 0 0 0 .05-.94 7.48 7.48 0 0 0-.05-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.13 7.13 0 0 0-1.63-.94l-.36-2.54a.5.5 0 0 0-.5-.42h-3.84a.5.5 0 0 0-.5.42l-.36 2.54a7.13 7.13 0 0 0-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.7 8.84a.5.5 0 0 0 .12.64l2.03 1.58a7.48 7.48 0 0 0-.05.94 7.48 7.48 0 0 0 .05.94L2.82 14.52a.5.5 0 0 0-.12.64l1.92 3.32a.5.5 0 0 0 .6.22l2.39-.96a7.13 7.13 0 0 0 1.63.94l.36 2.54a.5.5 0 0 0 .5.42h3.84a.5.5 0 0 0 .5-.42l.36-2.54a7.13 7.13 0 0 0 1.63-.94l2.39.96a.5.5 0 0 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64ZM12 15.5A3.5 3.5 0 1 1 15.5 12 3.5 3.5 0 0 1 12 15.5Z"/></svg></span><span>' . esc_html__( 'Settings', 'whippet' ) . '</span></button>';
		$out .= '</div>';
		$out .= '<div class="whippet-sidebar__actions">';
		$out .= '<input type="submit" id="submit-whippet" value="' . __( 'Save changes' ) . '">';
		$out .= '</div>';
		$out .= '</aside>';
		$out .= '<div class="whippet-main">';
		$out .= '<div class="whippet-panel-view is-active" data-whippet-view="script-manager">';
		$out .= '<div class="whippet-panel-view__header">';
		$out .= '<h2>' . esc_html__( 'Script Manager', 'whippet' ) . '</h2>';
		$out .= '<p>' . esc_html__( 'Manage loaded front-end scripts, styles, and plugin assets for the current page.', 'whippet' ) . '</p>';
		$out .= '</div>';

		// Specific resource types order.
		$tmp = array();
		if ( ! empty( $this->collection['plugins'] ) ) {
			$tmp['plugins'] = $this->collection['plugins'];
		}

		if ( ! empty( $this->collection['theme'] ) ) {
			$tmp['theme'] = $this->collection['theme'];
		}

		if ( ! empty( $this->collection['misc'] ) ) {
			$tmp['misc'] = $this->collection['misc'];
		}

		$this->collection = $tmp;

		foreach ( $this->collection as $resource_type => $types ) {

			if ( 'plugins' === $resource_type ) {
				$out .= '<h2>' . __( $resource_type , 'whippet' ) . '</h2>';

				foreach ( $types as $plugin_dir => $types_sub ) {
					$plugin_info = $this->get_plugin_info_by_directory( $plugin_dir );
					if ( null === $plugin_info || ! isset( $plugin_info['gPluginName'] ) ) {
						continue;
					}
					if ( in_array( $plugin_info['gPluginName'], $this->whitelist_plugins, true ) || in_array( $plugin_info['gPluginName'], $this->already_listed_plugins, true ) ) {
						continue;
					}
					$out .= $this->render_group( $resource_type, $types_sub, $plugin_info );
				}

				$plugin_states = array( 'disabled', 'enabled' );
				/**
				 * Disabled plugins (they do not attach CSS/JS; force showing).
				 * Enabled plugins (which do not generate CSS/JS).
				 */
				foreach ( $plugin_states as $plugin_state ) {
					if ( ! isset( $whippet_helper->control[ $plugin_state ] ) || ! is_array( $whippet_helper->control[ $plugin_state ] ) || empty( $whippet_helper->control[ $plugin_state ] ) ) {
						continue;
					}
					foreach ( $whippet_helper->control[ $plugin_state ] as $plugin_path ) {
						$plugin_dir = $this->get_plugin_slug( $plugin_path );
						$plugin_info = $this->get_plugin_info_by_directory( $plugin_dir );
						if ( null === $plugin_info || ! isset( $plugin_info['gPluginName'] ) ) {
							continue;
						}
						if ( in_array( $plugin_info['gPluginName'], $this->whitelist_plugins, true ) || in_array( $plugin_info['gPluginName'], $this->already_listed_plugins, true ) ) {
							continue;
						}
						if ( 'disabled' === $plugin_state ) {
							$out .= $this->render_empty_disabled_plugin_group( $types_sub, $plugin_info );
						} else {
							$out .= $this->render_empty_enabled_plugin_group( $types_sub, $plugin_info );
						}
					}
				}
			} else {
				$out .= $this->render_group( $resource_type, $types );
			}
		}
		$out .= '</div>';
		$out .= '<div class="whippet-panel-view" data-whippet-view="global">';
		$out .= $this->render_global_view();
		$out .= '</div>';
		$out .= '<div class="whippet-panel-view" data-whippet-view="settings">';
		$out .= $this->render_frontend_settings_panel();
		$out .= '</div>';
		$out .= '</div>';
		$out .= '</div>';
		$out .= wp_nonce_field( 'whippet', 'whippetUpdate', true, false );
		$out .= '<div id="whippet-global-view-deletions" hidden></div>';
		$out .= $this->render_mu_toggle_script();
		$out .= '<script>(function(){var cb=document.querySelector("#whippet .whippet-mu-global-toggle input");var st=document.getElementById("whippet-mu-status");if(!cb||!st)return;cb.addEventListener("change",function(){st.textContent=cb.checked?"' . esc_js( __( 'Active', 'whippet' ) ) . '":"' . esc_js( __( 'Off', 'whippet' ) ) . '";st.className="whippet-mu-global-status "+(cb.checked?"is-on":"is-off");});})();</script>';
		$out .= '<input type="hidden" name="currentURL" value="' . esc_url( $this->get_current_url() ) . '">
			<input type="hidden" name="allAssets" value="' . filter_var( json_encode( $this->all_assets ), FILTER_SANITIZE_SPECIAL_CHARS ) . '">
		</form>';

		print $out;
	}

	/**
	 * Render the Global View summary panel.
	 *
	 * @return string
	 */
	private function render_global_view() {
		$sections = $this->get_global_view_rows();

		$out  = '<div class="whippet-global-view">';
		$out .= '<div class="whippet-global-view__intro">';
		$out .= '<h2>' . esc_html__( 'Global View', 'whippet' ) . '</h2>';
		$out .= '<p>' . esc_html__( 'The "Global View" is a visual representation of the Script Manager configuration across your entire site. You can also delete disables or enables from this screen by clicking on the trash can icon.', 'whippet' ) . '</p>';
		$out .= '</div>';

		foreach ( $sections as $section_key => $rows ) {
			$section_title = ( 'disabled' === $section_key ) ? esc_html__( 'Disabled', 'whippet' ) : esc_html__( 'Enabled', 'whippet' );
			$empty_text = ( 'disabled' === $section_key ) ? esc_html__( 'No disabled rules found.', 'whippet' ) : esc_html__( 'No enabled rules found.', 'whippet' );
			$outdated_payloads = array();
			$outdated_row_keys = array();

			$out .= '<div class="whippet-global-view__section">';
			$out .= '<div class="whippet-global-view__bar">' . $section_title . '</div>';
			$out .= '<table class="whip whippet-global-view__table">';
			$out .= '<thead><tr>';
			$out .= '<th>' . esc_html__( 'Type', 'whippet' ) . '</th>';
			$out .= '<th>' . esc_html__( 'Script', 'whippet' ) . '</th>';
			$out .= '<th>' . esc_html__( 'Setting', 'whippet' ) . '</th>';
			$out .= '<th class="whippet-global-view__actions-col"></th>';
			$out .= '</tr></thead><tbody>';

			if ( empty( $rows ) ) {
				$out .= '<tr class="whippet-global-view__empty-row"><td colspan="4">' . $empty_text . '</td></tr>';
			} else {
				foreach ( $rows as $row ) {
					$delete_label = sprintf(
						/* translators: %s: Script or plugin name. */
						esc_html__( 'Delete rule for %s', 'whippet' ),
						$row['name']
					);
					$row_classes = array();
					if ( ! empty( $row['is_outdated'] ) ) {
						$row_classes[] = 'is-outdated';
						$outdated_payloads[] = $row['delete_payload'];
						$outdated_row_keys[] = $row['row_key'];
					}

					$out .= '<tr data-whippet-row-key="' . esc_attr( $row['row_key'] ) . '"' . ( ! empty( $row_classes ) ? ' class="' . esc_attr( implode( ' ', $row_classes ) ) . '"' : '' ) . '>';
					$out .= '<td>' . esc_html( $row['type_label'] ) . '</td>';
					$out .= '<td><strong>' . esc_html( $row['name'] ) . '</strong></td>';
					$out .= '<td>' . $row['setting_html'] . '</td>';
					$out .= '<td class="whippet-global-view__actions">';
					$out .= '<button type="button" class="whippet-global-view__delete" aria-label="' . esc_attr( $delete_label ) . '" title="' . esc_attr( $delete_label ) . '" data-whippet-delete-label="' . esc_attr( $row['name'] ) . '" data-whippet-delete-rule="' . esc_attr( $row['delete_payload'] ) . '">';
					$out .= esc_html__( 'Delete', 'whippet' );
					$out .= '</button>';
					$out .= '</td>';
					$out .= '</tr>';
				}
			}

			$out .= '</tbody></table>';
			if ( ! empty( $outdated_payloads ) ) {
				$out .= '<div class="whippet-global-view__footer">';
				$out .= '<button type="button" class="whippet-global-view__cleanup" data-whippet-delete-rules="' . esc_attr( base64_encode( wp_json_encode( array_values( $outdated_payloads ) ) ) ) . '" data-whippet-delete-row-keys="' . esc_attr( base64_encode( wp_json_encode( array_values( $outdated_row_keys ) ) ) ) . '">' . esc_html__( 'Clear outdated post IDs', 'whippet' ) . '</button>';
				$out .= '</div>';
			}
			$out .= '</div>';
		}

		$out .= '</div>';

		return $out;
	}

	/**
	 * Render frontend Script Manager settings, mirroring backend fields.
	 *
	 * @return string
	 */
	private function render_frontend_settings_panel() {
		$display_archives = (bool) get_option( 'whippet_scripts_display_archives', 0 );
		$display_deps = (bool) get_option( 'whippet_scripts_display_deps', 1 );
		$testing_mode = (bool) get_option( 'whippet_scripts_testing_mode', 0 );
		$mu_mode = (bool) get_option( 'whippet_scripts_mu_mode', 0 );
		$hide_disclaimer = (bool) get_option( 'whippet_scripts_hide_disclaimer', 0 );
		$mu_status = function_exists( 'whippet_scripts_mu_plugin_status' ) ? whippet_scripts_mu_plugin_status() : array(
			'exists' => false,
			'version_ok' => false,
		);

		$out  = '<div class="whippet-settings-card">';
		$out .= '<input type="hidden" name="whippet_frontend_settings_present" value="1">';
		$out .= '<div class="whippet-settings-card__header">';
		$out .= '<h2>' . esc_html__( 'Script Manager Settings', 'whippet' ) . '</h2>';
		$out .= '<p>' . esc_html__( 'These front-end settings mirror the backend Script Manager options.', 'whippet' ) . '</p>';
		$out .= '</div>';
		$out .= '<table class="whip whippet-settings-table"><tbody>';

		$out .= $this->render_frontend_setting_row(
			__( 'Display Archives', 'whippet' ),
			'whippet_scripts_display_archives',
			$display_archives,
			__( 'Add WordPress archives to your Script Manager selection options. Archive posts will no longer be grouped by their post type.', 'whippet' )
		);

		$out .= $this->render_frontend_setting_row(
			__( 'Display Dependencies', 'whippet' ),
			'whippet_scripts_display_deps',
			$display_deps,
			__( 'Show dependencies for each script.', 'whippet' )
		);

		$out .= $this->render_frontend_setting_row(
			__( 'Testing Mode', 'whippet' ),
			'whippet_scripts_testing_mode',
			$testing_mode,
			__( 'Restrict your Script Manager configuration to logged-in admins only.', 'whippet' )
		);

		$mu_description = __( 'Must-use (MU) mode requires elevated permissions and a file to be copied into the mu-plugins directory. This gives you more control and the ability to disable plugin queries, inline CSS, etc.', 'whippet' );
		if ( $mu_mode && ( ! $mu_status['exists'] || ! $mu_status['version_ok'] ) ) {
			$mu_description .= ' ' . __( 'The MU plugin file is missing or out of date.', 'whippet' );
		}

		$out .= $this->render_frontend_setting_row(
			__( 'MU Mode', 'whippet' ),
			'whippet_scripts_mu_mode',
			$mu_mode,
			$mu_description,
			'<span class="whippet-settings-badge">' . esc_html__( 'Beta', 'whippet' ) . '</span>'
		);

		$out .= $this->render_frontend_setting_row(
			__( 'Hide Disclaimer', 'whippet' ),
			'whippet_scripts_hide_disclaimer',
			$hide_disclaimer,
			__( 'Hide the disclaimer message box across all Script Manager views.', 'whippet' )
		);

		$out .= '<tr>';
		$out .= '<th scope="row">' . esc_html__( 'Reset Script Manager', 'whippet' ) . '</th>';
		$out .= '<td>';
		$out .= '<button type="button" class="whippet-reset-button" id="whippet-frontend-reset-btn" data-whippet-ajax-url="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" data-whippet-reset-nonce="' . esc_attr( wp_create_nonce( 'whippet_scripts_reset' ) ) . '">' . esc_html__( 'Reset Script Manager', 'whippet' ) . '</button>';
		$out .= '<span id="whippet-frontend-reset-msg" class="whippet-reset-message"></span>';
		$out .= '<p class="whippet-settings-desc">' . esc_html__( 'Remove and reset all of your existing Script Manager settings.', 'whippet' ) . '</p>';
		$out .= '</td>';
		$out .= '</tr>';

		$out .= '</tbody></table>';
		$out .= '</div>';

		return $out;
	}

	/**
	 * Render one frontend settings row.
	 *
	 * @param string $label Setting label.
	 * @param string $name Input name.
	 * @param bool   $checked Whether the input is checked.
	 * @param string $description Setting description.
	 * @param string $suffix Optional extra HTML appended to the label.
	 * @return string
	 */
	private function render_frontend_setting_row( $label, $name, $checked, $description, $suffix = '' ) {
		$out  = '<tr>';
		$out .= '<th scope="row">' . esc_html( $label ) . ' ' . $suffix . '</th>';
		$out .= '<td>';
		$out .= '<div class="whippet-input-wrapper">';
		$out .= '<input type="checkbox" name="' . esc_attr( $name ) . '" value="1"' . checked( $checked, true, false ) . '>';
		$out .= '</div>';
		$out .= '<p class="whippet-settings-desc">' . esc_html( $description ) . '</p>';
		$out .= '</td>';
		$out .= '</tr>';

		return $out;
	}

	/**
	 * Build Global View rows from saved rules.
	 *
	 * @return array<string, array<int, array<string, string>>>
	 */
	private function get_global_view_rows() {
		global $wpdb;

		$content_types = $this->get_public_post_types();
		$rows = array(
			'disabled' => array(),
			'enabled' => array(),
		);

		$asset_disabled = $wpdb->get_results( "SELECT id, handler_type, handler_name, url, regex FROM {$wpdb->prefix}whippet_disabled", ARRAY_A );
		$asset_enabled = $wpdb->get_results( "SELECT id, handler_type, handler_name, content_type, url FROM {$wpdb->prefix}whippet_enabled", ARRAY_A );
		$plugin_disabled = $wpdb->get_results( "SELECT id, name, url, regex FROM {$wpdb->prefix}whippet_p_disabled", ARRAY_A );
		$plugin_enabled = $wpdb->get_results( "SELECT id, name, content_type, url FROM {$wpdb->prefix}whippet_p_enabled", ARRAY_A );

		foreach ( $asset_disabled as $row ) {
			$type = $this->get_handler_type( (int) $row['handler_type'] );
			$scope = $this->get_global_view_scope_data( 'disabled', '', isset( $row['url'] ) ? $row['url'] : '', isset( $row['regex'] ) ? $row['regex'] : '', $content_types );
			$delete_payload = $this->encode_global_view_delete_rule(
				array(
					'table' => 'whippet_disabled',
					'handler_type' => (int) $row['handler_type'],
					'handler_name' => $row['handler_name'],
					'url' => isset( $row['url'] ) ? $row['url'] : '',
					'regex' => isset( $row['regex'] ) ? $row['regex'] : '',
				)
			);
			$rows['disabled'][] = array(
				'type_label' => $type,
				'name' => $row['handler_name'],
				'setting_label' => $scope['label'],
				'setting_html' => $scope['html'],
				'is_outdated' => $scope['is_outdated'],
				'delete_payload' => $delete_payload,
				'row_key' => md5( $delete_payload ),
			);
		}

		foreach ( $asset_enabled as $row ) {
			$type = $this->get_handler_type( (int) $row['handler_type'] );
			$scope = $this->get_global_view_scope_data( 'enabled', isset( $row['content_type'] ) ? $row['content_type'] : '', isset( $row['url'] ) ? $row['url'] : '', '', $content_types );
			$delete_payload = $this->encode_global_view_delete_rule(
				array(
					'table' => 'whippet_enabled',
					'handler_type' => (int) $row['handler_type'],
					'handler_name' => $row['handler_name'],
					'content_type' => isset( $row['content_type'] ) ? $row['content_type'] : '',
					'url' => isset( $row['url'] ) ? $row['url'] : '',
				)
			);
			$rows['enabled'][] = array(
				'type_label' => $type,
				'name' => $row['handler_name'],
				'setting_label' => $scope['label'],
				'setting_html' => $scope['html'],
				'is_outdated' => $scope['is_outdated'],
				'delete_payload' => $delete_payload,
				'row_key' => md5( $delete_payload ),
			);
		}

		foreach ( $plugin_disabled as $row ) {
			$scope = $this->get_global_view_scope_data( 'disabled', '', isset( $row['url'] ) ? $row['url'] : '', isset( $row['regex'] ) ? $row['regex'] : '', $content_types );
			$delete_payload = $this->encode_global_view_delete_rule(
				array(
					'table' => 'whippet_p_disabled',
					'name' => $row['name'],
					'url' => isset( $row['url'] ) ? $row['url'] : '',
					'regex' => isset( $row['regex'] ) ? $row['regex'] : '',
				)
			);
			$rows['disabled'][] = array(
				'type_label' => 'plugins',
				'name' => $row['name'],
				'setting_label' => $scope['label'],
				'setting_html' => $scope['html'],
				'is_outdated' => $scope['is_outdated'],
				'delete_payload' => $delete_payload,
				'row_key' => md5( $delete_payload ),
			);
		}

		foreach ( $plugin_enabled as $row ) {
			$scope = $this->get_global_view_scope_data( 'enabled', isset( $row['content_type'] ) ? $row['content_type'] : '', isset( $row['url'] ) ? $row['url'] : '', '', $content_types );
			$delete_payload = $this->encode_global_view_delete_rule(
				array(
					'table' => 'whippet_p_enabled',
					'name' => $row['name'],
					'content_type' => isset( $row['content_type'] ) ? $row['content_type'] : '',
					'url' => isset( $row['url'] ) ? $row['url'] : '',
				)
			);
			$rows['enabled'][] = array(
				'type_label' => 'plugins',
				'name' => $row['name'],
				'setting_label' => $scope['label'],
				'setting_html' => $scope['html'],
				'is_outdated' => $scope['is_outdated'],
				'delete_payload' => $delete_payload,
				'row_key' => md5( $delete_payload ),
			);
		}

		foreach ( $rows as $section_key => $section_rows ) {
			usort(
				$section_rows,
				static function( $left, $right ) {
					$type_compare = strcasecmp( $left['type_label'], $right['type_label'] );
					if ( 0 !== $type_compare ) {
						return $type_compare;
					}

					$name_compare = strcasecmp( $left['name'], $right['name'] );
					if ( 0 !== $name_compare ) {
						return $name_compare;
					}

					return strcasecmp( $left['setting_label'], $right['setting_label'] );
				}
			);
			$rows[ $section_key ] = $section_rows;
		}

		return $rows;
	}

	/**
	 * Convert a saved rule into a readable scope label.
	 *
	 * @param string $mode Disabled or enabled.
	 * @param string $content_type Content type for enabled rules.
	 * @param string $url Saved URL.
	 * @param string $regex Saved regex.
	 * @param array  $content_types Available content type labels.
	 * @return string
	 */
	private function get_global_view_scope_label( $mode, $content_type, $url, $regex, $content_types ) {
		$scope = $this->get_global_view_scope_data( $mode, $content_type, $url, $regex, $content_types );
		return $scope['label'];
	}

	/**
	 * Convert a saved rule into readable scope data.
	 *
	 * @param string $mode Disabled or enabled.
	 * @param string $content_type Content type for enabled rules.
	 * @param string $url Saved URL.
	 * @param string $regex Saved regex.
	 * @param array  $content_types Available content type labels.
	 * @return array<string, mixed>
	 */
	private function get_global_view_scope_data( $mode, $content_type, $url, $regex, $content_types ) {
		$url = is_string( $url ) ? $url : '';
		$regex = is_string( $regex ) ? $regex : '';
		$content_type = is_string( $content_type ) ? $content_type : '';

		if ( 'disabled' === $mode ) {
			$disabled_content_type = $this->get_asset_content_type_from_scope_value( $url );
			if ( '' !== $disabled_content_type ) {
				if ( isset( $content_types[ $disabled_content_type ] ) ) {
					$label = sprintf(
						/* translators: 1: Content type slug. 2: Content type label. */
						__( '%1$s (%2$s)', 'whippet' ),
						$disabled_content_type,
						$content_types[ $disabled_content_type ]
					);
					return array(
						'label' => $label,
						'html' => esc_html( $label ),
						'is_outdated' => false,
					);
				}

				return array(
					'label' => $disabled_content_type,
					'html' => esc_html( $disabled_content_type ),
					'is_outdated' => false,
				);
			}
		}

		if ( '' !== $regex ) {
			$label = sprintf(
				/* translators: %s: Regex pattern. */
				__( 'regex (%s)', 'whippet' ),
				$regex
			);
			return array(
				'label' => $label,
				'html' => esc_html( $label ),
				'is_outdated' => false,
			);
		}

		if ( 'enabled' === $mode ) {
			if ( 'here' === $content_type ) {
				return $this->get_global_view_current_scope_data( $url );
			}

			if ( 'regex' === $content_type ) {
				$label = sprintf(
					/* translators: %s: Regex pattern. */
					__( 'regex (%s)', 'whippet' ),
					$url
				);
				return array(
					'label' => $label,
					'html' => esc_html( $label ),
					'is_outdated' => false,
				);
			}

			if ( isset( $content_types[ $content_type ] ) ) {
				$label = sprintf(
					/* translators: 1: Content type slug. 2: Content type label. */
					__( '%1$s (%2$s)', 'whippet' ),
					$content_type,
					$content_types[ $content_type ]
				);
				return array(
					'label' => $label,
					'html' => esc_html( $label ),
					'is_outdated' => false,
				);
			}

			return array(
				'label' => $content_type,
				'html' => esc_html( $content_type ),
				'is_outdated' => false,
			);
		}

		if ( '' !== $url ) {
			return $this->get_global_view_current_scope_data( $url );
		}

		$label = __( 'everywhere', 'whippet' );
		return array(
			'label' => $label,
			'html' => esc_html( $label ),
			'is_outdated' => false,
		);
	}

	/**
	 * Build readable scope data for a current URL rule.
	 *
	 * @param string $url Relative URL path.
	 * @return array<string, mixed>
	 */
	private function get_global_view_current_scope_data( $url ) {
		$info = $this->get_global_view_location_data( $url );
		$label = sprintf(
			/* translators: %s: Location label. */
			__( 'current (%s)', 'whippet' ),
			$info['label']
		);
		$html = sprintf(
			/* translators: %s: Location label. */
			__( 'current (%s)', 'whippet' ),
			$info['html']
		);

		return array(
			'label' => $label,
			'html' => $html,
			'is_outdated' => ! empty( $info['is_outdated'] ),
		);
	}

	/**
	 * Get a readable label for a saved URL.
	 *
	 * @param string $url Relative URL path.
	 * @return string
	 */
	private function get_global_view_location_label( $url ) {
		$info = $this->get_global_view_location_data( $url );
		return $info['label'];
	}

	/**
	 * Get readable location data for a saved URL.
	 *
	 * @param string $url Relative URL path.
	 * @return array<string, mixed>
	 */
	private function get_global_view_location_data( $url ) {
		$url = is_string( $url ) ? trim( $url ) : '';
		if ( '' === $url || '/' === $url ) {
			return array(
				'label' => __( 'homepage', 'whippet' ),
				'html' => esc_html__( 'homepage', 'whippet' ),
				'is_outdated' => false,
			);
		}

		$post_id = url_to_postid( home_url( $url ) );
		if ( $post_id ) {
			$post_label = (string) $post_id;
			return array(
				'label' => $post_label,
				'html' => esc_html( $post_label ),
				'is_outdated' => false,
			);
		}

		return array(
			'label' => __( 'outdated', 'whippet' ),
			'html' => '<span class="whippet-global-view__outdated">' . esc_html( $url ) . '</span>',
			'is_outdated' => true,
		);
	}

	/**
	 * Encode a delete payload for Global View actions.
	 *
	 * @param array $payload Rule payload.
	 * @return string
	 */
	private function encode_global_view_delete_rule( $payload ) {
		return base64_encode( wp_json_encode( $payload ) );
	}

	/**
	 * Delete rules requested from the Global View.
	 *
	 * @param array $raw_rules Encoded delete payloads.
	 * @return void
	 */
	private function delete_global_view_rules( $raw_rules ) {
		global $wpdb;

		if ( ! is_array( $raw_rules ) || empty( $raw_rules ) ) {
			return;
		}

		foreach ( $raw_rules as $raw_rule ) {
			$raw_rule = is_string( $raw_rule ) ? sanitize_text_field( wp_unslash( $raw_rule ) ) : '';
			if ( '' === $raw_rule ) {
				continue;
			}

			$decoded = base64_decode( $raw_rule, true );
			if ( false === $decoded ) {
				continue;
			}

			$rule = json_decode( $decoded, true );
			if ( ! is_array( $rule ) || empty( $rule['table'] ) ) {
				continue;
			}

			$table = sanitize_key( $rule['table'] );
			switch ( $table ) {
				case 'whippet_disabled':
					$wpdb->delete(
						$wpdb->prefix . 'whippet_disabled',
						array(
							'handler_type' => isset( $rule['handler_type'] ) ? absint( $rule['handler_type'] ) : 0,
							'handler_name' => isset( $rule['handler_name'] ) ? sanitize_text_field( $rule['handler_name'] ) : '',
							'url' => isset( $rule['url'] ) ? sanitize_text_field( $rule['url'] ) : '',
							'regex' => isset( $rule['regex'] ) ? sanitize_text_field( $rule['regex'] ) : '',
						),
						array( '%d', '%s', '%s', '%s' )
					);
					break;

				case 'whippet_enabled':
					$wpdb->delete(
						$wpdb->prefix . 'whippet_enabled',
						array(
							'handler_type' => isset( $rule['handler_type'] ) ? absint( $rule['handler_type'] ) : 0,
							'handler_name' => isset( $rule['handler_name'] ) ? sanitize_text_field( $rule['handler_name'] ) : '',
							'content_type' => isset( $rule['content_type'] ) ? sanitize_text_field( $rule['content_type'] ) : '',
							'url' => isset( $rule['url'] ) ? sanitize_text_field( $rule['url'] ) : '',
						),
						array( '%d', '%s', '%s', '%s' )
					);
					break;

				case 'whippet_p_disabled':
					$wpdb->delete(
						$wpdb->prefix . 'whippet_p_disabled',
						array(
							'name' => isset( $rule['name'] ) ? sanitize_text_field( $rule['name'] ) : '',
							'url' => isset( $rule['url'] ) ? esc_url_raw( $rule['url'] ) : '',
							'regex' => isset( $rule['regex'] ) ? sanitize_text_field( $rule['regex'] ) : '',
						),
						array( '%s', '%s', '%s' )
					);
					break;

				case 'whippet_p_enabled':
					$wpdb->delete(
						$wpdb->prefix . 'whippet_p_enabled',
						array(
							'name' => isset( $rule['name'] ) ? sanitize_text_field( $rule['name'] ) : '',
							'content_type' => isset( $rule['content_type'] ) ? sanitize_text_field( $rule['content_type'] ) : '',
							'url' => isset( $rule['url'] ) ? sanitize_text_field( $rule['url'] ) : '',
						),
						array( '%s', '%s', '%s' )
					);
					break;
			}
		}
	}

	/**
	 * Render frontend MU toggle markup.
	 *
	 * @param string $slug Plugin slug.
	 * @param bool   $checked Whether MU mode is enabled for the plugin.
	 * @param bool   $disabled Whether the toggle should be disabled.
	 * @param string $title Title attribute text.
	 * @return string
	 */
	private function render_mu_toggle_control( $slug, $checked, $disabled, $title ) {
		$slug = is_string( $slug ) ? $slug : '';
		$button_classes = 'whippet-mu-switch';
		if ( $checked ) {
			$button_classes .= ' is-on';
		}
		if ( $disabled ) {
			$button_classes .= ' is-disabled';
		}

		$out = '<span class="whippet-mu-switch-wrap">';
		if ( ! $disabled && '' !== $slug ) {
			$out .= '<input type="checkbox" name="disabledPluginMu[' . esc_attr( $slug ) . ']" value="1" ' . checked( $checked, true, false ) . ' class="whippet-mu-toggle-input" data-whippet-mu-key="' . esc_attr( $slug ) . '">';
		}
		$out .= '<button type="button" class="' . esc_attr( $button_classes ) . '" data-whippet-mu-key="' . esc_attr( $slug ) . '" aria-pressed="' . ( $checked ? 'true' : 'false' ) . '" title="' . esc_attr( $title ) . '"' . ( $disabled ? ' disabled' : '' ) . '>';
		$out .= '<span class="whippet-mu-switch-track"><span class="whippet-mu-switch-handle"></span></span>';
		$out .= '</button>';
		$out .= '</span>';

		return $out;
	}

	/**
	 * Sync frontend MU toggle buttons with their hidden checkboxes.
	 *
	 * @return string
	 */
	private function render_mu_toggle_script() {
		return '<script>(function(){if(window.whippetMuSwitchInit){return;}window.whippetMuSwitchInit=true;document.addEventListener("click",function(event){var button=event.target.closest("#whippet .whippet-mu-switch");var buttons,inputs,key,nextState;if(!button||button.disabled||button.classList.contains("is-disabled")){return;}key=button.getAttribute("data-whippet-mu-key");if(!key){return;}nextState=button.getAttribute("aria-pressed")!=="true";buttons=document.querySelectorAll("#whippet .whippet-mu-switch[data-whippet-mu-key]");buttons.forEach(function(item){if(item.getAttribute("data-whippet-mu-key")!==key){return;}item.setAttribute("aria-pressed",nextState?"true":"false");item.classList.toggle("is-on",nextState);});inputs=document.querySelectorAll("#whippet .whippet-mu-toggle-input[data-whippet-mu-key]");inputs.forEach(function(input){if(input.getAttribute("data-whippet-mu-key")!==key){return;}input.checked=nextState;});});})();</script>';
	}

	/**
	 * Get plugin slug by path
	 *
	 * @param  string $plugin_path Input.
	 * @return string              Output
	 */
	private function get_plugin_slug( $plugin_path ) {
		if ( ! is_string( $plugin_path ) || '' === $plugin_path ) {
			return '';
		}
		$out = explode( '/', $plugin_path );
		if ( count( $out ) === 1 ) {
			$out = explode( '.', $plugin_path );
			array_pop( $out );
		}
		return isset( $out[0] ) ? $out[0] : '';
	}

	/**
	 * Get plugin details based on provided part of URL address.
	 *
	 * @param  string $plugin_slug Plugin directory/slug.
	 * @return array|null Plugin data with gPluginPath, gPluginName, or null if not found.
	 */
	private function get_plugin_info_by_directory( $plugin_slug ) {
		if ( ! is_string( $plugin_slug ) || '' === $plugin_slug ) {
			return null;
		}
		foreach ( $this->all_plugins as $plugin_path => $plugin_details ) {
			/*
			 * 0 = beginning of the string, / = end url
			 * Would not enlist "bloom" with "et-bloom-extender"
			 */
			if (
				( 0 === strpos( $plugin_path, $plugin_slug . '/' ) ) ||
				( 0 === strpos( $plugin_path, $plugin_slug . '.' ) )
			) {
				return array_merge(
					is_array( $plugin_details ) ? $plugin_details : array(),
					array(
						'gPluginPath' => $plugin_path,
						'gPluginName' => $plugin_slug,
					)
				);
			}
		}
		return null;
	}

	/**
	 * Sum total size (KB) of assets in a plugin group.
	 *
	 * @param  array $types Plugin assets by type (js/css).
	 * @return float
	 */
	private function get_plugin_group_size( $types ) {
		$total = 0;
		if ( ! is_array( $types ) ) {
			return $total;
		}
		foreach ( $types as $type_assets ) {
			if ( ! is_array( $type_assets ) ) {
				continue;
			}
			foreach ( $type_assets as $row ) {
				if ( isset( $row['size'] ) && is_numeric( $row['size'] ) ) {
					$total += (float) $row['size'];
				}
			}
		}
		return round( $total, 1 );
	}

	/**
	 * Display header with plugin control (layout matches reference: Disabled / Exceptions with Locations, Users, Devices, Regex).
	 *
	 * @param  array $types       List of assets in JS/CSS groups.
	 * @param  array $plugin_info Plugin details.
	 * @return string              Html
	 */
	private function render_plugin_group_header( $types, $plugin_info ) {
		$plugin = $plugin_info['gPluginName'];
		$id = '[' . $plugin . ']';
		$show_mu_mode = (bool) get_option( 'whippet_scripts_mu_mode', 0 );

		$is_checked_ever = isset( $this->whippet_data_plugins['disabled'][ $plugin ]['everywhere'] );
		$is_checked_here = isset( $this->whippet_data_plugins['disabled'][ $plugin ]['here'] );
		$is_checked_regex = isset( $this->whippet_data_plugins['disabled'][ $plugin ]['regex'] );
		$real_state = $this->get_visibility_plugin( $plugin );
		$mu_plugins = get_option( 'whippet_p_mu_plugins', array() );
		$mu_on = ! isset( $mu_plugins[ $plugin ] ) || (int) $mu_plugins[ $plugin ] === 1;
		$total_size = $this->get_plugin_group_size( $types );
		$enabled_regex_val = isset( $this->whippet_data_plugins['enabled'][ $plugin ]['regex'] ) ? $this->whippet_data_plugins['enabled'][ $plugin ]['regex'] : '';

		$out = '<table class="whip plugin ' . ( $show_mu_mode ? 'has-mu-mode' : 'no-mu-mode' ) . '">';
		$out .= '<thead><tr><th>' . esc_html__( 'Loaded', 'whippet' ) . '</th><th>' . esc_html__( 'Plugin info', 'whippet' ) . '</th><th>' . esc_html__( 'Size', 'whippet' ) . '</th><th>' . esc_html__( 'State', 'whippet' ) . '</th>';
		if ( $show_mu_mode ) {
			$out .= '<th>' . esc_html__( 'MU Mode', 'whippet' ) . '</th>';
		}
		$out .= '<th></th></tr></thead>';
		$out .= '<tbody><tr>';
		$out .= '<td><div class="state-' . (int) $real_state . '">' . ( $real_state ? 'YES' : 'NO' ) . '</div></td>';
		$out .= '<td class="overflow">';
		if ( ! empty( $plugin_info['PluginURI'] ) ) {
			$out .= '<a href="' . esc_url( $plugin_info['PluginURI'] ) . '" target="_blank">';
		}
		$out .= '<span class="whippet-plugin-name">' . esc_html( $plugin_info['Name'] ) . '</span>';
		if ( ! empty( $plugin_info['PluginURI'] ) ) {
			$out .= '</a>';
		}
		$out .= '<div class="g-info">';
		if ( ! empty( $plugin_info['Author'] ) ) {
			$out .= '<div><b>' . esc_html__( 'Author', 'whippet' ) . '</b>: ' . esc_html( $plugin_info['AuthorName'] ) . '</div>';
		}
		if ( ! empty( $plugin_info['Version'] ) ) {
			$out .= '<div><b>' . esc_html__( 'Version', 'whippet' ) . '</b>: ' . esc_html( $plugin_info['Version'] ) . '</div>';
		}
		$out .= '</div></td>';

		$out .= '<td>' . ( $total_size > 0 ? esc_html( (string) $total_size ) . ' KB' : '?' ) . '</td>';

		$out .= '<td class="option-everwhere">';
		$out .= '<select>';
		$out .= '<option value="e">' . esc_html__( 'Enable', 'whippet' ) . '</option>';
		$out .= '<option value="d" ' . ( ( $is_checked_ever || $is_checked_here || $is_checked_regex ) ? 'selected' : '' ) . '>' . esc_html__( 'Disable', 'whippet' ) . '</option>';
		$out .= '</select></td>';

		if ( $show_mu_mode ) {
			$out .= '<td class="whippet-mu-cell">';
			$out .= $this->render_mu_toggle_control(
				$plugin,
				$mu_on,
				true,
				__( 'MU Mode is controlled from the backend settings.', 'whippet' )
			);
			$out .= '</td>';
		}

		$out .= '<td class="options">';
		$cond_class = ( ! $is_checked_ever && ! $is_checked_here && ! $is_checked_regex ) ? 'g-disabled' : '';
		$out .= '<div class="g-cond ' . $cond_class . '"><b>' . esc_html__( 'Disabled', 'whippet' ) . '</b><br><span class="whippet-label">' . esc_html__( 'Locations:', 'whippet' ) . '</span><br>';
		$out .= '<label><input name="disabledPlugin' . $id . '" type="radio" value="here" ' . ( $is_checked_here ? 'checked' : '' ) . '> ' . esc_html__( 'Current URL', 'whippet' ) . '</label> ';
		$out .= '<label><input name="disabledPlugin' . $id . '" type="radio" value="everywhere" ' . ( $is_checked_ever ? 'checked' : '' ) . '> ' . esc_html__( 'Everywhere', 'whippet' ) . '</label> ';
		$out .= '<label><input name="disabledPlugin' . $id . '" type="radio" value="regex" ' . ( $is_checked_regex ? 'checked' : '' ) . '> ' . esc_html__( 'Regex', 'whippet' ) . '</label><br>';
		$out .= '<div class="g-regex ' . ( ! $is_checked_regex ? 'g-disabled' : '' ) . '"><input type="text" name="disabledPluginRegex' . $id . '" value="' . esc_attr( $is_checked_regex ? $this->whippet_data_plugins['disabled'][ $plugin ]['regex'] : '' ) . '" placeholder="' . esc_attr__( 'URL pattern', 'whippet' ) . '" spellcheck="false" class="whippet-regex-input"></div></div>';

		$excp_class = ( ! $is_checked_ever && ! $is_checked_here && ! $is_checked_regex ) ? 'g-disabled' : '';
		$out .= '<div class="g-excp ' . $excp_class . '"><b>' . esc_html__( 'Exceptions', 'whippet' ) . '</b><br><span class="whippet-label">' . esc_html__( 'Locations:', 'whippet' ) . '</span><br>';
		$out .= '<label><input type="checkbox" name="enabledPlugin' . $id . '[here]" ' . ( isset( $this->whippet_data_plugins['enabled'][ $plugin ]['here'] ) ? 'checked="checked"' : '' ) . '> ' . esc_html__( 'Current URL', 'whippet' ) . '</label> ';
		foreach ( $this->all_content_types as $content_type_code => $content_type_label ) {
			$out .= '<label><input type="checkbox" name="enabledPlugin' . $id . '[' . esc_attr( $content_type_code ) . ']" ' . ( isset( $this->whippet_data_plugins['enabled'][ $plugin ][ $content_type_code ] ) ? 'checked="checked"' : '' ) . '> ' . esc_html( $content_type_label ) . '</label> ';
		}
		$out .= '<br><span class="whippet-label">' . esc_html__( 'Users:', 'whippet' ) . '</span> <select class="whippet-excp-users"><option value="">' . esc_html__( 'Default', 'whippet' ) . '</option></select> ';
		$out .= '<span class="whippet-label">' . esc_html__( 'Devices:', 'whippet' ) . '</span> <select class="whippet-excp-devices"><option value="">' . esc_html__( 'Default', 'whippet' ) . '</option></select><br>';
		$out .= '<span class="whippet-label">' . esc_html__( 'Regex:', 'whippet' ) . '</span> <input type="text" name="enabledPlugin' . $id . '[regex]" value="' . esc_attr( $enabled_regex_val ) . '" placeholder="' . esc_attr__( 'Enable when URL matches', 'whippet' ) . '" spellcheck="false" class="whippet-regex-input"></div>';

		if ( get_option( 'whippet_scripts_mu_mode', 0 ) && ! get_option( 'whippet_scripts_hide_disclaimer', 0 ) ) {
			$out .= '<p class="whippet-mu-footer">' . esc_html__( 'MU Mode is currently enabled; the above settings will apply to the entire plugin.', 'whippet' ) . '</p>';
		}
		$out .= '</td></tr></tbody></table>';
		return $out;
	}

	/**
	 * Display empty list of plugins (for disabled plugins)
	 *
	 * @param  array $types       List of assets in JS/CSS groups.
	 * @param  array $plugin_info Plugin details.
	 * @return string              Html
	 */
	private function render_empty_disabled_plugin_group( $types, $plugin_info = null ) {
		$out = '';

		$this->already_listed_plugins[] = $plugin_info['gPluginName'];

		$plugin_wrapper = '<div class="plugin-wrapper">';
		$plugin_wrapper .= $this->render_plugin_group_header( $types, $plugin_info );
		$plugin_wrapper .= '<div class="whip empty">' . __( 'This list is empty because plugin has been disabled.', 'whippet' ) . '<br>' . __( 'It means that potential assets served by this plugin have been automatically disabled too.', 'whippet' ) . '</div>';
		$plugin_wrapper .= '</div>';

		$out .= $plugin_wrapper;

		return $out;
	}

	/**
	 * Display empty list of plugins (for enabled plugins)
	 *
	 * @param  array $types       List of assets in JS/CSS groups.
	 * @param  array $plugin_info Plugin details.
	 * @return string              Html
	 */
	private function render_empty_enabled_plugin_group( $types, $plugin_info = null ) {
		$out = '';

		$this->already_listed_plugins[] = $plugin_info['gPluginName'];

		$plugin_wrapper = '<div class="plugin-wrapper">';
		$plugin_wrapper .= $this->render_plugin_group_header( $types, $plugin_info );
		$plugin_wrapper .= '<div class="whip empty">' . __( 'This plugin doesn\'t serve assets', 'whippet' ) . '</div>';
		$plugin_wrapper .= '</div>';

		$out .= $plugin_wrapper;

		return $out;
	}

	/**
	 * Render table of assets (CSS/JS)
	 * To use common code in both certain plugins and themes/misc
	 *
	 * @return string
	 */
	private function render_group( $resource_type, $types, $plugin_info = null ) {
		$out = '';
		$show_mu_mode = (bool) get_option( 'whippet_scripts_mu_mode', 0 );

		if ( ! empty( $plugin_info ) ) {
			$plugin_wrapper = '<div class="plugin-wrapper">';
			$plugin_wrapper .= $this->render_plugin_group_header( $types, $plugin_info );

			$this->already_listed_plugins[] = $plugin_info['gPluginName'];

			$out .= $plugin_wrapper;
		} else {
			$out .= '<h2>' . __( $resource_type, 'whippet' ) . '</h2>';
		}

		$out .= '<table class="whip ' . ( $show_mu_mode ? 'has-mu-mode' : 'no-mu-mode' ) . '">';
		$out .= '<thead>';
			$out .= '<th>' . __( 'Loaded', 'whippet' ) . '</th>';
			$out .= '<th>' . __( 'Asset info', 'whippet' ) . '</th>';
			$out .= '<th>' . __( 'Size', 'whippet' ) . '</th>';
			if ( $show_mu_mode ) {
				$out .= '<th>' . esc_html__( 'MU Mode', 'whippet' ) . '</th>';
			}
			$out .= '<th>' . __( 'State', 'whippet' ) . '</th>';
			$out .= '<th></th>';
		$out .= '</thead>';
		$out .= '<tbody>';

		foreach ( $types as $type_name => $rows ) {
			foreach ( $rows as $handle => $row ) {

				/**
				 * Find dependency
				 */
				$deps = array();
				foreach ( $row['deps'] as $dep_val ) {
					$unique = $type_name . '-' . $dep_val;

					// jQuery is not visible to connect with formal owenr of jQuery handle.
					$href_name = ( 'js-jquery' === $unique ? 'js-jquery-core' : $unique );
					$deps[] = '<a href="#' . $href_name . '">' . $dep_val . '</a>';
				}

				$depend_on = array();
				foreach ( $this->dependency_collection as $asset ) {
					if ( in_array( $handle, $asset['deps'] ) && $asset['state'] && ! empty( $asset['filename'] ) ) {
						$unique = $asset['file_extension'] . '-' . $asset['name'];

						// jQuery is not visible to connect with formal owenr of jQuery handle.
						$href_name = ( 'js-jquery' === $unique ? 'js-jquery-core' : $unique );
						$depend_on[ $unique ] = '<a href="#' . $href_name . '">' . $asset['name'] . '</a>';
					}
				}

				$id = '[' . $type_name . '][' . $handle . ']';

				$is_checked_ever  = isset( $this->whippet_data['disabled'][ $type_name ][ $handle ]['everywhere'] );
				$is_checked_here  = isset( $this->whippet_data['disabled'][ $type_name ][ $handle ]['here'] );
				$is_checked_post  = isset( $this->whippet_data['disabled'][ $type_name ][ $handle ]['post'] );
				$is_checked_page  = isset( $this->whippet_data['disabled'][ $type_name ][ $handle ]['page'] );
				$is_checked_regex = isset( $this->whippet_data['disabled'][ $type_name ][ $handle ]['regex'] );
				$disabled_regex_val = $is_checked_regex ? $this->whippet_data['disabled'][ $type_name ][ $handle ]['regex'] : '';

				$option_everywhere = '<select>';
				$option_everywhere .= '<option value="e">' . __( 'Enable', 'whippet' ) . '</option>';
				$option_everywhere .= '<option value="d" ' . ( ( $is_checked_ever || $is_checked_here || $is_checked_post || $is_checked_page || $is_checked_regex ) ? 'selected' : '' ) . '>' . __( 'Disable', 'whippet' ) . '</option>';
				$option_everywhere .= '</select>';

				$cond_class = ( ! $is_checked_ever && ! $is_checked_here && ! $is_checked_post && ! $is_checked_page && ! $is_checked_regex ) ? 'g-disabled' : '';

				$out .= '<tr>';
				$out .= '<td><div class="state-' . (int) $row['state'] . '">' . ( $row['state'] ? 'YES' : 'NO' ) . '</div></td>';
				$out .= '<td class="overflow"><a class="g-link" name="' . esc_attr( $type_name . '-' . $handle ) . '" href="' . esc_url( $row['url_full'] ) . '" target="_blank">' . ( $row['is_external'] ? esc_html( $row['url_full'] ) : ( esc_html( $row['file_base'] ) . '.<b>' . esc_html( $row['file_extension'] ) . '</b>' ) ) . '</a>';
				$out .= '<div class="g-info"><div><b>' . __( 'Handle', 'whippet' ) . ':</b> ' . esc_html( $handle ) . '</div>';
				if ( get_option( 'whippet_scripts_display_deps', 1 ) ) {
					if ( ! empty( $deps ) ) {
						$out .= '<div><b>' . __( 'Require', 'whippet' ) . ':</b> ' . implode( ', ', $deps ) . '</div>';
					}
					if ( ! empty( $depend_on ) ) {
						$out .= '<div><b>' . __( 'Depend on', 'whippet' ) . ':</b> ' . implode( ', ', array_values( $depend_on ) ) . '</div>';
					}
				}
				$out .= '</div></td>';
				$out .= '<td>' . ( empty( $row['size'] ) ? '?' : esc_html( (string) $row['size'] ) ) . ' KB</td>';
				if ( $show_mu_mode ) {
					$out .= '<td class="whippet-mu-cell">';
					if ( ! empty( $plugin_info['gPluginName'] ) ) {
						$mu_plugins = get_option( 'whippet_p_mu_plugins', array() );
						$mu_on = ! isset( $mu_plugins[ $plugin_info['gPluginName'] ] ) || (int) $mu_plugins[ $plugin_info['gPluginName'] ] === 1;
						$out .= $this->render_mu_toggle_control(
							$plugin_info['gPluginName'],
							$mu_on,
							true,
							__( 'MU Mode is controlled from the backend settings.', 'whippet' )
						);
					} else {
						$out .= '<div class="whippet-input-wrapper">';
						$out .= '<input type="checkbox" value="1" style="display: block; margin: 0;" disabled title="' . esc_attr__( 'MU Mode applies to plugins only.', 'whippet' ) . '">';
						$out .= '</div>';
					}
					$out .= '</td>';
				}
				$out .= '<td class="option-everwhere">' . $option_everywhere . '</td>';
				$out .= '<td class="options">';
				$out .= '<div class="g-cond whippet-disable-panel ' . $cond_class . '">';
				$out .= '<b>' . __( 'Disabled', 'whippet' ) . '</b>';
				$out .= '<div class="whippet-disable-panel__row"><span class="whippet-disable-panel__label">' . __( 'Locations:', 'whippet' ) . '</span><div class="whippet-disable-panel__controls">';
				$out .= '<label><input type="checkbox" name="disabled' . $id . '[everywhere]" value="1" ' . checked( $is_checked_ever, true, false ) . '> ' . __( 'Everywhere', 'whippet' ) . '</label>';
				$out .= '<label><input type="checkbox" name="disabled' . $id . '[here]" value="1" ' . checked( $is_checked_here, true, false ) . '> ' . __( 'Current URL', 'whippet' ) . '</label>';
				$out .= '<label><input type="checkbox" name="disabled' . $id . '[post]" value="1" ' . checked( $is_checked_post, true, false ) . '> ' . __( 'Posts', 'whippet' ) . '</label>';
				$out .= '<label><input type="checkbox" name="disabled' . $id . '[page]" value="1" ' . checked( $is_checked_page, true, false ) . '> ' . __( 'Pages', 'whippet' ) . '</label>';
				$out .= '</div></div>';
				$out .= '<div class="whippet-disable-panel__row"><span class="whippet-disable-panel__label">' . __( 'Users:', 'whippet' ) . '</span><div class="whippet-disable-panel__controls"><select class="whippet-disable-panel__select"><option value="">' . esc_html__( 'Default', 'whippet' ) . '</option></select></div></div>';
				$out .= '<div class="whippet-disable-panel__row"><span class="whippet-disable-panel__label">' . __( 'Devices:', 'whippet' ) . '</span><div class="whippet-disable-panel__controls"><select class="whippet-disable-panel__select"><option value="">' . esc_html__( 'Default', 'whippet' ) . '</option></select></div></div>';
				$out .= '<div class="whippet-disable-panel__row"><span class="whippet-disable-panel__label">' . __( 'Regex:', 'whippet' ) . '</span><div class="whippet-disable-panel__controls"><input type="text" name="disabled' . $id . '[regex]" value="' . esc_attr( $disabled_regex_val ) . '" placeholder="' . esc_attr__( 'Disable when URL matches', 'whippet' ) . '" spellcheck="false" class="whippet-regex-input"></div></div>';
				$out .= '</div>';
				$out .= '</td></tr>';

				$this->all_assets[] = $handle;
			}
		}

		$out .= '</tbody>
		</table>';

		if ( ! empty( $plugin_info ) ) {
			$plugin_wrapper = '</div>';

			$out .= $plugin_wrapper;
		}

		return $out;
	}

	/**
	 * Get current URL
	 *
	 * @return string
	 */
	private function get_current_url() {
		/**
		 * Direct access for FastCGI servers.
		 * Intentionally used filter_var instead of filter_input( INPUT_SERVER...
		 */
		$request_uri = filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL );

		$url = explode( '?', $request_uri, 2 );
		if ( strlen( $url[0] ) > 1 ) {
			$out = rtrim( $url[0], '/' );
		} else {
			$out = $url[0];
		}

		return $out;
	}

	/**
	 * Generated content types
	 *
	 * @return mixed
	 */
	private function get_public_post_types() {
		$tmp = get_post_types( array(
			'public'   => true,
		), 'objects', 'and' );

		$out = array();
		foreach ( $tmp as $key => $value ) {
			$out[ $key ] = $value->label;
		}

		if ( get_option( 'whippet_scripts_display_archives', 0 ) ) {
			$archive_types = array(
				'archive'  => __( 'Archives', 'whippet' ),
				'category' => __( 'Category', 'whippet' ),
				'tag'      => __( 'Tag', 'whippet' ),
				'author'   => __( 'Author', 'whippet' ),
				'date'     => __( 'Date', 'whippet' ),
				'search'   => __( 'Search Results', 'whippet' ),
				'404'      => __( '404', 'whippet' ),
			);
			$out = array_merge( $out, $archive_types );
		}

		return $out;
	}
}

/**
 * Verify that everything's fine with instance.
 *
 * @return mixed
 */
function check_whippet() {
	$response = wp_remote_get( home_url( '/' ) );
	if ( ! is_wp_error( $response ) ) {
		return json_decode( $response['body'] );
	}
	return null;
}

new Whippet;
