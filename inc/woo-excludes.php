<?php
/**
 * WooCommerce page cache exclusions.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooExcludes {
	public static function boot() {
		add_filter( 'whippet_cache_exclude_urls', array( __CLASS__, 'urls' ) );
		add_filter( 'whippet_cache_exclude_cookies', array( __CLASS__, 'cookies' ) );
	}

	private static function enabled() {
		$options = (array) get_option( 'whippet_options', array() );
		return class_exists( 'WooCommerce' )
			&& '1' === (string) ( $options['enable_page_cache'] ?? '0' )
			&& '0' !== (string) get_option( 'whippet_woo_auto_excludes', '1' );
	}

	public static function urls( $urls ) {
		if ( ! self::enabled() ) {
			return $urls;
		}
		$urls = is_array( $urls ) ? $urls : preg_split( '/\r\n|\r|\n/', (string) $urls );
		return array_values( array_unique( array_merge( array_filter( $urls ), array( '/cart/', '/checkout/', '/my-account/' ) ) ) );
	}

	public static function cookies( $cookies ) {
		if ( ! self::enabled() ) {
			return $cookies;
		}
		$cookies = is_array( $cookies ) ? $cookies : preg_split( '/\r\n|\r|\n/', (string) $cookies );
		return array_values( array_unique( array_merge( array_filter( $cookies ), array( 'woocommerce_cart_hash', 'woocommerce_items_in_cart', 'wp_woocommerce_session_' ) ) ) );
	}
}
