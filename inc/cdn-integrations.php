<?php
/**
 * CDN cache integrations.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CdnIntegrations {
	public static function boot() {
		add_action( 'admin_init', array( __CLASS__, 'save' ) );
		add_action( 'whippet_cache_cleared', array( __CLASS__, 'purge' ), 10, 1 );
	}

	public static function save() {
		if ( empty( $_POST['whippet_cdn_save'] ) || ! Helper::user_can_manage() ) {
			return;
		}
		check_admin_referer( 'whippet_cdn_save', 'whippet_cdn_nonce' );
		foreach ( array( 'cloudflare_zone_id', 'cloudflare_api_token', 'bunny_api_key', 'bunny_pull_zone_id' ) as $key ) {
			$value = isset( $_POST[ $key ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) : '';
			if ( '' !== $value ) {
				update_option( 'whippet_' . $key, $value, false );
			}
		}
	}

	public static function purge( $url = '' ) {
		$zone  = (string) get_option( 'whippet_cloudflare_zone_id', '' );
		$token = (string) get_option( 'whippet_cloudflare_api_token', '' );
		if ( $zone && $token ) {
			$payload = $url ? array( 'files' => array( esc_url_raw( $url ) ) ) : array( 'purge_everything' => true );
			$response = wp_remote_post(
				'https://api.cloudflare.com/client/v4/zones/' . rawurlencode( $zone ) . '/purge_cache',
				array( 'timeout' => 15, 'sslverify' => Helper::ssl_verify(), 'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( $payload ) )
			);
			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 300 ) {
				Helper::audit_log( 'cloudflare_purge_failed', array( 'code' => is_wp_error( $response ) ? $response->get_error_code() : wp_remote_retrieve_response_code( $response ) ) );
			}
		}
		$bunny_key  = (string) get_option( 'whippet_bunny_api_key', '' );
		$bunny_zone = (string) get_option( 'whippet_bunny_pull_zone_id', '' );
		if ( $bunny_key && $bunny_zone ) {
			$endpoint = 'https://api.bunny.net/pullzone/' . rawurlencode( $bunny_zone ) . '/purgeCache';
			if ( $url ) {
				$endpoint = add_query_arg( 'url', esc_url_raw( $url ), 'https://api.bunny.net/purge' );
			}
			$response = wp_remote_post( $endpoint, array( 'timeout' => 15, 'sslverify' => Helper::ssl_verify(), 'headers' => array( 'AccessKey' => $bunny_key ) ) );
			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 300 ) {
				Helper::audit_log( 'bunny_purge_failed', array( 'code' => is_wp_error( $response ) ? $response->get_error_code() : wp_remote_retrieve_response_code( $response ) ) );
			}
		}
	}

	public static function render_settings_ui() {
		?>
		<div class="wa-card">
			<div class="wa-card-section-title"><?php esc_html_e( 'CDN integrations', 'whippet' ); ?></div>
			<form method="post">
				<?php wp_nonce_field( 'whippet_cdn_save', 'whippet_cdn_nonce' ); ?>
				<table class="form-table"><tbody>
					<?php foreach ( array( 'cloudflare_zone_id' => __( 'Cloudflare zone ID', 'whippet' ), 'cloudflare_api_token' => __( 'Cloudflare API token', 'whippet' ), 'bunny_api_key' => __( 'Bunny API key', 'whippet' ), 'bunny_pull_zone_id' => __( 'Bunny pull zone ID', 'whippet' ) ) as $key => $label ) : $saved = (string) get_option( 'whippet_' . $key, '' ); ?>
						<tr><th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th><td><input id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" type="<?php echo false !== strpos( $key, 'key' ) || false !== strpos( $key, 'token' ) ? 'password' : 'text'; ?>" value="" placeholder="<?php echo esc_attr( Helper::mask_secret( $saved ) ); ?>" autocomplete="off"></td></tr>
					<?php endforeach; ?>
				</tbody></table>
				<button class="button button-primary" name="whippet_cdn_save" value="1"><?php esc_html_e( 'Save CDN settings', 'whippet' ); ?></button>
			</form>
		</div>
		<?php
	}
}
