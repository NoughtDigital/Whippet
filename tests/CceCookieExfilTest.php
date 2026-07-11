<?php
/**
 * Auth cookie forwarding regression guard.
 */

use PHPUnit\Framework\TestCase;

class CceCookieExfilTest extends TestCase {

	public function test_cce_capture_auth_cookies_source_returns_empty_array(): void {
		$source = file_get_contents( dirname( __DIR__ ) . '/inc/critical-css.php' );
		$this->assertNotFalse( $source );
		$this->assertStringContainsString( 'function cce_capture_auth_cookies(): array', $source );
		$this->assertStringContainsString( "return [];", $source );
		$this->assertStringNotContainsString( "wordpress_logged_in_", $source );
	}
}
