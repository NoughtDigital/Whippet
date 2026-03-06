<?php
/**
 * Delay scripts module. Loaded by Whippet.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WHIPPET_INTEGRATION' ) ) {
	define( 'WHIPPET_INTEGRATION', true );
}

if ( ! defined( 'FLYING_SCRIPTS_VERSION' ) ) {
	define( 'FLYING_SCRIPTS_VERSION', '1.2.4' );
}

$dir = dirname( __FILE__ );
require_once $dir . '/init-config.php';
require_once $dir . '/inject-js.php';
require_once $dir . '/html-rewrite.php';

require_once $dir . '/settings/settings.php';
require_once $dir . '/settings/faq.php';
require_once $dir . '/settings/optimize-more.php';
