<?php
/**
 * Required asset integrity checks.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AssetIntegrity {
	public static function boot() {
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
	}

	public static function required_files() {
		return array(
			'analytics/js/gtag.js',
			'analytics/js/minimal-analytics.js',
			'pages/whippet-pages.min.js',
			'dist/css/admin.css',
			'js/editor.js',
		);
	}

	public static function missing_files() {
		return array_values(
			array_filter(
				self::required_files(),
				static function ( $file ) {
					return ! is_readable( WHIPPET_PATH . $file );
				}
			)
		);
	}

	public static function notice() {
		if ( ! Helper::is_whippet_page() || ! Helper::user_can_manage() ) {
			return;
		}
		$missing = self::missing_files();
		if ( $missing ) {
			printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( sprintf( __( 'Whippet is missing required assets: %s. Reinstall the plugin package before enabling optimisation.', 'whippet' ), implode( ', ', $missing ) ) ) );
		}
	}

	public static function render_ui() {
		$missing = self::missing_files();
		?>
		<div class="wa-card">
			<div class="wa-card-section-title"><?php esc_html_e( 'Asset integrity', 'whippet' ); ?></div>
			<div class="wa-card-body">
				<p><?php echo $missing ? esc_html( sprintf( __( 'Missing: %s', 'whippet' ), implode( ', ', $missing ) ) ) : esc_html__( 'All required Whippet assets are present.', 'whippet' ); ?></p>
			</div>
		</div>
		<?php
	}
}
