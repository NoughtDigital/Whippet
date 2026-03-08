<?php
/**
 * Critical CSS Engine — Premium Feature
 *
 * Integrates with a self-hosted Critical CSS Engine service to generate and
 * inject above-the-fold CSS for every post/page template.
 *
 * @package Whippet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── API Wrapper ───────────────────────────────────────────────────────────────

class CCE_API {

	private static function base_url(): string {
		return self::base_url_public();
	}

	public static function base_url_public(): string {
		return rtrim( get_option( 'cce_api_url', 'http://localhost:3000' ), '/' );
	}

	public static function api_key(): string {
		return trim( (string) get_option( 'cce_api_key', '' ) );
	}

	private static function headers(): array {
		return [
			'Authorization' => 'Bearer ' . self::api_key(),
			'Content-Type'  => 'application/json',
		];
	}

	public static function looks_masked_key( string $api_key ): bool {
		return (bool) preg_match( '/^[^\s*]{4}\*+$/', $api_key );
	}

	private static function check_configured(): ?WP_Error {
		if ( empty( self::api_key() ) ) {
			return new WP_Error( 'cce_not_configured', 'API key is not set. Save your API key in the Critical CSS settings first.' );
		}
		if ( self::looks_masked_key( self::api_key() ) ) {
			return new WP_Error( 'cce_masked_key', 'The saved API key looks masked (for example `abc1**`). Paste the full unmasked key from the Critical CSS Engine and save again.' );
		}
		return null;
	}

	/** POST /v1/wordpress/critical-css */
	public static function generate( int $post_id, string $url, string $variant = 'public', array $cookies = [] ): array|WP_Error {
		$err = self::check_configured();
		if ( $err ) return $err;

		$body = [
			'siteUrl' => home_url(),
			'postId'  => $post_id,
			'url'     => $url,
			'variantKey' => cce_normalize_variant( $variant ),
		];
		if ( ! empty( $cookies ) ) {
			$body['cookies'] = array_values( $cookies );
		}

		$response = wp_remote_post(
			self::base_url() . '/v1/wordpress/critical-css',
			[ 'headers' => self::headers(), 'body' => wp_json_encode( $body ), 'timeout' => 15 ]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 401 ) {
			return new WP_Error( 'cce_auth_error', 'Invalid API key — update it in Premium → Critical CSS settings.', [ 'status' => 401 ] );
		}

		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'cce_api_error', $data['message'] ?? sprintf( 'HTTP %d from generate endpoint', $code ), [ 'status' => $code ] );
		}

		return $data ?? [];
	}

	/** GET /v1/status/:jobId */
	public static function status( string $job_id ): array|WP_Error {
		$response = wp_remote_get(
			self::base_url() . '/v1/status/' . rawurlencode( $job_id ),
			[ 'headers' => self::headers(), 'timeout' => 10 ]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true ) ?? [];
	}

	/** POST /v1/scan */
	public static function scan( array $options = [] ): array|WP_Error {
		$err = self::check_configured();
		if ( $err ) return $err;

		$body = wp_json_encode( array_merge( [ 'url' => home_url() ], $options ) );

		$response = wp_remote_post(
			self::base_url() . '/v1/scan',
			[ 'headers' => self::headers(), 'body' => $body, 'timeout' => 15 ]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 401 ) {
			return new WP_Error( 'cce_auth_error', 'Invalid API key — update it in Premium → Critical CSS settings.', [ 'status' => 401 ] );
		}

		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'cce_api_error', $data['message'] ?? sprintf( 'HTTP %d from scan endpoint', $code ), [ 'status' => $code ] );
		}

		return $data ?? [];
	}

	/** GET /v1/report/:scanId */
	public static function report( string $scan_id ): array|WP_Error {
		$response = wp_remote_get(
			self::base_url() . '/v1/report/' . rawurlencode( $scan_id ),
			[ 'headers' => self::headers(), 'timeout' => 10 ]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true ) ?? [];
	}

	/** DELETE /v1/cache/:siteId */
	public static function purge_cache( string $site_id ): array|WP_Error {
		$response = wp_remote_request(
			self::base_url() . '/v1/cache/' . rawurlencode( $site_id ),
			[ 'method' => 'DELETE', 'headers' => self::headers(), 'timeout' => 10 ]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true ) ?? [];
	}

	/** POST /v1/generate — cluster-level generation */
	public static function generate_cluster( string $cluster_id, string $representative_url, string $variant = 'public', array $cookies = [] ): array|WP_Error {
		$err = self::check_configured();
		if ( $err ) return $err;

		$body = [
			// The `/v1/generate` route accepts a single page URL and returns a job ID.
			// Cluster IDs are tracked in WordPress and mapped back during polling.
			'url'        => $representative_url,
			'variantKey' => cce_normalize_variant( $variant ),
		];
		if ( ! empty( $cookies ) ) {
			$body['cookies'] = array_values( $cookies );
		}

		$response = wp_remote_post(
			self::base_url() . '/v1/generate',
			[ 'headers' => self::headers(), 'body' => wp_json_encode( $body ), 'timeout' => 15 ]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 401 ) {
			return new WP_Error( 'cce_auth_error', 'Invalid API key — update it in Premium → Critical CSS settings.', [ 'status' => 401 ] );
		}

		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'cce_api_error', $data['message'] ?? sprintf( 'HTTP %d from generate endpoint', $code ), [ 'status' => $code ] );
		}

		return $data ?? [];
	}

	/** GET /health */
	public static function health(): bool {
		$response = wp_remote_get( self::base_url() . '/health', [ 'timeout' => 5 ] );
		return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
	}
}

