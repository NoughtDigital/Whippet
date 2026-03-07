<?php
/**
 * Whippet Image Optimizer
 *
 * Features:
 *  - Converts newly uploaded images to WebP on upload
 *  - Serves WebP automatically via .htaccess (Apache) to supporting browsers
 *  - Bulk re-compresses the existing media library via batched AJAX
 *  - Preview mode: shows how many images will be processed before running
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image optimisation class.
 */
class ImageOptimizer {

	/** @var array Plugin options. */
	private $options;

	public function __construct() {
		$this->options = (array) get_option( 'whippet_options', array() );

		// WebP conversion on upload.
		if ( ! empty( $this->options['enable_webp_conversion'] ) && '1' === $this->options['enable_webp_conversion'] ) {
			add_filter( 'wp_generate_attachment_metadata', array( $this, 'generate_webp_on_upload' ), 10, 2 );
		}

		// AJAX handlers (always registered so the buttons work even without WebP enabled).
		add_action( 'wp_ajax_whippet_bulk_compress_preview', array( $this, 'ajax_bulk_compress_preview' ) );
		add_action( 'wp_ajax_whippet_bulk_compress',         array( $this, 'ajax_bulk_compress' ) );

		// Write / remove .htaccess WebP-serve rules when options change.
		add_action( 'update_option_whippet_options', array( $this, 'on_options_updated' ), 10, 2 );
	}

	// -------------------------------------------------------------------------
	// WebP on upload
	// -------------------------------------------------------------------------

	/**
	 * After WordPress generates all image sizes for a new upload, create a
	 * .webp copy of the original file and each generated thumbnail.
	 *
	 * @param array $metadata    Attachment metadata array.
	 * @param int   $attachment_id Attachment post ID.
	 * @return array Unchanged metadata.
	 */
	public function generate_webp_on_upload( $metadata, $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! $this->is_convertible_mime( get_post_mime_type( $attachment_id ) ) ) {
			return $metadata;
		}

		// Convert the full-size original.
		$this->create_webp( $file );

