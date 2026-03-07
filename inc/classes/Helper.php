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
			error_log( sprintf( '[Whippet][%s] %s', strtoupper( $level ), $message ) );
		}
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

