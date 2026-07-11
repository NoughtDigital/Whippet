<?php
/**
 * Whippet product platform bootstrap.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once WHIPPET_PATH . 'inc/classes/Capabilities.php';
require_once WHIPPET_PATH . 'inc/conflict-detector.php';
require_once WHIPPET_PATH . 'inc/setup-wizard.php';
require_once WHIPPET_PATH . 'inc/settings-snapshots.php';
require_once WHIPPET_PATH . 'inc/impact-metrics.php';
require_once WHIPPET_PATH . 'inc/recovery-mode.php';
require_once WHIPPET_PATH . 'inc/cloud-connect.php';
require_once WHIPPET_PATH . 'inc/cdn-integrations.php';
require_once WHIPPET_PATH . 'inc/anomaly-alerts.php';
require_once WHIPPET_PATH . 'inc/script-advisor.php';
require_once WHIPPET_PATH . 'inc/woo-excludes.php';
require_once WHIPPET_PATH . 'inc/cli.php';
require_once WHIPPET_PATH . 'inc/multisite.php';
require_once WHIPPET_PATH . 'inc/rule-intelligence.php';
require_once WHIPPET_PATH . 'inc/asset-integrity.php';
require_once WHIPPET_PATH . 'inc/legacy-menus.php';
require_once WHIPPET_PATH . 'inc/admin-platform-panels.php';

SetupWizard::boot();
SettingsSnapshots::boot();
ImpactMetrics::boot();
RecoveryMode::boot();
CloudConnect::boot();
CdnIntegrations::boot();
AnomalyAlerts::boot();
ScriptAdvisor::boot();
WooExcludes::boot();
Multisite::boot();
RuleIntelligence::boot();
AssetIntegrity::boot();
LegacyMenus::boot();

add_action( 'admin_notices', array( '\Whippet\ConflictDetector', 'render_notice_html' ) );
