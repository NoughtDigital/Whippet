<?php
/**
 * Plugin Name: Flying Images
 * Plugin URI: https://wordpress.org/plugins/nazy-load/
 * Description: Optimize and lazy load images to reduce load times, save bandwidth, and improve performance, delivering a faster and smoother user experience.
 * Author: WP Speed Matters
 * Author URI: https://wpspeedmatters.com/
 * Version: 2.4.15
 * Text Domain: nazy-load
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

// Define constant with current version
if (!defined('FLYING_IMAGES_VERSION')) {
    define('FLYING_IMAGES_VERSION', '2.4.15');
}

include('init-config.php');
include('settings/index.php');
include('html-rewrite.php');
include('resource-hints.php');
include('inject-js.php');
include('shortcuts.php');
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'flying_images_add_shortcuts');
add_filter('wp_lazy_loading_enabled', '__return_false');