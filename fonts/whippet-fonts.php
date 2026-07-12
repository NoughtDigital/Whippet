<?php
/**
 * Fonts module: remove, display-swap, or self-host Google Fonts.
 */

defined( 'ABSPATH' ) || die;

if ( ! defined( 'WHIPPET_FONTS_VERSION' ) ) {
	define( 'WHIPPET_FONTS_VERSION', '1.1.0' );
}

/**
 * Rewrite HTML to strip Google Fonts and inject a system stack.
 *
 * @param string $html Document HTML.
 * @return string
 */
function whippet_fonts_rewrite_html( $html ) {
	$html  = preg_replace( '/<link[^>]*fonts\.googleapis\.com[^>]*>/i', '', $html );
	$html  = preg_replace( '/<link[^>]*fonts\.gstatic\.com[^>]*>/i', '', $html );
	$html  = preg_replace( '/@import\s+url\([^)]*fonts\.googleapis\.com[^)]*\);?/i', '', $html );
	$style = "<style id=\"whippet-system-fonts\">body{font-family:-apple-system,system-ui,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,'Fira Sans','Droid Sans','Helvetica Neue',sans-serif !important}code{font-family:Menlo,Consolas,Monaco,Liberation Mono,Lucida Console,monospace !important}</style>";
	return str_replace( '</head>', $style . '</head>', $html );
}

/**
 * Inject display=swap into Google Fonts URLs.
 *
 * @param string $html Document HTML.
 * @return string
 */
function whippet_fonts_inject_display_swap( $html ) {
	$html = str_replace( '&#038;display=swap', '', $html );
	$html = str_replace( 'googleapis.com/css?family', 'googleapis.com/css?display=swap&family', $html );
	$html = str_replace( 'googleapis.com/css2?family', 'googleapis.com/css2?display=swap&family', $html );
	$html = preg_replace( "/(WebFontConfig\['google'\])(.+[\w])(.+};)/", '$1$2&display=swap$3', $html );
	return $html;
}

/**
 * LiteSpeed Cache: add font-display:swap to @font-face in combined CSS.
 *
 * @param string $content Combined content.
 * @param string $file_type css|js.
 * @param array  $urls URLs.
 * @return string
 */
function whippet_fonts_litespeed_swap( $content, $file_type, $urls ) {
	if ( 'css' === $file_type ) {
		$content = str_replace( '@font-face{', '@font-face{font-display:swap;', $content );
	}
	return $content;
}

/**
 * Upload directory for self-hosted fonts.
 *
 * @return array{basedir:string,baseurl:string}|WP_Error
 */
function whippet_fonts_upload_dir() {
	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) ) {
		return new WP_Error( 'whippet_fonts_upload', $uploads['error'] );
	}
	$dir = trailingslashit( $uploads['basedir'] ) . 'whippet-fonts';
	$url = trailingslashit( $uploads['baseurl'] ) . 'whippet-fonts';
	if ( ! wp_mkdir_p( $dir ) ) {
		return new WP_Error( 'whippet_fonts_mkdir', __( 'Could not create fonts directory.', 'whippet' ) );
	}
	return array(
		'basedir' => $dir,
		'baseurl' => $url,
	);
}

/**
 * Download a remote Google Fonts stylesheet and its font files locally.
 *
 * @param string $css_url Google Fonts CSS URL.
 * @return string|WP_Error Local CSS URL or error.
 */