function cce_normalize_variant( string $variant ): string {
	return 'logged_in' === $variant ? 'logged_in' : 'public';
}

function cce_variant_suffix( string $variant ): string {
	return 'logged_in' === cce_normalize_variant( $variant ) ? '_logged_in' : '';
}

function cce_current_variant(): string {
	return is_user_logged_in() && is_admin_bar_showing() ? 'logged_in' : 'public';
}

function cce_post_meta_key( string $base, string $variant ): string {
	return $base . cce_variant_suffix( $variant );
}

function cce_cluster_option_key( string $cluster_id, string $base, string $variant ): string {
	$key = md5( $cluster_id );
	return $base . $key . cce_variant_suffix( $variant );
}

function cce_capture_auth_cookies(): array {
	if ( ! is_user_logged_in() || empty( $_COOKIE ) ) {
		return [];
	}

	$cookies = [];
	$site_url = home_url( '/' );

	foreach ( $_COOKIE as $name => $value ) {
		if ( 0 !== strpos( $name, 'wordpress_logged_in_' ) && 'wordpress_test_cookie' !== $name ) {
			continue;
		}

		$cookies[] = [
			'name'     => (string) $name,
			'value'    => (string) $value,
			'url'      => $site_url,
			'path'     => '/',
			'secure'   => is_ssl(),
			'httpOnly' => false,
			'sameSite' => 'Lax',
		];
	}

	return $cookies;
}

// ── Admin: Settings Registration + Meta Box + AJAX ───────────────────────────

class CCE_Admin {

	public static function init(): void {
		add_action( 'admin_init',                [ __CLASS__, 'register_settings' ] );
		add_action( 'add_meta_boxes',            [ __CLASS__, 'add_meta_box' ] );
		add_action( 'wp_ajax_cce_generate',      [ __CLASS__, 'ajax_generate' ] );
		add_action( 'wp_ajax_cce_status',        [ __CLASS__, 'ajax_status' ] );
		add_action( 'wp_ajax_cce_scan',          [ __CLASS__, 'ajax_scan' ] );
		add_action( 'wp_ajax_cce_cluster_status', [ __CLASS__, 'ajax_cluster_status' ] );
		add_action( 'wp_ajax_cce_purge',         [ __CLASS__, 'ajax_purge' ] );
		add_action( 'wp_ajax_cce_test',          [ __CLASS__, 'ajax_test' ] );
		add_action( 'wp_ajax_cce_regenerate_all', [ __CLASS__, 'ajax_regenerate_all' ] );
		add_action( 'wp_ajax_cce_poll_scan',     [ __CLASS__, 'ajax_poll_scan' ] );
		add_action( 'upgrader_process_complete', [ __CLASS__, 'on_upgrade' ], 10, 2 );
		add_action( 'save_post',                 [ __CLASS__, 'on_save_post' ], 10, 3 );
		add_action( 'switch_theme',              [ __CLASS__, 'on_switch_theme' ] );
		add_action( 'customize_save_after',      [ __CLASS__, 'on_customize_save' ] );
	}

	// ── Settings ─────────────────────────────────────────────────────────────

