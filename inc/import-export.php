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

		if ( ! wp_verify_nonce( $_POST['whippet_export_nonce'], 'whippet_export_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get all Options used in core plugin.
		$whippet_core = get_option( 'whippet_options' );

		// Get all Options used in analytic.
		$whippet_analytics = array(
			'sgal_tracking_id'              => esc_attr( get_option( 'sgal_tracking_id' ) ),
			'sgal_adjusted_bounce_rate'     => esc_attr( get_option( 'sgal_adjusted_bounce_rate' ) ),
			'sgal_script_position'          => esc_attr( get_option( 'sgal_script_position' ) ),
			'sgal_enqueue_order'            => esc_attr( get_option( 'sgal_enqueue_order' ) ),
			'sgal_anonymize_ip'             => esc_attr( get_option( 'sgal_anonymize_ip' ) ),
			'sgal_track_admin'              => esc_attr( get_option( 'sgal_track_admin' ) ),
			'caos_remove_wp_cron'           => esc_attr( get_option( 'caos_remove_wp_cron' ) ),
		);

		// Make all Options a main array to encode into json.
		$whippet_options = array(
			'whippet_core'      => $whippet_core,
			'whippet_analytics' => $whippet_analytics,
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

		if ( ! wp_verify_nonce( $_POST['whippet_import_nonce'], 'whippet_import_nonce' ) ) {
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
			if ( $key == 'whippet_core' ) {
				add_option( 'whippet_options', $value );
			} elseif ( $key == 'whippet_analytics' ) {
				foreach ( $value as $setting => $setting_value ) {
					update_option( $setting, $setting_value );
				}
			}
		}

		wp_safe_redirect( admin_url( 'tools.php?page=whippet' ) );
		exit;

	}
}
add_action( 'admin_init', 'whippet_process_settings_import' );
