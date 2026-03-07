<?php
/**
 * Whippet Save GA Local
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
 * Create Menu Item
 *
 * @var [type]
 */
add_action( 'admin_init', 'register_save_ga_locally_settings' );

/**
 * Register Settings
 */
function register_save_ga_locally_settings() {
	register_setting( 'save-ga-locally-basic-settings', 'sgal_tracking_id' );
	register_setting( 'save-ga-locally-basic-settings', 'sgal_adjusted_bounce_rate' );
	register_setting( 'save-ga-locally-basic-settings', 'sgal_script_position' );
	register_setting( 'save-ga-locally-basic-settings', 'sgal_enqueue_order' );
	register_setting( 'save-ga-locally-basic-settings', 'sgal_anonymize_ip' );
	register_setting( 'save-ga-locally-basic-settings', 'sgal_track_admin' );
	register_setting( 'save-ga-locally-basic-settings', 'caos_remove_wp_cron' );
}

/**
 * Create Settings Page
 */
function save_ga_locally_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html( "You're not cool enough to access this page." ) );
	}
	?>

	<div class="wrap">
		<h2>Host Analytics Locally</h2>

		<form method="post" action="options.php">
			<?php
			settings_fields( 'save-ga-locally-basic-settings' );
			do_settings_sections( 'save-ga-locally-basic-settings' );

			$sgal_tracking_id              = esc_attr( get_option( 'sgal_tracking_id' ) );
			$sgal_adjusted_bounce_rate     = esc_attr( get_option( 'sgal_adjusted_bounce_rate' ) );
			$sgal_script_position          = esc_attr( get_option( 'sgal_script_position' ) );
			$sgal_enqueue_order            = esc_attr( get_option( 'sgal_enqueue_order' ) );
			$sgal_anonymize_ip             = esc_attr( get_option( 'sgal_anonymize_ip' ) );
			$sgal_track_admin              = esc_attr( get_option( 'sgal_track_admin' ) );
			$caos_remove_wp_cron           = esc_attr( get_option( 'caos_remove_wp_cron' ) );
			?>

			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Google Analytics Tracking ID', 'whippet' ); ?></th>
					<td><input type="text" name="sgal_tracking_id" value="<?php echo esc_attr( $sgal_tracking_id ); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Position of tracking code', 'whippet' ); ?></th>
					<td>
						<?php
						$sgal_script_position = array( 'header', 'footer' );
						$current_position = get_option( 'sgal_script_position' );

						foreach ( $sgal_script_position as $option ) {
							$checked = ( $option === $current_position ) ? ' checked="checked"' : '';
							echo "<input type='radio' name='sgal_script_position' value='" . esc_attr( $option ) . "'" . $checked . " />";
							echo esc_html( ucfirst( $option ) );
							if ( 'header' === $option ) {
								echo ' ' . esc_html__( ' (default)', 'whippet' );
							}
							echo '<br>';
						}
						?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Use adjusted bounce rate?', 'whippet' ); ?></th>
					<td><input type="number" name="sgal_adjusted_bounce_rate" min="0" max="60" value="<?php echo esc_attr( $sgal_adjusted_bounce_rate ); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Change enqueue order? (Default = 0)', 'whippet' ); ?></th>
					<td><input type="number" name="sgal_enqueue_order" min="0" value="<?php echo esc_attr( $sgal_enqueue_order ); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php echo wp_kses( __( 'Use <a href="https://support.google.com/analytics/answer/2763052?hl=en" target="_blank">Anonymize IP</a>? (Required by law for some countries)', 'whippet' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) ); ?></th>
					<td><input type="checkbox" name="sgal_anonymize_ip" value="on"<?php checked( $sgal_anonymize_ip, 'on' ); ?> /></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Track logged in Administrators?', 'whippet' ); ?></th>
					<td><input type="checkbox" name="sgal_track_admin" value="on"<?php checked( $sgal_track_admin, 'on' ); ?> /></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Remove script from wp-cron?', 'whippet' ); ?></th>
					<td><input type="checkbox" name="caos_remove_wp_cron" value="on"<?php checked( $caos_remove_wp_cron, 'on' ); ?> /></td>
				</tr>
			</table>

			<?php do_action( 'caos_after_form_settings' ); ?>

			<?php submit_button(); ?>

		</form>
	</div>
	<?php
} // End: save_ga_locally_settings_page()

