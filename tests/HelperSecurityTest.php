<?php
/**
 * Security helper unit tests.
 */

use PHPUnit\Framework\TestCase;
use Whippet\Helper;

class HelperSecurityTest extends TestCase {

	protected function setUp(): void {
		global $whippet_test_options;
		$whippet_test_options = array();
	}

	public function test_php_snippets_disabled_by_default(): void {
		$this->assertFalse( Helper::php_snippets_enabled() );
	}

	public function test_php_snippets_enabled_when_option_set(): void {
		update_option( 'whippet_php_snippets_enabled', 1 );
		$this->assertTrue( Helper::php_snippets_enabled() );
	}

	public function test_allowed_import_option_keys_are_allowlisted(): void {
		$keys = Helper::allowed_import_option_keys();
		$this->assertContains( 'whippet_analytics_id', $keys );
		$this->assertNotContains( 'admin_email', $keys );
		$this->assertNotContains( 'siteurl', $keys );
		$this->assertNotContains( 'active_plugins', $keys );
	}

	public function test_mask_secret_hides_most_of_key(): void {
		$masked = Helper::mask_secret( 'abcdefghijklmnop' );
		$this->assertSame( 'abcd', substr( $masked, 0, 4 ) );
		$this->assertStringNotContainsString( 'efghijklmnop', $masked );
		$this->assertStringContainsString( '*', $masked );
	}

	public function test_ssl_verify_defaults_true(): void {
		$this->assertTrue( Helper::ssl_verify() );
	}

	public function test_resolve_path_rejects_traversal(): void {
		$root = sys_get_temp_dir() . '/whippet-test-root-' . uniqid();
		mkdir( $root );
		$file = $root . '/ok.txt';
		file_put_contents( $file, 'ok' );

		$this->assertSame(
			wp_normalize_path( realpath( $file ) ),
			Helper::resolve_path_under_root( $file, $root )
		);
		$this->assertSame( '', Helper::resolve_path_under_root( $root . '/../ok.txt', $root ) );
		$this->assertSame( '', Helper::resolve_path_under_root( $root . '/../../etc/passwd', $root ) );

		unlink( $file );
		rmdir( $root );
	}
}
