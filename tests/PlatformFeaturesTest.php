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
