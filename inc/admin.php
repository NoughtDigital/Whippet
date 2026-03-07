<?php
/**
 * Admin Page Template
 *
 * @package Whippet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap whippet-admin" x-data="{
	activeTab: (function() {
		var h = window.location.hash.slice(1) || localStorage.getItem('whippet_tab') || 'dashboard';
		var valid = ['dashboard','performance','analytics','fonts','lazyload','pages','scripts','tools','import-export','docs'];
		return valid.indexOf(h) !== -1 ? h : 'dashboard';
	})(),
	isDragging: false,
	setTab(tab) {
		this.activeTab = tab;
		localStorage.setItem('whippet_tab', tab);
		window.location.hash = tab;
	},
	selectAllDashboard() {
		var form = document.getElementById('whippet-dashboard-form');
		if (form) form.querySelectorAll('input[type=checkbox]').forEach(function(cb) { cb.checked = true; });
	},
	deselectAllDashboard() {
		var form = document.getElementById('whippet-dashboard-form');
		if (form) form.querySelectorAll('input[type=checkbox]').forEach(function(cb) { cb.checked = false; });
	}
}" x-cloak>

<div class="wa-shell">

	<!-- ── Sidebar ─────────────────────────────────────────────── -->
	<aside class="wa-sidebar">

		<div class="wa-brand">
			<div class="wa-brand-icon">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
				</svg>
			</div>
			<div>
				<div class="wa-brand-name"><?php esc_html_e( 'Whippet', 'whippet' ); ?></div>
				<span class="wa-brand-ver">v<?php echo esc_html( WHIPPET_VERSION ); ?></span>
			</div>
		</div>

		<nav class="wa-nav">

			<button class="wa-nav-btn" :class="activeTab === 'dashboard' ? 'active' : ''" @click="setTab('dashboard')">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
				</svg>
				<span><?php esc_html_e( 'Dashboard', 'whippet' ); ?></span>
			</button>

			<button class="wa-nav-btn" :class="activeTab === 'performance' ? 'active' : ''" @click="setTab('performance')">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
				</svg>
				<span><?php esc_html_e( 'Performance', 'whippet' ); ?></span>
			</button>

			<button class="wa-nav-btn" :class="activeTab === 'analytics' ? 'active' : ''" @click="setTab('analytics')">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M18 20V10M12 20V4M6 20v-6"/>
				</svg>
				<span><?php esc_html_e( 'Analytics', 'whippet' ); ?></span>
			</button>

			<button class="wa-nav-btn" :class="activeTab === 'fonts' ? 'active' : ''" @click="setTab('fonts')">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/>
				</svg>
				<span><?php esc_html_e( 'Fonts', 'whippet' ); ?></span>
			</button>

			<button class="wa-nav-btn" :class="activeTab === 'lazyload' ? 'active' : ''" @click="setTab('lazyload')">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
				</svg>
				<span><?php esc_html_e( 'Lazy Load', 'whippet' ); ?></span>
			</button>

			<button class="wa-nav-btn" :class="activeTab === 'pages' ? 'active' : ''" @click="setTab('pages')">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
				</svg>
				<span><?php esc_html_e( 'Preload', 'whippet' ); ?></span>
			</button>

			<button class="wa-nav-btn" :class="activeTab === 'scripts' ? 'active' : ''" @click="setTab('scripts')">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>
				</svg>
				<span><?php esc_html_e( 'Scripts', 'whippet' ); ?></span>
			</button>

			<button class="wa-nav-btn" :class="activeTab === 'tools' ? 'active' : ''" @click="setTab('tools')">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
				</svg>
				<span><?php esc_html_e( 'Tools', 'whippet' ); ?></span>
			</button>

			<div class="wa-nav-divider"></div>

			<button class="wa-nav-btn" :class="activeTab === 'import-export' ? 'active' : ''" @click="setTab('import-export')">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
				</svg>
				<span><?php esc_html_e( 'Import / Export', 'whippet' ); ?></span>
			</button>

			<button class="wa-nav-btn" :class="activeTab === 'docs' ? 'active' : ''" @click="setTab('docs')">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/>
				</svg>
				<span><?php esc_html_e( 'Docs', 'whippet' ); ?></span>
			</button>

		</nav>
	</aside>
	<!-- /sidebar -->

	<!-- ── Main Content ───────────────────────────────────────── -->
	<div class="wa-content">

		<!-- ============================================================
		     DASHBOARD TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'dashboard'" style="display:none"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2><?php esc_html_e( 'Dashboard', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Enable or disable WordPress features to reduce page weight and improve performance.', 'whippet' ); ?></p>
			</div>

			<div class="wa-card">
				<form method="post" action="options.php" id="whippet-dashboard-form">
					<?php
					settings_fields( 'whippet_options' );
					do_settings_sections( 'whippet_options' );
					?>
					<div class="wa-actions">
						<?php submit_button( __( 'Save Settings', 'whippet' ), 'primary', 'submit', false ); ?>
						<button type="button" class="button" @click="selectAllDashboard()"><?php esc_html_e( 'Enable all', 'whippet' ); ?></button>
						<button type="button" class="button" @click="deselectAllDashboard()"><?php esc_html_e( 'Disable all', 'whippet' ); ?></button>
					</div>
				</form>
			</div>
		</div>

		<!-- ============================================================
		     PERFORMANCE TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'performance'" style="display:none"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2><?php esc_html_e( 'Performance', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Page caching, compression, image optimisation, database cleanup and server tuning.', 'whippet' ); ?></p>
			</div>

			<div class="wa-card">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'whippet_performance' );
					do_settings_sections( 'whippet_performance' );
					?>
					<div class="wa-actions">
						<?php submit_button( __( 'Save Settings', 'whippet' ), 'primary', 'submit', false ); ?>
					</div>
				</form>
			</div>
		</div>

		<!-- ============================================================
		     ANALYTICS TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'analytics'" style="display:none"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2><?php esc_html_e( 'Analytics', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Configure your local analytics settings for improved privacy and performance.', 'whippet' ); ?></p>
			</div>

			<div class="wa-card">
				<?php
				if ( function_exists( 'whippet_analytics_settings' ) ) {
					whippet_analytics_settings();
				} else {
					echo '<div style="padding:1.25rem;"><p style="color:#64748b;font-size:.875rem;">' . esc_html__( 'Analytics module not loaded.', 'whippet' ) . '</p></div>';
				}
				?>
			</div>
		</div>

		<!-- ============================================================
		     FONTS TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'fonts'" style="display:none"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2><?php esc_html_e( 'Fonts', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Remove Google Fonts overhead and control font display behaviour.', 'whippet' ); ?></p>
			</div>

			<?php
			if ( isset( $_POST['whippet_fonts_save'] ) && check_admin_referer( 'whippet_fonts', 'whippet_fonts_nonce' ) ) {
				update_option( 'whippet_fonts_enabled', ! empty( $_POST['whippet_fonts_enabled'] ) );
				update_option( 'whippet_fonts_display_swap', ! empty( $_POST['whippet_fonts_display_swap'] ) );
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'whippet' ) . '</p></div>';
			}
			$fonts_enabled = get_option( 'whippet_fonts_enabled', true );
			$fonts_swap    = get_option( 'whippet_fonts_display_swap', false );
			?>

			<div class="wa-card">
				<form method="post">
					<?php wp_nonce_field( 'whippet_fonts', 'whippet_fonts_nonce' ); ?>
					<table class="form-table" role="presentation">
						<tbody>
						<tr>
							<th scope="row"><label for="whippet_fonts_enabled"><?php esc_html_e( 'Remove Google Fonts', 'whippet' ); ?></label></th>
							<td>
								<input type="checkbox" name="whippet_fonts_enabled" id="whippet_fonts_enabled" value="1" <?php checked( $fonts_enabled ); ?> />
								<p class="description"><?php esc_html_e( 'Replace Google Fonts with system fonts — removes external DNS lookups.', 'whippet' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="whippet_fonts_display_swap"><?php esc_html_e( 'font-display: swap', 'whippet' ); ?></label></th>
							<td>
								<input type="checkbox" name="whippet_fonts_display_swap" id="whippet_fonts_display_swap" value="1" <?php checked( $fonts_swap ); ?> />
								<p class="description"><?php esc_html_e( 'Keeps text visible during font load. Only applies when Remove Google Fonts is off.', 'whippet' ); ?></p>
							</td>
						</tr>
						</tbody>
					</table>
					<div class="wa-actions">
						<button type="submit" name="whippet_fonts_save" class="button button-primary"><?php esc_html_e( 'Save Settings', 'whippet' ); ?></button>
					</div>
				</form>
			</div>
		</div>

		<!-- ============================================================
		     LAZY LOAD TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'lazyload'" style="display:none"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2><?php esc_html_e( 'Lazy Load', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Lazy load images and optimise delivery.', 'whippet' ); ?></p>
			</div>

			<?php
			$whippet_ltab  = isset( $_GET['whippet_ltab'] ) ? sanitize_text_field( $_GET['whippet_ltab'] ) : 'lazyload';
			$lazyload_tabs = array(
				'lazyload'       => __( 'Lazy Load', 'whippet' ),
				'cdn'            => __( 'CDN', 'whippet' ),
				'responsiveness' => __( 'Responsiveness', 'whippet' ),
				'compression'    => __( 'Compression', 'whippet' ),
				'webp'           => __( 'WebP', 'whippet' ),
			);
			?>
			<div class="wa-subtabs">
				<?php foreach ( $lazyload_tabs as $key => $label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'whippet', 'whippet_ltab' => $key ), admin_url( 'tools.php' ) ) . '#lazyload' ); ?>"
					   class="wa-subtab-btn <?php echo $whippet_ltab === $key ? 'active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</div>

			<div class="wa-card">
				<?php
				if ( isset( $_POST['submit'] ) ) {
					echo '<div class="notice notice-success is-dismissible" style="margin:1rem 1.25rem 0;"><p>' . esc_html__( 'Settings saved. Clear cache if you use a cache plugin.', 'whippet' ) . '</p></div>';
				}
				switch ( $whippet_ltab ) {
					case 'cdn':            whippet_images_settings_cdn(); break;
					case 'compression':    whippet_images_settings_compression(); break;
					case 'responsiveness': whippet_images_settings_responsiveness(); break;
					case 'webp':           whippet_images_settings_webp(); break;
					default:               whippet_images_settings_lazy_load(); break;
				}
				?>
			</div>
		</div>

		<!-- ============================================================
		     PRELOAD / PAGES TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'pages'" style="display:none"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2><?php esc_html_e( 'Preload', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Preload pages so they appear to load instantly on click.', 'whippet' ); ?></p>
			</div>

			<div class="wa-card">
				<?php
				if ( isset( $_POST['submit'] ) ) {
					echo '<div class="notice notice-success is-dismissible" style="margin:1rem 1.25rem 0;"><p>' . esc_html__( 'Settings saved.', 'whippet' ) . '</p></div>';
				}
				whippet_pages_settings();
				?>
			</div>
		</div>

		<!-- ============================================================
		     SCRIPTS TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'scripts'" style="display:none"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2><?php esc_html_e( 'Scripts', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Delay JavaScript to reduce render-blocking.', 'whippet' ); ?></p>
			</div>

			<div class="wa-card">
				<?php whippet_scripts_view_settings(); ?>
			</div>
		</div>

		<!-- ============================================================
		     TOOLS TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'tools'" style="display:none"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2><?php esc_html_e( 'Tools', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Site health checks and server capability diagnostics.', 'whippet' ); ?></p>
			</div>

			<?php
			$run_checks       = isset( $_GET['whippet_tools'] );
			$is_https         = is_ssl();
			$brotli_available = function_exists( 'brotli_compress' );
			$webp_gd          = function_exists( 'imagewebp' );
			$webp_imagick     = class_exists( 'Imagick' ) && ( new \Imagick() )->queryFormats( 'WEBP' );
			$webp_ok          = $webp_gd || $webp_imagick;
			$php_ver          = phpversion();
			global $wp_version;

			$cc_header  = null;
			$cc_checked = false;
			if ( $run_checks ) {
				$response = wp_remote_get( home_url( '/' ), array( 'timeout' => 10, 'sslverify' => false ) );
				if ( ! is_wp_error( $response ) ) {
					$cc_header  = wp_remote_retrieve_header( $response, 'cache-control' );
					$cc_checked = true;
				}
			}

			$checks = array(
				array(
					'label' => __( 'HTTPS', 'whippet' ),
					'ok'    => $is_https,
					'pass'  => __( 'Your site is served over HTTPS.', 'whippet' ),
					'fail'  => __( 'HTTPS not detected. Required for HTTP/2 and improved performance.', 'whippet' ),
				),
				array(
					'label' => __( 'PHP Brotli', 'whippet' ),
					'ok'    => $brotli_available,
					'pass'  => __( 'php-brotli detected — cached pages will be saved as .br files.', 'whippet' ),
					'fail'  => __( 'php-brotli not installed. Gzip will be used instead.', 'whippet' ),
				),
				array(
					'label' => __( 'WebP (GD / Imagick)', 'whippet' ),
					'ok'    => $webp_ok,
					'pass'  => sprintf( __( 'WebP available via %s.', 'whippet' ), implode( ' + ', array_filter( array( $webp_gd ? 'GD' : '', $webp_imagick ? 'Imagick' : '' ) ) ) ),
					'fail'  => __( 'Neither GD imagewebp() nor Imagick with WebP support found.', 'whippet' ),
				),
			);
			?>

			<div class="wa-tools-checks">
				<?php foreach ( $checks as $check ) : ?>
				<div class="wa-check-card <?php echo $check['ok'] ? 'ok' : 'fail'; ?>">
					<div class="wa-check-badge"><?php echo $check['ok'] ? '✓' : '✗'; ?></div>
					<div>
						<div class="wa-check-label"><?php echo esc_html( $check['label'] ); ?></div>
						<div class="wa-check-msg"><?php echo esc_html( $check['ok'] ? $check['pass'] : $check['fail'] ); ?></div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>

			<div class="wa-card" style="margin-bottom:1rem;">
				<div class="wa-card-section-title"><?php esc_html_e( 'Server Info', 'whippet' ); ?></div>
				<table class="form-table" role="presentation">
					<tbody>
					<tr><th><?php esc_html_e( 'PHP', 'whippet' ); ?></th><td><code><?php echo esc_html( $php_ver ); ?></code></td></tr>
					<tr><th><?php esc_html_e( 'WordPress', 'whippet' ); ?></th><td><code><?php echo esc_html( $wp_version ); ?></code></td></tr>
					<tr><th><?php esc_html_e( 'GD', 'whippet' ); ?></th><td><code><?php echo function_exists( 'gd_info' ) ? esc_html( ( gd_info() )['GD Version'] ?? 'available' ) : esc_html__( 'not available', 'whippet' ); ?></code></td></tr>
					<tr><th><?php esc_html_e( 'Imagick', 'whippet' ); ?></th><td><code><?php echo class_exists( 'Imagick' ) ? esc_html( \Imagick::getVersion()['versionString'] ?? 'available' ) : esc_html__( 'not available', 'whippet' ); ?></code></td></tr>
					<tr><th><?php esc_html_e( 'Gzip', 'whippet' ); ?></th><td><code><?php echo defined( 'ZLIB_VERSION' ) ? esc_html( ZLIB_VERSION ) : ( function_exists( 'ob_gzhandler' ) ? esc_html__( 'available', 'whippet' ) : esc_html__( 'not available', 'whippet' ) ); ?></code></td></tr>
					<tr><th><?php esc_html_e( 'Brotli', 'whippet' ); ?></th><td><code><?php echo $brotli_available ? esc_html__( 'available', 'whippet' ) : esc_html__( 'not available', 'whippet' ); ?></code></td></tr>
					</tbody>
				</table>
			</div>

			<div class="wa-card">
				<div class="wa-card-section-title"><?php esc_html_e( 'Cache-Control Header', 'whippet' ); ?></div>
				<?php if ( $cc_checked ) :
					$no_store = strpos( $cc_header ?? '', 'no-store' ) !== false; ?>
					<?php if ( ! $no_store ) : ?>
						<p style="font-size:.8125rem;color:#15803d;padding:0 1.25rem .75rem;">✓ <?php esc_html_e( '"no-store" not found — caching is not blocked by your server.', 'whippet' ); ?></p>
					<?php else : ?>
						<p style="font-size:.8125rem;color:#b91c1c;padding:0 1.25rem .5rem;">✗ <?php esc_html_e( '"no-store" found — server is preventing caching. Contact your host.', 'whippet' ); ?></p>
						<p style="font-size:.8125rem;padding:0 1.25rem .75rem;"><?php esc_html_e( 'Current:', 'whippet' ); ?> <code><?php echo esc_html( $cc_header ); ?></code></p>
					<?php endif; ?>
				<?php else : ?>
					<p style="font-size:.8125rem;color:#64748b;padding:0 1.25rem .75rem;"><?php esc_html_e( 'Makes an HTTP request to your homepage to inspect response headers.', 'whippet' ); ?></p>
					<div style="padding:0 1.25rem 1rem;">
						<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'whippet', 'whippet_tools' => '1' ), admin_url( 'tools.php' ) ) . '#tools' ); ?>" class="button button-secondary">
							<?php esc_html_e( 'Run Cache-Control Check', 'whippet' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>

		</div>

		<!-- ============================================================
		     IMPORT / EXPORT TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'import-export'" style="display:none"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2><?php esc_html_e( 'Import / Export', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Back up your settings or migrate them to another site.', 'whippet' ); ?></p>
			</div>

			<div class="wa-2col">

				<div class="wa-io-card">
					<h3>
						<svg viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
						</svg>
						<?php esc_html_e( 'Export Settings', 'whippet' ); ?>
					</h3>
					<p><?php esc_html_e( 'Download all plugin settings as a JSON file. Use this to back up or copy your config to another site.', 'whippet' ); ?></p>
					<form method="post">
						<input type="hidden" name="whippet_action" value="export_settings" />
						<?php wp_nonce_field( 'whippet_export_nonce', 'whippet_export_nonce' ); ?>
						<button type="submit" name="submit" class="button button-primary"><?php esc_html_e( 'Download Export File', 'whippet' ); ?></button>
					</form>
				</div>

				<div class="wa-io-card">
					<h3>
						<svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
						</svg>
						<?php esc_html_e( 'Import Settings', 'whippet' ); ?>
					</h3>
					<p><?php esc_html_e( 'Restore settings from a JSON export file. This will overwrite your current configuration.', 'whippet' ); ?></p>
					<form method="post" enctype="multipart/form-data">
						<div class="wa-drop-zone"
							 @drop.prevent="isDragging = false; $refs.importFile.files = $event.dataTransfer.files"
							 @dragover.prevent="isDragging = true"
							 @dragleave.prevent="isDragging = false"
							 :class="isDragging ? 'dragging' : ''"
							 @click="$refs.importFile.click()">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
								<polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/>
								<path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/>
							</svg>
							<p><strong><?php esc_html_e( 'Drop JSON file here', 'whippet' ); ?></strong></p>
							<p><?php esc_html_e( 'or click to browse', 'whippet' ); ?></p>
							<input type="file" name="import_file" x-ref="importFile" style="display:none" accept=".json" />
						</div>
						<input type="hidden" name="whippet_action" value="import_settings" />
						<?php wp_nonce_field( 'whippet_import_nonce', 'whippet_import_nonce' ); ?>
						<button type="submit" name="submit" class="button button-primary"><?php esc_html_e( 'Import Settings', 'whippet' ); ?></button>
					</form>
				</div>

			</div>
		</div>

		<!-- ============================================================
		     DOCS TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'docs'" style="display:none"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2><?php esc_html_e( 'Documentation', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Learn how to use Whippet and fix common issues.', 'whippet' ); ?></p>
			</div>

			<?php
			if ( function_exists( 'whippet_tutorials_page' ) ) {
				whippet_tutorials_page();
			} else {
				?>
				<div class="wa-docs-grid">
					<div class="wa-docs-feature">
						<h3><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg><?php esc_html_e( 'Getting Started', 'whippet' ); ?></h3>
						<ul>
							<li><?php esc_html_e( 'Enable Dashboard tweaks one by one and test after each change', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Turn on Page Caching in Performance for the biggest single speed gain', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Enable Apache Direct-Serve to bypass PHP for cached pages entirely', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Always clear third-party caches after saving settings', 'whippet' ); ?></li>
						</ul>
					</div>
					<div class="wa-docs-feature">
						<h3><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?php esc_html_e( 'Troubleshooting', 'whippet' ); ?></h3>
						<ul>
							<li><?php esc_html_e( 'If a plugin breaks, add its script keyword to the Scripts exclude list', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Use local Analytics for better Core Web Vitals (avoids GA blocking)', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Check Tools tab to verify your server supports Brotli and WebP', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Export settings before major changes as a quick backup', 'whippet' ); ?></li>
						</ul>
					</div>
				</div>
				<?php
			}
			?>
		</div>

	</div>
	<!-- /wa-content -->

</div>
<!-- /wa-shell -->
</div>
<!-- /wrap.whippet-admin -->
