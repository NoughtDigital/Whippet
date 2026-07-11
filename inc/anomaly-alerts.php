<?php
/**
 * Operational anomaly alerts.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AnomalyAlerts {
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'schedule' ) );
		add_action( 'whippet_anomaly_check', array( __CLASS__, 'check' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_action( 'admin_init', array( __CLASS__, 'save' ) );
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( 'whippet_anomaly_check' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'whippet_anomaly_check' );
		}
	}

	public static function check() {
		$cutoff    = time() - DAY_IN_SECONDS;
		$anomalies = array();
		foreach ( (array) get_option( 'whippet_audit_log', array() ) as $entry ) {
			$action = (string) ( $entry['action'] ?? '' );
			$time   = strtotime( (string) ( $entry['time'] ?? '' ) );
			if ( $time >= $cutoff && preg_match( '/_failed$/', $action ) ) {
				$anomalies[] = $entry;
			}
		}
		update_option( 'whippet_unresolved_anomalies', array_slice( $anomalies, 0, 20 ), false );
		if ( empty( $anomalies ) ) {
			return;
		}
		$hash = md5( wp_json_encode( $anomalies ) );
		if ( $hash === get_option( 'whippet_last_alert_hash' ) ) {
			return;
		}
		update_option( 'whippet_last_alert_hash', $hash, false );
		$message = sprintf( __( 'Whippet found %d recent failed operation(s). Review the activity log.', 'whippet' ), count( $anomalies ) );
		if ( get_option( 'whippet_alerts_email', false ) ) {
			wp_mail( get_option( 'admin_email' ), __( 'Whippet performance alert', 'whippet' ), $message );
		}
		$webhook = esc_url_raw( get_option( 'whippet_alerts_slack_webhook', '' ) );
		if ( $webhook ) {
			wp_remote_post( $webhook, array( 'timeout' => 10, 'sslverify' => Helper::ssl_verify(), 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( array( 'text' => $message ) ) ) );
		}
	}

	public static function notice() {
		if ( ! Helper::is_whippet_page() || ! Helper::user_can_manage() ) {
			return;
		}
		$count = count( (array) get_option( 'whippet_unresolved_anomalies', array() ) );
		if ( $count ) {
			printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( sprintf( __( 'Whippet has %d unresolved operational alert(s).', 'whippet' ), $count ) ) );
		}
	}

	public static function save() {
		if ( empty( $_POST['whippet_alerts_save'] ) || ! Helper::user_can_manage() ) {
			return;
		}
		check_admin_referer( 'whippet_alerts_save', 'whippet_alerts_nonce' );
		update_option( 'whippet_alerts_email', ! empty( $_POST['whippet_alerts_email'] ), false );
		$url = isset( $_POST['whippet_alerts_slack_webhook'] ) ? esc_url_raw( wp_unslash( $_POST['whippet_alerts_slack_webhook'] ) ) : '';
		if ( $url ) {
			update_option( 'whippet_alerts_slack_webhook', $url, false );
		}
	}

	public static function render_settings_ui() {
		$webhook = (string) get_option( 'whippet_alerts_slack_webhook', '' );
		?>
		<div class="wa-card">
			<div class="wa-card-section-title"><?php esc_html_e( 'Anomaly alerts', 'whippet' ); ?></div>
			<form method="post"><?php wp_nonce_field( 'whippet_alerts_save', 'whippet_alerts_nonce' ); ?>
				<p><label><input type="checkbox" name="whippet_alerts_email" value="1" <?php checked( get_option( 'whippet_alerts_email', false ) ); ?>> <?php esc_html_e( 'Email the site administrator when failures are detected', 'whippet' ); ?></label></p>
				<p><label><?php esc_html_e( 'Slack webhook URL', 'whippet' ); ?><br><input type="password" name="whippet_alerts_slack_webhook" value="" placeholder="<?php echo esc_attr( Helper::mask_secret( $webhook ) ); ?>" class="regular-text" autocomplete="off"></label></p>
				<button class="button button-primary" name="whippet_alerts_save" value="1"><?php esc_html_e( 'Save alert settings', 'whippet' ); ?></button>
			</form>
		</div>
		<?php
	}
}
