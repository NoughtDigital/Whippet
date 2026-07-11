<?php
/**
 * Cache plugin conflict detection.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ConflictDetector {
	/**
	 * Known cache and asset optimisation plugins.
	 *
	 * @return array<string,array<string,string>>
	 */
	private static function plugins() {
		return array(
			'wp-rocket/wp-rocket.php'                       => array( 'name' => 'WP Rocket', 'type' => 'page_cache' ),
			'litespeed-cache/litespeed-cache.php'           => array( 'name' => 'LiteSpeed Cache', 'type' => 'page_cache' ),
			'w3-total-cache/w3-total-cache.php'              => array( 'name' => 'W3 Total Cache', 'type' => 'page_cache' ),
			'wp-super-cache/wp-cache.php'                    => array( 'name' => 'WP Super Cache', 'type' => 'page_cache' ),
			'wp-fastest-cache/wpFastestCache.php'            => array( 'name' => 'WP Fastest Cache', 'type' => 'page_cache' ),
			'wp-hummingbird/wp-hummingbird.php'              => array( 'name' => 'Hummingbird', 'type' => 'page_cache' ),
			'sg-cachepress/sg-cachepress.php'                => array( 'name' => 'Speed Optimizer', 'type' => 'page_cache' ),
			'nitropack/main.php'                             => array( 'name' => 'NitroPack', 'type' => 'page_cache' ),
			'flying-press/flying-press.php'                  => array( 'name' => 'FlyingPress', 'type' => 'page_cache' ),
			'autoptimize/autoptimize.php'                    => array( 'name' => 'Autoptimize', 'type' => 'assets' ),
		);
	}

	/**
	 * Return active conflicts.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function get_conflicts() {
		$active = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		$conflicts = array();
		foreach ( self::plugins() as $file => $details ) {
			if ( in_array( $file, $active, true ) ) {
				$details['file'] = $file;
				$conflicts[]     = $details;
			}
		}
		return $conflicts;
	}

	/**
	 * Whether another page cache is active.
	 *
	 * @return bool
	 */
	public static function has_page_cache_conflict() {
		foreach ( self::get_conflicts() as $conflict ) {
			if ( 'page_cache' === $conflict['type'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Render an admin notice describing detected conflicts.
	 */
	public static function render_notice_html() {
		if ( ! Helper::user_can_manage() || ! Helper::is_whippet_page() ) {
			return;
		}
		$conflicts = self::get_conflicts();
		if ( empty( $conflicts ) ) {
			return;
		}

		$cache_names = array();
		$asset_names = array();
		foreach ( $conflicts as $conflict ) {
			if ( 'page_cache' === $conflict['type'] ) {
				$cache_names[] = $conflict['name'];
			} else {
				$asset_names[] = $conflict['name'];
			}
		}

		if ( $cache_names ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html( sprintf( __( 'Whippet detected another page cache: %s. Use only one page cache to avoid stale or inconsistent pages.', 'whippet' ), implode( ', ', $cache_names ) ) )
			);
		}
		if ( $asset_names ) {
			printf(
				'<div class="notice notice-info"><p>%s</p></div>',
				esc_html( sprintf( __( 'Whippet detected %s. Review overlapping asset optimisation settings before enabling script changes.', 'whippet' ), implode( ', ', $asset_names ) ) )
			);
		}
	}
}
