<?php
/**
 * Emergency recovery mode.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RecoveryMode {
	const COOKIE = 'whippet_recovery';

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'maybe_activate' ), -100 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 5 );
		add_filter( 'whippet_cache_enabled_for_request', array( __CLASS__, 'disable_when_active' ) );
		add_filter( 'whippet_scripts_output_buffer', array( __CLASS__, 'disable_when_active' ) );
		add_filter( 'whippet_images_output_buffer', array( __CLASS__, 'disable_when_active' ) );
		add_filter( 'whippet_cce_inject_enabled', array( __CLASS__, 'disable_when_active' ) );
		add_filter( 'whippet_ie_rewrite_enabled', array( __CLASS__, 'disable_when_active' ) );
	}

	public static function maybe_activate() {
		if ( empty( $_GET['whippet_recovery'] ) || '1' !== sanitize_text_field( wp_unslash( $_GET['whippet_recovery'] ) ) ) {
			return;
		}
		$token      = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
		$saved      = (string) get_option( 'whippet_recovery_token', '' );
		$nonce      = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		$authorised = ( $saved && $token && hash_equals( $saved, $token ) ) || ( Helper::user_can_manage() && wp_verify_nonce( $nonce, 'whippet_recovery' ) );
		if ( ! $authorised ) {
			return;
		}
		setcookie( self::COOKIE, '1', array( 'expires' => time() + HOUR_IN_SECONDS, 'path' => COOKIEPATH ?: '/', 'domain' => COOKIE_DOMAIN, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
		$_COOKIE[ self::COOKIE ] = '1';
		Helper::audit_log( 'recovery_mode_activated' );
		wp_safe_redirect( remove_query_arg( array( 'whippet_recovery', 'token', '_wpnonce' ) ) );
		exit;
	}

	public static function is_active() {
		return isset( $_COOKIE[ self::COOKIE ] ) && '1' === sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) );
	}

	public static function disable_when_active( $enabled ) {
		return self::is_active() ? false : $enabled;
	}

	public static function generate_token() {
		$token = wp_generate_password( 32, false, false );
		update_option( 'whippet_recovery_token', $token, false );
		return $token;
	}

	public static function recovery_url() {
		$token = (string) get_option( 'whippet_recovery_token', '' );
		if ( ! $token ) {
			$token = self::generate_token();
		}
		return add_query_arg( array( 'whippet_recovery' => '1', 'token' => $token ), home_url( '/' ) );
	}

	public static function admin_bar( $bar ) {
		if ( ! self::is_active() || ! is_admin_bar_showing() ) {
			return;
		}
		$bar->add_node( array( 'id' => 'whippet-recovery', 'title' => __( 'Whippet recovery mode active', 'whippet' ), 'href' => admin_url( 'tools.php?page=whippet#tools' ) ) );
	}

	public static function render_ui() {
		$url = self::recovery_url();
		?>
		<div class="wa-card">
			<div class="wa-card-section-title"><?php esc_html_e( 'Recovery mode', 'whippet' ); ?></div>
			<div class="wa-card-body">
				<p><?php esc_html_e( 'This private URL temporarily bypasses Whippet page caching and front-end rewrites for one hour.', 'whippet' ); ?></p>
				<input type="text" class="large-text code" readonly value="<?php echo esc_attr( $url ); ?>" onclick="this.select()">
				<button type="button" class="button" onclick="navigator.clipboard.writeText('<?php echo esc_js( $url ); ?>')"><?php esc_html_e( 'Copy recovery URL', 'whippet' ); ?></button>
			</div>
		</div>
		<?php
	}
}
