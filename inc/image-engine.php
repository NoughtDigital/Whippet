<?php
/**
 * Image Engine — Premium Feature
 *
 * Automatically optimises all media library images. Choose format, compression
 * mode, auto-optimise uploads, restore originals, and delete originals to
 * reclaim storage. All processing happens on cloud servers, not your host.
 *
 * @package Whippet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WHIPPET_IE_API_URL' ) ) {
	define( 'WHIPPET_IE_API_URL', 'http://localhost:3100' );
}

class Whippet_Image_Engine {

	private static ?self $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init',                                  [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts',                       [ $this, 'enqueue_admin_assets' ] );
		add_action( 'wp_dashboard_setup',                          [ $this, 'register_dashboard_widget' ] );
		add_filter( 'manage_upload_columns',                       [ $this, 'add_media_column' ] );
		add_action( 'manage_media_custom_column',                  [ $this, 'render_media_column' ], 10, 2 );
		add_action( 'wp_ajax_ie_sync_all',                         [ $this, 'ajax_sync_all' ] );
		add_action( 'wp_ajax_ie_restore_attachment',               [ $this, 'ajax_restore_attachment' ] );
		add_action( 'wp_ajax_ie_delete_original',                  [ $this, 'ajax_delete_original' ] );
		add_action( 'wp_ajax_ie_bulk_restore',                     [ $this, 'ajax_bulk_restore' ] );
		add_action( 'wp_ajax_ie_bulk_delete_originals',            [ $this, 'ajax_bulk_delete_originals' ] );
		add_filter( 'wp_get_attachment_image_attributes',          [ $this, 'filter_image_attributes' ], 20, 2 );
		add_filter( 'the_content',                                 [ $this, 'filter_content_images' ], 20 );
		add_action( 'rest_api_init',                               [ $this, 'register_webhook_endpoint' ] );
	}

	// =========================================================================
	// Config helpers
	// =========================================================================

	private function api_base(): string {
		return rtrim( WHIPPET_IE_API_URL, '/' ) . '/api/v1/wp';
	}

	private function api_key(): string {
		return (string) get_option( 'ie_api_key', '' );
	}

	public function is_configured(): bool {
		return '' !== $this->api_key();
	}

	private function preset(): string {
		return (string) get_option( 'ie_preset', 'balanced' );
	}

	private function delivery_format(): string {
		return (string) get_option( 'ie_delivery_format', 'webp' );
	}

	private function format_filter(): string {
		return (string) get_option( 'ie_format_filter', '' );
	}

	private function lossless(): string {
		return (string) get_option( 'ie_lossless', '' );
	}

	public function auto_optimize(): bool {
		return (bool) get_option( 'ie_auto_optimize', true );
	}

	/**
	 * Returns the webhook secret, generating and persisting one automatically
	 * if it doesn't exist yet. Users never need to create or copy this value —
	 * it is sent to Image Engine as callback_secret on every upload request so
	 * both sides always stay in sync.
	 */
	private function webhook_secret(): string {
		$secret = (string) get_option( 'ie_webhook_secret', '' );
		if ( empty( $secret ) ) {
			$secret = wp_generate_password( 32, false );
			update_option( 'ie_webhook_secret', $secret );
		}
		return $secret;
	}

	private function clear_stats_cache(): void {
		delete_transient( 'ie_dashboard_stats' );
	}

	/**
	 * @param array<int> $ids
	 */
	private function set_last_run_attachment_ids( array $ids ): void {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		update_option( 'ie_last_run_attachment_ids', $ids, false );
	}

	private function clear_last_run_attachment_ids(): void {
		update_option( 'ie_last_run_attachment_ids', [], false );
	}

	// =========================================================================
	// Settings registration (WP options only — UI lives in the Premium tab)
	// =========================================================================

	public function register_settings(): void {
		$options = [
			'ie_api_url'         => 'sanitize_text_field',
			'ie_api_key'         => 'sanitize_text_field',
			'ie_preset'          => 'sanitize_text_field',
			'ie_delivery_format' => 'sanitize_text_field',
			'ie_format_filter'   => 'sanitize_text_field',
			'ie_lossless'        => 'sanitize_text_field',
			'ie_auto_optimize'   => 'absint',
		];
		foreach ( $options as $key => $sanitizer ) {
			register_setting( 'image_engine', $key, [ 'sanitize_callback' => $sanitizer ] );
		}
	}

	// =========================================================================
	// Enqueue inline styles for the media library column
	// =========================================================================

	public function enqueue_admin_assets( string $hook ): void {
		if ( 'upload.php' !== $hook ) {
			return;
		}
		wp_add_inline_style( 'list-tables', '
			.ie-status { white-space: nowrap; }
			.ie-action-link { font-size: 11px; display: block; color: #999; text-decoration: none; margin-top: 2px; }
			.ie-action-link:hover { color: #d63638; text-decoration: underline; }
		' );
	}

	// =========================================================================
	// HTTP helpers
	// =========================================================================

	/** @return array<string,string> */
	private function auth_headers(): array {
		$api_key = $this->api_key();
		if ( '' === $api_key ) {
			return [];
		}
		return [ 'Authorization' => 'Bearer ' . $api_key ];
	}

	/** @return array<string,mixed>|WP_Error */
	private function http_get( string $path ): array|WP_Error {
		return $this->parse_response(
			wp_remote_get( $this->api_base() . $path, [
				'headers' => $this->auth_headers(),
				'timeout' => 15,
			] )
		);
	}

	/** @return array<string,mixed>|WP_Error */
	private function http_post( string $path ): array|WP_Error {
		return $this->parse_response(
			wp_remote_post( $this->api_base() . $path, [
				'headers' => $this->auth_headers(),
				'timeout' => 15,
			] )
		);
	}

	/** @return array<string,mixed>|WP_Error */
	private function http_post_json( string $path, array $body ): array|WP_Error {
		$headers                 = $this->auth_headers();
		$headers['Content-Type'] = 'application/json';
		return $this->parse_response(
			wp_remote_post( $this->api_base() . $path, [
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
				'timeout' => 15,
			] )
		);
	}

	/** @return array<string,mixed>|WP_Error */
	private function http_delete( string $path ): array|WP_Error {
		return $this->parse_response(
			wp_remote_request( $this->api_base() . $path, [
				'method'  => 'DELETE',
				'headers' => $this->auth_headers(),
				'timeout' => 15,
			] )
		);
	}

	/**
	 * @param array<string, string|array{path:string,mime:string}> $fields
	 * @return array<string,mixed>|WP_Error
	 */
	private function http_post_multipart( string $path, array $fields ): array|WP_Error {
		if ( ! function_exists( 'curl_init' ) || ! function_exists( 'curl_file_create' ) ) {
			return new WP_Error( 'ie_api', 'cURL file uploads are not available on this server.' );
		}

		$post_fields = [];
		foreach ( $fields as $name => $value ) {
			if ( '' === $value || [] === $value ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$post_fields[ $name ] = curl_file_create( $value['path'], $value['mime'], basename( $value['path'] ) );
			} else {
				$post_fields[ $name ] = $value;
			}
		}

		$ch = curl_init( $this->api_base() . $path );
		if ( false === $ch ) {
			return new WP_Error( 'ie_api', 'Failed to initialise cURL.' );
		}

		$curl_headers = [];
		foreach ( $this->auth_headers() as $header_name => $header_value ) {
			$curl_headers[] = $header_name . ': ' . $header_value;
		}

		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_fields );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 60 );
		if ( $curl_headers ) {
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $curl_headers );
		}

		$raw = curl_exec( $ch );
		if ( false === $raw ) {
			$error = curl_error( $ch );
			curl_close( $ch );
			return new WP_Error( 'ie_api', $error ?: 'Multipart upload failed.' );
		}

		$code = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
		curl_close( $ch );

		$body = json_decode( $raw, true ) ?? [];
		if ( $code >= 400 ) {
			$detail = $body['error'] ?? $body['message'] ?? ( $raw ? substr( $raw, 0, 200 ) : "HTTP {$code}" );
			return new WP_Error( 'ie_api', "HTTP {$code}: {$detail}", [ 'status' => $code ] );
		}

		return $body;
	}

	/** @return array<string,mixed>|WP_Error */
	private function parse_response( array|WP_Error $response ): array|WP_Error {
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code    = (int) wp_remote_retrieve_response_code( $response );
		$raw     = wp_remote_retrieve_body( $response );
		$body    = json_decode( $raw, true ) ?? [];
		if ( $code >= 400 ) {
			$detail = $body['error'] ?? $body['message'] ?? ( $raw ? substr( $raw, 0, 200 ) : "HTTP {$code}" );
			return new WP_Error( 'ie_api', "HTTP {$code}: {$detail}", [ 'status' => $code ] );
		}
		return $body;
	}

	// =========================================================================
	// Core: upload one attachment to Image Engine
	// =========================================================================

	public function on_attachment_added( int $attachment_id ): void {
		if ( ! $this->is_configured() || ! $this->auto_optimize() ) {
			return;
		}
		$this->upload_attachment( $attachment_id );
	}

	public function upload_attachment( int $attachment_id ): void {
		$mime = (string) get_post_mime_type( $attachment_id );
		if ( ! str_starts_with( $mime, 'image/' ) ) {
			return;
		}
		$file_path = (string) get_attached_file( $attachment_id );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return;
		}
		$fields = [
			'file'             => [ 'path' => $file_path, 'mime' => $mime ],
			'wp_attachment_id' => (string) $attachment_id,
			'wp_sizes'         => 'defaults',
			'preset'           => $this->preset(),
			'callback_url'     => rest_url( 'image-engine/v1/webhook' ),
			'callback_secret'  => $this->webhook_secret(),
		];
		if ( '' !== $this->format_filter() ) {
			$fields['format_filter'] = $this->format_filter();
		}
		if ( '' !== $this->lossless() ) {
			$fields['lossless'] = $this->lossless();
		}
		$result = $this->http_post_multipart( '/images', $fields );
		if ( is_wp_error( $result ) ) {
			update_post_meta( $attachment_id, '_ie_status', 'error' );
			update_post_meta( $attachment_id, '_ie_error', $result->get_error_message() );
			return;
		}
		update_post_meta( $attachment_id, '_ie_id', $result['id'] ?? '' );
		update_post_meta( $attachment_id, '_ie_status', 'queued' );
		delete_post_meta( $attachment_id, '_ie_error' );
		$this->clear_stats_cache();
	}

	// =========================================================================
	// Restore: reset to original without re-queueing
	// =========================================================================

	public function restore_attachment( int $attachment_id ): true|WP_Error {
		$result = $this->http_post( "/images/{$attachment_id}/restore" );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$file = get_attached_file( $attachment_id );
		if ( $file && file_exists( $file ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$meta = wp_generate_attachment_metadata( $attachment_id, $file );
			if ( ! is_wp_error( $meta ) && ! empty( $meta ) ) {
				wp_update_attachment_metadata( $attachment_id, $meta );
			}
		}

		update_post_meta( $attachment_id, '_ie_status', 'pending' );
		delete_post_meta( $attachment_id, '_ie_error' );
		delete_post_meta( $attachment_id, '_ie_original_deleted' );
		$this->clear_last_run_attachment_ids();
		foreach ( [ 'avif', 'webp', 'jpeg', 'png' ] as $fmt ) {
			delete_transient( "ie_srcset_{$attachment_id}_{$fmt}" );
		}
		$this->clear_stats_cache();
		return true;
	}

	// =========================================================================
	// Delete original: keep variants, free disk space
	// =========================================================================

	public function delete_original( int $attachment_id ): true|WP_Error {
		$result = $this->http_delete( "/images/{$attachment_id}/original" );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		update_post_meta( $attachment_id, '_ie_original_deleted', '1' );
		$this->clear_stats_cache();
		return true;
	}

	// =========================================================================
	// Webhook — Image Engine calls back when processing completes
	// =========================================================================

	public function register_webhook_endpoint(): void {
		register_rest_route( 'image-engine/v1', '/webhook', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'handle_webhook' ],
			'permission_callback' => '__return_true',
		] );
	}

	public function handle_webhook( WP_REST_Request $request ): WP_REST_Response {
		$secret = $this->webhook_secret();
		if ( ! empty( $secret ) ) {
			$sig      = (string) $request->get_header( 'X-Image-Engine-Signature' );
			$expected = 'sha256=' . hash_hmac( 'sha256', $request->get_body(), $secret );
			if ( ! hash_equals( $expected, $sig ) ) {
				return new WP_REST_Response( [ 'error' => 'Invalid signature' ], 403 );
			}
		}
		$payload = (array) $request->get_json_params();
		$wp_id   = (int) ( $payload['wp_attachment_id'] ?? 0 );
		if ( ! $wp_id ) {
			return new WP_REST_Response( [ 'error' => 'Missing wp_attachment_id' ], 400 );
		}
		$this->pull_attachment_meta( $wp_id );
		return new WP_REST_Response( [ 'ok' => true ], 200 );
	}

	private function pull_attachment_meta( int $attachment_id ): void {
		$meta = $this->http_get( "/images/{$attachment_id}/attachment-meta" );
		if ( is_wp_error( $meta ) ) {
			update_post_meta( $attachment_id, '_ie_status', 'error' );
			update_post_meta( $attachment_id, '_ie_error', $meta->get_error_message() );
			return;
		}
		update_post_meta( $attachment_id, '_wp_attachment_metadata', $meta );
		update_post_meta( $attachment_id, '_ie_status', 'completed' );
		delete_post_meta( $attachment_id, '_ie_error' );
		$this->clear_stats_cache();
	}

	// =========================================================================
	// Frontend: inject optimised srcset
	// =========================================================================

	public function filter_image_attributes( array $attr, WP_Post $attachment ): array {
		if ( ! $this->is_configured() ) {
			return $attr;
		}
		if ( 'completed' !== get_post_meta( $attachment->ID, '_ie_status', true ) ) {
			return $attr;
		}
		$data = $this->get_srcset_cached( $attachment->ID );
		if ( is_wp_error( $data ) || empty( $data ) ) {
			return $attr;
		}
		if ( ! empty( $data['srcset'] ) ) $attr['srcset'] = $data['srcset'];
		if ( ! empty( $data['src'] ) )    $attr['src']    = $data['src'];
		if ( ! empty( $data['width'] ) )  $attr['width']  = $data['width'];
		if ( ! empty( $data['height'] ) ) $attr['height'] = $data['height'];
		return $attr;
	}

	public function filter_content_images( string $content ): string {
		if ( ! $this->is_configured() ) {
			return $content;
		}
		return preg_replace_callback(
			'/<img[^>]+class="[^"]*wp-image-(\d+)[^"]*"[^>]*>/i',
			function ( array $m ): string {
				$id = (int) $m[1];
				if ( 'completed' !== get_post_meta( $id, '_ie_status', true ) ) {
					return $m[0];
				}
				$data = $this->get_srcset_cached( $id );
				if ( is_wp_error( $data ) || empty( $data ) ) {
					return $m[0];
				}
				$tag = $m[0];
				if ( ! empty( $data['srcset'] ) ) {
					$repl = 'srcset="' . esc_attr( $data['srcset'] ) . '"';
					$tag  = preg_match( '/srcset="[^"]*"/', $tag )
						? preg_replace( '/srcset="[^"]*"/', $repl, $tag )
						: str_replace( '<img', '<img ' . $repl, $tag );
				}
				if ( ! empty( $data['src'] ) ) {
					$tag = preg_replace( '/src="[^"]*"/', 'src="' . esc_attr( $data['src'] ) . '"', $tag );
				}
				return $tag;
			},
			$content
		) ?? $content;
	}

	/** @return array<string,mixed>|WP_Error */
	private function get_srcset_cached( int $attachment_id ): array|WP_Error {
		$preferred_format = $this->delivery_format();
		$cache_key        = "ie_srcset_{$attachment_id}_{$preferred_format}";
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (array) $cached;
		}

		$formats = array_values( array_unique( array_filter( [
			$preferred_format,
			'avif',
			'webp',
			'jpeg',
			'png',
		] ) ) );

		$last_error = null;
		foreach ( $formats as $format ) {
			$data = $this->http_get( "/images/{$attachment_id}/srcset?format={$format}" );
			if ( ! is_wp_error( $data ) ) {
				set_transient( $cache_key, $data, HOUR_IN_SECONDS );
				return $data;
			}

			$status = (int) ( $data->get_error_data( 'ie_api' )['status'] ?? 0 );
			if ( 409 !== $status ) {
				return $data;
			}
			$last_error = $data;
		}

		return $last_error ?: [];
	}

	// =========================================================================
	// AJAX: bulk sync (Optimise Images button)
	// =========================================================================

	public function ajax_sync_all(): void {
		check_ajax_referer( 'ie_sync_all' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorised.' ] );
		}
		if ( ! $this->is_configured() ) {
			wp_send_json_error( [ 'message' => 'No API key configured. Enter your Image Engine API key and save settings first.' ] );
		}
		$ids = get_posts( [
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		] );
		$ids_to_process = [];
		$skipped        = 0;
		foreach ( (array) $ids as $id ) {
			$status = get_post_meta( (int) $id, '_ie_status', true );
			if ( in_array( $status, [ 'queued', 'completed' ], true ) ) {
				$skipped++;
				continue;
			}
			$ids_to_process[] = (int) $id;
		}

		$this->set_last_run_attachment_ids( $ids_to_process );

		$queued = $failed = 0;
		$errors = [];
		foreach ( $ids_to_process as $id ) {
			$this->upload_attachment( $id );
			if ( 'queued' === get_post_meta( (int) $id, '_ie_status', true ) ) {
				$queued++;
			} else {
				$failed++;
				$err = (string) get_post_meta( (int) $id, '_ie_error', true );
				if ( $err && ! in_array( $err, $errors, true ) ) {
					$errors[] = $err;
				}
			}
		}
		$message = "{$queued} queued, {$skipped} skipped, {$failed} failed.";
		if ( $errors ) {
			$message .= ' Error: ' . implode( '; ', array_slice( $errors, 0, 3 ) );
		}
		wp_send_json_success( [ 'message' => $message ] );
	}

	// =========================================================================
	// AJAX: per-attachment restore
	// =========================================================================

	public function ajax_restore_attachment(): void {
		check_ajax_referer( 'ie_attachment_action' );
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorised.' ] );
		}
		$id = (int) ( $_POST['attachment_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => 'Missing attachment_id.' ] );
		}
		$result = $this->restore_attachment( $id );
		is_wp_error( $result )
			? wp_send_json_error( [ 'message' => $result->get_error_message() ] )
			: wp_send_json_success( [ 'message' => 'Attachment restored to the original image.' ] );
	}

	// =========================================================================
	// AJAX: per-attachment delete original
	// =========================================================================

	public function ajax_delete_original(): void {
		check_ajax_referer( 'ie_attachment_action' );
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorised.' ] );
		}
		$id = (int) ( $_POST['attachment_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => 'Missing attachment_id.' ] );
		}
		$result = $this->delete_original( $id );
		is_wp_error( $result )
			? wp_send_json_error( [ 'message' => $result->get_error_message() ] )
			: wp_send_json_success( [ 'message' => 'Original deleted. Optimised variants are still served.' ] );
	}

	// =========================================================================
	// AJAX: bulk restore all (Danger Zone)
	// =========================================================================

	public function ajax_bulk_restore(): void {
		check_ajax_referer( 'ie_bulk_action' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorised.' ] );
		}
		$ids = get_posts( [
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'meta_query'     => [
				[
					'key'   => '_ie_status',
					'value' => 'completed',
				],
				[
					'relation' => 'OR',
					[
						'key'     => '_ie_original_deleted',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => '_ie_original_deleted',
						'value'   => '1',
						'compare' => '!=',
					],
				],
			],
			'fields'         => 'ids',
		] );
		$done = $failed = 0;
		foreach ( (array) $ids as $id ) {
			$result = $this->restore_attachment( (int) $id );
			is_wp_error( $result ) ? $failed++ : $done++;
		}
		wp_send_json_success( [ 'message' => "{$done} restored to originals, {$failed} failed." ] );
	}

	// =========================================================================
	// AJAX: bulk delete originals (Danger Zone)
	// =========================================================================

	public function ajax_bulk_delete_originals(): void {
		check_ajax_referer( 'ie_bulk_action' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorised.' ] );
		}
		$ids = get_posts( [
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'meta_key'       => '_ie_status',
			'meta_value'     => 'completed',
			'fields'         => 'ids',
		] );
		$done = $failed = 0;
		foreach ( (array) $ids as $id ) {
			if ( get_post_meta( (int) $id, '_ie_original_deleted', true ) ) {
				continue;
			}
			$result = $this->delete_original( (int) $id );
			is_wp_error( $result ) ? $failed++ : $done++;
		}
		wp_send_json_success( [ 'message' => "{$done} originals deleted, {$failed} failed." ] );
	}

	// =========================================================================
	// Dashboard widget — savings stats
	// =========================================================================

	public function register_dashboard_widget(): void {
		wp_add_dashboard_widget( 'ie_stats_widget', 'Image Engine', [ $this, 'render_dashboard_widget' ] );
	}

	public function render_dashboard_widget(): void {
		if ( ! $this->is_configured() ) {
			printf(
				'<p>Configure Image Engine in <a href="%s">Tools &rarr; Whippet &rarr; Premium</a>.</p>',
				esc_url( admin_url( 'tools.php?page=whippet#premium' ) )
			);
			return;
		}
		$stats = get_transient( 'ie_dashboard_stats' );
		if ( false === $stats ) {
			$stats = $this->http_get( '/stats' );
			if ( ! is_wp_error( $stats ) ) {
				set_transient( 'ie_dashboard_stats', $stats, 5 * MINUTE_IN_SECONDS );
			}
		}
		if ( is_wp_error( $stats ) ) {
			printf( '<p>Could not load stats: %s</p>', esc_html( $stats->get_error_message() ) );
			return;
		}
		$saved_mb = number_format( ( $stats['total_saved_bytes'] ?? 0 ) / 1048576, 2 );
		?>
		<ul>
			<li><strong><?php esc_html_e( 'Images processed:', 'whippet' ); ?></strong> <?php echo absint( $stats['total_images_processed'] ?? 0 ); ?></li>
			<li><strong><?php esc_html_e( 'Bandwidth saved:', 'whippet' ); ?></strong> <?php echo esc_html( $saved_mb ); ?> MB</li>
			<li><strong><?php esc_html_e( 'Average savings:', 'whippet' ); ?></strong> <?php echo esc_html( $stats['average_savings_percent'] ?? 0 ); ?>%</li>
		</ul>
		<?php
	}

	// =========================================================================
	// Media library column
	// =========================================================================

	public function add_media_column( array $columns ): array {
		$columns['image_engine'] = 'Image Engine';
		return $columns;
	}

	public function render_media_column( string $column, int $post_id ): void {
		if ( 'image_engine' !== $column ) {
			return;
		}
		$status           = (string) get_post_meta( $post_id, '_ie_status', true );
		$error            = (string) get_post_meta( $post_id, '_ie_error', true );
		$original_deleted = (bool) get_post_meta( $post_id, '_ie_original_deleted', true );
		$nonce            = wp_create_nonce( 'ie_attachment_action' );

		$status_html = match ( $status ) {
			'queued'     => '<span style="color:#b45309">&#9203; Queued</span>',
			'processing' => '<span style="color:#1d4ed8">&#9881; Processing</span>',
			'completed'  => '<span style="color:#15803d">&#10003; Optimised</span>',
			'pending'    => '<span style="color:#6b7280">&#9685; Pending</span>',
			'error'      => sprintf( '<span style="color:#b91c1c" title="%s">&#10007; Error</span>', esc_attr( $error ) ),
			default      => '<span style="color:#9ca3af">&mdash;</span>',
		};

		echo '<div class="ie-status">' . $status_html; // phpcs:ignore WordPress.Security.EscapeOutput

		$mime = (string) get_post_mime_type( $post_id );
		if ( $this->is_configured() && str_starts_with( $mime, 'image/' ) ) {
			if ( 'completed' === $status ) {
				if ( ! $original_deleted ) {
					printf(
						'<a href="#" class="ie-action-link ie-restore" data-id="%d" data-nonce="%s">&#8635; Restore original</a>',
						$post_id, esc_attr( $nonce )
					);
					printf(
						'<a href="#" class="ie-action-link ie-delete-original" data-id="%d" data-nonce="%s" style="color:#b91c1c">&#128465; Delete original</a>',
						$post_id, esc_attr( $nonce )
					);
				} else {
					echo '<span style="color:#9ca3af;font-size:11px;display:block">Original deleted</span>';
				}
			} elseif ( in_array( $status, [ '', 'error', 'pending' ], true ) ) {
				printf(
					'<a href="#" class="ie-action-link ie-send" data-id="%d" data-nonce="%s">&#8593; Send to Image Engine</a>',
					$post_id, esc_attr( $nonce )
				);
			}
		}

		echo '</div>';

		static $js_printed = false;
		if ( ! $js_printed ) {
			$js_printed = true;
			?>
			<script>
			(function () {
				if (window._ieHandlersAttached) return;
				window._ieHandlersAttached = true;
				function ieRequest(action, id, nonce, btn, confirmMsg) {
					if (confirmMsg && !confirm(confirmMsg)) return;
					btn.style.opacity = '0.5';
					btn.style.pointerEvents = 'none';
					fetch(window.ajaxurl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: new URLSearchParams({ action, attachment_id: id, _wpnonce: nonce })
					})
					.then(function(r) { return r.json(); })
					.then(function(d) {
						if (d.success) {
							btn.closest('.ie-status').innerHTML = '<em>' + d.data.message + '</em>';
						} else {
							alert(d.data && d.data.message ? d.data.message : 'Something went wrong.');
							btn.style.opacity = '';
							btn.style.pointerEvents = '';
						}
					})
					.catch(function() {
						alert('Request failed.');
						btn.style.opacity = '';
						btn.style.pointerEvents = '';
					});
				}
				document.addEventListener('click', function (e) {
					var btn = e.target.closest('a.ie-action-link');
					if (!btn) return;
					e.preventDefault();
					var id = btn.dataset.id, nonce = btn.dataset.nonce;
					if (btn.classList.contains('ie-restore')) {
						ieRequest('ie_restore_attachment', id, nonce, btn, 'Restore this image to its original file? Optimised variants will be removed.');
					} else if (btn.classList.contains('ie-delete-original')) {
						ieRequest('ie_delete_original', id, nonce, btn, 'Permanently delete the original file? This cannot be undone.');
					} else if (btn.classList.contains('ie-send')) {
						ieRequest('ie_restore_attachment', id, nonce, btn, null);
					}
				});
			})();
			</script>
			<?php
		}
	}

	// =========================================================================
	// Public stats helper (used by the Premium tab)
	// =========================================================================

	/** @return array<string,mixed>|WP_Error */
	public function get_stats(): array|WP_Error {
		$stats = get_transient( 'ie_dashboard_stats' );
		if ( false === $stats ) {
			$stats = $this->http_get( '/stats' );
			if ( ! is_wp_error( $stats ) ) {
				set_transient( 'ie_dashboard_stats', $stats, 5 * MINUTE_IN_SECONDS );
			}
		}
		return is_wp_error( $stats ) ? $stats : (array) $stats;
	}

	/** @return array<string,int>|WP_Error */
	public function get_last_run_stats(): array|WP_Error {
		$ids = (array) get_option( 'ie_last_run_attachment_ids', [] );
		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );

		if ( ! $ids ) {
			return [
				'total_images'     => 0,
				'processed_images' => 0,
				'original_bytes'   => 0,
				'saved_bytes'      => 0,
			];
		}

		$status_map = [];
		foreach ( array_chunk( $ids, 100 ) as $chunk ) {
			$result = $this->http_post_json( '/images/sync', [
				'wp_attachment_ids' => $chunk,
			] );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$status_map = array_merge( $status_map, $result );
		}

		$processed_images = 0;
		$original_bytes   = 0;
		$saved_bytes      = 0;

		foreach ( $ids as $id ) {
			$file      = (string) get_attached_file( $id );
			$file_size = ( $file && file_exists( $file ) ) ? (int) filesize( $file ) : 0;
			$original_bytes += max( 0, $file_size );

			$entry = $status_map[ (string) $id ] ?? null;
			if ( ! is_array( $entry ) ) {
				continue;
			}

			if ( 'completed' === ( $entry['status'] ?? '' ) ) {
				$processed_images++;
			}

			if ( isset( $entry['savings_percent'] ) && $file_size > 0 ) {
				$savings_pct = max( 0, min( 100, (float) $entry['savings_percent'] ) );
				$saved_bytes += (int) round( $file_size * ( $savings_pct / 100 ) );
			}
		}

		return [
			'total_images'     => count( $ids ),
			'processed_images' => $processed_images,
			'original_bytes'   => $original_bytes,
			'saved_bytes'      => $saved_bytes,
		];
	}
}

add_action( 'plugins_loaded', [ 'Whippet_Image_Engine', 'get_instance' ] );
