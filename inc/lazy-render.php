<?php
/**
 * Lazy render / offscreen HTML via content-visibility.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LazyRender {
	public static function boot() {
		if ( ! function_exists( 'add_action' ) ) {
			return;
		}
		add_action( 'wp_head', array( __CLASS__, 'print_css' ), 2 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_buffer' ), 1 );
	}

	private static function options() {
		global $whippet_options;
		if ( ! is_array( $whippet_options ) ) {
			$whippet_options = get_option( 'whippet_options', array() );
		}
		return is_array( $whippet_options ) ? $whippet_options : array();
	}

	public static function is_enabled() {
		if ( ! \apply_filters( 'whippet_lazy_render_enabled', true ) ) {
			return false;
		}
		$opts = self::options();
		return ! empty( $opts['enable_lazy_render'] ) && '1' === (string) $opts['enable_lazy_render'];
	}

	/**
	 * Default selectors for below-fold / non-critical blocks.
	 *
	 * @return array<int,string>
	 */
	public static function default_selectors() {
		return array(
			'footer',
			'.site-footer',
			'#colophon',
			'.widget-area',
			'.sidebar',
			'#secondary',
			'.related-posts',
			'.comments-area',
			'#comments',
			'.wp-block-latest-posts',
			'.wp-block-latest-comments',
			'.elementor-location-footer',
			'.e-con.e-parent:not(:first-child)',
		);
	}

	/**
	 * @return array<int,string>
	 */
	public static function selectors() {
		$opts = self::options();
		$raw  = ! empty( $opts['lazy_render_selectors'] ) ? $opts['lazy_render_selectors'] : '';
		if ( is_array( $raw ) ) {
			$lines = $raw;
		} else {
			$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
		}
		$custom = array_values( array_filter( array_map( 'trim', is_array( $lines ) ? $lines : array() ) ) );
		$list   = $custom ? $custom : self::default_selectors();
		return array_values( array_unique( \apply_filters( 'whippet_lazy_render_selectors', $list ) ) );
	}

	public static function print_css() {
		if ( is_admin() || ! self::is_enabled() ) {
			return;
		}
		$selectors = self::selectors();
		if ( ! $selectors ) {
			return;
		}
		$joined = implode( ',', array_map( static function ( $s ) {
			return $s . '.whippet-lazy-render';
		}, $selectors ) );
		echo '<style id="whippet-lazy-render">';
		echo esc_html( $joined );
		echo '{content-visibility:auto;contain-intrinsic-size:1px 1000px;}';
		echo '</style>' . "\n";
	}

	public static function maybe_buffer() {
		if ( is_admin() || is_feed() || is_preview() || ! self::is_enabled() ) {
			return;
		}
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}
		ob_start( array( __CLASS__, 'rewrite' ) );
	}

	/**
	 * Add whippet-lazy-render class to matching elements in the HTML.
	 *
	 * @param string $html Document HTML.
	 * @return string
	 */
	public static function rewrite( $html ) {
		if ( ! is_string( $html ) || '' === $html || '<' !== substr( trim( $html ), 0, 1 ) ) {
			return $html;
		}

		foreach ( self::selectors() as $selector ) {
			$selector = trim( $selector );
			if ( '' === $selector || false !== strpos( $selector, '<' ) ) {
				continue;
			}

			// Tag selectors: footer, main, aside, etc.
			if ( preg_match( '/^[a-z][a-z0-9]*$/i', $selector ) ) {
				$tag  = strtolower( $selector );
				$html = preg_replace_callback(
					'/<(' . preg_quote( $tag, '/' ) . ')(\s[^>]*)?>/i',
					static function ( $m ) {
						$attrs = isset( $m[2] ) ? $m[2] : '';
						if ( false !== stripos( $attrs, 'whippet-lazy-render' ) ) {
							return $m[0];
						}
						if ( preg_match( '/\sclass=(["\'])(.*?)\1/i', $attrs, $cm ) ) {
							$attrs = preg_replace(
								'/\sclass=(["\'])(.*?)\1/i',
								' class=$1' . $cm[2] . ' whippet-lazy-render$1',
								$attrs,
								1
							);
						} else {
							$attrs .= ' class="whippet-lazy-render"';
						}
						return '<' . $m[1] . $attrs . '>';
					},
					$html,
					20
				);
				continue;
			}

			// ID selectors: #comments
			if ( 0 === strpos( $selector, '#' ) ) {
				$id = \sanitize_html_class( substr( $selector, 1 ) );
				if ( ! $id ) {
					continue;
				}
				$html = preg_replace_callback(
					'/<([a-z0-9]+)([^>]*\sid=(["\'])' . preg_quote( $id, '/' ) . '\3[^>]*)>/i',
					static function ( $m ) {
						$attrs = $m[2];
						if ( false !== stripos( $attrs, 'whippet-lazy-render' ) ) {
							return $m[0];
						}
						if ( preg_match( '/\sclass=(["\'])(.*?)\1/i', $attrs, $cm ) ) {
							$attrs = preg_replace(
								'/\sclass=(["\'])(.*?)\1/i',
								' class=$1' . $cm[2] . ' whippet-lazy-render$1',
								$attrs,
								1
							);
						} else {
							$attrs .= ' class="whippet-lazy-render"';
						}
						return '<' . $m[1] . $attrs . '>';
					},
					$html,
					10
				);
				continue;
			}

			// Simple class selectors: .site-footer (first class token only)
			if ( 0 === strpos( $selector, '.' ) && false === strpos( $selector, ' ' ) && false === strpos( $selector, ':' ) ) {
				$class = \sanitize_html_class( substr( $selector, 1 ) );
				if ( ! $class ) {
					continue;
				}
				$html = preg_replace_callback(
					'/<([a-z0-9]+)([^>]*\sclass=(["\'])([^"\']*\b' . preg_quote( $class, '/' ) . '\b[^"\']*)\3[^>]*)>/i',
					static function ( $m ) {
						$attrs = $m[2];
						if ( false !== stripos( $attrs, 'whippet-lazy-render' ) ) {
							return $m[0];
						}
						$attrs = preg_replace(
							'/\sclass=(["\'])(.*?)\1/i',
							' class=$1$2 whippet-lazy-render$1',
							$attrs,
							1
						);
						return '<' . $m[1] . $attrs . '>';
					},
					$html,
					20
				);
			}
		}

		return $html;
	}
}

LazyRender::boot();