/**
 * Register hook to schedule script in wp_cron()
 * register_activation_hook(__FILE__, 'activate_update_local_ga');
 *
 * @return [type] [description]
 */
function activate_update_local_ga() {
	if ( ! wp_next_scheduled( 'update_local_ga' ) ) {
		wp_schedule_event( time(), 'daily', 'update_local_ga' );
	}
}

/**
 * Load update script to schedule in wp_cron()
 *
 * @var [type]
 */
add_action( 'update_local_ga', 'update_local_ga_script' );

function update_local_ga_script() {
	include 'includes/update_local_ga.php';
}

/**
 * Remove script from wp_cron upon plugin deactivation
 * Note: Deactivation hook is registered in main plugin file
 */

function deactivate_update_local_ga() {
	if ( wp_next_scheduled( 'update_local_ga' ) ) {
		wp_clear_scheduled_hook( 'update_local_ga' );
	}
}

/**
 * Remove script from wp_cron if option is selected
 *
 * @var [type]
 */
$caos_remove_wp_cron = esc_attr( get_option( 'caos_remove_wp_cron' ) );

switch ( $caos_remove_wp_cron ) {
	case 'on':
		if ( wp_next_scheduled( 'update_local_ga' ) ) {
			wp_clear_scheduled_hook( 'update_local_ga' );
		}
		break;
	default:
		if ( ! wp_next_scheduled( 'update_local_ga' ) ) {
			wp_schedule_event( time(), 'daily', 'update_local_ga' );
		}
		break;
}

/**
 * Generate tracking code and add to header/footer (default is header)
 */
function add_ga_header_script() {
	$sgal_track_admin = get_option( 'sgal_track_admin' );
	if ( current_user_can( 'manage_options' ) && ! $sgal_track_admin ) {
		return;
	}

	$sgal_tracking_id          = esc_js( sanitize_text_field( get_option( 'sgal_tracking_id' ) ) );
	$sgal_adjusted_bounce_rate = absint( get_option( 'sgal_adjusted_bounce_rate', 0 ) );
	$sgal_anonymize_ip         = get_option( 'sgal_anonymize_ip' );
	$local_ga_url              = esc_url( plugin_dir_url( __FILE__ ) . 'cache/local-ga.js' );

	echo '<!-- This site is running Whippet: Whippet for WordPress -->';

	echo "<script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','" . $local_ga_url . "','ga');";

	echo "ga('create', '" . $sgal_tracking_id . "', 'auto');";

	if ( 'on' === $sgal_anonymize_ip ) {
		echo "ga('set', 'anonymizeIp', true);";
	}

	echo "ga('send', 'pageview');";

	if ( $sgal_adjusted_bounce_rate > 0 ) {
		$delay_ms = $sgal_adjusted_bounce_rate * 1000;
		echo "setTimeout(function(){ga('send','event','adjusted bounce rate','" . $sgal_adjusted_bounce_rate . " seconds')}," . $delay_ms . ");";
	}

	echo '</script>';
}

$sgal_script_position = esc_attr( get_option( 'sgal_script_position' ) );
$sgal_enqueue_order   = ( esc_attr( get_option( 'sgal_enqueue_order' ) ) ) ? esc_attr( get_option( 'sgal_enqueue_order' ) ) : 0;

switch ( $sgal_script_position ) {
	case 'footer':
		add_action( 'wp_footer', 'add_ga_header_script', $sgal_enqueue_order );
		break;
	default:
		add_action( 'wp_head', 'add_ga_header_script', $sgal_enqueue_order );
		break;
}
