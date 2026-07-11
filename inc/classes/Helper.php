<?php
/**
 * Helper Class
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper utility functions
 */
class Helper {

	/**
	 * Sanitize array recursively
	 *
	 * @param array $array Array to sanitize.
	 * @return array Sanitized array.
	 */
	public static function sanitize_array( $array ) {
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$array[ $key ] = self::sanitize_array( $value );
			} else {
				$array[ $key ] = sanitize_text_field( $value );
			}
		}
		return $array;
	}

	/**
	 * Get plugin option with default value
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value if option doesn't exist.
	 * @return mixed Option value or default.
	 */
	public static function get_option( $key, $default = '' ) {
		return get_option( 'whippet_' . $key, $default );
	}

	/**
	 * Update plugin option
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Option value.
	 * @return bool True if updated successfully.
	 */
	public static function update_option( $key, $value ) {
		return update_option( 'whippet_' . $key, $value );
	}

	/**
	 * Delete plugin option
	 *
	 * @param string $key Option key.
	 * @return bool True if deleted successfully.
	 */
	public static function delete_option( $key ) {
		return delete_option( 'whippet_' . $key );
	}

	/**
	 * Check if current user can manage plugin
	 *
	 * @return bool True if user has capability.
	 */
	public static function user_can_manage() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Log message (only in debug mode)
	 *
	 * @param string $message Message to log.
	 * @param string $level   Log level (info, warning, error).
	 */
	public static function log( $message, $level = 'info' ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( '[Whippet][%s] %s', strtoupper( $level ), $message ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Structured audit log for destructive / security-sensitive actions.
	 *
	 * @param string               $action  Action name.
	 * @param array<string,mixed>  $context Extra context (no secrets).
	 */
	public static function audit_log( $action, $context = array() ) {
		$user_id = get_current_user_id();
		$payload = array(
			'time'    => gmdate( 'c' ),
			'action'  => sanitize_key( $action ),
			'user_id' => (int) $user_id,
			'ip'      => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			'context' => $context,
		);
		$entry = wp_json_encode( $payload );
		if ( false === $entry ) {
			return;
		}
		error_log( '[Whippet][AUDIT] ' . $entry ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

		$recent = get_option( 'whippet_audit_log', array() );
		if ( ! is_array( $recent ) ) {
			$recent = array();
		}
		array_unshift( $recent, $payload );
		$recent = array_slice( $recent, 0, 100 );
		update_option( 'whippet_audit_log', $recent, false );
	}

	/**
	 * Whether outbound HTTP should verify TLS certificates.
	 *
	 * @return bool
	 */
	public static function ssl_verify() {
		if ( defined( 'WHIPPET_SSLVERIFY' ) ) {
			return (bool) WHIPPET_SSLVERIFY;
		}
		return true;
	}

	/**
	 * Whether PHP snippet execution is explicitly enabled.
	 *
	 * @return bool
	 */
	public static function php_snippets_enabled() {
		return (bool) get_option( 'whippet_php_snippets_enabled', false );
	}

	/**
	 * Allowlisted option keys permitted during settings import.
	 *
	 * @return array<int,string>
	 */
	public static function allowed_import_option_keys() {
		return array(
			'whippet_analytics_id',
			'whippet_analytics_method',
			'whippet_analytics_disable_on_login',
			'whippet_fonts_enabled',
			'whippet_fonts_display_swap',
			'whippet_images_enable_lazyloading',
			'whippet_images_lazymethod',
			'whippet_images_margin',
			'whippet_images_exclude_keywords',
			'whippet_images_enable_cdn',
			'whippet_images_cdn_exclude_keywords',
			'whippet_images_enable_compression',
			'whippet_images_quality',
			'whippet_images_enable_responsive_images',
			'whippet_pages_config_ignore_keywords',
			'whippet_pages_config_delay',
			'whippet_pages_config_max_rps',
			'whippet_pages_config_hover_delay',
			'whippet_pages_config_disable_on_login',
			'whippet_scripts_timeout',
			'whippet_scripts_include_list',
			'whippet_scripts_disabled_pages',
			'whippet_scripts_regex_rules',
			'whippet_scripts_disabled_pages_regex',
			'whippet_scripts_regex_operator',
			'whippet_scripts_mu_mode',
			'whippet_scripts_testing_mode',
		);
	}

	/**
	 * Resolve a filesystem path and ensure it stays under an allowed root.
	 *
	 * @param string $path Absolute path candidate.
	 * @param string $allowed_root Allowed root directory.
	 * @return string Safe real path or empty string.
	 */
	public static function resolve_path_under_root( $path, $allowed_root ) {
		$path         = wp_normalize_path( (string) $path );
		$allowed_root = wp_normalize_path( trailingslashit( (string) $allowed_root ) );

		if ( '' === $path || '' === $allowed_root ) {
			return '';
		}
		if ( false !== strpos( $path, "\0" ) || false !== strpos( $path, '..' ) ) {
			return '';
		}

		$real_root = realpath( $allowed_root );
		if ( false === $real_root ) {
			return '';
		}
		$real_root = wp_normalize_path( trailingslashit( $real_root ) );

		$real_path = realpath( $path );
		if ( false === $real_path ) {
			return '';
		}
		$real_path = wp_normalize_path( $real_path );

		if ( 0 !== strpos( $real_path, $real_root ) ) {
			return '';
		}

		return is_file( $real_path ) ? $real_path : '';
	}

	/**
	 * Mask a secret for HTML display (never echo full keys).
	 *
	 * @param string $secret Secret value.
	 * @return string
	 */
	public static function mask_secret( $secret ) {
		$secret = (string) $secret;
		$len    = strlen( $secret );
		if ( $len <= 4 ) {
			return $len ? str_repeat( '*', $len ) : '';
		}
		return substr( $secret, 0, 4 ) . str_repeat( '*', max( 4, $len - 4 ) );
	}

	/**
	 * Get formatted file size
	 *
	 * @param int $bytes File size in bytes.
	 * @return string Formatted file size.
	 */
	public static function format_bytes( $bytes ) {
		if ( $bytes <= 0 ) {
			return '0 B';
		}

		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		$i     = floor( log( $bytes, 1024 ) );
		$i     = min( $i, count( $units ) - 1 );

		return round( $bytes / pow( 1024, $i ), 2 ) . ' ' . $units[ $i ];
	}

	/**
	 * Check if request is AJAX
	 *
	 * @return bool True if AJAX request.
	 */
	public static function is_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	/**
	 * Check if we're on a Whippet admin page
	 *
	 * @return bool True if on Whippet admin page.
	 */
	public static function is_whippet_page() {
		if ( ! is_admin() ) {
			return false;
		}

		$screen = get_current_screen();
		
		return $screen && strpos( $screen->id, 'whippet' ) !== false;
	}

	/**
	 * Render a notice
	 *
	 * @param string $message Message to display.
	 * @param string $type    Notice type (success, error, warning, info).
	 */
	public static function render_notice( $message, $type = 'info' ) {
		$class = 'notice notice-' . esc_attr( $type ) . ' is-dismissible';
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
}

