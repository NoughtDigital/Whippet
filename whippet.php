<?php
/**
 * Plugin Name: Whippet
 * Plugin URI: https://whippetwp.com/
 * Description: All-in-one WordPress performance: remove bloat, cache pages, control scripts, optimise images, and connect optional Cloud premium services.
 * Version: 1.1.1
 * Author: Jake Henshall
 * Author URI: https://www.nought.digital/
 * License: GPLv3
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: whippet
 * Domain Path: /languages
 *
 * @package Whippet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoloader for plugin classes
 *
 * @param string $class Class name to load.
 */
function whippet_autoloader( $class ) {
	// Project-specific namespace prefix
	$prefix = 'Whippet\\';

	// Base directory for the namespace prefix
	$base_dir = plugin_dir_path( __FILE__ ) . 'inc/classes/';

	// Does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	// Get the relative class name
	$relative_class = substr( $class, $len );

	// Replace namespace separators with directory separators
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	// If the file exists, require it
	if ( file_exists( $file ) ) {
		require $file;
	}
}
spl_autoload_register( 'whippet_autoloader' );

/**
 * Initialize the plugin
 *
 * @return Whippet\Plugin
 */
function whippet() {
	return Whippet\Plugin::instance();
}

// Start the plugin
whippet();
