<?php
/**
 * PHPUnit bootstrap for Whippet unit tests (no full WordPress install required).
 */

define( 'ABSPATH', __DIR__ . '/../' );

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $text;
	}
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $text;
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		$key = strtolower( (string) $key );
		return preg_replace( '/[^a-z0-9_\-]/', '', $key );
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}
if ( ! function_exists( 'wp_normalize_path' ) ) {
	function wp_normalize_path( $path ) {
		$path = str_replace( '\\', '/', (string) $path );
		$path = preg_replace( '|/+|', '/', $path );
		return $path;
	}
}
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( (string) $string, '/\\' ) . '/';
	}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $key, $default = false ) {
		global $whippet_test_options;
		if ( ! is_array( $whippet_test_options ) ) {
			$whippet_test_options = array();
		}
		return array_key_exists( $key, $whippet_test_options ) ? $whippet_test_options[ $key ] : $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $key, $value, $autoload = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		global $whippet_test_options;
		if ( ! is_array( $whippet_test_options ) ) {
			$whippet_test_options = array();
		}
		$whippet_test_options[ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 1;
	}
}
if ( ! function_exists( 'is_multisite' ) ) {
	function is_multisite() {
		return false;
	}
}
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return $value;
	}
}
if ( ! function_exists( 'absint' ) ) {
	function absint( $value ) {
		return abs( (int) $value );
	}
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return (string) $url;
	}
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $value;
	}
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return true;
	}
}

require_once dirname( __DIR__ ) . '/inc/classes/Helper.php';
require_once dirname( __DIR__ ) . '/inc/classes/Capabilities.php';
require_once dirname( __DIR__ ) . '/inc/conflict-detector.php';
require_once dirname( __DIR__ ) . '/inc/setup-wizard.php';
require_once dirname( __DIR__ ) . '/inc/script-advisor.php';
require_once dirname( __DIR__ ) . '/inc/recovery-mode.php';
