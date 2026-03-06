<?php
/**
 * Whippet Import & Export
 *
 * @category Whippet
 * @package  Whippet
 * @author   Jake Henshall
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.hashbangcode.com/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the settings page
 */
function whippet_settings_page() {

	?>
	<div class="wrap">

		<div class="metabox-holder">
			<div class="postbox">
				<h3><span><?php _e( 'Export Settings', 'whippet' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Export the plugin settings for this site as a .json file. This allows you to easily import the configuration into another site.', 'whippet' ); ?></p>
					<form method="post">
						<p><input type="hidden" name="whippet_action" value="export_settings" /></p>
						<p>
							<?php wp_nonce_field( 'whippet_export_nonce', 'whippet_export_nonce' ); ?>
							<?php submit_button( __( 'Export', 'whippet' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->

			<div class="postbox">
				<h3><span><?php _e( 'Import Settings', 'whippet' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Import the plugin settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.', 'whippet' ); ?></p>
					<form method="post" enctype="multipart/form-data">
						<p>
							<input type="file" name="import_file"/>
						</p>
						<p>
							<input type="hidden" name="whippet_action" value="import_settings" />
							<?php wp_nonce_field( 'whippet_import_nonce', 'whippet_import_nonce' ); ?>
							<?php submit_button( __( 'Import', 'whippet' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->
		</div><!-- .metabox-holder -->

	</div><!--end .wrap-->
	<?php
}

/**
 * Process a settings export that generates a .json file of the shop settings
 */
function whippet_process_settings_export() {

	if ( isset( $_POST['submit'] ) ) {

		if ( empty( $_POST['whippet_export_nonce'] ) || ! wp_verify_nonce( $_POST['whippet_export_nonce'], 'whippet_export_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get all Options used in core plugin.
		$whippet_core = get_option( 'whippet_options' );

		// Get all Options used in analytics.
		$whippet_analytics = array(
			'flying_analytics_id'               => esc_attr( get_option( 'flying_analytics_id' ) ),
			'flying_analytics_method'           => esc_attr( get_option( 'flying_analytics_method' ) ),
			'flying_analytics_disable_on_login'  => get_option( 'flying_analytics_disable_on_login' ),
		);

		$whippet_fonts = array(
			'whippet_fonts_enabled'      => get_option( 'whippet_fonts_enabled', true ),
			'whippet_fonts_display_swap' => get_option( 'whippet_fonts_display_swap', false ),
		);

		$whippet_lazyload = array(
			'flying_images_enable_lazyloading'     => get_option( 'flying_images_enable_lazyloading' ),
			'flying_images_lazymethod'             => get_option( 'flying_images_lazymethod' ),
			'flying_images_margin'                 => get_option( 'flying_images_margin' ),
			'flying_images_exclude_keywords'       => get_option( 'flying_images_exclude_keywords' ),
			'flying_images_enable_cdn'             => get_option( 'flying_images_enable_cdn' ),
			'flying_images_cdn_exclude_keywords'   => get_option( 'flying_images_cdn_exclude_keywords' ),
			'flying_images_enable_compression'     => get_option( 'flying_images_enable_compression' ),
			'flying_images_quality'                 => get_option( 'flying_images_quality' ),
			'flying_images_enable_responsive_images' => get_option( 'flying_images_enable_responsive_images' ),
		);

		$whippet_pages = array(
			'flying_pages_config_ignore_keywords'  => get_option( 'flying_pages_config_ignore_keywords' ),
			'flying_pages_config_delay'             => get_option( 'flying_pages_config_delay' ),
			'flying_pages_config_max_rps'           => get_option( 'flying_pages_config_max_rps' ),
			'flying_pages_config_hover_delay'       => get_option( 'flying_pages_config_hover_delay' ),
			'flying_pages_config_disable_on_login'  => get_option( 'flying_pages_config_disable_on_login' ),
		);

		$whippet_scripts = array(
			'flying_scripts_timeout'        => get_option( 'flying_scripts_timeout' ),
			'flying_scripts_include_list'   => get_option( 'flying_scripts_include_list' ),
			'flying_scripts_disabled_pages' => get_option( 'flying_scripts_disabled_pages' ),
		);

		// Make all Options a main array to encode into json.
		$whippet_options = array(
			'whippet_core'      => $whippet_core,
			'whippet_analytics' => $whippet_analytics,
			'whippet_fonts'     => $whippet_fonts,
			'whippet_lazyload'  => $whippet_lazyload,
			'whippet_pages'     => $whippet_pages,
			'whippet_scripts'  => $whippet_scripts,
		);
		$data            = json_encode( $whippet_options );

		ignore_user_abort( true );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=whippet-settings-export-' . date( 'm-d-Y' ) . '.json' );
		header( 'Expires: 0' );

		echo json_encode( $data, JSON_UNESCAPED_SLASHES );
		exit;
	}
}
add_action( 'admin_init', 'whippet_process_settings_export' );

/**
 * Process a settings import from a json file
 */
function whippet_process_settings_import() {

	if ( isset( $_POST['submit'] ) ) {

		if ( empty( $_POST['whippet_import_nonce'] ) || ! wp_verify_nonce( $_POST['whippet_import_nonce'], 'whippet_import_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_FILES['import_file'] ) || ! is_uploaded_file( $_FILES['import_file']['tmp_name'] ) ) {
			wp_die( esc_html__( 'Please upload a file to import', 'whippet' ) );
		}

		$file_name = sanitize_file_name( $_FILES['import_file']['name'] );
		$file_parts = explode( '.', $file_name );
		$extension = end( $file_parts );

		if ( 'json' !== strtolower( $extension ) ) {
			wp_die( esc_html__( 'Please upload a valid .json file', 'whippet' ) );
		}

		$import_file = sanitize_text_field( $_FILES['import_file']['tmp_name'] );

		if ( empty( $import_file ) || ! file_exists( $import_file ) ) {
			wp_die( esc_html__( 'Please upload a file to import', 'whippet' ) );
		}
		$whippet_options = get_option( 'whippet_options' );

		delete_option( 'whippet_options' );

		// Retrieve the settings from the file and convert the json object to an array.
		$content = file_get_contents( $import_file );
		if ( false === $content ) {
			wp_die( esc_html__( 'Error reading import file', 'whippet' ) );
		}
		
		$obj = json_decode( $content, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_die( esc_html__( 'Invalid JSON file format', 'whippet' ) );
		}
		
		// Handle double-encoded JSON from export
		if ( is_string( $obj ) ) {
			$settings = json_decode( $obj, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				wp_die( esc_html__( 'Invalid JSON file format', 'whippet' ) );
			}
		} else {
			$settings = $obj;
		}

		foreach ( $settings as $key => $value ) {
			if ( $key === 'whippet_core' ) {
				add_option( 'whippet_options', $value );
			} elseif ( in_array( $key, array( 'whippet_analytics', 'whippet_fonts', 'whippet_lazyload', 'whippet_pages', 'whippet_scripts' ), true ) && is_array( $value ) ) {
				foreach ( $value as $setting => $setting_value ) {
					update_option( $setting, $setting_value );
				}
			}
		}

		// Migrate legacy sgal_* to analytics options if not in import
		if ( empty( get_option( 'flying_analytics_id' ) ) && ! empty( get_option( 'sgal_tracking_id' ) ) ) {
			update_option( 'flying_analytics_id', get_option( 'sgal_tracking_id' ) );
			update_option( 'flying_analytics_method', 'minimal-analytics' );
			update_option( 'flying_analytics_disable_on_login', get_option( 'sgal_track_admin' ) === 'on' ? false : true );
		}

		wp_safe_redirect( admin_url( 'tools.php?page=whippet' ) );
		exit;

	}
}
add_action( 'admin_init', 'whippet_process_settings_import' );
