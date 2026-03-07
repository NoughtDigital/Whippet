<?php
/**
 * Plugin Name: Whippet Images
 * Plugin URI: https://wordpress.org/plugins/lazy-load/
 * Description: Optimize and lazy load images to reduce load times, save bandwidth, and improve performance, delivering a faster and smoother user experience.
 * Author: WP Speed Matters
 * Author URI: https://wpspeedmatters.com/
 * Version: 2.4.15
 * Text Domain: lazy-load
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

// Define constant with current version
if (!defined('WHIPPET_IMAGES_VERSION')) {
    define('WHIPPET_IMAGES_VERSION', '2.4.15');
}

require_once __DIR__ . '/init-config.php';
require_once __DIR__ . '/settings/index.php';
require_once __DIR__ . '/html-rewrite.php';
require_once __DIR__ . '/resource-hints.php';
require_once __DIR__ . '/inject-js.php';
require_once __DIR__ . '/shortcuts.php';
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'whippet_images_add_shortcuts');
add_filter('wp_lazy_loading_enabled', '__return_false');