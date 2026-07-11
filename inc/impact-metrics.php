<?php
/**
 * Local impact metrics and PageSpeed checks.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ImpactMetrics {
	const OPTION = 'whippet_impact_history';

	public static function boot() {
		add_action( 'wp_ajax_whippet_capture_impact', array( __CLASS__, 'ajax_capture' ) );
		add_action( 'wp_ajax_whippet_run_psi', array( __CLASS__, 'ajax_run_psi' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_save_psi_key' ) );
	}

	/**
	 * Persist optional PageSpeed Insights API key.
	 */
	public static function maybe_save_psi_key() {
		if ( empty( $_POST['whippet_psi_save'] ) || ! Helper::user_can_manage() ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		check_admin_referer( 'whippet_psi_key', 'whippet_psi_nonce' );
		$value = isset( $_POST['whippet_psi_api_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['whippet_psi_api_key'] ) ) ) : '';
		if ( '' === $value || false !== strpos( $value, '*' ) ) {
			return;
		}
		update_option( 'whippet_psi_api_key', $value, false );
	}

	public static function capture() {
		global $wpdb;
		$options = (array) get_option( 'whippet_options', array() );
		$enabled = count(
			array_filter(
				$options,
				static function ( $value ) {
					return '1' === (string) $value || true === $value;
				}
			)
		);
		$cache = array( 'count' => 0, 'size_bytes' => 0 );
		$cache_dir = WP_CONTENT_DIR . '/cache/whippet/';
		if ( class_exists( '\Whippet\Cache' ) && is_dir( $cache_dir ) ) {
			try {
				$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $cache_dir, \RecursiveDirectoryIterator::SKIP_DOTS ) );
				foreach ( $iterator as $file ) {
					if ( $file->isFile() && 'html' === $file->getExtension() ) {
						++$cache['count'];
						$cache['size_bytes'] += $file->getSize();
					}
				}
			} catch ( \UnexpectedValueException $exception ) {
				$cache = array( 'count' => 0, 'size_bytes' => 0 );
			}
		}
		$rules = array();
		foreach ( array( 'whippet_disabled', 'whippet_enabled', 'whippet_p_disabled', 'whippet_p_enabled' ) as $table ) {
			$name   = $wpdb->prefix . $table;
			$like   = $wpdb->esc_like( $name );
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( $exists ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$rules[ $table ] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$name}`" );
			} else {
				$rules[ $table ] = 0;
			}
		}
		$entry = array(
			'time'            => gmdate( 'c' ),
			'enabled_options' => $enabled,
			'cache_files'     => (int) ( $cache['count'] ?? 0 ),
			'cache_bytes'     => (int) ( $cache['size_bytes'] ?? 0 ),
			'rules'           => $rules,
		);
		$history = (array) get_option( self::OPTION, array() );
		array_unshift( $history, $entry );
		update_option( self::OPTION, array_slice( $history, 0, 30 ), false );
		return $entry;
	}

	private static function authorise( $action ) {
		check_ajax_referer( $action, 'nonce' );
		if ( ! Helper::user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'whippet' ) ), 403 );
		}
	}

	public static function ajax_capture() {
		self::authorise( 'whippet_impact' );
		$entry = self::capture();
		Helper::audit_log( 'impact_captured' );
		wp_send_json_success( $entry );
	}

	public static function run_psi( $url = '' ) {
		$key = trim( (string) get_option( 'whippet_psi_api_key', '' ) );
		if ( '' === $key ) {
			return new \WP_Error( 'missing_key', __( 'Add a PageSpeed Insights API key first.', 'whippet' ) );
		}
		$url      = $url ? esc_url_raw( $url ) : home_url( '/' );
		$endpoint = add_query_arg(
			array( 'url' => $url, 'key' => $key, 'category' => 'performance', 'strategy' => 'mobile' ),
			'https://www.googleapis.com/pagespeedonline/v5/runPagespeed'
		);
		$response = wp_remote_get( $endpoint, array( 'timeout' => 45, 'sslverify' => Helper::ssl_verify(), 'headers' => array( 'Accept' => 'application/json' ) ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( wp_remote_retrieve_response_code( $response ) >= 300 || ! is_array( $body ) ) {
			return new \WP_Error( 'psi_failed', __( 'PageSpeed Insights did not return a valid result.', 'whippet' ) );
		}
		return array(
			'time'  => gmdate( 'c' ),
			'url'   => $url,
			'score' => (int) round( 100 * (float) ( $body['lighthouseResult']['categories']['performance']['score'] ?? 0 ) ),
			'lcp'   => $body['lighthouseResult']['audits']['largest-contentful-paint']['displayValue'] ?? '',
			'cls'   => $body['lighthouseResult']['audits']['cumulative-layout-shift']['displayValue'] ?? '',
			'inp'   => $body['lighthouseResult']['audits']['interaction-to-next-paint']['displayValue'] ?? '',
		);
	}

	public static function ajax_run_psi() {
		self::authorise( 'whippet_impact' );
		$result = self::run_psi();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		update_option( 'whippet_last_psi', $result, false );
		wp_send_json_success( $result );
	}

	public static function render_ui() {
		$history = (array) get_option( self::OPTION, array() );
		$latest  = reset( $history );
		$psi     = (array) get_option( 'whippet_last_psi', array() );
		$psi_key = (string) get_option( 'whippet_psi_api_key', '' );
		?>
		<div class="wa-card">
			<div class="wa-card-section-title"><?php esc_html_e( 'Impact metrics', 'whippet' ); ?></div>
			<div class="wa-2col">
				<p><strong><?php esc_html_e( 'Enabled optimisations', 'whippet' ); ?></strong><br><?php echo esc_html( (string) ( $latest['enabled_options'] ?? 0 ) ); ?></p>
				<p><strong><?php esc_html_e( 'Cached pages', 'whippet' ); ?></strong><br><?php echo esc_html( (string) ( $latest['cache_files'] ?? 0 ) ); ?></p>
			</div>
			<?php if ( $psi ) : ?>
				<p><?php echo esc_html( sprintf( __( 'Last PageSpeed score: %1$d (LCP %2$s, CLS %3$s)', 'whippet' ), (int) ( $psi['score'] ?? 0 ), (string) ( $psi['lcp'] ?? '—' ), (string) ( $psi['cls'] ?? '—' ) ) ); ?></p>
			<?php endif; ?>
			<p>
				<button type="button" class="button" onclick="fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'whippet_capture_impact',nonce:'<?php echo esc_js( wp_create_nonce( 'whippet_impact' ) ); ?>'})}).then(()=>location.reload())"><?php esc_html_e( 'Capture current impact', 'whippet' ); ?></button>
				<?php if ( $psi_key ) : ?>
					<button type="button" class="button" onclick="fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'whippet_run_psi',nonce:'<?php echo esc_js( wp_create_nonce( 'whippet_impact' ) ); ?>'})}).then(r=>r.json()).then(r=>{alert(r.success?('Score '+r.data.score):r.data.message);location.reload();})"><?php esc_html_e( 'Run PageSpeed Insights', 'whippet' ); ?></button>
				<?php endif; ?>
			</p>
			<p class="description"><?php esc_html_e( 'Optional PageSpeed Insights API key enables lab score capture for before/after comparisons.', 'whippet' ); ?></p>
			<form method="post">
				<?php wp_nonce_field( 'whippet_psi_key', 'whippet_psi_nonce' ); ?>
				<input type="password" name="whippet_psi_api_key" value="" placeholder="<?php echo esc_attr( Helper::mask_secret( $psi_key ) ); ?>" class="regular-text" autocomplete="off">
				<button class="button" name="whippet_psi_save" value="1"><?php esc_html_e( 'Save PSI API key', 'whippet' ); ?></button>
			</form>
		</div>
		<?php
	}
}