	public static function register_settings(): void {
		register_setting( 'cce_settings', 'cce_api_url', [ 'sanitize_callback' => 'esc_url_raw',  'default' => 'http://localhost:3000' ] );
		register_setting( 'cce_settings', 'cce_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'cce_settings', 'cce_enabled', [ 'sanitize_callback' => 'absint', 'default' => 1 ] );
		register_setting( 'cce_settings', 'cce_post_types', [
			'sanitize_callback' => function ( $val ) {
				return is_array( $val ) ? array_values( array_map( 'sanitize_key', $val ) ) : [ 'post', 'page' ];
			},
			'default' => [ 'post', 'page' ],
		] );
	}

	// ── Meta Box ─────────────────────────────────────────────────────────────

	public static function add_meta_box(): void {
		foreach ( array_keys( get_post_types( [ 'public' => true ] ) ) as $pt ) {
			add_meta_box(
				'cce_meta_box',
				'Critical CSS',
				[ __CLASS__, 'render_meta_box' ],
				$pt,
				'side',
				'default'
			);
		}
	}

	public static function render_meta_box( WP_Post $post ): void {
		$job_id  = get_post_meta( $post->ID, '_cce_job_id',  true );
		$status  = get_post_meta( $post->ID, '_cce_status',  true );
		$css     = get_post_meta( $post->ID, '_cce_css',     true );
		$bytes   = get_post_meta( $post->ID, '_cce_bytes',   true );
		$savings = get_post_meta( $post->ID, '_cce_savings', true );
		$url     = get_permalink( $post->ID );
		?>
		<div id="cce-meta-status">
		<?php if ( $css ) : ?>
			<p style="color:#10b981;margin:0 0 8px;">
				<strong>Critical CSS ready</strong><br>
				<?php echo esc_html( number_format( (int) $bytes ) ); ?> bytes &mdash; <?php echo esc_html( $savings ); ?>% reduction
			</p>
			<button type="button" id="cce-regenerate-btn" class="button button-small"
			        data-post-id="<?php echo esc_attr( $post->ID ); ?>"
			        data-url="<?php echo esc_attr( $url ); ?>">Regenerate</button>
		<?php elseif ( $job_id && in_array( $status, [ 'pending', 'active' ], true ) ) : ?>
			<p style="color:#f59e0b;margin:0 0 8px;"><strong>Generating&hellip;</strong> (<?php echo esc_html( $status ); ?>)</p>
			<button type="button" id="cce-poll-btn" class="button button-small"
			        data-job-id="<?php echo esc_attr( $job_id ); ?>"
			        data-post-id="<?php echo esc_attr( $post->ID ); ?>">Check Status</button>
		<?php else : ?>
			<p style="color:#6b7280;margin:0 0 8px;">No critical CSS generated yet.</p>
			<button type="button" id="cce-generate-btn" class="button button-primary button-small"
			        data-post-id="<?php echo esc_attr( $post->ID ); ?>"
			        data-url="<?php echo esc_attr( $url ); ?>">Generate Critical CSS</button>
		<?php endif; ?>
		</div>
		<?php
		static $js_printed = false;
		if ( ! $js_printed ) {
			$js_printed = true;
			$nonce      = wp_create_nonce( 'cce_nonce' );
			$ajax_url   = admin_url( 'admin-ajax.php' );
			?>
			<script>
			(function($){
				var ajaxUrl = <?php echo wp_json_encode( $ajax_url ); ?>;
				var nonce   = <?php echo wp_json_encode( $nonce ); ?>;

				$(document).on('click','#cce-generate-btn,#cce-regenerate-btn',function(){
					var $btn=$(this),postId=$btn.data('post-id'),url=$btn.data('url');
					$btn.prop('disabled',true).text('Requesting\u2026');
					$.post(ajaxUrl,{action:'cce_generate',nonce:nonce,post_id:postId,url:url})
						.done(function(res){
							if(!res.success){alert('Error: '+res.data);$btn.prop('disabled',false).text('Retry');return;}
							$btn.closest('#cce-meta-status').html('<p style="color:#f59e0b"><strong>Generating\u2026</strong></p><button type="button" id="cce-poll-btn" class="button button-small" data-job-id="'+res.data.jobId+'" data-post-id="'+postId+'">Check Status</button>');
							cceStartPolling(postId,res.data.jobId);
						})
						.fail(function(){alert('Request failed. Is the service running?');$btn.prop('disabled',false);});
				});

				$(document).on('click','#cce-poll-btn',function(){
					ccePollOnce($(this).data('post-id'),$(this).data('job-id'));
				});

				window.cceStartPolling=function(postId,jobId){
					var attempts=0,max=48;
					var interval=setInterval(function(){
						attempts++;
						ccePollOnce(postId,jobId,function(done){if(done||attempts>=max)clearInterval(interval);});
					},5000);
				};

				window.ccePollOnce=function(postId,jobId,cb){
					$.post(ajaxUrl,{action:'cce_status',nonce:nonce,job_id:jobId,post_id:postId})
						.done(function(res){
							if(!res.success)return;
							var s=res.data;
							if(s.status==='completed'){
								var r=s.result||{};
								$('#cce-meta-status').html('<p style="color:#10b981"><strong>Critical CSS ready</strong><br>'+(r.criticalBytes||0).toLocaleString()+' bytes \u2014 '+(r.reductionPercent||0).toFixed(1)+'% reduction</p><button type="button" id="cce-regenerate-btn" class="button button-small" data-post-id="'+postId+'" data-url="">Regenerate</button>');
								if(cb)cb(true);
							}else if(s.status==='failed'){
								$('#cce-meta-status').html('<p style="color:#ef4444"><strong>Generation failed.</strong></p>');
								if(cb)cb(true);
							}else{
								var progress=s.progress?' ('+s.progress+'%)':'';
								$('#cce-meta-status').find('p').text('Generating\u2026 '+s.status+progress);
								if(cb)cb(false);
							}
						});
				};
			}(jQuery));
			</script>
			<?php
		}
	}

	// ── AJAX ─────────────────────────────────────────────────────────────────

	public static function ajax_generate(): void {
		check_ajax_referer( 'cce_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		$url     = esc_url_raw( $_POST['url'] ?? '' );

		if ( ! $post_id || ! $url ) {
			wp_send_json_error( 'Missing post_id or url' );
		}

		$result = CCE_API::generate( $post_id, $url, 'public' );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		update_post_meta( $post_id, '_cce_job_id', sanitize_text_field( $result['jobId'] ) );
		update_post_meta( $post_id, '_cce_status', 'pending' );

		wp_schedule_single_event( time() + 20, 'cce_poll_job', [ $post_id, $result['jobId'], 'public' ] );

		$logged_in_job_id  = '';
		$logged_in_cookies = cce_capture_auth_cookies();
		if ( ! empty( $logged_in_cookies ) ) {
			$logged_in = CCE_API::generate( $post_id, $url, 'logged_in', $logged_in_cookies );
			if ( ! is_wp_error( $logged_in ) && ! empty( $logged_in['jobId'] ) ) {
				$logged_in_job_id = sanitize_text_field( $logged_in['jobId'] );
				update_post_meta( $post_id, cce_post_meta_key( '_cce_job_id', 'logged_in' ), $logged_in_job_id );
				update_post_meta( $post_id, cce_post_meta_key( '_cce_status', 'logged_in' ), 'pending' );
				wp_schedule_single_event( time() + 25, 'cce_poll_job', [ $post_id, $logged_in_job_id, 'logged_in' ] );
			}
		}

		wp_send_json_success( [
			'jobId'         => $result['jobId'],
			'status'        => 'pending',
			'loggedInJobId' => $logged_in_job_id,
		] );
	}

	public static function ajax_status(): void {
		check_ajax_referer( 'cce_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		$job_id  = sanitize_text_field( $_POST['job_id'] ?? '' );
		$post_id = absint( $_POST['post_id'] ?? 0 );
		$variant = cce_normalize_variant( sanitize_key( $_POST['variant'] ?? 'public' ) );

		if ( ! $job_id ) {
			wp_send_json_error( 'Missing job_id' );
		}

		$status = CCE_API::status( $job_id );
		if ( is_wp_error( $status ) ) {
			wp_send_json_error( $status->get_error_message() );
		}

		if ( ( $status['status'] ?? '' ) === 'completed' && isset( $status['result']['css'] ) ) {
			$result = $status['result'];
			update_post_meta( $post_id, cce_post_meta_key( '_cce_css', $variant ),     wp_strip_all_tags( $result['css'] ) );
			update_post_meta( $post_id, cce_post_meta_key( '_cce_status', $variant ),  'completed' );
			update_post_meta( $post_id, cce_post_meta_key( '_cce_bytes', $variant ),   absint( $result['criticalBytes'] ?? 0 ) );
			update_post_meta( $post_id, cce_post_meta_key( '_cce_savings', $variant ), round( $result['reductionPercent'] ?? 0, 1 ) );
		} elseif ( ( $status['status'] ?? '' ) === 'failed' ) {
			update_post_meta( $post_id, cce_post_meta_key( '_cce_status', $variant ), 'failed' );
		} else {
			update_post_meta( $post_id, cce_post_meta_key( '_cce_status', $variant ), $status['status'] ?? 'unknown' );
		}

		wp_send_json_success( $status );
	}

	public static function ajax_scan(): void {
		check_ajax_referer( 'cce_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		$result = CCE_API::scan();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		if ( ! empty( $result['jobId'] ) )  update_option( 'cce_scan_job_id', $result['jobId'] );
		if ( ! empty( $result['scanId'] ) ) update_option( 'cce_scan_id',     $result['scanId'] );
		if ( ! empty( $result['siteId'] ) ) update_option( 'cce_site_id',     $result['siteId'] );

		update_option( 'cce_scan_status', 'pending', false );

		// Kick off background polling so clusters auto-generate even without a browser open.
		if ( ! empty( $result['scanId'] ) && ! empty( $result['jobId'] ) ) {
			wp_schedule_single_event( time() + 30, 'cce_poll_scan', [ $result['scanId'], $result['jobId'] ] );
		}

		wp_send_json_success( $result );
	}

	public static function get_cluster_status_payload(): array {
		$cluster_ids    = get_option( 'cce_cluster_ids', [] );
		$clusters       = [];
		$has_pending    = false;

		foreach ( (array) $cluster_ids as $cid ) {
			$ckey     = md5( $cid );
			$cmeta    = get_option( 'cce_cluster_meta_' . $ckey, [] );
			$cstatus  = (string) get_option( 'cce_cluster_status_' . $ckey, 'pending' );
			$cbytes   = (int) get_option( 'cce_cluster_bytes_' . $ckey, 0 );
			$csavings = (float) get_option( 'cce_cluster_savings_' . $ckey, 0 );

			if ( in_array( $cstatus, [ 'pending', 'active' ], true ) ) {
				$has_pending = true;
			}

			$clusters[] = [
				'id'          => $cid,
				'postCount'   => (int) ( $cmeta['post_count'] ?? 0 ),
				'bytes'       => $cbytes,
				'savings'     => $csavings,
				'status'      => $cstatus,
				'statusColor' => $cstatus === 'completed' ? '#10b981' : ( $cstatus === 'failed' ? '#ef4444' : '#f59e0b' ),
			];
		}

		return [
			'scanId'             => (string) get_option( 'cce_scan_id', '' ),
			'scanStatus'         => (string) get_option( 'cce_scan_status', '' ),
			'hasClusters'        => ! empty( $clusters ),
			'hasPendingClusters' => $has_pending,
			'clusters'           => $clusters,
		];
	}

	public static function ajax_cluster_status(): void {
		check_ajax_referer( 'cce_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		wp_send_json_success( self::get_cluster_status_payload() );
	}

	public static function ajax_purge(): void {
		check_ajax_referer( 'cce_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		$site_id = sanitize_text_field( $_POST['site_id'] ?? get_option( 'cce_site_id', '' ) );
		if ( ! $site_id ) {
			wp_send_json_error( 'No site ID known yet. Run a scan first.' );
		}

		$result = CCE_API::purge_cache( $site_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	public static function ajax_test(): void {
		check_ajax_referer( 'cce_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		$api_url = CCE_API::base_url_public();
		$api_key = CCE_API::api_key();
		$key_set = ! empty( $api_key );

		$key_display = $key_set
			? substr( $api_key, 0, 4 ) . str_repeat( '*', max( 0, strlen( $api_key ) - 4 ) )
			: '(empty)';

		// Health check — no auth
		$health_resp = wp_remote_get( $api_url . '/health', [ 'timeout' => 5, 'sslverify' => false ] );
		$health_code = is_wp_error( $health_resp ) ? $health_resp->get_error_message() : wp_remote_retrieve_response_code( $health_resp );
		$health_body = is_wp_error( $health_resp ) ? '' : wp_remote_retrieve_body( $health_resp );

		$json_body = wp_json_encode( [ 'url' => home_url() ] );

		// Helper to fire a POST probe with a given set of headers
		$probe = function ( array $headers ) use ( $api_url, $json_body ): array {
			$r = wp_remote_post( $api_url . '/v1/scan', [
				'headers'   => array_merge( $headers, [ 'Content-Type' => 'application/json' ] ),
				'body'      => $json_body,
				'timeout'   => 10,
				'sslverify' => false,
			] );
			return [
				'code' => is_wp_error( $r ) ? $r->get_error_message() : wp_remote_retrieve_response_code( $r ),
				'body' => is_wp_error( $r ) ? '' : wp_remote_retrieve_body( $r ),
			];
		};

		// Try all three common auth formats
		$bearer    = $probe( [ 'Authorization' => 'Bearer ' . $api_key ] );
		$raw_auth  = $probe( [ 'Authorization' => $api_key ] );
		$x_api_key = $probe( [ 'X-API-Key' => $api_key ] );

		wp_send_json_success( [
			'apiUrl'        => $api_url,
			'apiKeySet'     => $key_set,
			'apiKeyDisplay' => $key_display,
			'apiKeyLength'  => strlen( $api_key ),
			'looksMasked'   => CCE_API::looks_masked_key( $api_key ),
			'healthCode'    => $health_code,
			'healthBody'    => $health_body,
			'bearer'        => $bearer,
			'rawAuth'       => $raw_auth,
			'xApiKey'       => $x_api_key,
		] );
	}

	// ── Auto-generate on post save ────────────────────────────────────────────

	public static function on_save_post( int $post_id, WP_Post $post, bool $update ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( wp_is_post_revision( $post_id ) ) return;
		if ( $post->post_status !== 'publish' ) return;
		if ( ! get_option( 'cce_enabled', 1 ) ) return;

		$enabled_types = get_option( 'cce_post_types', [ 'post', 'page' ] );
		if ( ! in_array( $post->post_type, $enabled_types, true ) ) return;

		$new_hash = md5( $post->post_content . $post->post_modified . $post->post_title );
		$old_hash = get_post_meta( $post_id, '_cce_content_hash', true );
		if ( $new_hash === $old_hash ) return;

		update_post_meta( $post_id, '_cce_content_hash', $new_hash );
		update_post_meta( $post_id, '_cce_status', 'pending' );

		$result = CCE_API::generate( $post_id, get_permalink( $post_id ), 'public' );
		if ( ! is_wp_error( $result ) && ! empty( $result['jobId'] ) ) {
			update_post_meta( $post_id, '_cce_job_id', sanitize_text_field( $result['jobId'] ) );
			wp_schedule_single_event( time() + 20, 'cce_poll_job', [ $post_id, $result['jobId'], 'public' ] );
		}
	}

	// ── Purge + regenerate on theme switch ────────────────────────────────────

	public static function on_switch_theme(): void {
		$site_id = get_option( 'cce_site_id', '' );
		if ( $site_id ) {
			CCE_API::purge_cache( $site_id );
		}
		cce_regenerate_all_published_posts();
	}

	// ── Purge + regenerate after Customizer save ──────────────────────────────

	public static function on_customize_save(): void {
		$site_id = get_option( 'cce_site_id', '' );
		if ( $site_id ) {
			CCE_API::purge_cache( $site_id );
		}
		cce_regenerate_all_published_posts();
	}

	// ── Auto-purge (+ regenerate) on theme / plugin upgrade ───────────────────

	public static function on_upgrade( $upgrader, array $hook_extra ): void {
		if ( ! in_array( $hook_extra['type'] ?? '', [ 'theme', 'plugin' ], true ) ) {
			return;
		}
		$site_id = get_option( 'cce_site_id', '' );
		if ( $site_id ) {
			CCE_API::purge_cache( $site_id );
		}
		cce_regenerate_all_published_posts();
	}

	// ── AJAX: regenerate all published posts ──────────────────────────────────

	public static function ajax_regenerate_all(): void {
		check_ajax_referer( 'cce_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		$count = cce_regenerate_all_published_posts( cce_capture_auth_cookies() );
		wp_send_json_success( [ 'queued' => $count ] );
	}

	// ── AJAX: poll an in-flight scan job ─────────────────────────────────────

	public static function ajax_poll_scan(): void {
		check_ajax_referer( 'cce_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		$job_id  = sanitize_text_field( $_POST['job_id']  ?? '' );
		$scan_id = sanitize_text_field( $_POST['scan_id'] ?? '' );

		if ( ! $job_id ) {
			wp_send_json_error( 'Missing job_id' );
		}

		$status = CCE_API::status( $job_id );
		if ( is_wp_error( $status ) ) {
			wp_send_json_error( $status->get_error_message() );
		}

		update_option( 'cce_scan_status', $status['status'] ?? 'unknown', false );

		if ( ( $status['status'] ?? '' ) === 'completed' && $scan_id ) {
			cce_process_scan_report( $scan_id, cce_capture_auth_cookies() );
		}

		wp_send_json_success( $status );
	}

}

// ── Front-end Critical CSS Injector ──────────────────────────────────────────

class CCE_Injector {

	public static function init(): void {
		add_action( 'template_redirect', [ __CLASS__, 'maybe_defer_non_critical_css' ], 0 );
		add_action( 'wp_head', [ __CLASS__, 'inject' ], 1 );
	}

	private static function get_post_css( int $post_id, string $variant ): string {
		$variant = cce_normalize_variant( $variant );

		$css = (string) get_post_meta( $post_id, cce_post_meta_key( '_cce_css', $variant ), true );
		if ( ! $css && 'public' !== $variant ) {
			$css = (string) get_post_meta( $post_id, '_cce_css', true );
		}

		if ( $css ) {
			return $css;
		}

		$cluster_id = (string) get_post_meta( $post_id, '_cce_cluster_id', true );
		if ( ! $cluster_id ) {
			return '';
		}

		$css = (string) get_option( cce_cluster_option_key( $cluster_id, 'cce_cluster_css_', $variant ), '' );
		if ( ! $css && 'public' !== $variant ) {
			$css = (string) get_option( cce_cluster_option_key( $cluster_id, 'cce_cluster_css_', 'public' ), '' );
		}

		return $css;
	}

	public static function maybe_defer_non_critical_css(): void {
		if ( is_admin() || is_user_logged_in() || is_feed() || is_preview() ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id || ! self::get_post_css( $post_id, 'public' ) ) {
			return;
		}

		ob_start( [ __CLASS__, 'defer_non_critical_css_markup' ] );
	}

	public static function defer_non_critical_css_markup( string $html ): string {
		if ( false === stripos( $html, 'id="critical-css"' ) && false === stripos( $html, "id='critical-css'" ) ) {
			return $html;
		}

		$patterns = [
			'/(<style\b[^>]*\bid=(["\'])(wp-block-[^"\']*-inline-css|wp-block-library-inline-css|global-styles-inline-css|core-block-supports-inline-css|twentytwentyfive-style-inline-css)\2[^>]*>.*?<\/style>)/is',
			'/(<link\b[^>]*\bid=(["\'])(whippet-css)\2[^>]*>)/i',
		];

		$deferred_markup = [];

		foreach ( $patterns as $pattern ) {
			$html = preg_replace_callback(
				$pattern,
				static function ( array $matches ) use ( &$deferred_markup ) {
					$deferred_markup[] = $matches[1];
					return '';
				},
				$html
			);
		}

		if ( empty( $deferred_markup ) ) {
			return $html;
		}

		$payload = wp_json_encode( array_values( $deferred_markup ) );
		$loader  = '<script id="cce-deferred-css-loader">(function(){var tags=' . $payload . ';function load(){if(!Array.isArray(tags)||!tags.length){return;}tags.forEach(function(markup){var wrap=document.createElement("div");wrap.innerHTML=markup;var node=wrap.firstElementChild;if(node){document.head.appendChild(node);}});}if("requestIdleCallback" in window){requestIdleCallback(load,{timeout:1500});}else{window.setTimeout(load,300);}})();</script>';

		return str_replace( '</body>', $loader . '</body>', $html );
	}

	public static function inject(): void {
		if ( ! get_option( 'cce_enabled', 1 ) ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		$css = self::get_post_css( $post_id, cce_current_variant() );

		if ( ! $css ) {
			return;
		}

		echo '<style id="critical-css">' . wp_strip_all_tags( $css ) . '</style>' . "\n";
	}
}

// ── Background Job Polling via WP-Cron ────────────────────────────────────────

class CCE_Cron {

	public static function init(): void {
		add_action( 'cce_poll_job',     [ __CLASS__, 'poll_job' ],     10, 3 );
		add_action( 'cce_poll_scan',    [ __CLASS__, 'poll_scan' ],    10, 2 );
		add_action( 'cce_poll_cluster', [ __CLASS__, 'poll_cluster' ], 10, 3 );
	}

	public static function unschedule(): void {
		wp_clear_scheduled_hook( 'cce_poll_job' );
		wp_clear_scheduled_hook( 'cce_poll_scan' );
		wp_clear_scheduled_hook( 'cce_poll_cluster' );
	}

	/** Poll a per-post generate job every 15 s until done. */
	public static function poll_job( int $post_id, string $job_id, string $variant = 'public' ): void {
		$variant = cce_normalize_variant( $variant );
		$status_data = CCE_API::status( $job_id );
		if ( is_wp_error( $status_data ) ) {
			return;
		}

		$status = $status_data['status'] ?? 'unknown';

		if ( $status === 'completed' && isset( $status_data['result']['css'] ) ) {
			$result = $status_data['result'];
			update_post_meta( $post_id, cce_post_meta_key( '_cce_css', $variant ),     wp_strip_all_tags( $result['css'] ) );
			update_post_meta( $post_id, cce_post_meta_key( '_cce_status', $variant ),  'completed' );
			update_post_meta( $post_id, cce_post_meta_key( '_cce_bytes', $variant ),   absint( $result['criticalBytes'] ?? 0 ) );
			update_post_meta( $post_id, cce_post_meta_key( '_cce_savings', $variant ), round( $result['reductionPercent'] ?? 0, 1 ) );
			return;
		}

		if ( $status === 'failed' ) {
			update_post_meta( $post_id, cce_post_meta_key( '_cce_status', $variant ), 'failed' );
			return;
		}

		if ( in_array( $status, [ 'pending', 'active' ], true ) ) {
			wp_schedule_single_event( time() + 15, 'cce_poll_job', [ $post_id, $job_id, $variant ] );
		}
	}

	/** Poll a full-site scan job every 30 s; on completion process the report. */
	public static function poll_scan( string $scan_id, string $job_id ): void {
		$status_data = CCE_API::status( $job_id );
		if ( is_wp_error( $status_data ) ) {
			return;
		}

		$status = $status_data['status'] ?? 'unknown';
		update_option( 'cce_scan_status', $status, false );

		if ( $status === 'completed' ) {
			cce_process_scan_report( $scan_id );
			return;
		}

		if ( in_array( $status, [ 'pending', 'active' ], true ) ) {
			wp_schedule_single_event( time() + 30, 'cce_poll_scan', [ $scan_id, $job_id ] );
		}
	}

	/** Poll a cluster-level generate job every 15 s; store CSS in an option on completion. */
	public static function poll_cluster( string $cluster_id, string $job_id, string $variant = 'public' ): void {
		$variant = cce_normalize_variant( $variant );
		$status_data = CCE_API::status( $job_id );
		if ( is_wp_error( $status_data ) ) {
			return;
		}

		$status = $status_data['status'] ?? 'unknown';

		if ( $status === 'completed' && isset( $status_data['result']['css'] ) ) {
			$result = $status_data['result'];
			update_option( cce_cluster_option_key( $cluster_id, 'cce_cluster_css_', $variant ), wp_strip_all_tags( $result['css'] ), false );
			update_option( cce_cluster_option_key( $cluster_id, 'cce_cluster_status_', $variant ), 'completed', false );
			update_option( cce_cluster_option_key( $cluster_id, 'cce_cluster_bytes_', $variant ), absint( $result['criticalBytes'] ?? 0 ), false );
			update_option( cce_cluster_option_key( $cluster_id, 'cce_cluster_savings_', $variant ), round( $result['reductionPercent'] ?? 0, 1 ), false );
			return;
		}

		if ( $status === 'failed' ) {
			update_option( cce_cluster_option_key( $cluster_id, 'cce_cluster_status_', $variant ), 'failed', false );
			return;
		}

		if ( in_array( $status, [ 'pending', 'active' ], true ) ) {
			wp_schedule_single_event( time() + 15, 'cce_poll_cluster', [ $cluster_id, $job_id, $variant ] );
		}
	}
}

// ── Bulk Regeneration Helper ──────────────────────────────────────────────────

/**
 * Queue a fresh generate job for every published post/page across all
 * configured post types. Returns the number of jobs queued.
 */
function cce_regenerate_all_published_posts( array $logged_in_cookies = [] ): int {
	if ( ! get_option( 'cce_enabled', 1 ) ) {
		return 0;
	}

	$post_types = get_option( 'cce_post_types', [ 'post', 'page' ] );
	$posts      = get_posts( [
		'post_type'      => $post_types,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	] );

	$delay  = 0;
	$queued = 0;

	foreach ( $posts as $post_id ) {
		delete_post_meta( $post_id, '_cce_content_hash' );

		$result = CCE_API::generate( $post_id, get_permalink( $post_id ), 'public' );
		if ( ! is_wp_error( $result ) && ! empty( $result['jobId'] ) ) {
			update_post_meta( $post_id, '_cce_job_id', sanitize_text_field( $result['jobId'] ) );
			update_post_meta( $post_id, '_cce_status', 'pending' );
			wp_schedule_single_event( time() + 30 + $delay, 'cce_poll_job', [ $post_id, $result['jobId'], 'public' ] );
			$delay += 5;
			$queued++;
		}

		if ( ! empty( $logged_in_cookies ) ) {
			$logged_in = CCE_API::generate( $post_id, get_permalink( $post_id ), 'logged_in', $logged_in_cookies );
			if ( ! is_wp_error( $logged_in ) && ! empty( $logged_in['jobId'] ) ) {
				update_post_meta( $post_id, cce_post_meta_key( '_cce_job_id', 'logged_in' ), sanitize_text_field( $logged_in['jobId'] ) );
				update_post_meta( $post_id, cce_post_meta_key( '_cce_status', 'logged_in' ), 'pending' );
				wp_schedule_single_event( time() + 30 + $delay, 'cce_poll_job', [ $post_id, $logged_in['jobId'], 'logged_in' ] );
				$delay += 5;
				$queued++;
			}
		}
	}

	return $queued;
}

// ── Scan Report Processor ─────────────────────────────────────────────────────

/**
 * Fetch the scan report, store cluster membership for every post, and queue
 * one cluster-level generate job per cluster (staggered to avoid API hammering).
 */
function cce_process_scan_report( string $scan_id, array $logged_in_cookies = [] ): void {
	$report = CCE_API::report( $scan_id );
	if ( is_wp_error( $report ) ) {
		return;
	}

	if ( empty( $report['clusters'] ) && empty( $report['pages'] ) ) {
		return;
	}

	$clusters    = $report['clusters'];
	$pages       = is_array( $report['pages'] ?? null ) ? $report['pages'] : [];
	$cluster_ids = [];
	$delay       = 0;
	$pages_by_cluster = [];

	foreach ( $pages as $page ) {
		$page_cluster_id = $page['clusterId'] ?? '';
		$page_url        = $page['canonicalUrl'] ?? $page['url'] ?? '';

		if ( ! $page_cluster_id || ! $page_url ) {
			continue;
		}

		$pages_by_cluster[ $page_cluster_id ][] = $page_url;
	}

	foreach ( $clusters as $cluster ) {
		$cluster_id     = $cluster['id'] ?? $cluster['clusterId'] ?? '';
		$representative = $cluster['representativeUrl'] ?? '';

		if ( ! $cluster_id || ! $representative ) {
			continue;
		}

		$post_ids = [];
		$page_urls = $pages_by_cluster[ $cluster_id ] ?? [ $representative ];

		foreach ( array_unique( $page_urls ) as $page_url ) {
			$post_id = url_to_postid( $page_url );
			if ( $post_id ) {
				$post_ids[] = $post_id;
			}
		}

		$post_ids = array_values( array_unique( array_map( 'absint', $post_ids ) ) );

		$key           = md5( $cluster_id );
		$cluster_ids[] = $cluster_id;

		// Tag each post with its cluster so the injector can look it up.
		foreach ( $post_ids as $pid ) {
			update_post_meta( (int) $pid, '_cce_cluster_id', $cluster_id );
		}

		// Persist cluster metadata for admin display.
		update_option( 'cce_cluster_meta_' . $key, [
			'id'               => $cluster_id,
			'representativeUrl' => $representative,
			'post_count'       => count( $post_ids ) ?: absint( $cluster['pageCount'] ?? 0 ),
		], false );

		update_option( cce_cluster_option_key( $cluster_id, 'cce_cluster_status_', 'public' ), 'pending', false );

		// Queue a CSS generate job for this cluster.
		$result = CCE_API::generate_cluster( $cluster_id, $representative, 'public' );
		if ( ! is_wp_error( $result ) && ! empty( $result['jobId'] ) ) {
			update_option( 'cce_cluster_job_' . $key, sanitize_text_field( $result['jobId'] ), false );
			wp_schedule_single_event(
				time() + 30 + $delay,
				'cce_poll_cluster',
				[ $cluster_id, $result['jobId'], 'public' ]
			);
			$delay += 5;
		}

		if ( ! empty( $logged_in_cookies ) ) {
			update_option( cce_cluster_option_key( $cluster_id, 'cce_cluster_status_', 'logged_in' ), 'pending', false );
			$logged_in_result = CCE_API::generate_cluster( $cluster_id, $representative, 'logged_in', $logged_in_cookies );
			if ( ! is_wp_error( $logged_in_result ) && ! empty( $logged_in_result['jobId'] ) ) {
				update_option( 'cce_cluster_job_' . $key . '_logged_in', sanitize_text_field( $logged_in_result['jobId'] ), false );
				wp_schedule_single_event(
					time() + 30 + $delay,
					'cce_poll_cluster',
					[ $cluster_id, $logged_in_result['jobId'], 'logged_in' ]
				);
				$delay += 5;
			}
		}
	}

	foreach ( $pages as $page ) {
		$page_strategy = $page['criticalCssStrategy'] ?? '';
		$page_url      = $page['canonicalUrl'] ?? $page['url'] ?? '';

		if ( 'page' !== $page_strategy || ! $page_url ) {
			continue;
		}

		$post_id = url_to_postid( $page_url );
		if ( ! $post_id ) {
			continue;
		}

		$result = CCE_API::generate( $post_id, $page_url, 'public' );
		if ( ! is_wp_error( $result ) && ! empty( $result['jobId'] ) ) {
			update_post_meta( $post_id, '_cce_job_id', sanitize_text_field( $result['jobId'] ) );
			update_post_meta( $post_id, '_cce_status', 'pending' );
			wp_schedule_single_event( time() + 30 + $delay, 'cce_poll_job', [ $post_id, $result['jobId'], 'public' ] );
			$delay += 5;
		}

		if ( ! empty( $logged_in_cookies ) ) {
			$logged_in_result = CCE_API::generate( $post_id, $page_url, 'logged_in', $logged_in_cookies );
			if ( ! is_wp_error( $logged_in_result ) && ! empty( $logged_in_result['jobId'] ) ) {
				update_post_meta( $post_id, cce_post_meta_key( '_cce_job_id', 'logged_in' ), sanitize_text_field( $logged_in_result['jobId'] ) );
				update_post_meta( $post_id, cce_post_meta_key( '_cce_status', 'logged_in' ), 'pending' );
				wp_schedule_single_event( time() + 30 + $delay, 'cce_poll_job', [ $post_id, $logged_in_result['jobId'], 'logged_in' ] );
				$delay += 5;
			}
		}
	}

	// Keep a master list of all cluster IDs for admin display.
	update_option( 'cce_cluster_ids', $cluster_ids, false );
}

// ── Bootstrap ─────────────────────────────────────────────────────────────────

add_action( 'plugins_loaded', function () {
	CCE_Admin::init();
	CCE_Injector::init();
	CCE_Cron::init();
} );