		// Convert every generated thumbnail size.
		if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			$dir = trailingslashit( dirname( $file ) );
			foreach ( $metadata['sizes'] as $size_data ) {
				if ( ! empty( $size_data['file'] ) ) {
					$this->create_webp( $dir . $size_data['file'] );
				}
			}
		}

		return $metadata;
	}

	/**
	 * Create a .webp version of a source image file.
	 *
	 * @param string $source_path Absolute path to the source image.
	 * @return bool True on success, false on failure.
	 */
	public function create_webp( $source_path ) {
		if ( ! file_exists( $source_path ) ) {
			return false;
		}

		$webp_path = $source_path . '.webp';

		$editor = wp_get_image_editor( $source_path );
		if ( is_wp_error( $editor ) ) {
			return false;
		}

		$quality = ! empty( $this->options['webp_quality'] ) ? (int) $this->options['webp_quality'] : 80;
		$editor->set_quality( $quality );

		$result = $editor->save( $webp_path, 'image/webp' );
		return ! is_wp_error( $result );
	}

	// -------------------------------------------------------------------------
	// .htaccess WebP auto-serve rules (Apache)
	// -------------------------------------------------------------------------

	/**
	 * Write .htaccess rules so Apache transparently serves .webp images
	 * to browsers that send Accept: image/webp — zero PHP overhead.
	 */
	public function write_webp_htaccess_rules() {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		$htaccess = get_home_path() . '.htaccess';
		insert_with_markers( $htaccess, 'Whippet WebP', $this->build_webp_htaccess_rules() );
	}

	/**
	 * Remove WebP .htaccess rules.
	 */
	public function remove_webp_htaccess_rules() {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		$htaccess = get_home_path() . '.htaccess';
		insert_with_markers( $htaccess, 'Whippet WebP', array() );
	}

	/**
	 * Build the WebP .htaccess lines.
	 *
	 * @return string[]
	 */
	private function build_webp_htaccess_rules() {
		return array(
			'<IfModule mod_rewrite.c>',
			'RewriteEngine On',
			'',
			'# Serve .webp image when the browser supports it and a .webp file exists.',
			'RewriteCond %{HTTP_ACCEPT} image/webp',
			'RewriteCond %{REQUEST_FILENAME} -f',
			'RewriteCond %{REQUEST_FILENAME}\.webp -f',
			'RewriteRule ^(.+)\.(jpe?g|png)$ $1.$2.webp [T=image/webp,L]',
			'</IfModule>',
			'',
			'<IfModule mod_headers.c>',
			'<FilesMatch "\.(jpe?g|png)\.webp$">',
			'    Header set Content-Type image/webp',
			'    Header append Vary Accept',
			'</FilesMatch>',
			'</IfModule>',
		);
	}

	/**
	 * React to whippet_options being updated: manage WebP .htaccess rules.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $new_value New option value.
	 */
	public function on_options_updated( $old_value, $new_value ) {
		$new_value = (array) $new_value;
		$this->options = $new_value;

		$webp_on = ! empty( $new_value['enable_webp_conversion'] )
			&& '1' === $new_value['enable_webp_conversion'];

		if ( $webp_on ) {
			$this->write_webp_htaccess_rules();
		} else {
			$this->remove_webp_htaccess_rules();
		}
	}

	// -------------------------------------------------------------------------
	// AJAX: preview
	// -------------------------------------------------------------------------

	/**
	 * AJAX: count how many images would be processed (no changes made).
	 */
	public function ajax_bulk_compress_preview() {
		check_ajax_referer( 'whippet_image_optimizer_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'whippet' ) ) );
		}

		$total = $this->count_processable_images();

		wp_send_json_success(
			array(
				'total'   => $total,
				/* translators: %d: image count */
				'message' => sprintf(
					_n(
						'%d image found in the media library.',
						'%d images found in the media library.',
						$total,
						'whippet'
					),
					$total
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// AJAX: bulk compress (batched)
	// -------------------------------------------------------------------------

	/**
	 * AJAX: process a single batch of images.
	 *
	 * Expects POST params:
	 *  - batch (int, 0-based batch index)
	 */
	public function ajax_bulk_compress() {
		check_ajax_referer( 'whippet_image_optimizer_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'whippet' ) ) );
		}

		$batch      = isset( $_POST['batch'] ) ? max( 0, (int) $_POST['batch'] ) : 0;
		$batch_size = 10;
		$offset     = $batch * $batch_size;
		$total      = $this->count_processable_images();
		$ids        = $this->get_image_ids( $offset, $batch_size );

		if ( empty( $ids ) ) {
			wp_send_json_success(
				array(
					'done'    => true,
					'total'   => $total,
					'message' => __( 'All images processed successfully.', 'whippet' ),
				)
			);
		}

		$quality = ! empty( $this->options['image_quality'] ) ? (int) $this->options['image_quality'] : 82;
		$do_webp = ! empty( $this->options['enable_webp_conversion'] ) && '1' === $this->options['enable_webp_conversion'];
		$processed = 0;

		foreach ( $ids as $id ) {
			$file = get_attached_file( $id );
			if ( ! $file || ! file_exists( $file ) ) {
				continue;
			}

			$mime = get_post_mime_type( $id );
			if ( ! $this->is_convertible_mime( $mime ) ) {
				continue;
			}

			$editor = wp_get_image_editor( $file );
			if ( is_wp_error( $editor ) ) {
				continue;
			}

			// Re-save at the configured quality.
			$editor->set_quality( $quality );
			$saved = $editor->save( $file, $mime );

			if ( ! is_wp_error( $saved ) ) {
				// Regenerate all thumbnail sizes at the new quality.
				$meta = wp_generate_attachment_metadata( $id, $file );
				wp_update_attachment_metadata( $id, $meta );

				// Create WebP if enabled.
				if ( $do_webp ) {
					$this->create_webp( $file );

					// Also create WebP for each regenerated thumbnail.
					if ( ! empty( $meta['sizes'] ) ) {
						$dir = trailingslashit( dirname( $file ) );
						foreach ( $meta['sizes'] as $size ) {
							if ( ! empty( $size['file'] ) ) {
								$this->create_webp( $dir . $size['file'] );
							}
						}
					}
				}

				$processed++;
			}
		}

		$done = ( $offset + $batch_size ) >= $total;

		wp_send_json_success(
			array(
				'done'       => $done,
				'processed'  => $processed,
				'total'      => $total,
				'next_batch' => $batch + 1,
				/* translators: 1: number processed so far, 2: total */
				'message'    => sprintf(
					__( 'Processed %1$d of %2$d images…', 'whippet' ),
					min( $offset + $batch_size, $total ),
					$total
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Count all processable image attachments in the media library.
	 *
	 * @return int
	 */
	private function count_processable_images() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var(
			"SELECT COUNT(ID) FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_mime_type LIKE 'image/%'
			AND post_mime_type NOT IN ('image/gif', 'image/svg+xml', 'image/svg')"
		);
	}

	/**
	 * Get a paginated list of image attachment IDs.
	 *
	 * @param int $offset How many rows to skip.
	 * @param int $limit  How many rows to return.
	 * @return int[]
	 */
	private function get_image_ids( $offset = 0, $limit = 10 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
				WHERE post_type = 'attachment'
				AND post_mime_type LIKE 'image/%'
				AND post_mime_type NOT IN ('image/gif', 'image/svg+xml', 'image/svg')
				ORDER BY ID ASC
				LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);
	}

	/**
	 * Returns true for MIME types that can be re-compressed and WebP-converted.
	 *
	 * @param string $mime MIME type.
	 * @return bool
	 */
	private function is_convertible_mime( $mime ) {
		return in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true );
	}
}

new ImageOptimizer();
