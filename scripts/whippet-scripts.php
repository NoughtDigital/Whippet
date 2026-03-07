<?php
/**
 * Plugin Name: Whippet Scripts
 * Plugin URI: https://wordpress.org/plugins/whippet-scripts/
 * Description: Delay JavaScript to boost speed by loading scripts only when needed, reducing render-blocking for faster loading and a smoother user experience.
 * Author: WP Speed Matters
 * Author URI: https://wpspeedmatters.com/
 * Version: 1.2.4
 * Text Domain: whippet-scripts
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

// Define constant with current version
if ( ! defined( 'WHIPPET_SCRIPTS_VERSION' ) ) {
    define( 'WHIPPET_SCRIPTS_VERSION', '1.2.4' );
}

require_once __DIR__ . '/init-config.php';
require_once __DIR__ . '/settings/index.php';
require_once __DIR__ . '/inject-js.php';
require_once __DIR__ . '/html-rewrite.php';
require_once __DIR__ . '/shortcuts.php';
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'whippet_scripts_add_shortcuts');