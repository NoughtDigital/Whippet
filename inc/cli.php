<?php
/**
 * WP-CLI commands.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	class CLI {
		/**
		 * Print Whippet settings as JSON.
		 */
		public function export() {
			\WP_CLI::line( wp_json_encode( array( 'whippet_core' => get_option( 'whippet_options', array() ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
		}

		/**
		 * Show current Whippet status.
		 */
		public function status() {
			$options = (array) get_option( 'whippet_options', array() );
			\WP_CLI\Utils\format_items(
				'table',
				array(
					array( 'setting' => 'Version', 'value' => defined( 'WHIPPET_VERSION' ) ? WHIPPET_VERSION : 'unknown' ),
					array( 'setting' => 'Page cache', 'value' => '1' === (string) ( $options['enable_page_cache'] ?? '0' ) ? 'enabled' : 'disabled' ),
					array( 'setting' => 'Cloud plan', 'value' => get_option( 'whippet_cloud_plan', 'free' ) ),
					array( 'setting' => 'Recovery mode', 'value' => RecoveryMode::is_active() ? 'active' : 'inactive' ),
				),
				array( 'setting', 'value' )
			);
		}

	}

	class CacheCLI {
		/**
		 * Clear the Whippet page cache.
		 */
		public function clear() {
			$cache = new Cache();
			$cache->clear_all_cache();
			\WP_CLI::success( 'Whippet cache cleared.' );
		}
	}

	class SetupCLI {
		/**
		 * Mark setup as complete.
		 */
		public function complete() {
			update_option( 'whippet_setup_completed', gmdate( 'c' ), false );
			Helper::audit_log( 'setup_completed_cli' );
			\WP_CLI::success( 'Whippet setup marked as complete.' );
		}
	}

	class ImpactCLI {
		/**
		 * Capture an impact metrics snapshot.
		 */
		public function capture() {
			\WP_CLI::line( wp_json_encode( ImpactMetrics::capture(), JSON_PRETTY_PRINT ) );
		}
	}

	\WP_CLI::add_command( 'whippet', __NAMESPACE__ . '\CLI' );
	\WP_CLI::add_command( 'whippet cache', __NAMESPACE__ . '\CacheCLI' );
	\WP_CLI::add_command( 'whippet setup', __NAMESPACE__ . '\SetupCLI' );
	\WP_CLI::add_command( 'whippet impact', __NAMESPACE__ . '\ImpactCLI' );
}
