<?php
/**
 * Fonts module: remove Google Fonts and/or add font-display:swap. Loaded by Whippet.
 */

defined( 'ABSPATH' ) || die;

if ( ! defined( 'WHIPPET_FONTS_VERSION' ) ) {
	define( 'WHIPPET_FONTS_VERSION', '1.0.2' );
}

function whippet_fonts_rewrite_html( $html ) {
	$html = preg_replace( '/<link.*fonts\.googleapis\.com.*>/i', '', $html );
	$html = preg_replace( '/<link.*fonts\.gstatic\.com.*>/i', '', $html );
	$style = "<style>body{font-family:-apple-system,system-ui,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,'Fira Sans','Droid Sans','Helvetica Neue',sans-serif !important}code{font-family:Menlo,Consolas,Monaco,Liberation Mono,Lucida Console,monospace !important}</style>";
	$html = str_replace( '</head>', $style . '</head>', $html );
	return $html;
}

/**
 * Inject display=swap into Google Fonts URLs so text remains visible during webfont load.
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
 */
function whippet_fonts_litespeed_swap( $content, $file_type, $urls ) {
	if ( $file_type === 'css' ) {
		$content = str_replace( '@font-face{', '@font-face{font-display:swap;', $content );
	}
	return $content;
}

if ( ! is_admin() ) {
	$fonts_enabled = get_option( 'whippet_fonts_enabled', true );
	$display_swap  = get_option( 'whippet_fonts_display_swap', false );
	if ( $fonts_enabled ) {
		ob_start( 'whippet_fonts_rewrite_html' );
	} elseif ( $display_swap ) {
		ob_start( 'whippet_fonts_inject_display_swap' );
	}
}

if ( get_option( 'whippet_fonts_display_swap', false ) ) {
	add_filter( 'litespeed_optm_cssjs', 'whippet_fonts_litespeed_swap', 10, 3 );
}