<?php
/**
 * Settings import allowlist behaviour.
 */

use PHPUnit\Framework\TestCase;
use Whippet\Helper;

class ImportAllowlistTest extends TestCase {

	protected function setUp(): void {
		global $whippet_test_options;
		$whippet_test_options = array(
			'admin_email' => 'owner@example.com',
		);
	}

	public function test_import_simulation_rejects_foreign_keys(): void {
		$payload = array(
			'whippet_analytics' => array(
				'whippet_analytics_id' => 'G-TEST',
				'admin_email'          => 'attacker@evil.com',
			),
		);

		$allowed = Helper::allowed_import_option_keys();
		foreach ( $payload as $group => $values ) {
			if ( ! is_array( $values ) ) {
				continue;
			}
			foreach ( $values as $setting => $setting_value ) {
				if ( in_array( $setting, $allowed, true ) ) {
					update_option( $setting, $setting_value );
				}
			}
		}

		$this->assertSame( 'G-TEST', get_option( 'whippet_analytics_id' ) );
		$this->assertSame( 'owner@example.com', get_option( 'admin_email' ) );
	}
}
