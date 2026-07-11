<?php
/**
 * Retire duplicate Settings menus and redirect to the unified shell.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy menu cleanup.
 */
class LegacyMenus {

	/**
	 * Boot hooks.
	 */
	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'remove_legacy_menus' ), 999 );
		add_action( 'admin_init', array( __CLASS__, 'redirect_legacy_pages' ) );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_import_notice' ) );
	}

	/**
	 * Remove standalone Settings → Whippet* pages.
	 */
	public static function remove_legacy_menus() {
		remove_submenu_page( 'options-general.php', 'whippet-analytics' );
		remove_submenu_page( 'options-general.php', 'whippet-scripts' );
		remove_submenu_page( 'options-general.php', 'whippet-pages' );
		remove_submenu_page( 'options-general.php', 'whippet-images' );
		remove_submenu_page( 'tools.php', 'whippet-import-export' );
		remove_submenu_page( 'tools.php', 'whippet-tutorials' );
	}

	/**
	 * Redirect bookmarked legacy admin URLs into Tools → Whippet tabs.
	 */
	public static function redirect_legacy_pages() {
		if ( ! is_admin() || ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$page = sanitize_key( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$map  = array(
			'whippet-analytics'     => 'analytics',
			'whippet-scripts'       => 'scripts',
			'whippet-pages'         => 'pages',
			'whippet-images'        => 'lazyload',
			'whippet-import-export' => 'import-export',
			'whippet-tutorials'     => 'docs',
		);

		if ( ! isset( $map[ $page ] ) ) {
			return;
		}

		wp_safe_redirect(
			admin_url( 'tools.php?page=whippet&whippet_tab=' . $map[ $page ] . '#' . $map[ $page ] )
		);
		exit;
	}

	/**
	 * Show success notice after settings import.
	 */
	public static function maybe_import_notice() {
		if ( ! Helper::user_can_manage() ) {
			return;
		}
		$key  = 'whippet_import_notice_' . get_current_user_id();
		$data = get_transient( $key );
		if ( ! is_array( $data ) ) {
			return;
		}
		delete_transient( $key );
		$applied = isset( $data['applied'] ) ? implode( ', ', array_map( 'strval', (array) $data['applied'] ) ) : '';
		echo '<div class="notice notice-success is-dismissible"><p>';
		echo esc_html(
			sprintf(
				/* translators: %s: applied import groups */
				__( 'Whippet settings imported successfully. Applied: %s', 'whippet' ),
				$applied ? $applied : __( 'none', 'whippet' )
			)
		);
		echo '</p></div>';
	}
}
