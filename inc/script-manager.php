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
$whippet_db_version = 1.3;

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
	$current_db_version = floatval( get_option( 'whippet_db_version', 1.0 ) );

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
	 * Stores list of all plugins
	 *
	 * @var array
	 */
	private $all_plugin = array();

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
		// add_action( 'admin_init', array( $this, 'check_updates' ), 1 );

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
		// WordPress 5.0.0.
		$polyfills_filter = 'wp-polyfill';

		if ( substr( $handle, 0, strlen( $polyfills_filter ) ) !== $polyfills_filter ) {
			$type = ( current_filter() == 'script_loader_src' ) ? 'js' : 'css';
			$source = ( current_filter() == 'script_loader_src' ) ? wp_scripts() : wp_styles();

			return ( $this->get_visibility_asset( $type, $handle ) ? $url : false);
		}
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
	 * Read list of all plugins in plugins dir.
	 *
	 * @return mixed
	 */
	public function read_plugins_list() {
		// Check if get_plugins() function exists. This is required on the front end of the
		// site, since it is in a file that is normally only loaded in the admin.
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->all_plugins = get_plugins();
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
		$theme_root_uri = get_theme_root_uri();
		foreach ( $data_assets as $type => $data ) {
			foreach ( $data->done as $el ) {
				if ( ! in_array( $el, $this->whitelist_assets[ $type ] ) ) {
					if ( isset( $data->registered[ $el ]->src ) && ! empty( $data->registered[ $el ]->src ) ) {
						$url = $this->prepare_correct_url( $data->registered[ $el ]->src );

						if ( false !== strpos( $url, $plugins_url ) ) {
							$resource_name = 'plugins';

							// Generuje nazwę folderu pluginu z URL asseta.
							$plugin_path = str_replace( $plugins_url, '', $url );
							if ( '/' == $plugin_path[0] ) {
								$plugin_path = substr( $plugin_path, 1 );
							}
							$plugin_path = explode( '/', $plugin_path );
							$plugin_dir = $plugin_path[0];

						} elseif ( false !== strpos( $url, $theme_root_uri ) ) {
							$resource_name = 'theme';
						} else {
							$resource_name = 'misc';
						}

						$url_info = pathinfo( $url );
						$filename = isset( $url_info['basename'] ) ? $url_info['basename'] : '';
						$file_base = isset( $url_info['filename'] ) ? $url_info['filename'] : '';
						$file_extension = isset( $url_info['extension'] ) ? $url_info['extension'] : '';

						$arr = array(
							'url_full' => $url,
							'filename' => $filename,
							'file_base' => $file_base,
							'is_external' => $this->check_if_external( $url ),
							'file_extension' => $file_extension,
							'state' => $this->get_visibility_asset( $type, $el ),
							'size' => $this->get_asset_size( $url ),
							'deps' => ( isset( $data->registered[ $el ]->deps ) ? $data->registered[ $el ]->deps : array() ),
						);

						if ( 'plugins' == $resource_name ) {
							$arr['plugin'] = '';
							$this->collection[ $resource_name ][ $plugin_dir ][ $type ][ $el ] = $arr;
						} else {
							$this->collection[ $resource_name ][ $type ][ $el ] = $arr;
						}

						// to not look for dependencies in many levels nested foreach loops.
						$this->dependency_collection[] = array(
							'name' => $el,
							'filename' => $filename,
							'file_extension' => $file_extension,
							'state' => $this->get_visibility_asset( $type, $el ),
							'deps' => ( isset( $data->registered[ $el ]->deps ) ? $data->registered[ $el ]->deps : array() ),
						);
					}
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
		$components_dest = parse_url( $url );
		$components_base = parse_url( get_site_url() );
		return ! empty( $components_dest['host'] ) && strcasecmp( $components_dest['host'], $components_base['host'] );
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

		if ( is_admin() && 'Whippet' == get_option( 'Activated_Plugin' ) ) {
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

	// Add preconnect for Google Fonts
	add_action( is_admin() ? 'admin_head' : 'wp_head', array( $this, 'add_font_preconnect_panel' ), 1 );
	
	// Enqueue Figtree font from Google Fonts
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
	 * Execute action once checkbox is changed
	 */
	public function update_configuration() {
		global $wpdb;

		if ( ! current_user_can( 'manage_options' ) ||
		 ! isset( $_POST['whippetUpdate'] ) ||
		 ! wp_verify_nonce( filter_input( INPUT_POST, 'whippetUpdate' ), 'whippet' ) ||
		 ! isset( $_POST['allAssets'] ) ||
		 empty( $_POST['allAssets'] ) ||
		 empty( $_POST['currentURL'] ) ) {
			return false;
		}

		$all_assets = json_decode( html_entity_decode( filter_input( INPUT_POST, 'allAssets', FILTER_SANITIZE_SPECIAL_CHARS ) ) );

		if ( empty( $all_assets ) ) {
			return false;
		}

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
		$current_url = esc_url_raw( filter_input( INPUT_POST, 'currentURL' ) );
		$placeholders = implode( ', ', array_fill( 0, count( $all_assets ), '%s' ) );
		$sql = $wpdb->prepare( 
			"DELETE FROM {$wpdb->prefix}whippet_disabled WHERE handler_name IN ($placeholders) AND (url = '' OR url = %s)",
			array_merge( $all_assets, array( $current_url ) )
		);
		$wpdb->query( $sql );

		$sql = $wpdb->prepare( 
			"DELETE FROM {$wpdb->prefix}whippet_enabled WHERE handler_name IN ($placeholders) AND (url = '' OR url = %s)",
			array_merge( $all_assets, array( $current_url ) )
		);
		$wpdb->query( $sql );


		/**
		 * Inserting new configuration
		 */
		if ( isset( $_POST['disabled'] ) && ! empty( $_POST['disabled'] ) && is_array( $_POST['disabled'] ) ) {
			foreach ( $_POST['disabled'] as $type => $assets ) {
				if ( ! empty( $assets ) && is_array( $assets ) ) {
					$type = sanitize_text_field( $type );
					foreach ( $assets as $handle => $where ) {
						if ( ! empty( $where ) && is_array( $where ) ) {
							$handle = sanitize_text_field( $handle );
							foreach ( $where as $place ) {
								$place = sanitize_text_field( $place );
								$wpdb->insert(
									$wpdb->prefix . 'whippet_disabled',
									array(
										'handler_type' => $this->get_handler_type( $type ),
										'handler_name' => $handle,
										'url' => ( 'here' === $place ? esc_url_raw( filter_input( INPUT_POST, 'currentURL' ) ) : '' ),
									),
									array( '%d', '%s', '%s' )
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
							foreach ( $content_types as $content_type => $nvm ) {
								$content_type = sanitize_text_field( $content_type );
								$wpdb->insert(
									$wpdb->prefix . 'whippet_enabled',
									array(
										'handler_type' => $this->get_handler_type( $type ),
										'handler_name' => $handle,
										'content_type' => $content_type,
										'url' => ( 'here' === $content_type ? esc_url_raw( filter_input( INPUT_POST, 'currentURL' ) ) : '' ),
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
		$current_url = esc_url_raw( filter_input( INPUT_POST, 'currentURL' ) );
		$wpdb->query( $wpdb->prepare( 
			"DELETE FROM {$wpdb->prefix}whippet_p_disabled WHERE (url = '' OR url = %s)",
			$current_url
		) );

		$wpdb->query( $wpdb->prepare( 
			"DELETE FROM {$wpdb->prefix}whippet_p_enabled WHERE (url = '' OR url = %s)",
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
							'url' => ( 'here' === $where ? esc_url_raw( filter_input( INPUT_POST, 'currentURL' ) ) : '' ),
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
					foreach ( $content_types as $content_type => $nvm ) {
						$content_type = sanitize_text_field( $content_type );
						$wpdb->insert(
							$wpdb->prefix . 'whippet_p_enabled',
							array(
								'name' => $plugin,
								'content_type' => $content_type,
								'url' => ( 'here' === $content_type ? esc_url_raw( filter_input( INPUT_POST, 'currentURL' ) ) : '' ),
							),
							array( '%s', '%s', '%s' )
						);
					}
				}
			}
		}


		$http_referer = '';
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$http_referer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		}
		
		// Fallback to current admin URL if referer is invalid
		if ( empty( $http_referer ) || ! wp_http_validate_url( $http_referer ) ) {
			$http_referer = admin_url( 'tools.php?page=whippet' );
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

		if ( isset( $this->whippet_data['disabled'][ $type ][ $plugin ] ) ) {
			$state = false;

			if ( isset( $this->whippet_data['enabled'][ $type ][ $plugin ][ $this->content_type ] ) ||
				isset( $this->whippet_data['enabled'][ $type ][ $plugin ]['here'] ) ) {
				$state = true;
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

			// Even if regex is available checks if it's valid!
			if ( isset( $this->whippet_data_plugins['disabled'][ $plugin ]['regex'] ) ) {
				$matches = array();
				@preg_match( '/' . $this->whippet_data_plugins['disabled'][ $plugin ]['regex'] . '/', esc_url( $this->get_current_url() ), $matches );
				$state = ( count( $matches ) ? 0 : 1 );
			} else {
				$state = 0;
			}

			if ( isset( $this->whippet_data_plugins['enabled'][ $plugin ][ $this->content_type ] ) ||
				isset( $this->whippet_data_plugins['enabled'][ $plugin ]['here'] ) ) {
				$state = 1;
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
		if ( isset( $url[0] ) && isset( $url[1] ) && '/' == $url[0] && '/' == $url[1] ) {
			$out = (is_ssl() ? 'https:' : 'http:') . $url;
		} else {
			$out = $url;
		}

		return $out;
	}

	/**
	 * Checks how heavy is file
	 *
	 * @param  string $src    URL.
	 * @return int          Size in KB.
	 */
	private function get_asset_size( $src ) {
		$weight = 0;

		$home = get_theme_root() . '/../..';
		$src = explode( '?', $src );

		$src_relative = $home . str_replace( get_home_url(), '', $this->prepare_correct_url( $src[0] ) );

		if ( file_exists( $src_relative ) ) {
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
			if ( $wpdb->get_var( 'SHOW TABLES LIKE "' . $table_name . '"' ) != $table_name ) {
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

		$disabled_global = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'whippet_disabled WHERE url = ""', ARRAY_A );
		$disabled_here = $wpdb->get_results( sprintf( 'SELECT * FROM ' . $wpdb->prefix . 'whippet_disabled WHERE url = "%s"',
			esc_url( $this->get_current_url() )
			), ARRAY_A );

		$enabled_posts = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'whippet_enabled WHERE content_type != "here"', ARRAY_A );
		$enabled_here = $wpdb->get_results( sprintf( 'SELECT * FROM %s WHERE content_type = \'%s\' AND url=\'%s\'',
			$wpdb->prefix . 'whippet_enabled',
			'here',
			esc_url( $this->get_current_url() ) ), ARRAY_A );
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

		if ( ! empty( $enabled ) ) {
			foreach ( $enabled as $row ) {
				$type = $this->get_handler_type( $row['handler_type'] );
				$out['enabled'][ $type ][ $row['handler_name'] ][ $row['content_type'] ] = true;
			}
		}

		$this->whippet_data = $out;


		/**
		 * Plugins
		 */
		$out = array();
		$current_url = esc_url( $this->get_current_url() );

		$disabled_global = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'whippet_p_disabled WHERE url = "" AND regex = ""', ARRAY_A );
		$disabled_here = $wpdb->get_results( sprintf( 'SELECT * FROM ' . $wpdb->prefix . 'whippet_p_disabled WHERE url = "%s"',
			$current_url
			), ARRAY_A );
		$disabled_regex = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'whippet_p_disabled WHERE regex != ""', ARRAY_A );

		$enabled_posts = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'whippet_p_enabled WHERE content_type != "here"', ARRAY_A );
		$enabled_here = $wpdb->get_results( sprintf( 'SELECT * FROM %s WHERE content_type = \'%s\' AND url=\'%s\'',
			$wpdb->prefix . 'whippet_p_enabled',
			'here',
			$current_url ), ARRAY_A );
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
				$out['disabled'][ $row['name'] ]['regex'] = stripslashes( $row['regex'] );
			}
		}

		if ( ! empty( $enabled ) ) {
			foreach ( $enabled as $row ) {
				$out['enabled'][ $row['name'] ][ $row['content_type'] ] = true;
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

		$out = '<form id="whippet" class="whippet-panel" method="POST" style="display: none;">
		<h1><span>Whippet</span></h1>';

		$this->all_assets = array();
		$this->all_content_types = $this->get_public_post_types();

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

			if ( 'plugins' == $resource_type ) {
				$out .= '<h2>' . __( $resource_type , 'whippet' ) . '</h2>';

				foreach ( $types as $plugin_dir => $types_sub ) {
					$plugin_info = $this->get_plugin_info_by_directory( $plugin_dir );

					// Do not place whitelisted plugins + do not clone the same plugin among enabled/disable plugins list.
					if ( in_array( $plugin_info['gPluginName'], $this->whitelist_plugins ) || in_array( $plugin_info['gPluginName'], $this->already_listed_plugins ) ) {
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
					if ( isset( $whippet_helper->control[ $plugin_state ] ) && ! empty( $whippet_helper->control[ $plugin_state ] ) ) {
						foreach ( $whippet_helper->control[ $plugin_state ] as $plugin_path ) {

							$plugin_dir = $this->get_plugin_slug( $plugin_path );
							$plugin_info = $this->get_plugin_info_by_directory( $plugin_dir );

							// Do not place whitelisted plugins + do not clone the same plugin among enabled/disable plugins list.
							if ( in_array( $plugin_info['gPluginName'], $this->whitelist_plugins ) || in_array( $plugin_info['gPluginName'], $this->already_listed_plugins ) ) {
								continue;
							}

							if ( 'disabled' == $plugin_state ) {
								$out .= $this->render_empty_disabled_plugin_group( $types_sub, $plugin_info );
							} else {
								$out .= $this->render_empty_enabled_plugin_group( $types_sub, $plugin_info );
							}
						}
					}
				}
			} else {
				$out .= $this->render_group( $resource_type, $types );
			}
		}

		$out .= '<input type="submit" id="submit-whippet" value="' . __( 'Save changes' ) . '">';
		$out .= wp_nonce_field( 'whippet', 'whippetUpdate', true, false );
		$out .= '<input type="hidden" name="currentURL" value="' . esc_url( $this->get_current_url() ) . '">
			<input type="hidden" name="allAssets" value="' . filter_var( json_encode( $this->all_assets ), FILTER_SANITIZE_SPECIAL_CHARS ) . '">
		</form>';

		print $out;
	}

	/**
	 * Get plugin slug by path
	 *
	 * @param  string $plugin_path Input.
	 * @return string              Output
	 */
	/*private function get_plugin_dir_by_path( $plugin_path ) {
		$plugin_dir = explode( '/', $plugin_path );
		$plugin_dir = $plugin_dir[0];
		if ( '/' == $plugin_dir[0] ) {
			$plugin_dir = substr( $plugin_dir, 1 );
		}

		return $plugin_dir;
	}*/
	private function get_plugin_slug( $plugin_path ) {
		$out = explode( '/', $plugin_path );

		if ( count( $out ) == 1 ) {
			/**
			 * Single file, not nested in folder.
			 * Exploding and removing extension assuming it can be .php5 or php7 instead of traditional .php
			 */
			$out = explode( '.', $plugin_path );
			array_pop( $out );
		}

		return $out[0];
	}

	/**
	 * Get plugin details base on provided part of URL address.
	 *
	 * @param  string $plugin_slug Plugin directory.
	 * @return string
	 */
	private function get_plugin_info_by_directory( $plugin_slug ) {
		foreach ( $this->all_plugins as $plugin_path => $plugin_details ) {
			/*
			 * 0 = beginning of the string, / = end url
			 * Would not enlist "bloom" with "et-bloom-extender"
			 */
			if (
				( 0 === strpos( $plugin_path, $plugin_slug . '/' ) ) || // Plugin in directory.
				( 0 === strpos( $plugin_path, $plugin_slug . '.' ) )    // Plugin as single file in plugins directory.
			) {
				return array_merge(
					$plugin_details,
					array(
						'gPluginPath' => $plugin_path,
						'gPluginName' => $plugin_slug,
					)
				);
			}
		}
	}

	/**
	 * Display header with plugin control
	 *
	 * @param  array $types       List of assets in JS/CSS groups.
	 * @param  array $plugin_info Plugin details.
	 * @return string              Html
	 */
	private function render_plugin_group_header( $types, $plugin_info ) {
		$plugin = $plugin_info['gPluginName'];
		$id = '[' . $plugin . ']';

		// Configured state (theory).
		$is_checked_ever = isset( $this->whippet_data_plugins['disabled'][ $plugin ]['everywhere'] );
		$is_checked_here = isset( $this->whippet_data_plugins['disabled'][ $plugin ]['here'] );
		$is_checked_regex = isset( $this->whippet_data_plugins['disabled'][ $plugin ]['regex'] );

		// Real Plugin state (practice).
		$real_state = $this->get_visibility_plugin( $plugin );

		$out = '<table class="gonz plugin">';
		$out .= '<thead>';
			$out .= '<tr>';
				$out .= '<th>' . __( 'Loaded', 'whippet' ) . '</th>';
				$out .= '<th>' . __( 'Plugin info', 'whippet' ) . '</th>';
				$out .= '<th>' . __( 'State', 'whippet' ) . '</th>';
				$out .= '<th></th>';
			$out .= '</tr>';
		$out .= '</thead>';
		$out .= '<tbody>';
		$out .= '<tr>';
		$out .= '<td><div class="state-' . $real_state . '">' . ( true == $real_state ? 'YES' : 'NO' ) . '</div></td>';
		$out .= '<td class="overflow">';

		if ( ! empty( $plugin_info['PluginURI'] ) ) {
			$out .= '<a href="' . $plugin_info['PluginURI'] . '" target="_blank">';
		}

		$out .= '<span>' . $plugin_info['Name'] . '</span>';

		if ( ! empty( $plugin_info['PluginURI'] ) ) {
			$out .= '</a>';
		}

		$out .= '<div class="g-info">';
		if ( ! empty( $plugin_info['Author'] ) ) {
			$out .= '<div><b>Author</b>: ' . $plugin_info['AuthorName'] . '</div>';
		}

		if ( ! empty( $plugin_info['Version'] ) ) {
			$out .= '<div><b>Version</b>: ' . $plugin_info['Version'] . '</div>';
		}
		$out .= '</div>';

		$out .= '</td>';

		$out .= '<td class="option-everwhere">';
			$option_everywhere = '<select>';
				$option_everywhere .= '<option value="e">' . __( 'Enable', 'gonales' ) . '</option>';
				$option_everywhere .= '<option value="d" ' . ( ( $is_checked_ever || $is_checked_here || $is_checked_regex ) ? 'selected' : '' ) . '>' . __( 'Disable', 'whippet' ) . '</option>';
			$option_everywhere .= '</select>';

			$out .= $option_everywhere;
		$out .= '</td>';

		$out .= '<td class="options"><div class="g-cond ' . ( ( ! $is_checked_ever && ! $is_checked_here && ! $is_checked_regex ) ? 'g-disabled' : '' ) . '"><b>' . __( 'Where', 'whippet' ) . ':</b><br>';
			$out .= '<label><input name="' . 'disabledPlugin' . $id . '" type="radio" value="here" ' . ( $is_checked_here ? 'checked' : '' ) . '>' . __( 'Current URL', 'whippet' ) . '</label>';
			$out .= '<label><input name="' . 'disabledPlugin' . $id . '" type="radio" value="everywhere" ' . ( $is_checked_ever ? 'checked' : '' ) . '>' . __( 'Everywhere', 'whippet' ) . '</label>';
			$out .= '<label><input name="' . 'disabledPlugin' . $id . '" type="radio" value="regex" ' . ( $is_checked_regex ? 'checked' : '' ) . '>' . __( 'Regex', 'whippet' ) . '</label><br></div>';

			// Exceptions: Current URL.
			$is_checked = (isset( $this->whippet_data_plugins['enabled'][ $plugin ]['here'] ) ? 'checked="checked"' : '');
			$options_enable = '<label><input type="checkbox" name="enabledPlugin' . $id . '[here]" ' . $is_checked . '>' . __( 'Current URL', 'whippet' ) . '</label>';

			// Exceptions: Content types.
			/**
				Because of technical problems it's not possible to access content type on option filter
				$id_type = 'enabledPlugin' . $id . '[' . $content_type_code . ']';
				$is_checked = ( isset( $this->whippet_data_plugins['enabled'][ $plugin ][ $content_type_code ] ) ? 'checked="checked"' : '' );
				$options_enable .= '<label><input type="checkbox" name="' . $id_type . '" ' . $is_checked . '>' . $content_type . '</label>';
			}*/

			// Regex
			$out .= '<div class="g-excp ' . ( ! $is_checked_ever ? 'g-disabled' : '' ) . '">';
				$out .= '<b>' . __( 'Exceptions', 'whippet' ) . ':</b><br>' . $options_enable;
		$out .= '</div>';
		$out .= '<div class="g-regex ' . ( ! $is_checked_regex ? 'g-disabled' : '' ) . '">';
			$out .= '<textarea name="' . 'disabledPluginRegex' . $id . '" spellcheck="false">' . ( $is_checked_regex ? $this->whippet_data_plugins['disabled'][ $plugin ]['regex'] : '' ) . '</textarea>';
		$out .= '</div>';
		$out .= '</td>';


		$out .= '</tr></tbody>
		</table>';

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
		$plugin_wrapper .= '<div class="gonz empty">' . __( 'This list is empty because plugin has been disabled.', 'whippet' ) . '<br>' . __( 'It means that potential assets served by this plugin have been automatically disabled too.', 'whippet' ) . '</div>';
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
		$plugin_wrapper .= '<div class="gonz empty">' . __( 'This plugin doesn\'t serve assets', 'whippet' ) . '</div>';
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

		if ( ! empty( $plugin_info ) ) {
			$plugin_wrapper = '<div class="plugin-wrapper">';
			$plugin_wrapper .= $this->render_plugin_group_header( $types, $plugin_info );

			$this->already_listed_plugins[] = $plugin_info['gPluginName'];

			$out .= $plugin_wrapper;
		} else {
			$out .= '<h2>' . __( $resource_type, 'whippet' ) . '</h2>';
		}

		$out .= '<table class="gonz">';
		$out .= '<thead>';
			$out .= '<th>' . __( 'Loaded', 'whippet' ) . '</th>';
			$out .= '<th>' . __( 'Asset info', 'whippet' ) . '</th>';
			$out .= '<th>' . __( 'Size', 'whippet' ) . '</th>';
			$out .= '<th>' . __( 'State', 'whippet' ) . '</th>';
			$out .= '<th>' /*. __( 'Conditions', 'whippet' )*/ . '</th>';
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
					$href_name = ( 'js-jquery' == $unique ? 'js-jquery-core' : $unique );
					$deps[] = '<a href="#' . $href_name . '">' . $dep_val . '</a>';
				}

				$depend_on = array();
				foreach ( $this->dependency_collection as $asset ) {
					if ( in_array( $handle, $asset['deps'] ) && $asset['state'] && ! empty( $asset['filename'] ) ) {
						$unique = $asset['file_extension'] . '-' . $asset['name'];

						// jQuery is not visible to connect with formal owenr of jQuery handle.
						$href_name = ( 'js-jquery' == $unique ? 'js-jquery-core' : $unique );
						$depend_on[ $unique ] = '<a href="#' . $href_name . '">' . $asset['name'] . '</a>';
					}
				}

				$id = '[' . $type_name . '][' . $handle . ']';

				// Disable everywhere.
				$is_checked_ever = isset( $this->whippet_data['disabled'][ $type_name ][ $handle ]['everywhere'] );
				$is_checked_here = isset( $this->whippet_data['disabled'][ $type_name ][ $handle ]['here'] );

				// Exceptions: Current URL.
				$is_checked = (isset( $this->whippet_data['enabled'][ $type_name ][ $handle ]['here'] ) ? 'checked="checked"' : '');
				$options_enable = '<label><input type="checkbox" name="enabled' . $id . '[here]" ' . $is_checked . '>' . __( 'Current URL', 'whippet' ) . '</label>';

				// Exceptions: Content types.
				foreach ( $this->all_content_types as $content_type_code => $content_type ) {
					$id_type = 'enabled' . $id . '[' . $content_type_code . ']';
					$is_checked = ( isset( $this->whippet_data['enabled'][ $type_name ][ $handle ][ $content_type_code ] ) ? 'checked="checked"' : '' );
					$options_enable .= '<label><input type="checkbox" name="' . $id_type . '" ' . $is_checked . '>' . $content_type . '</label>';
				}

				$option_everywhere = '<select>';
					$option_everywhere .= '<option value="e">' . __( 'Enable', 'gonales' ) . '</option>';
					$option_everywhere .= '<option value="d" ' . ( ( $is_checked_ever || $is_checked_here ) ? 'selected' : '' ) . '>' . __( 'Disable', 'whippet' ) . '</option>';
				$option_everywhere .= '</select>';

				$out .= '<tr>';
					$out .= '<td><div class="state-' . (int) $row['state'] . '">' . ( true == $row['state'] ? 'YES' : 'NO' ) . '</div></td>';
					$out .= '<td class="overflow"><a class="g-link" name="' . $type_name . '-' . $handle . '" href="' . $row['url_full'] . '" target="_blank">' . ($row['is_external'] ? $row['url_full'] : ($row['file_base'] . '.<b>' . $row['file_extension'] . '</b>')) . '</a>';
					$out .= '<div class="g-info">';
						$out .= '<div><b>' . __( 'Handle', 'whippet' ) . ':</b> ' . $handle . '</div>';

				if ( ! empty( $deps ) ) {
					$out .= '<div><b>' . __( 'Require', 'whippet' ) . ':</b> ' . implode( ', ', $deps ) . '</div>';
				}

				if ( ! empty( $depend_on ) ) {
					$out .= '<div><b>' . __( 'Depend on', 'whippet' ) . ':</b> ' . implode( ', ', array_values( $depend_on ) ) . '</div>';
				}

					$out .= '</div>';
					$out .= '</td>';

					$out .= '<td>' . (empty( $row['size'] ) ? '?' : $row['size']) . ' KB</td>';

					$out .= '<td class="option-everwhere">' . $option_everywhere . '</td>';
					$out .= '<td class="options"><div class="g-cond ' . ( ( ! $is_checked_ever && ! $is_checked_here ) ? 'g-disabled' : '' ) . '"><b>' . __( 'Where', 'whippet' ) . ':</b><br>';
					$out .= '<label><input name="' . 'disabled' . $id . '[type]' . '" type="radio" value="here" ' . ( $is_checked_here ? 'checked' : '' ) . '>' . __( 'Current URL', 'whippet' ) . '</label>';
					$out .= '<label><input name="' . 'disabled' . $id . '[type]' . '" type="radio" value="everywhere" ' . ( $is_checked_ever ? 'checked' : '' ) . '>' . __( 'Everywhere', 'whippet' ) . '</label><br></div>';

					$out .= '<div class="g-excp ' . ( ! $is_checked_ever ? 'g-disabled' : '' ) . '">';
						$out .= '<b>' . __( 'Exceptions', 'whippet' ) . ':</b><br>' . $options_enable;
					$out .= '</div></td>';
				$out .= '</tr>';

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

		return $out;
	}
}

/**
 * Verify that everything's fine with instance.
 *
 * @return mixed
 */
function check_whippet() {
	if ( ! is_wp_error( $response ) ) {
		return json_decode( $response['body'] );
	}
}

new Whippet;
