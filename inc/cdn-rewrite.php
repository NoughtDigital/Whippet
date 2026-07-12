<?php
/**
 * Generic CDN URL rewrite for static assets.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CdnRewrite {
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'init' ), 25 );
	}

	public static function init() {
		if ( is_admin() || ! apply_filters( 'whippet_cdn_rewrite_enabled', true ) ) {
			return;
		}
		$opts = self::options();
		$cdn  = ! empty( $opts['cdn_url'] ) ? esc_url_raw( trim( $opts['cdn_url'] ) ) : '';
		if ( ! $cdn || empty( $opts['enable_cdn_rewrite'] ) || '1' !== $opts['enable_cdn_rewrite'] ) {
			return;
		}

		add_filter( 'script_loader_src', array( __CLASS__, 'rewrite' ), 20 );
		add_filter( 'style_loader_src', array( __CLASS__, 'rewrite' ), 20 );
		add_filter( 'wp_get_attachment_url', array( __CLASS__, 'rewrite' ), 20 );
		add_filter( 'the_content', array( __CLASS__, 'rewrite_content' ), 20 );
	}

	private static function options() {
		global $whippet_options;
		if ( ! is_array( $whippet_options ) ) {
			$whippet_options = get_option( 'whippet_options', array() );
		}
		return is_array( $whippet_options ) ? $whippet_options : array();
	}

	public static function rewrite( $url ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return $url;
		}
		$opts = self::options();
		$cdn  = rtrim( (string) $opts['cdn_url'], '/' );
		$site = rtrim( site_url(), '/' );
		$content = rtrim( content_url(), '/' );

		if ( 0 === strpos( $url, $content ) ) {
			return $cdn . substr( $url, strlen( $site ) );
		}
		if ( 0 === strpos( $url, $site ) ) {
			return $cdn . substr( $url, strlen( $site ) );
		}
		return $url;
	}

	public static function rewrite_content( $content ) {
		if ( empty( $content ) || ! is_string( $content ) ) {
			return $content;
		}
		$opts = self::options();
		$cdn  = rtrim( (string) $opts['cdn_url'], '/' );
		$site = rtrim( site_url(), '/' );
		if ( ! $cdn || ! $site ) {
			return $content;
		}
		return str_replace( $site . '/wp-content/', $cdn . '/wp-content/', $content );
	}
}

CdnRewrite::boot();
