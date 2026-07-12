<?php
/**
 * Platform feature regression tests.
 */

use PHPUnit\Framework\TestCase;
use Whippet\Helper;
use Whippet\SetupWizard;
use Whippet\ConflictDetector;
use Whippet\ScriptAdvisor;
use Whippet\RecoveryMode;

class PlatformFeaturesTest extends TestCase {

	protected function setUp(): void {
		global $whippet_test_options;
		$whippet_test_options = array();
	}

	public function test_core_import_allowlist_rejects_unknown_keys(): void {
		$clean = Helper::sanitise_imported_core_options(
			array(
				'disable_emojis' => '1',
				'admin_email'    => 'evil@example.com',
				'siteurl'        => 'https://evil.example',
				'enable_page_cache' => '1',
			)
		);
		$this->assertArrayHasKey( 'disable_emojis', $clean );
		$this->assertArrayHasKey( 'enable_page_cache', $clean );
		$this->assertArrayNotHasKey( 'admin_email', $clean );
		$this->assertArrayNotHasKey( 'siteurl', $clean );
	}

	public function test_safe_preset_keys_are_conservative(): void {
		$keys = SetupWizard::safe_preset_keys();
		$this->assertContains( 'disable_emojis', $keys );
		$this->assertNotContains( 'enable_page_cache', $keys );
		$this->assertNotContains( 'maintenance_mode_enabled', $keys );
	}

	public function test_script_advisor_only_suggests_observed_handles(): void {
		$suggestions = ScriptAdvisor::suggest( array( 'wc-cart-fragments', 'unknown-handle-xyz' ) );
		$handles     = array_column( $suggestions, 'handle' );
		$this->assertContains( 'wc-cart-fragments', $handles );
		$this->assertNotContains( 'unknown-handle-xyz', $handles );
		foreach ( $suggestions as $item ) {
			$this->assertArrayHasKey( 'reason', $item );
			$this->assertArrayHasKey( 'confidence', $item );
		}
	}

	public function test_recovery_mode_filter_disables_when_active(): void {
		$_COOKIE[ RecoveryMode::COOKIE ] = '1';
		$this->assertTrue( RecoveryMode::is_active() );
		$this->assertFalse( RecoveryMode::disable_when_active( true ) );
		unset( $_COOKIE[ RecoveryMode::COOKIE ] );
		$this->assertTrue( RecoveryMode::disable_when_active( true ) );
	}

	public function test_recovery_mode_registers_facade_filter(): void {
		$source = file_get_contents( dirname( __DIR__ ) . '/inc/recovery-mode.php' );
		$this->assertStringContainsString( 'whippet_youtube_facade_enabled', $source );
	}

	public function test_sanitize_merges_partial_tab_saves(): void {
		if ( ! function_exists( 'add_option' ) ) {
			function add_option( $key, $value ) { // phpcs:ignore
				return update_option( $key, $value );
			}
		}
		if ( ! function_exists( 'add_settings_section' ) ) {
			function add_settings_section() { return true; }
		}
		if ( ! function_exists( 'add_settings_field' ) ) {
			function add_settings_field() { return true; }
		}
		if ( ! function_exists( 'register_setting' ) ) {
			function register_setting() { return true; }
		}
		require_once dirname( __DIR__ ) . '/inc/settings.php';
		update_option(
			'whippet_options',
			array(
				'disable_emojis'    => '1',
				'enable_page_cache' => '1',
				'enable_minify_css' => '1',
			)
		);
		$_POST['option_page'] = 'whippet_options';
		$out                  = \Whippet\Settings::whippet_sanitize_extras(
			array(
				'disable_emojis'  => '1',
				'hide_wp_version' => '1',
			)
		);
		$this->assertSame( '1', $out['disable_emojis'] );
		$this->assertSame( '1', $out['hide_wp_version'] );
		$this->assertSame( '1', $out['enable_page_cache'], 'Performance keys must survive a Dashboard save' );
		$this->assertSame( '1', $out['enable_minify_css'] );
		unset( $_POST['option_page'] );
	}

