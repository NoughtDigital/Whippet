<?php
/**
 * Admin platform panel renderers.
 *
 * @package Whippet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function whippet_render_impact_panel() {
	\Whippet\ImpactMetrics::render_ui();
}

function whippet_render_activity_log_panel() {
	$entries = (array) get_option( 'whippet_audit_log', array() );
	echo '<div class="wa-card"><div class="wa-card-section-title">' . esc_html__( 'Activity log', 'whippet' ) . '</div>';
	if ( ! $entries ) {
		echo '<p>' . esc_html__( 'No recorded activity yet.', 'whippet' ) . '</p>';
	} else {
		echo '<table class="widefat striped" style="margin:0 0 1rem;"><thead><tr><th>' . esc_html__( 'When', 'whippet' ) . '</th><th>' . esc_html__( 'Action', 'whippet' ) . '</th><th>' . esc_html__( 'User', 'whippet' ) . '</th></tr></thead><tbody>';
		foreach ( array_slice( $entries, 0, 25 ) as $entry ) {
			$user = isset( $entry['user_id'] ) ? get_userdata( (int) $entry['user_id'] ) : false;
			printf(
				'<tr><td>%1$s</td><td>%2$s</td><td>%3$s</td></tr>',
				esc_html( function_exists( 'wp_date' ) ? wp_date( 'j F Y, H:i', strtotime( $entry['time'] ?? 'now' ) ) : ( $entry['time'] ?? '' ) ),
				esc_html( str_replace( '_', ' ', $entry['action'] ?? '' ) ),
				esc_html( $user ? $user->user_login : (string) ( $entry['user_id'] ?? '—' ) )
			);
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}

function whippet_render_snapshots_panel() {
	\Whippet\SettingsSnapshots::render_ui();
}

function whippet_render_recovery_panel() {
	\Whippet\RecoveryMode::render_ui();
}

function whippet_render_cdn_panel() {
	\Whippet\CdnIntegrations::render_settings_ui();
}

function whippet_render_alerts_panel() {
	\Whippet\AnomalyAlerts::render_settings_ui();
}

function whippet_render_advisor_panel() {
	\Whippet\ScriptAdvisor::render_ui();
}

function whippet_render_cloud_panel() {
	\Whippet\CloudConnect::render_connect_ui();
}

function whippet_render_telemetry_panel() {
	\Whippet\RuleIntelligence::render_ui();
}
