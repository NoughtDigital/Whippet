<?php
/**
 * Site-wide CSS/JS minification and defer JS.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Minify {
	const CACHE_DIR = 'cache/whippet/min';

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'init' ), 20 );
		add_action( 'wp_ajax_whippet_clear_min_cache', array( __CLASS__, 'ajax_clear' ) );
		add_action( 'whippet_cache_cleared', array( __CLASS__, 'clear_cache' ) );
	}

	public static function init() {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}
		if ( ! apply_filters( 'whippet_minify_enabled', true ) ) {
			return;
		}

		$opts = self::options();

		if ( ! empty( $opts['enable_minify_css'] ) && '1' === $opts['enable_minify_css'] ) {
			add_filter( 'style_loader_src', array( __CLASS__, 'minify_style_src' ), 15, 2 );
		}
		if ( ! empty( $opts['enable_minify_js'] ) && '1' === $opts['enable_minify_js'] ) {
			add_filter( 'script_loader_src', array( __CLASS__, 'minify_script_src' ), 15, 2 );
		}
		if ( ! empty( $opts['enable_defer_js'] ) && '1' === $opts['enable_defer_js'] ) {
			add_filter( 'script_loader_tag', array( __CLASS__, 'defer_script_tag' ), 15, 3 );
		}
	}

	private static function options() {
		global $whippet_options;
		if ( ! is_array( $whippet_options ) ) {
			$whippet_options = get_option( 'whippet_options', array() );
		}
		return is_array( $whippet_options ) ? $whippet_options : array();
	}

	private static function exclusions( $key ) {
		$opts = self::options();
		$raw  = ! empty( $opts[ $key ] ) ? $opts[ $key ] : '';
		if ( is_array( $raw ) ) {
			return array_filter( array_map( 'trim', $raw ) );
		}
		$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
		return array_filter( array_map( 'trim', $lines ) );
	}

	private static function is_excluded( $src, $handle, $key ) {
		$haystack = $src . ' ' . $handle;
		foreach ( self::exclusions( $key ) as $needle ) {
			if ( $needle && false !== stripos( $haystack, $needle ) ) {
				return true;
			}
		}
		// Skip vendor runtimes and WP admin; still minify theme/plugin and core block CSS
		// (including already-*.min.* files) so block themes get cache/whippet/min URLs.
		$defaults = array( 'jquery.js', 'jquery.min.js', 'jquery-migrate', 'wp-includes/js/dist/', 'wp-admin/', 'wp-includes/js/jquery/' );
		foreach ( $defaults as $needle ) {
			if ( false !== stripos( $src, $needle ) || false !== stripos( $handle, $needle ) ) {
				return true;
			}
		}
		return (bool) apply_filters( 'whippet_minify_exclude', false, $src, $handle, $key );
	}

	public static function minify_style_src( $src, $handle ) {
		return self::maybe_minify( $src, $handle, 'css', 'minify_css_exclude' );
	}

	public static function minify_script_src( $src, $handle ) {
		return self::maybe_minify( $src, $handle, 'js', 'minify_js_exclude' );
	}

	private static function maybe_minify( $src, $handle, $type, $exclude_key ) {
		if ( empty( $src ) || ! is_string( $src ) ) {
			return $src;
		}
		if ( self::is_excluded( $src, $handle, $exclude_key ) ) {
			return $src;
		}

		$path = self::url_to_path( $src );
		if ( ! $path || ! is_readable( $path ) ) {
			return $src;
		}

		$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $content || '' === $content ) {
			return $src;
		}

		$min = 'css' === $type ? self::minify_css( $content ) : self::minify_js( $content );
		$hash = substr( md5( $min ), 0, 12 );
		$dir  = trailingslashit( WP_CONTENT_DIR ) . self::CACHE_DIR . '/' . $type;
		if ( ! wp_mkdir_p( $dir ) ) {
			return $src;
		}

		$filename = sanitize_file_name( $handle ) . '-' . $hash . '.min.' . $type;
		$file     = trailingslashit( $dir ) . $filename;
		if ( ! file_exists( $file ) ) {
			file_put_contents( $file, $min ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		$url = content_url( self::CACHE_DIR . '/' . $type . '/' . $filename );
		$qs  = wp_parse_url( $src, PHP_URL_QUERY );
		return $qs ? $url . '?' . $qs : $url;
	}

	private static function url_to_path( $src ) {
		$src_path = wp_parse_url( $src, PHP_URL_PATH );
		if ( ! $src_path ) {
			return '';
		}

		$content_url  = wp_parse_url( content_url(), PHP_URL_PATH );
		$includes_url = wp_parse_url( includes_url(), PHP_URL_PATH );
		$plugins_url  = wp_parse_url( plugins_url(), PHP_URL_PATH );
		$theme_url    = wp_parse_url( get_theme_root_uri(), PHP_URL_PATH );

		$map = array(
			$content_url  => WP_CONTENT_DIR,
			$includes_url => ABSPATH . WPINC,
			$plugins_url  => WP_PLUGIN_DIR,
			$theme_url    => get_theme_root(),
		);

		foreach ( $map as $url_base => $fs_base ) {
			if ( $url_base && 0 === strpos( $src_path, $url_base ) ) {
				$rel = substr( $src_path, strlen( $url_base ) );
				$path = trailingslashit( $fs_base ) . ltrim( $rel, '/' );
				return realpath( $path ) ?: '';
			}
		}

		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( $home_path && 0 === strpos( $src_path, $home_path ) ) {
			$rel  = substr( $src_path, strlen( rtrim( $home_path, '/' ) ) );
			$path = ABSPATH . ltrim( $rel, '/' );
			return realpath( $path ) ?: '';
		}

		return '';
	}

	public static function minify_css( $css ) {
		$css = preg_replace( '!/\*.*?\*/!s', '', $css );
		$css = preg_replace( '/\s+/', ' ', $css );
		$css = preg_replace( '/\s*([{};:,>~+])\s*/', '$1', $css );
		$css = str_replace( ';}', '}', $css );
		return trim( $css );
	}

	public static function minify_js( $js ) {
		$js = preg_replace( '!/\*.*?\*/!s', '', $js );
		$js = preg_replace( '/(?:^|\n)\s*\/\/[^\n]*/', '', $js );
		$js = preg_replace( '/\s+/', ' ', $js );
		return trim( $js );
	}

	public static function defer_script_tag( $tag, $handle, $src ) {
		if ( empty( $src ) || false !== stripos( $tag, ' defer' ) || false !== stripos( $tag, ' async' ) ) {
			return $tag;
		}
		if ( self::is_excluded( $src, $handle, 'defer_js_exclude' ) ) {
			return $tag;
		}
		// Never defer jQuery or scripts that others depend on for document.ready timing by default.
		$never = array( 'jquery-core', 'jquery', 'jquery-migrate' );
		if ( in_array( $handle, $never, true ) ) {
			return $tag;
		}
		return preg_replace( '/<script\b/i', '<script defer', $tag, 1 );
	}

	public static function clear_cache() {
		$dir = trailingslashit( WP_CONTENT_DIR ) . self::CACHE_DIR;
		if ( ! is_dir( $dir ) ) {
			return 0;
		}
		$count = 0;
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $files as $file ) {
			if ( $file->isFile() ) {
				unlink( $file->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				++$count;
			}
		}
		return $count;
	}

	public static function ajax_clear() {
		check_ajax_referer( 'whippet_minify_nonce', 'nonce' );
		if ( ! Helper::user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorised.', 'whippet' ) ) );
		}
		$count = self::clear_cache();
		Helper::audit_log( 'minify_cache_cleared', array( 'files' => $count ) );
		wp_send_json_success( array( 'message' => sprintf( __( 'Cleared %d minified files.', 'whippet' ), $count ) ) );
	}
}

Minify::boot();
