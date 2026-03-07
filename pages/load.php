<?php
/**
 * Preload pages module. Loaded by Whippet.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WHIPPET_INTEGRATION' ) ) {
	define( 'WHIPPET_INTEGRATION', true );
}

if ( ! defined( 'WHIPPET_PAGES_VERSION' ) ) {
	define( 'WHIPPET_PAGES_VERSION', '2.4.7' );
}

$dir = dirname( __FILE__ );
require_once $dir . '/init-config.php';
require_once $dir . '/inject-js.php';

require_once $dir . '/settings/settings.php';
require_once $dir . '/settings/compatibility.php';
