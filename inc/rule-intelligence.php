<?php
/**
 * Privacy-preserving rule intelligence.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RuleIntelligence {
	public static function boot() {
		add_action( 'admin_init', array( __CLASS__, 'save' ) );
		add_action( 'whippet_telemetry_send', array( __CLASS__, 'send_queue' ) );
		if ( get_option( 'whippet_telemetry_opt_in', false ) && ! wp_next_scheduled( 'whippet_telemetry_send' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'whippet_telemetry_send' );
		}
	}

	public static function seeds() {
		return apply_filters(
			'whippet_rule_intelligence_seeds',
			array(
				'contact-form-7'    => array( 'contact-form-7', 'google-recaptcha' ),
				'woocommerce'      => array( 'wc-cart-fragments', 'woocommerce' ),
				'elementor'        => array( 'elementor-frontend' ),
				'wordpress-embed'  => array( 'wp-embed' ),
			)
		);
	}

	public static function boost( $handle, $plugin_slugs ) {
		$score = 0;
		foreach ( self::seeds() as $plugin => $handles ) {
			if ( in_array( $plugin, (array) $plugin_slugs, true ) && in_array( $handle, $handles, true ) ) {
				$score += 10;
			}
		}
		return min( 20, $score );
	}

	public static function queue( $plugin_slugs, $disabled_handles ) {
		if ( ! get_option( 'whippet_telemetry_opt_in', false ) ) {
			return;
		}
		$normalise = static function ( $values ) {
			$values = array_filter( array_map( 'sanitize_key', (array) $values ) );
			sort( $values );
			return array_values( array_unique( $values ) );
		};
		$queue   = (array) get_option( 'whippet_telemetry_queue', array() );
		$queue[] = array(
			'plugins_hash' => hash( 'sha256', implode( '|', $normalise( $plugin_slugs ) ) ),
			'handles'      => $normalise( $disabled_handles ),
			'time'         => gmdate( 'Y-m-d' ),
		);
		update_option( 'whippet_telemetry_queue', array_slice( $queue, -50 ), false );
	}

	public static function send_queue() {
		if ( ! get_option( 'whippet_telemetry_opt_in', false ) ) {
			return;
		}
		$queue = (array) get_option( 'whippet_telemetry_queue', array() );
		$url   = (string) apply_filters( 'whippet_telemetry_api_url', '' );
		if ( ! $queue || ! $url ) {
			return;
		}
		$response = wp_remote_post( esc_url_raw( $url ), array( 'timeout' => 10, 'sslverify' => Helper::ssl_verify(), 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( array( 'patterns' => $queue ) ) ) );
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 300 ) {
			delete_option( 'whippet_telemetry_queue' );
		}
	}

	public static function save() {
		if ( empty( $_POST['whippet_telemetry_save'] ) || ! Helper::user_can_manage() ) {
			return;
		}
		check_admin_referer( 'whippet_telemetry_save', 'whippet_telemetry_nonce' );
		$enabled = ! empty( $_POST['whippet_telemetry_opt_in'] );
		update_option( 'whippet_telemetry_opt_in', $enabled, false );
		if ( ! $enabled ) {
			delete_option( 'whippet_telemetry_queue' );
			wp_clear_scheduled_hook( 'whippet_telemetry_send' );
		}
	}

	public static function render_ui() {
		?>
		<div class="wa-card">
			<div class="wa-card-section-title"><?php esc_html_e( 'Rule intelligence', 'whippet' ); ?></div>
			<p><?php esc_html_e( 'Optional telemetry sends only a one-way hash of active plugin slugs, disabled asset handles and a date. It never sends page URLs, content, user details, IP addresses or cookies.', 'whippet' ); ?></p>
			<form method="post"><?php wp_nonce_field( 'whippet_telemetry_save', 'whippet_telemetry_nonce' ); ?>
				<label><input type="checkbox" name="whippet_telemetry_opt_in" value="1" <?php checked( get_option( 'whippet_telemetry_opt_in', false ) ); ?>> <?php esc_html_e( 'Share anonymised rule patterns', 'whippet' ); ?></label>
				<p><button class="button button-primary" name="whippet_telemetry_save" value="1"><?php esc_html_e( 'Save privacy setting', 'whippet' ); ?></button></p>
			</form>
		</div>
		<?php
	}
}
