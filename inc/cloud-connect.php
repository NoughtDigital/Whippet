<?php
/**
 * Whippet Cloud connection.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudConnect {
	public static function boot() {
		add_action( 'admin_init', array( __CLASS__, 'save' ) );
		add_action( 'admin_init', array( __CLASS__, 'block_settings_saves' ), 1 );
		add_action( 'admin_init', array( __CLASS__, 'register_ajax_guards' ), 1 );
		add_action( 'wp_ajax_whippet_cloud_test', array( __CLASS__, 'ajax_test' ) );
		add_action( 'wp_ajax_whippet_premium_waitlist', array( __CLASS__, 'ajax_waitlist' ) );
	}

	private static function api_url() {
		return untrailingslashit( (string) apply_filters( 'whippet_cloud_api_url', 'https://api.whippetwp.com' ) );
	}

	/**
	 * Whether Premium admin controls may mutate settings / run Cloud actions.
	 * Locked until a Pro/Agency plan is active (Coming soon).
	 *
	 * @return bool
	 */
	public static function controls_enabled() {
		return (bool) apply_filters( 'whippet_premium_controls_enabled', self::is_pro() );
	}

	/**
	 * User-facing lock message.
	 *
	 * @return string
	 */
	public static function locked_message() {
		return __( 'Whippet Cloud Premium is coming soon. These controls are not active yet.', 'whippet' );
	}

	/**
	 * Reject Premium settings form posts while locked.
	 */
	public static function block_settings_saves() {
		if ( self::controls_enabled() || empty( $_POST['option_page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		$page = sanitize_key( wp_unslash( $_POST['option_page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! in_array( $page, array( 'image_engine', 'cce_settings' ), true ) ) {
			return;
		}
		wp_die(
			esc_html( self::locked_message() ),
			esc_html__( 'Coming soon', 'whippet' ),
			array( 'response' => 403 )
		);
	}

	/**
	 * Short-circuit Premium AJAX actions while locked.
	 */
	public static function register_ajax_guards() {
		if ( self::controls_enabled() ) {
			return;
		}
		$actions = array(
			'ie_sync_all',
			'ie_send_attachment',
			'ie_restore_attachment',
			'ie_delete_original',
			'ie_bulk_restore',
			'ie_bulk_delete_originals',
			'cce_generate',
			'cce_status',
			'cce_scan',
			'cce_cluster_status',
			'cce_purge',
			'cce_test',
			'cce_regenerate_all',
			'cce_poll_scan',
			'whippet_cloud_test',
		);
		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_' . $action, array( __CLASS__, 'reject_ajax' ), 0 );
		}
	}

	/**
	 * JSON reject for locked Premium AJAX.
	 */
	public static function reject_ajax() {
		wp_send_json_error( array( 'message' => self::locked_message() ), 403 );
	}

	public static function save() {
		if ( empty( $_POST['whippet_cloud_save'] ) || ! Helper::user_can_manage() ) {
			return;
		}
		check_admin_referer( 'whippet_cloud_save', 'whippet_cloud_nonce' );
		if ( ! self::controls_enabled() ) {
			add_settings_error(
				'whippet_cloud',
				'whippet_cloud_locked',
				self::locked_message(),
				'error'
			);
			return;
		}
		$key = isset( $_POST['whippet_cloud_api_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['whippet_cloud_api_key'] ) ) ) : '';
		if ( '' !== $key && false === strpos( $key, '*' ) ) {
			update_option( 'whippet_cloud_api_key', $key, false );
		}
	}

	public static function test_connection() {
		$key = (string) get_option( 'whippet_cloud_api_key', '' );
		if ( '' === $key ) {
			return new \WP_Error( 'missing_key', __( 'Enter an API key first.', 'whippet' ) );
		}
		$response = wp_remote_get(
			self::api_url() . '/v1/account',
			array(
				'timeout'   => 15,
				'sslverify' => Helper::ssl_verify(),
				'headers'   => array( 'Authorization' => 'Bearer ' . $key, 'Accept' => 'application/json' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			update_option( 'whippet_cloud_status', 'unreachable', false );
			return $response;
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( wp_remote_retrieve_response_code( $response ) >= 300 || ! is_array( $data ) ) {
			update_option( 'whippet_cloud_status', 'invalid', false );
			return new \WP_Error( 'cloud_rejected', __( 'Whippet Cloud rejected the connection.', 'whippet' ) );
		}
		$plan = sanitize_key( $data['plan'] ?? 'free' );
		if ( ! in_array( $plan, array( 'free', 'pro', 'agency' ), true ) ) {
			$plan = 'free';
		}
		update_option( 'whippet_cloud_plan', $plan, false );
		update_option( 'whippet_cloud_status', 'connected', false );
		update_option( 'whippet_cloud_usage', is_array( $data['usage'] ?? null ) ? $data['usage'] : array(), false );
		return array( 'plan' => $plan, 'status' => 'connected' );
	}

	public static function ajax_test() {
		check_ajax_referer( 'whippet_cloud_test', 'nonce' );
		if ( ! Helper::user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'whippet' ) ), 403 );
		}
		if ( ! self::controls_enabled() ) {
			wp_send_json_error( array( 'message' => self::locked_message() ), 403 );
		}
		$result = self::test_connection();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( $result );
	}

	/**
	 * Store Premium launch interest email for the current admin.
	 */
	public static function ajax_waitlist() {
		check_ajax_referer( 'whippet_premium_waitlist', 'nonce' );
		if ( ! Helper::user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'whippet' ) ), 403 );
		}
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Enter a valid email address.', 'whippet' ) ) );
		}
		update_option( 'whippet_premium_waitlist_email', $email, false );
		Helper::audit_log(
			'premium_waitlist',
			array(
				'email' => $email,
			)
		);
		wp_send_json_success(
			array(
				'message' => __( 'You are on the list. We will be in touch.', 'whippet' ),
			)
		);
	}

	public static function is_pro() {
		return in_array( get_option( 'whippet_cloud_plan', 'free' ), array( 'pro', 'agency' ), true );
	}

	public static function is_agency() {
		return 'agency' === get_option( 'whippet_cloud_plan', 'free' );
	}

	public static function render_connect_ui() {
		$key = (string) get_option( 'whippet_cloud_api_key', '' );
		?>
		<div class="wa-card">
			<div class="wa-card-section-title"><?php esc_html_e( 'Whippet Cloud', 'whippet' ); ?></div>
			<div class="wa-card-body">
				<form method="post" class="wa-card-form">
					<?php wp_nonce_field( 'whippet_cloud_save', 'whippet_cloud_nonce' ); ?>
					<div class="wa-card-row">
						<input type="password" name="whippet_cloud_api_key" value="" placeholder="<?php echo esc_attr( Helper::mask_secret( $key ) ); ?>" autocomplete="off">
						<button class="button button-primary" type="submit" name="whippet_cloud_save" value="1"><?php esc_html_e( 'Save connection', 'whippet' ); ?></button>
					</div>
				</form>
				<p><?php echo esc_html( sprintf( __( 'Status: %1$s. Plan: %2$s.', 'whippet' ), get_option( 'whippet_cloud_status', 'not connected' ), get_option( 'whippet_cloud_plan', 'free' ) ) ); ?></p>
				<button type="button" class="button" onclick="fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'whippet_cloud_test',nonce:'<?php echo esc_js( wp_create_nonce( 'whippet_cloud_test' ) ); ?>'})}).then(r=>r.json()).then(r=>alert(r.success?'<?php echo esc_js( __( 'Connection successful.', 'whippet' ) ); ?>':r.data.message))"><?php esc_html_e( 'Test connection', 'whippet' ); ?></button>
			</div>
		</div>
		<?php
	}
}
