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

	if ( ! isset( $_POST['whippet_action'] ) || 'export_settings' !== $_POST['whippet_action'] ) {
		return;
	}

	if ( empty( $_POST['whippet_export_nonce'] ) || ! wp_verify_nonce( $_POST['whippet_export_nonce'], 'whippet_export_nonce' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$whippet_core = get_option( 'whippet_options' );

	$whippet_analytics = array(
		'whippet_analytics_id'              => esc_attr( get_option( 'whippet_analytics_id' ) ),
		'whippet_analytics_method'          => esc_attr( get_option( 'whippet_analytics_method' ) ),
		'whippet_analytics_disable_on_login' => get_option( 'whippet_analytics_disable_on_login' ),
	);

	$whippet_fonts = array(
		'whippet_fonts_enabled'      => get_option( 'whippet_fonts_enabled', true ),
		'whippet_fonts_display_swap' => get_option( 'whippet_fonts_display_swap', false ),
	);

	$whippet_lazyload = array(
		'whippet_images_enable_lazyloading'       => get_option( 'whippet_images_enable_lazyloading' ),
		'whippet_images_lazymethod'               => get_option( 'whippet_images_lazymethod' ),
		'whippet_images_margin'                   => get_option( 'whippet_images_margin' ),
		'whippet_images_exclude_keywords'         => get_option( 'whippet_images_exclude_keywords' ),
		'whippet_images_enable_cdn'               => get_option( 'whippet_images_enable_cdn' ),
		'whippet_images_cdn_exclude_keywords'     => get_option( 'whippet_images_cdn_exclude_keywords' ),
		'whippet_images_enable_compression'       => get_option( 'whippet_images_enable_compression' ),
		'whippet_images_quality'                  => get_option( 'whippet_images_quality' ),
		'whippet_images_enable_responsive_images' => get_option( 'whippet_images_enable_responsive_images' ),
	);

	$whippet_pages = array(
		'whippet_pages_config_ignore_keywords' => get_option( 'whippet_pages_config_ignore_keywords' ),
		'whippet_pages_config_delay'           => get_option( 'whippet_pages_config_delay' ),
		'whippet_pages_config_max_rps'         => get_option( 'whippet_pages_config_max_rps' ),
		'whippet_pages_config_hover_delay'     => get_option( 'whippet_pages_config_hover_delay' ),
		'whippet_pages_config_disable_on_login' => get_option( 'whippet_pages_config_disable_on_login' ),
	);

	$whippet_scripts = array(
		'whippet_scripts_timeout'        => get_option( 'whippet_scripts_timeout' ),
		'whippet_scripts_include_list'   => get_option( 'whippet_scripts_include_list' ),
		'whippet_scripts_disabled_pages' => get_option( 'whippet_scripts_disabled_pages' ),
	);

	$whippet_options = array(
		'whippet_core'      => $whippet_core,
		'whippet_analytics' => $whippet_analytics,
		'whippet_fonts'     => $whippet_fonts,
		'whippet_lazyload'  => $whippet_lazyload,
		'whippet_pages'     => $whippet_pages,
		'whippet_scripts'   => $whippet_scripts,
	);

	ignore_user_abort( true );

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=whippet-settings-export-' . gmdate( 'm-d-Y' ) . '.json' );
	header( 'Expires: 0' );

	echo wp_json_encode( $whippet_options, JSON_UNESCAPED_SLASHES );
	exit;
}
add_action( 'admin_init', 'whippet_process_settings_export' );

/**
 * Process a settings import from a json file
 */
function whippet_process_settings_import() {

	if ( ! isset( $_POST['whippet_action'] ) || 'import_settings' !== $_POST['whippet_action'] ) {
		return;
	}

	if ( empty( $_POST['whippet_import_nonce'] ) || ! wp_verify_nonce( $_POST['whippet_import_nonce'], 'whippet_import_nonce' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_FILES['import_file'] ) || ! is_uploaded_file( $_FILES['import_file']['tmp_name'] ) ) {
		wp_die( esc_html__( 'Please upload a file to import', 'whippet' ) );
	}

	$file_name  = sanitize_file_name( $_FILES['import_file']['name'] );
	$file_parts = explode( '.', $file_name );
	$extension  = end( $file_parts );

	if ( 'json' !== strtolower( $extension ) ) {
		wp_die( esc_html__( 'Please upload a valid .json file', 'whippet' ) );
	}

	$import_file = sanitize_text_field( $_FILES['import_file']['tmp_name'] );

	if ( empty( $import_file ) || ! file_exists( $import_file ) ) {
		wp_die( esc_html__( 'Please upload a file to import', 'whippet' ) );
	}

	delete_option( 'whippet_options' );

	$content = file_get_contents( $import_file );
	if ( false === $content ) {
		wp_die( esc_html__( 'Error reading import file', 'whippet' ) );
	}

	$settings = json_decode( $content, true );
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		wp_die( esc_html__( 'Invalid JSON file format', 'whippet' ) );
	}

	// Handle double-encoded JSON from legacy exports.
	if ( is_string( $settings ) ) {
		$settings = json_decode( $settings, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_die( esc_html__( 'Invalid JSON file format', 'whippet' ) );
		}
	}

	$valid_groups = array( 'whippet_analytics', 'whippet_fonts', 'whippet_lazyload', 'whippet_pages', 'whippet_scripts' );

	foreach ( $settings as $key => $value ) {
		if ( 'whippet_core' === $key ) {
			add_option( 'whippet_options', $value );
		} elseif ( in_array( $key, $valid_groups, true ) && is_array( $value ) ) {
			foreach ( $value as $setting => $setting_value ) {
				update_option( $setting, $setting_value );
			}
		}
	}

	// Migrate legacy sgal_* to analytics options if not in import.
	$analytics_id = get_option( 'whippet_analytics_id' );
	$legacy_id    = get_option( 'sgal_tracking_id' );
	if ( empty( $analytics_id ) && ! empty( $legacy_id ) ) {
		update_option( 'whippet_analytics_id', $legacy_id );
		update_option( 'whippet_analytics_method', 'minimal-analytics' );
		update_option( 'whippet_analytics_disable_on_login', get_option( 'sgal_track_admin' ) !== 'on' );
	}

	wp_safe_redirect( admin_url( 'tools.php?page=whippet' ) );
	exit;
}
add_action( 'admin_init', 'whippet_process_settings_import' );