	public function test_cache_controls_registered_on_performance_page(): void {
		$source = file_get_contents( dirname( __DIR__ ) . '/inc/settings.php' );
		$this->assertMatchesRegularExpression(
			"/whippet_cache_controls'[\s\S]{0,200}'whippet_performance'/",
			$source
		);
		$this->assertStringContainsString( 'array_merge( $existing, $values )', $source );
	}

	public function test_cce_api_calls_include_sslverify(): void {
		$source = file_get_contents( dirname( __DIR__ ) . '/inc/critical-css.php' );
		$this->assertNotFalse( $source );
		$this->assertGreaterThanOrEqual( 7, substr_count( $source, "Helper::ssl_verify()" ) );
		$this->assertStringContainsString( "/v1/wordpress/critical-css", $source );
		$this->assertMatchesRegularExpression( "/critical-css',\s*\[[^\]]*sslverify/s", $source );
	}

	public function test_required_runtime_assets_exist(): void {
		$root = dirname( __DIR__ );
		$this->assertFileExists( $root . '/analytics/js/gtag.js' );
		$this->assertFileExists( $root . '/analytics/js/minimal-analytics.js' );
		$this->assertFileExists( $root . '/pages/whippet-pages.min.js' );
		$this->assertFileExists( $root . '/js/editor.js' );
		$this->assertFileExists( $root . '/inc/minify.php' );
		$this->assertFileExists( $root . '/inc/cdn-rewrite.php' );
		$this->assertFileExists( $root . '/inc/speculative-loading.php' );
		$this->assertFileExists( $root . '/inc/lazy-render.php' );
	}

	public function test_core_allowlist_includes_new_performance_keys(): void {
		$keys = Helper::allowed_core_option_keys();
		$this->assertContains( 'enable_minify_css', $keys );
		$this->assertContains( 'enable_defer_js', $keys );
		$this->assertContains( 'enable_cdn_rewrite', $keys );
		$this->assertContains( 'enable_speculation', $keys );
		$this->assertContains( 'enable_youtube_facade', $keys );
		$this->assertContains( 'enable_lazy_render', $keys );
		$this->assertContains( 'disable_admin_bar', $keys );
	}

	public function test_lazy_render_rewrites_footer(): void {
		require_once dirname( __DIR__ ) . '/inc/lazy-render.php';
		$html = '<html><body><footer id="colophon" class="site-footer"><p>x</p></footer></body></html>';
		update_option( 'whippet_options', array( 'enable_lazy_render' => '1' ) );
		$out = \Whippet\LazyRender::rewrite( $html );
		$this->assertStringContainsString( 'whippet-lazy-render', $out );
	}

	public function test_cloud_defaults_are_production(): void {
		$cce = file_get_contents( dirname( __DIR__ ) . '/inc/critical-css.php' );
		$ie  = file_get_contents( dirname( __DIR__ ) . '/inc/image-engine.php' );
		$this->assertStringContainsString( 'https://api.whippetwp.com/critical-css', $cce );
		$this->assertStringContainsString( 'https://api.whippetwp.com/image-engine', $ie );
		$this->assertStringNotContainsString( "DEFAULT_BASE_URL = 'http://localhost:3000'", $cce );
	}

	public function test_conflict_detector_page_cache_flag(): void {
		update_option( 'active_plugins', array( 'wp-rocket/wp-rocket.php' ) );
		$this->assertTrue( ConflictDetector::has_page_cache_conflict() );
		$conflicts = ConflictDetector::get_conflicts();
		$this->assertSame( 'WP Rocket', $conflicts[0]['name'] );
		update_option( 'active_plugins', array() );
		$this->assertFalse( ConflictDetector::has_page_cache_conflict() );
	}
}
