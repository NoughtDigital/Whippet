<?php
/**
 * Speculative Loading (Speculation Rules API) + resource hints manager.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SpeculativeLoading {
	public static function boot() {
		add_action( 'wp_head', array( __CLASS__, 'resource_hints' ), 1 );
		add_action( 'wp_footer', array( __CLASS__, 'speculation_rules' ), 5 );
	}

	private static function options() {
		global $whippet_options;
		if ( ! is_array( $whippet_options ) ) {
			$whippet_options = get_option( 'whippet_options', array() );
		}
		return is_array( $whippet_options ) ? $whippet_options : array();
	}

	public static function resource_hints() {
		if ( is_admin() ) {
			return;
		}
		$opts = self::options();
		$raw  = ! empty( $opts['resource_hints'] ) ? $opts['resource_hints'] : '';
		if ( is_array( $raw ) ) {
			$lines = $raw;
		} else {
			$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
		}
		foreach ( $lines as $line ) {
			$line = trim( (string) $line );
			if ( '' === $line ) {
				continue;
			}
			// Format: type|url  e.g. preconnect|https://fonts.gstatic.com
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( 2 !== count( $parts ) ) {
				continue;
			}
			$type = sanitize_key( $parts[0] );
			$url  = esc_url( $parts[1] );
			if ( ! $url || ! in_array( $type, array( 'preconnect', 'dns-prefetch', 'prefetch', 'preload' ), true ) ) {
				continue;
			}
			$cross = ( 'preconnect' === $type ) ? ' crossorigin' : '';
			printf( '<link rel="%s" href="%s"%s>' . "\n", esc_attr( $type ), esc_url( $url ), $cross ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public static function speculation_rules() {
		if ( is_admin() || ! apply_filters( 'whippet_speculation_enabled', true ) ) {
			return;
		}
		$opts = self::options();
		if ( empty( $opts['enable_speculation'] ) || '1' !== $opts['enable_speculation'] ) {
			return;
		}

		$eagerness = ! empty( $opts['speculation_eagerness'] ) ? sanitize_key( $opts['speculation_eagerness'] ) : 'moderate';
		if ( ! in_array( $eagerness, array( 'conservative', 'moderate', 'eager' ), true ) ) {
			$eagerness = 'moderate';
		}

		$rules = array(
			'prerender' => array(
				array(
					'where'     => array(
						'and' => array(
							array( 'href_matches' => '/*' ),
							array(
								'not' => array(
									'href_matches' => array( '/wp-admin/*', '/wp-login.php*', '/*\\?*(^|&)preview=*' ),
								),
							),
							array(
								'not' => array(
									'selector_matches' => '[rel~="nofollow"], .no-prerender, [data-no-speculation]',
								),
							),
						),
					),
					'eagerness' => $eagerness,
				),
			),
		);

		$rules = apply_filters( 'whippet_speculation_rules', $rules );
		echo '<script type="speculationrules">' . wp_json_encode( $rules ) . '</script>' . "\n";
	}
}

SpeculativeLoading::boot();
