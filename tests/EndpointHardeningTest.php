<?php
/**
 * MU bypass and webhook source guards.
 */

use PHPUnit\Framework\TestCase;

class EndpointHardeningTest extends TestCase {

	public function test_mu_bypass_requires_token_or_admin_nonce(): void {
		$source = file_get_contents( dirname( __DIR__ ) . '/inc/whippet_mu.php' );
		$this->assertNotFalse( $source );
		$this->assertStringContainsString( 'whippet_mu_bypass_token', $source );
		$this->assertStringContainsString( 'hash_equals', $source );
		$this->assertStringContainsString( 'manage_options', $source );
		$this->assertStringNotContainsString(
			"sanitize_text_field( wp_unslash( \$_GET['whippet_mu_mode'] ) ) === 'off' ) {\n\t\treturn \$plugins;",
			$source
		);
	}

	public function test_webhook_fail_closed_without_secret(): void {
		$source = file_get_contents( dirname( __DIR__ ) . '/inc/image-engine.php' );
		$this->assertNotFalse( $source );
		$this->assertStringContainsString( 'webhook_secret( false )', $source );
		$this->assertStringContainsString( 'Webhook secret unavailable', $source );
		$this->assertStringContainsString( 'ie_webhook_seen_', $source );
	}

	public function test_import_uses_allowlist(): void {
		$source = file_get_contents( dirname( __DIR__ ) . '/inc/import-export.php' );
		$this->assertNotFalse( $source );
		$this->assertStringContainsString( 'allowed_import_option_keys', $source );
	}
}