function whippet_fonts_self_host_stylesheet( $css_url ) {
	$uploads = whippet_fonts_upload_dir();
	if ( is_wp_error( $uploads ) ) {
		return $uploads;
	}

	$hash     = substr( md5( $css_url ), 0, 12 );
	$css_file = trailingslashit( $uploads['basedir'] ) . 'gf-' . $hash . '.css';
	$css_url_local = trailingslashit( $uploads['baseurl'] ) . 'gf-' . $hash . '.css';

	if ( file_exists( $css_file ) && filesize( $css_file ) > 0 ) {
		return $css_url_local;
	}

	$response = wp_remote_get(
		$css_url,
		array(
			'timeout'    => 20,
			'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
			'sslverify'  => class_exists( '\Whippet\Helper' ) ? \Whippet\Helper::ssl_verify() : true,
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = wp_remote_retrieve_response_code( $response );
	$css  = wp_remote_retrieve_body( $response );
	if ( 200 !== (int) $code || '' === $css ) {
		return new WP_Error( 'whippet_fonts_fetch', __( 'Could not download Google Fonts stylesheet.', 'whippet' ) );
	}

	// Ensure font-display:swap.
	if ( false === stripos( $css, 'font-display' ) ) {
		$css = str_replace( '@font-face{', '@font-face{font-display:swap;', $css );
		$css = str_replace( '@font-face {', '@font-face {font-display:swap;', $css );
	}

	$css = preg_replace_callback(
		'/url\((["\']?)(https?:\/\/fonts\.gstatic\.com\/[^)"\']+)\1\)/i',
		static function ( $m ) use ( $uploads ) {
			$font_url = $m[2];
			$ext      = pathinfo( wp_parse_url( $font_url, PHP_URL_PATH ), PATHINFO_EXTENSION );
			$ext      = $ext ? sanitize_key( $ext ) : 'woff2';
			$name     = 'f-' . substr( md5( $font_url ), 0, 12 ) . '.' . $ext;
			$path     = trailingslashit( $uploads['basedir'] ) . $name;
			$local    = trailingslashit( $uploads['baseurl'] ) . $name;

			if ( ! file_exists( $path ) ) {
				$font_resp = wp_remote_get(
					$font_url,
					array(
						'timeout'   => 20,
						'sslverify' => class_exists( '\Whippet\Helper' ) ? \Whippet\Helper::ssl_verify() : true,
					)
				);
				if ( ! is_wp_error( $font_resp ) && 200 === (int) wp_remote_retrieve_response_code( $font_resp ) ) {
					file_put_contents( $path, wp_remote_retrieve_body( $font_resp ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				} else {
					return $m[0];
				}
			}
			return 'url(' . $local . ')';
		},
		$css
	);

	file_put_contents( $css_file, $css ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	return $css_url_local;
}

/**
 * Collect Google Fonts stylesheet URLs from HTML.
 *
 * @param string $html Document HTML.
 * @return array
 */
function whippet_fonts_collect_google_urls( $html ) {
	$urls = array();
	if ( preg_match_all( '/<link[^>]+href=(["\'])([^"\']*fonts\.googleapis\.com[^"\']*)\1[^>]*>/i', $html, $matches ) ) {
		foreach ( $matches[2] as $url ) {
			$urls[] = html_entity_decode( $url, ENT_QUOTES );
		}
	}
	return array_values( array_unique( $urls ) );
}

/**
 * Rewrite Google Fonts links to self-hosted copies.
 *
 * @param string $html Document HTML.
 * @return string
 */
function whippet_fonts_self_host_html( $html ) {
	$urls = whippet_fonts_collect_google_urls( $html );
	$map  = array();

	foreach ( $urls as $remote ) {
		$local = whippet_fonts_self_host_stylesheet( $remote );
		if ( ! is_wp_error( $local ) ) {
			$map[ $remote ] = $local;
		}
	}

	foreach ( $map as $remote => $local ) {
		$html = str_replace( $remote, esc_url( $local ), $html );
		$html = str_replace( esc_url( $remote ), esc_url( $local ), $html );
		$html = str_replace( htmlspecialchars( $remote, ENT_QUOTES ), esc_url( $local ), $html );
	}

	// Drop gstatic preconnect once fonts are local.
	if ( ! empty( $map ) ) {
		$html = preg_replace( '/<link[^>]*fonts\.gstatic\.com[^>]*>/i', '', $html );
	}

	return $html;
}

/**
 * Clear cached self-hosted font files.
 *
 * @return int Files removed.
 */
function whippet_fonts_clear_cache() {
	$uploads = whippet_fonts_upload_dir();
	if ( is_wp_error( $uploads ) || ! is_dir( $uploads['basedir'] ) ) {
		return 0;
	}
	$count = 0;
	foreach ( glob( trailingslashit( $uploads['basedir'] ) . '*' ) as $file ) {
		if ( is_file( $file ) ) {
			unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			++$count;
		}
	}
	return $count;
}

if ( ! is_admin() ) {
	$fonts_enabled = get_option( 'whippet_fonts_enabled', true );
	$display_swap  = get_option( 'whippet_fonts_display_swap', false );
	$self_host     = get_option( 'whippet_fonts_self_host', false );

	if ( $fonts_enabled ) {
		ob_start( 'whippet_fonts_rewrite_html' );
	} elseif ( $self_host ) {
		ob_start( 'whippet_fonts_self_host_html' );
	} elseif ( $display_swap ) {
		ob_start( 'whippet_fonts_inject_display_swap' );
	}
}

if ( get_option( 'whippet_fonts_display_swap', false ) || get_option( 'whippet_fonts_self_host', false ) ) {
	add_filter( 'litespeed_optm_cssjs', 'whippet_fonts_litespeed_swap', 10, 3 );
}
