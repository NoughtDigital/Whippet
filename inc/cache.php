<?php
/**
 * Whippet Page Cache
 *
 * WP Rocket-level PHP file-based full-page output cache. Features:
 *  - Path-based cache files ({host}/{uri}/index.html) for Apache direct-serve
 *  - Pre-compressed Gzip (.html.gz) and Brotli (.html.br) variants
 *  - Separate mobile cache
 *  - URL and cookie-based exclusions
 *  - WooCommerce-aware bypass
 *  - Sitemap-based cache preloading via WP-Cron
 *  - .htaccess rewrite rules for zero-PHP Apache direct serving
 *  - AJAX cache stats
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * File-based page caching class.
 */
class Cache {

	/** @var string Absolute path to the cache root directory. */
	private $cache_dir;

	/** @var array Plugin options. */
	private $options;

	public function __construct() {
		$this->options   = (array) get_option( 'whippet_options', array() );
		$this->cache_dir = WP_CONTENT_DIR . '/cache/whippet/';

		// Always register AJAX + settings-update hooks regardless of whether cache is on.
		add_action( 'wp_ajax_whippet_clear_cache',    array( $this, 'ajax_clear_cache' ) );
		add_action( 'wp_ajax_whippet_cache_stats',    array( $this, 'ajax_cache_stats' ) );
		add_action( 'wp_ajax_whippet_preload_cache',  array( $this, 'ajax_preload_cache' ) );
		add_action( 'update_option_whippet_options',  array( $this, 'on_options_updated' ), 10, 2 );

		// WP-Cron event handlers.
		add_action( 'whippet_cache_preload_event', array( $this, 'cron_preload_cache' ) );

		if ( empty( $this->options['enable_page_cache'] ) || '1' !== $this->options['enable_page_cache'] ) {
			return;
		}

		// Serve from cache before anything else runs.
		add_action( 'template_redirect', array( $this, 'maybe_serve_cache' ), -99 );

		// Capture rendered HTML and write to disk.
		add_action( 'wp', array( $this, 'maybe_start_buffering' ) );

		// Invalidation.
		add_action( 'save_post',            array( $this, 'invalidate_post_cache' ), 10, 1 );
		add_action( 'deleted_post',         array( $this, 'invalidate_post_cache' ), 10, 1 );
		add_action( 'switch_theme',         array( $this, 'clear_all_cache' ) );
		add_action( 'wp_update_nav_menu',   array( $this, 'clear_all_cache' ) );
		add_action( 'comment_post',         array( $this, 'on_new_comment' ), 10, 2 );
		add_action( 'customize_save_after', array( $this, 'clear_all_cache' ) );
		add_action( 'activated_plugin',     array( $this, 'clear_all_cache' ) );
		add_action( 'deactivated_plugin',   array( $this, 'clear_all_cache' ) );

		// Schedule preload cron if enabled.
		if ( ! empty( $this->options['cache_preload'] ) && '1' === $this->options['cache_preload'] ) {
			if ( ! wp_next_scheduled( 'whippet_cache_preload_event' ) ) {
				wp_schedule_event( time() + 300, 'twicedaily', 'whippet_cache_preload_event' );
			}
		}
	}

	// -------------------------------------------------------------------------
	// Serve
	// -------------------------------------------------------------------------

