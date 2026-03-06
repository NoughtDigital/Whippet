<?php
/**
 * Lazy load / images module. Loaded by Whippet.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WHIPPET_INTEGRATION' ) ) {
	define( 'WHIPPET_INTEGRATION', true );
}

if ( ! defined( 'FLYING_IMAGES_VERSION' ) ) {
	define( 'FLYING_IMAGES_VERSION', '2.4.15' );
}

$dir = dirname( __FILE__ );
require_once $dir . '/init-config.php';
require_once $dir . '/html-rewrite.php';
require_once $dir . '/resource-hints.php';
require_once $dir . '/inject-js.php';

require_once $dir . '/settings/lazyload.php';
require_once $dir . '/settings/cdn.php';
require_once $dir . '/settings/compression.php';
require_once $dir . '/settings/responsiveness.php';
require_once $dir . '/settings/webp.php';
require_once $dir . '/settings/faq.php';
require_once $dir . '/settings/optimize-more.php';
