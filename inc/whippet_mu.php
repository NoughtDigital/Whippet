<?php
/**
 * Whippet MU (Must-Use) plugin – runs from wp-content/mu-plugins/ when MU mode is enabled.
 * Disables entire WordPress plugins (scripts, queries, hooks, inline CSS/JS) per Script Manager rules.
 *
 * Copy this file to mu-plugins via Script Manager Settings. Do not require from Whippet; it must be standalone.
 *
 * @package Whippet
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WPINC' ) ) {
	return;
}

define( 'WHIPPET_MU_VERSION', '1.0.0' );

/**
 * Skip MU plugin filtering for requests that should keep the full admin/plugin stack.
 *
 * @return bool
 */
function whippet_mu_should_bypass_request() {
	if ( is_admin() ) {
		return true;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return true;
	}
	if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
		return true;
	}
	if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
		return true;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return true;
	}
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		return true;
	}

	return false;
}

/**
 * Get current request path (no query string), matching Script Manager get_current_url().
 *
 * @return string
 */
function whippet_mu_current_path() {
	if ( ! isset( $_SERVER['REQUEST_URI'] ) || ! is_string( $_SERVER['REQUEST_URI'] ) ) {
		return '';
	}
	$uri = filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_URL );
	$path = explode( '?', $uri, 2 )[0];
	$path = ( strlen( $path ) > 1 ) ? rtrim( $path, '/' ) : $path;
	return $path;
}

/**
 * Get plugin slug from plugin file path (e.g. "foo/foo.php" -> "foo").
 *
 * @param string $plugin_path Plugin path from active_plugins.
 * @return string
 */
function whippet_mu_plugin_slug( $plugin_path ) {
	$plugin_path = trim( (string) $plugin_path );
	if ( '' === $plugin_path ) {
		return '';
	}
	if ( strpos( $plugin_path, '/' ) !== false ) {
		return strtok( $plugin_path, '/' );
	}
	return strtok( $plugin_path, '.' );
}

/**
 * Prepare regex pattern (plain or /pattern/flags) for matching.
 *
 * @param string $pattern Pattern from settings.
 * @return string Regex string.
 */
function whippet_mu_prepare_regex( $pattern ) {
	$pattern = trim( (string) $pattern );
	if ( '' === $pattern ) {
		return '';
	}
	$first = substr( $pattern, 0, 1 );
	$last  = substr( $pattern, -1 );
	if ( strlen( $pattern ) > 2 && $first === $last && ! ctype_alnum( $first ) && ! ctype_space( $first ) ) {
		return $pattern;
	}
	return '/' . str_replace( '/', '\/', $pattern ) . '/i';
}

/**
 * Check if plugin should be disabled for current request (MU logic mirrors Script Manager).
 *
 * @param string $slug Plugin slug (directory name).
 * @param array  $disabled Disabled config for this plugin (everywhere, here, regex).
 * @param array  $enabled  Enabled exceptions (here, content types).
 * @param string $current_path Current request path.
 * @return bool True if plugin should be disabled (excluded from active_plugins).
 */
