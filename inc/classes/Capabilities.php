<?php
/**
 * Whippet capabilities.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Capabilities {
	/**
	 * Register plugin capabilities for administrators.
	 */
	public static function register() {
		$role = get_role( 'administrator' );
		if ( ! $role ) {
			return;
		}

		foreach ( array( 'whippet_manage', 'whippet_scripts', 'whippet_snippets', 'whippet_premium' ) as $capability ) {
			$role->add_cap( $capability );
		}
	}

	/**
	 * Check a Whippet capability with manage_options as a compatibility fallback.
	 *
	 * @param string $capability Capability name.
	 * @return bool
	 */
	public static function user_can( $capability ) {
		$capability = sanitize_key( $capability );
		return current_user_can( $capability ) || current_user_can( 'manage_options' );
	}
}
