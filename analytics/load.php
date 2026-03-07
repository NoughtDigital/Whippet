<?php
/**
 * Analytics module: GA4 self-hosted / minimal analytics.
 * Loaded by Whippet.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WHIPPET_ANALYTICS_VERSION' ) ) {
	define( 'WHIPPET_ANALYTICS_VERSION', '2.0.0' );
}

$analytics_dir = dirname( __FILE__ );
require_once $analytics_dir . '/init-config.php';
require_once $analytics_dir . '/inject-js.php';
require_once $analytics_dir . '/settings/settings.php';