function whippet_mu_plugin_should_disable( $slug, $disabled, $enabled, $current_path ) {
	if ( empty( $disabled ) ) {
		return false;
	}

	$is_disabled_scope = false;

	if ( ! empty( $disabled['everywhere'] ) ) {
		$is_disabled_scope = true;
	} elseif ( ! empty( $disabled['here'] ) && $current_path !== '' ) {
		$is_disabled_scope = true;
	} elseif ( ! empty( $disabled['regex'] ) ) {
		$regex = whippet_mu_prepare_regex( $disabled['regex'] );
		if ( '' !== $regex && false !== @preg_match( $regex, '' ) && preg_match( $regex, $current_path ) ) {
			$is_disabled_scope = true;
		}
	}

	if ( ! $is_disabled_scope ) {
		return false;
	}

	// Exceptions: if enabled for "here" (current URL), do not disable.
	if ( ! empty( $enabled['here'] ) && $current_path !== '' ) {
		return false;
	}

	// Exceptions: if enabled for URL matching regex pattern, do not disable.
	if ( ! empty( $enabled['regex'] ) && $current_path !== '' ) {
		$regex = whippet_mu_prepare_regex( $enabled['regex'] );
		if ( '' !== $regex && false !== @preg_match( $regex, '' ) && preg_match( $regex, $current_path ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Filter active_plugins to exclude plugins disabled by Script Manager when MU mode is on.
 *
 * @param array $plugins Active plugins list.
 * @return array
 */
function whippet_mu_filter_active_plugins( $plugins ) {
	if ( ! is_array( $plugins ) ) {
		return $plugins;
	}

	if ( isset( $_GET['whippet_mu_mode'] ) && sanitize_text_field( wp_unslash( $_GET['whippet_mu_mode'] ) ) === 'off' ) {
		return $plugins;
	}

	if ( ! get_option( 'whippet_scripts_mu_mode', 0 ) ) {
		return $plugins;
	}

	if ( whippet_mu_should_bypass_request() ) {
		return $plugins;
	}

	global $wpdb;
	$current_path = whippet_mu_current_path();
	$prefix       = $wpdb->prefix;

	$disabled_global = $wpdb->get_results( "SELECT name, url, regex FROM {$prefix}whippet_p_disabled WHERE url = '' AND (regex IS NULL OR regex = '')", ARRAY_A );
	$disabled_here   = $current_path !== '' ? $wpdb->get_results( $wpdb->prepare( "SELECT name, url, regex FROM {$prefix}whippet_p_disabled WHERE url = %s", $current_path ), ARRAY_A ) : array();
	$disabled_regex  = $wpdb->get_results( "SELECT name, url, regex FROM {$prefix}whippet_p_disabled WHERE regex IS NOT NULL AND regex != ''", ARRAY_A );

	$enabled_here  = $current_path !== '' ? $wpdb->get_results( $wpdb->prepare( "SELECT name FROM {$prefix}whippet_p_enabled WHERE content_type = 'here' AND url = %s", $current_path ), ARRAY_A ) : array();
	$enabled_regex = $wpdb->get_results( "SELECT name, url FROM {$prefix}whippet_p_enabled WHERE content_type = 'regex'", ARRAY_A );

	$disabled_by_slug = array();
	foreach ( array_merge( $disabled_global, $disabled_here, $disabled_regex ) as $row ) {
		$name = isset( $row['name'] ) ? trim( (string) $row['name'] ) : '';
		if ( '' === $name ) {
			continue;
		}
		if ( ! isset( $disabled_by_slug[ $name ] ) ) {
			$disabled_by_slug[ $name ] = array();
		}
		if ( isset( $row['regex'] ) && (string) $row['regex'] !== '' ) {
			$disabled_by_slug[ $name ]['regex'] = stripslashes( (string) $row['regex'] );
		} elseif ( isset( $row['url'] ) && (string) $row['url'] !== '' ) {
			$disabled_by_slug[ $name ]['here'] = true;
		} else {
			$disabled_by_slug[ $name ]['everywhere'] = true;
		}
	}

	$enabled_here_slugs  = array();
	$enabled_regex_slugs = array();
	foreach ( $enabled_here as $row ) {
		$name = isset( $row['name'] ) ? trim( (string) $row['name'] ) : '';
		if ( '' !== $name ) {
			$enabled_here_slugs[ $name ] = true;
		}
	}
	foreach ( $enabled_regex as $row ) {
		$name = isset( $row['name'] ) ? trim( (string) $row['name'] ) : '';
		if ( '' !== $name && isset( $row['url'] ) ) {
			$enabled_regex_slugs[ $name ] = stripslashes( (string) $row['url'] );
		}
	}

	$mu_plugins = get_option( 'whippet_p_mu_plugins', array() );
	if ( ! is_array( $mu_plugins ) ) {
		$mu_plugins = array();
	}

	$filtered = array();
	foreach ( $plugins as $plugin_path ) {
		$slug = whippet_mu_plugin_slug( $plugin_path );
		if ( '' === $slug ) {
			$filtered[] = $plugin_path;
			continue;
		}
		$disabled = isset( $disabled_by_slug[ $slug ] ) ? $disabled_by_slug[ $slug ] : array();
		$enabled  = array(
			'here'  => ! empty( $enabled_here_slugs[ $slug ] ),
			'regex' => isset( $enabled_regex_slugs[ $slug ] ) ? $enabled_regex_slugs[ $slug ] : '',
		);
		$should_disable = whippet_mu_plugin_should_disable( $slug, $disabled, $enabled, $current_path );
		if ( $should_disable && isset( $mu_plugins[ $slug ] ) && 0 === (int) $mu_plugins[ $slug ] ) {
			$should_disable = false;
		}
		if ( $should_disable ) {
			continue;
		}
		$filtered[] = $plugin_path;
	}

	return $filtered;
}

add_filter( 'option_active_plugins', 'whippet_mu_filter_active_plugins', 1 );

add_action( 'init', function () {
	if ( get_option( 'whippet_mu_version' ) !== WHIPPET_MU_VERSION ) {
		update_option( 'whippet_mu_version', WHIPPET_MU_VERSION );
	}
}, 9999 );