	/**
	 * If a fresh cache file exists, serve it (with best available encoding) and exit.
	 */
	public function maybe_serve_cache() {
		if ( ! $this->is_cacheable_request() ) {
			return;
		}

		$file = $this->get_cache_file();
		if ( ! file_exists( $file ) ) {
			return;
		}

		$ttl = $this->get_ttl();
		if ( ( time() - filemtime( $file ) ) > $ttl ) {
			$this->delete_cache_file_group( $file );
			return;
		}

		$accept = isset( $_SERVER['HTTP_ACCEPT_ENCODING'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_ENCODING'] ) )
			: '';

		header( 'X-Whippet-Cache: HIT' );
		header( 'Vary: Accept-Encoding' );

		// Brotli — best compression, try first.
		if ( strpos( $accept, 'br' ) !== false && file_exists( $file . '.br' ) ) {
			header( 'Content-Encoding: br' );
			header( 'Content-Type: text/html; charset=UTF-8' );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			echo file_get_contents( $file . '.br' );
			exit;
		}

		// Gzip.
		if ( strpos( $accept, 'gzip' ) !== false && file_exists( $file . '.gz' ) ) {
			header( 'Content-Encoding: gzip' );
			header( 'Content-Type: text/html; charset=UTF-8' );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			echo file_get_contents( $file . '.gz' );
			exit;
		}

		// Plain.
		header( 'Content-Type: text/html; charset=UTF-8' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		echo file_get_contents( $file );
		exit;
	}

	// -------------------------------------------------------------------------
	// Write
	// -------------------------------------------------------------------------

	/**
	 * Start output buffering to capture the rendered page.
	 */
	public function maybe_start_buffering() {
		if ( ! $this->is_cacheable_request() ) {
			return;
		}
		ob_start( array( $this, 'write_cache' ) );
	}

	/**
	 * Output-buffer callback — save HTML + compressed variants to disk.
	 *
	 * @param string $html Full rendered HTML.
	 * @return string Unchanged HTML (passed through to the browser).
	 */
	public function write_cache( $html ) {
		if ( empty( trim( $html ) ) ) {
			return $html;
		}

		$file = $this->get_cache_file();
		$dir  = dirname( $file );

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		// Append a cache timestamp comment.
		$stamp = "\n<!-- Cached by Whippet on " . gmdate( 'Y-m-d H:i:s' ) . ' UTC -->';
		$html .= $stamp;

		// 1. Plain HTML.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		file_put_contents( $file, $html, LOCK_EX );

		// 2. Gzip — maximum compression (level 9).
		$gz = gzencode( $html, 9 );
		if ( false !== $gz ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
			file_put_contents( $file . '.gz', $gz, LOCK_EX );
		}

		// 3. Brotli — requires the php-brotli PECL extension (level 11 = max).
		if ( function_exists( 'brotli_compress' ) ) {
			$br = brotli_compress( $html, 11 );
			if ( false !== $br ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
				file_put_contents( $file . '.br', $br, LOCK_EX );
			}
		}

		return $html;
	}

	// -------------------------------------------------------------------------
	// Invalidation
	// -------------------------------------------------------------------------

	/**
	 * Delete the cached file(s) for a specific post when it is saved or deleted.
	 *
	 * @param int $post_id Post ID.
	 */
	public function invalidate_post_cache( $post_id ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$permalink = get_permalink( $post_id );
		if ( $permalink ) {
			$this->delete_cache_file_group( $this->url_to_cache_file( $permalink ) );
		}

		// Always clear the home/front page too (post counts, recent posts widgets, etc.).
		$this->delete_cache_file_group( $this->url_to_cache_file( home_url( '/' ) ) );
	}

	/**
	 * On a new approved comment, bust the post's cache.
	 *
	 * @param int $comment_id         Comment ID.
	 * @param int $comment_approved   Approval status (1 = approved).
	 */
	public function on_new_comment( $comment_id, $comment_approved ) {
		if ( 1 === (int) $comment_approved ) {
			$comment = get_comment( $comment_id );
			if ( $comment && $comment->comment_post_ID ) {
				$this->invalidate_post_cache( $comment->comment_post_ID );
			}
		}
	}

	/**
	 * Delete every cached file in the cache directory (recursive).
	 */
	public function clear_all_cache() {
		$this->recursive_rmdir( $this->cache_dir, false );
	}

	// -------------------------------------------------------------------------
	// Cache preloading
	// -------------------------------------------------------------------------

	/**
	 * WP-Cron callback: crawl every public URL to warm up the cache.
	 */
	public function cron_preload_cache() {
		foreach ( $this->get_all_public_urls() as $url ) {
			wp_remote_get(
				$url,
				array(
					'timeout'    => 15,
					'blocking'   => false,
					'user-agent' => 'WhippetCachePreloader/1.0 (compatible; WordPress)',
					'sslverify'  => false,
				)
			);
		}
	}

	/**
	 * AJAX: trigger a background preload (fires cron immediately).
	 */
	public function ajax_preload_cache() {
		check_ajax_referer( 'whippet_cache_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'whippet' ) ) );
		}
		$this->clear_all_cache();
		wp_schedule_single_event( time(), 'whippet_cache_preload_event' );
		wp_send_json_success( array( 'message' => __( 'Cache cleared. Preload started in the background.', 'whippet' ) ) );
	}

	/**
	 * Collect all published, publicly accessible URLs.
	 *
	 * @return string[]
	 */
	private function get_all_public_urls() {
		$urls = array( trailingslashit( home_url( '/' ) ) );

		$post_types = get_post_types( array( 'public' => true ), 'names' );
		foreach ( $post_types as $pt ) {
			$ids = get_posts(
				array(
					'post_type'      => $pt,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);
			foreach ( $ids as $id ) {
				$url = get_permalink( $id );
				if ( $url ) {
					$urls[] = $url;
				}
			}
		}

		// Blog page if different from front page.
		$blog_page_id = (int) get_option( 'page_for_posts' );
		if ( $blog_page_id ) {
			$urls[] = get_permalink( $blog_page_id );
		}

		return array_values( array_unique( $urls ) );
	}

	// -------------------------------------------------------------------------
	// AJAX handlers
	// -------------------------------------------------------------------------

	/**
	 * AJAX: clear all cache and respond with JSON.
	 */
	public function ajax_clear_cache() {
		check_ajax_referer( 'whippet_cache_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'whippet' ) ) );
		}
		$this->clear_all_cache();
		wp_send_json_success( array( 'message' => __( 'Cache cleared successfully.', 'whippet' ) ) );
	}

	/**
	 * AJAX: return cache stats (file count + disk size).
	 */
	public function ajax_cache_stats() {
		check_ajax_referer( 'whippet_cache_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'whippet' ) ) );
		}
		wp_send_json_success( $this->get_cache_stats() );
	}

	/**
	 * Count cached HTML files and sum their total size.
	 *
	 * @return array{count: int, size: string, size_bytes: int}
	 */
	public function get_cache_stats() {
		$count = 0;
		$bytes = 0;

		if ( is_dir( $this->cache_dir ) ) {
			$iter = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $this->cache_dir, \RecursiveDirectoryIterator::SKIP_DOTS )
			);
			foreach ( $iter as $f ) {
				if ( $f->isFile() && 'html' === $f->getExtension() ) {
					$count++;
					$bytes += $f->getSize();
				}
			}
		}

		return array(
			'count'      => $count,
			'size'       => size_format( $bytes ),
			'size_bytes' => $bytes,
		);
	}

	// -------------------------------------------------------------------------
	// .htaccess management — Apache direct-serve (zero PHP overhead)
	// -------------------------------------------------------------------------

	/**
	 * Write Whippet Cache rewrite rules into the root .htaccess.
	 * When active, Apache serves cached files directly, bypassing PHP entirely.
	 */
	public function write_htaccess_rules() {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		$htaccess = get_home_path() . '.htaccess';
		insert_with_markers( $htaccess, 'Whippet Cache', $this->build_htaccess_rules() );
	}

	/**
	 * Remove Whippet Cache rules from root .htaccess.
	 */
	public function remove_htaccess_rules() {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		$htaccess = get_home_path() . '.htaccess';
		insert_with_markers( $htaccess, 'Whippet Cache', array() );
	}

	/**
	 * Build the array of .htaccess lines.
	 *
	 * @return string[]
	 */
	private function build_htaccess_rules() {
		$rel = ltrim( str_replace( ABSPATH, '', $this->cache_dir ), '/' );

		$lines = array(
			'<IfModule mod_rewrite.c>',
			'RewriteEngine On',
			'',
			'# Bypass: logged-in users.',
			'RewriteCond %{HTTP_COOKIE} wordpress_logged_in [NC]',
			'RewriteRule . - [L]',
			'',
			'# Bypass: POST requests.',
			'RewriteCond %{REQUEST_METHOD} POST',
			'RewriteRule . - [L]',
			'',
			'# Bypass: query string present.',
			'RewriteCond %{QUERY_STRING} !^$',
			'RewriteRule . - [L]',
			'',
			'# Bypass: WooCommerce cart/checkout cookies.',
			'RewriteCond %{HTTP_COOKIE} woocommerce_cart_hash [NC]',
			'RewriteRule . - [L]',
			'RewriteCond %{HTTP_COOKIE} woocommerce_items_in_cart [NC]',
			'RewriteRule . - [L]',
		);

		// User-defined cookie exclusions.
		$raw_cookies = ! empty( $this->options['cache_exclude_cookies'] )
			? $this->options['cache_exclude_cookies'] : '';
		if ( $raw_cookies ) {
			$cookies = array_filter( array_map( 'trim', explode( "\n", $raw_cookies ) ) );
			if ( $cookies ) {
				$lines[] = '';
				$lines[] = '# Bypass: custom cookie exclusions.';
				foreach ( $cookies as $c ) {
					$lines[] = 'RewriteCond %{HTTP_COOKIE} ' . preg_quote( $c, '/' ) . ' [NC]';
					$lines[] = 'RewriteRule . - [L]';
				}
			}
		}

		// User-defined URL exclusions.
		$raw_urls = ! empty( $this->options['cache_exclude_urls'] )
			? $this->options['cache_exclude_urls'] : '';
		if ( $raw_urls ) {
			$urls = array_filter( array_map( 'trim', explode( "\n", $raw_urls ) ) );
			if ( $urls ) {
				$lines[] = '';
				$lines[] = '# Bypass: custom URL exclusions.';
				foreach ( $urls as $u ) {
					$lines[] = 'RewriteCond %{REQUEST_URI} ' . preg_quote( $u, '/' ) . ' [NC]';
					$lines[] = 'RewriteRule . - [L]';
				}
			}
		}

		$lines = array_merge( $lines, array(
			'',
			'# Serve Brotli-compressed cache.',
			'RewriteCond %{HTTP:Accept-Encoding} br',
			'RewriteCond %{DOCUMENT_ROOT}/' . $rel . '%{HTTP_HOST}%{REQUEST_URI}index.html.br -f',
			'RewriteRule .* /' . $rel . '%{HTTP_HOST}%{REQUEST_URI}index.html.br [L,T=text/html]',
			'',
			'# Serve Gzip-compressed cache.',
			'RewriteCond %{HTTP:Accept-Encoding} gzip',
			'RewriteCond %{DOCUMENT_ROOT}/' . $rel . '%{HTTP_HOST}%{REQUEST_URI}index.html.gz -f',
			'RewriteRule .* /' . $rel . '%{HTTP_HOST}%{REQUEST_URI}index.html.gz [L,T=text/html]',
			'',
			'# Serve plain HTML cache.',
			'RewriteCond %{DOCUMENT_ROOT}/' . $rel . '%{HTTP_HOST}%{REQUEST_URI}index.html -f',
			'RewriteRule .* /' . $rel . '%{HTTP_HOST}%{REQUEST_URI}index.html [L,T=text/html]',
			'</IfModule>',
			'',
			'# Correct Content-Encoding headers for pre-compressed cached files.',
			'<IfModule mod_headers.c>',
			'<FilesMatch "\.html\.gz$">',
			'    Header set Content-Encoding gzip',
			'    Header set Content-Type "text/html; charset=UTF-8"',
			'    Header append Vary Accept-Encoding',
			'</FilesMatch>',
			'<FilesMatch "\.html\.br$">',
			'    Header set Content-Encoding br',
			'    Header set Content-Type "text/html; charset=UTF-8"',
			'    Header append Vary Accept-Encoding',
			'</FilesMatch>',
			'</IfModule>',
		) );

		return $lines;
	}

	/**
	 * React to whippet_options being updated: manage .htaccess rules and cron.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $new_value New option value.
	 */
	public function on_options_updated( $old_value, $new_value ) {
		$new_value = (array) $new_value;

		// .htaccess rules.
		$htaccess_on = ! empty( $new_value['cache_htaccess'] ) && '1' === $new_value['cache_htaccess'];
		$cache_on    = ! empty( $new_value['enable_page_cache'] ) && '1' === $new_value['enable_page_cache'];

		if ( $htaccess_on && $cache_on ) {
			$this->write_htaccess_rules();
		} else {
			$this->remove_htaccess_rules();
		}

		// Preload cron.
		$preload_on = ! empty( $new_value['cache_preload'] ) && '1' === $new_value['cache_preload'];
		if ( $preload_on && $cache_on ) {
			if ( ! wp_next_scheduled( 'whippet_cache_preload_event' ) ) {
				wp_schedule_event( time() + 300, 'twicedaily', 'whippet_cache_preload_event' );
			}
		} else {
			wp_clear_scheduled_hook( 'whippet_cache_preload_event' );
		}

		// Clear stale cache whenever settings change.
		$this->clear_all_cache();
	}

	// -------------------------------------------------------------------------
	// Cacheable request check
	// -------------------------------------------------------------------------

	/**
	 * Determine whether the current request may be cached / served from cache.
	 *
	 * @return bool
	 */
	private function is_cacheable_request() {
		// GET only.
		if ( isset( $_SERVER['REQUEST_METHOD'] )
			&& 'GET' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) ) {
			return false;
		}

		// Not admin area.
		if ( is_admin() ) {
			return false;
		}

		// Not logged-in users.
		if ( is_user_logged_in() ) {
			return false;
		}

		// No query string (excludes ?preview, ?p=, paginated search, etc.).
		if ( ! empty( $_SERVER['QUERY_STRING'] )
			&& '' !== sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) ) {
			return false;
		}

		// Cookie-based exclusions.
		$raw_cookies = ! empty( $this->options['cache_exclude_cookies'] )
			? $this->options['cache_exclude_cookies'] : '';
		if ( $raw_cookies ) {
			foreach ( array_filter( array_map( 'trim', explode( "\n", $raw_cookies ) ) ) as $c ) {
				if ( isset( $_COOKIE[ $c ] ) ) {
					return false;
				}
			}
		}

		// WooCommerce cart/checkout cookies.
		if ( ! empty( $_COOKIE ) ) {
			foreach ( array_keys( (array) $_COOKIE ) as $name ) {
				if ( 0 === strpos( $name, 'woocommerce_cart_hash' )
					|| 0 === strpos( $name, 'woocommerce_items_in_cart' ) ) {
					return false;
				}
			}
		}

		// URL-based exclusions.
		$raw_urls = ! empty( $this->options['cache_exclude_urls'] )
			? $this->options['cache_exclude_urls'] : '';
		if ( $raw_urls ) {
			$uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) );
			foreach ( array_filter( array_map( 'trim', explode( "\n", $raw_urls ) ) ) as $pattern ) {
				if ( false !== strpos( $uri, $pattern ) ) {
					return false;
				}
			}
		}

		// WooCommerce dynamic pages.
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return false;
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return false;
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return false;
		}

		// Skip 404 and search.
		if ( is_404() || is_search() ) {
			return false;
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// File path helpers
	// -------------------------------------------------------------------------

	/**
	 * Build the cache file path for the current HTTP request.
	 *
	 * @return string Absolute path to the expected cache file.
	 */
	private function get_cache_file() {
		$is_mobile = ! empty( $this->options['cache_mobile_separate'] )
			&& '1' === $this->options['cache_mobile_separate']
			&& wp_is_mobile();

		$host = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );
		$uri  = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) );
		$path = $this->normalise_uri( $uri );
		$name = $is_mobile ? 'index-mobile.html' : 'index.html';

		return $this->cache_dir . $host . $path . $name;
	}

	/**
	 * Convert any public URL to its cache file path.
	 *
	 * @param string $url Full URL.
	 * @return string Absolute path to the expected cache file.
	 */
	private function url_to_cache_file( $url ) {
		$parsed = wp_parse_url( $url );
		$host   = $parsed['host'] ?? '';
		$path   = $this->normalise_uri( $parsed['path'] ?? '/' );
		return $this->cache_dir . $host . $path . 'index.html';
	}

	/**
	 * Normalise a URI path to a consistent trailing-slash form.
	 * Result always starts and ends with '/', e.g. '/blog/post/' or '/'.
	 *
	 * @param string $uri Raw URI path.
	 * @return string
	 */
	private function normalise_uri( $uri ) {
		$path = '/' . trim( $uri, '/' ) . '/';
		// Collapse any double-slashes (handles root '/' → '//').
		return preg_replace( '#/+#', '/', $path );
	}

	/**
	 * Delete all cache variants for a single canonical HTML file.
	 * Removes .html, .html.gz, .html.br, and the mobile equivalents.
	 *
	 * @param string $file Absolute path to the plain .html cache file.
	 */
	private function delete_cache_file_group( $file ) {
		$variants = array( '', '.gz', '.br' );

		foreach ( $variants as $ext ) {
			if ( file_exists( $file . $ext ) ) {
				@unlink( $file . $ext ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			}
		}

		// Mobile variant.
		$mobile = str_replace( 'index.html', 'index-mobile.html', $file );
		foreach ( $variants as $ext ) {
			if ( file_exists( $mobile . $ext ) ) {
				@unlink( $mobile . $ext ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			}
		}
	}

	/**
	 * Get the configured TTL in seconds.
	 *
	 * @return int
	 */
	private function get_ttl() {
		$map = array(
			'3600'   => 3600,
			'21600'  => 21600,
			'43200'  => 43200,
			'86400'  => 86400,
			'604800' => 604800,
		);
		$key = $this->options['page_cache_ttl'] ?? '86400';
		return $map[ $key ] ?? 86400;
	}

	/**
	 * Recursively delete all contents of a directory.
	 *
	 * @param string $dir        Absolute directory path.
	 * @param bool   $remove_dir Whether to also remove the directory itself.
	 */
	private function recursive_rmdir( $dir, $remove_dir = false ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$items = @scandir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( ! $items ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			if ( is_dir( $path ) ) {
				$this->recursive_rmdir( $path, true );
			} else {
				@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			}
		}

		if ( $remove_dir ) {
			@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
	}
}

new Cache();
