<?php
/**
 * Plugin Name: Whippet Pages
 * Plugin URI: https://wordpress.org/plugins/whippet-pages/
 * Description: Preload pages intelligently to boost site speed and enhance user experience by loading pages before users click, ensuring instant page transitions.
 * Author: WP Speed Matters
 * Author URI: https://wpspeedmatters.com/
 * Version: 2.4.7
 * Text Domain: whippet-pages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) die;

// Define constant with current version
if (!defined('WHIPPET_PAGES_VERSION'))
    define('WHIPPET_PAGES_VERSION', '2.4.7');

require_once __DIR__ . '/init-config.php';
require_once __DIR__ . '/settings/index.php';
require_once __DIR__ . '/inject-js.php';
require_once __DIR__ . '/shortcuts.php';
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'whippet_pages_add_action_links');