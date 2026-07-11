<?php
/**
 * Human-reviewed script optimisation suggestions.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ScriptAdvisor {
	public static function boot() {
		add_action( 'wp_ajax_whippet_advisor_suggest', array( __CLASS__, 'ajax_suggest' ) );
		add_action( 'wp_ajax_whippet_advisor_feedback', array( __CLASS__, 'ajax_feedback' ) );
	}

	private static function rules() {
		return array(
			'wc-cart-fragments'      => array( 'reason' => 'WooCommerce cart fragments are usually unnecessary outside shop, basket and checkout pages.', 'scope' => 'non-commerce pages', 'confidence' => 92 ),
			'woocommerce'            => array( 'reason' => 'WooCommerce front-end assets may be removable from pages without commerce content.', 'scope' => 'non-commerce pages', 'confidence' => 86 ),
			'contact-form-7'         => array( 'reason' => 'Contact Form 7 assets are normally required only on pages containing a form.', 'scope' => 'pages without forms', 'confidence' => 95 ),
			'google-recaptcha'       => array( 'reason' => 'reCAPTCHA is normally required only where a protected form is present.', 'scope' => 'pages without protected forms', 'confidence' => 90 ),
			'elementor-pro-frontend' => array( 'reason' => 'Elementor Pro assets should be reviewed on pages not built with Elementor.', 'scope' => 'non-Elementor pages', 'confidence' => 72 ),
			'wp-embed'               => array( 'reason' => 'WordPress embed support can be removed when the page contains no embedded WordPress content.', 'scope' => 'pages without embeds', 'confidence' => 88 ),
			'comment-reply'          => array( 'reason' => 'Comment reply JavaScript is unnecessary where threaded comments are unavailable.', 'scope' => 'pages without comments', 'confidence' => 96 ),
		);
	}

	private static function observed_handles() {
		global $wpdb, $wp_scripts, $wp_styles;
		$handles = array();
		foreach ( array( $wp_scripts, $wp_styles ) as $registry ) {
			if ( is_object( $registry ) && ! empty( $registry->queue ) ) {
				$handles = array_merge( $handles, (array) $registry->queue );
			}
		}
		foreach ( array( 'whippet_disabled', 'whippet_enabled' ) as $table ) {
			$name = $wpdb->prefix . $table;
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $name ) ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$handles = array_merge( $handles, (array) $wpdb->get_col( "SELECT DISTINCT handler_name FROM `{$name}`" ) );
			}
		}
		return array_values( array_unique( array_filter( array_map( 'sanitize_key', $handles ) ) ) );
	}

	public static function suggest( $handles ) {
		$handles     = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $handles ) ) ) );
		$suggestions = array();
		foreach ( self::rules() as $handle => $rule ) {
			foreach ( $handles as $observed ) {
				if ( $observed === $handle || 0 === strpos( $observed, $handle . '-' ) ) {
					$suggestions[] = array_merge( array( 'handle' => $observed, 'source' => 'deterministic' ), $rule );
				}
			}
		}

		$api_key = (string) get_option( 'whippet_ai_api_key', '' );
		$api_url = (string) apply_filters( 'whippet_advisor_ai_api_url', '' );
		if ( $api_key && $api_url && $handles ) {
			$response = wp_remote_post(
				esc_url_raw( $api_url ),
				array(
					'timeout'   => 20,
					'sslverify' => Helper::ssl_verify(),
					'headers'   => array( 'Authorization' => 'Bearer ' . $api_key, 'Content-Type' => 'application/json' ),
					'body'      => wp_json_encode( array( 'handles' => $handles ) ),
				)
			);
			$data = is_wp_error( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );
			foreach ( is_array( $data['suggestions'] ?? null ) ? $data['suggestions'] : array() as $item ) {
				$handle = sanitize_key( $item['handle'] ?? '' );
				if ( ! in_array( $handle, $handles, true ) ) {
					continue;
				}
				$suggestions[] = array(
					'handle'     => $handle,
					'reason'     => sanitize_text_field( $item['reason'] ?? '' ),
					'scope'      => sanitize_text_field( $item['scope'] ?? 'review manually' ),
					'confidence' => min( 100, absint( $item['confidence'] ?? 50 ) ),
					'source'     => 'ai',
				);
			}
		}
		return $suggestions;
	}

	private static function authorise() {
		check_ajax_referer( 'whippet_script_advisor', 'nonce' );
		if ( ! Capabilities::user_can( 'whippet_scripts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'whippet' ) ), 403 );
		}
	}

	public static function ajax_suggest() {
		self::authorise();
		$handles = isset( $_POST['handles'] ) ? (array) wp_unslash( $_POST['handles'] ) : array();
		$handles = array_merge( $handles, self::observed_handles() );
		wp_send_json_success( array( 'suggestions' => self::suggest( $handles ), 'requires_approval' => true ) );
	}

	public static function ajax_feedback() {
		self::authorise();
		$decision = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';
		if ( ! in_array( $decision, array( 'accepted', 'rejected' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid decision.', 'whippet' ) ) );
		}
		Helper::audit_log( 'advisor_' . $decision, array( 'handle' => sanitize_key( wp_unslash( $_POST['handle'] ?? '' ) ) ) );
		wp_send_json_success();
	}

	public static function render_ui() {
		?>
		<div class="wa-card">
			<div class="wa-card-section-title"><?php esc_html_e( 'Script adviser', 'whippet' ); ?></div>
			<p><?php esc_html_e( 'Paste registered script or style handles to receive review suggestions. Whippet never applies these suggestions automatically.', 'whippet' ); ?></p>
			<textarea id="whippet-advisor-handles" class="large-text code" rows="4" placeholder="wc-cart-fragments&#10;contact-form-7"></textarea>
			<p><button type="button" class="button" onclick="var h=document.getElementById('whippet-advisor-handles').value.split(/\s+/);fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'whippet_advisor_suggest',nonce:'<?php echo esc_js( wp_create_nonce( 'whippet_script_advisor' ) ); ?>',handles:h})}).then(r=>r.json()).then(r=>document.getElementById('whippet-advisor-results').textContent=JSON.stringify(r.data.suggestions||[],null,2))"><?php esc_html_e( 'Review handles', 'whippet' ); ?></button></p>
			<pre id="whippet-advisor-results" style="white-space:pre-wrap"></pre>
		</div>
		<?php
	}
}
