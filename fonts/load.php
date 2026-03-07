<?php
/**
 * Fonts module: remove Google Fonts. Loaded by Whippet.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WHIPPET_INTEGRATION' ) ) {
	define( 'WHIPPET_INTEGRATION', true );
}

if ( ! defined( 'WHIPPET_FONTS_VERSION' ) ) {
	define( 'WHIPPET_FONTS_VERSION', '1.0.2' );
}

require_once dirname( __FILE__ ) . '/whippet-fonts.php';
