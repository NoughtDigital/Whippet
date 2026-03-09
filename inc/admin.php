<?php
/**
 * Admin Page Template
 *
 * @package Whippet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$whippet_valid_tabs = array( 'dashboard', 'performance', 'analytics', 'fonts', 'lazyload', 'pages', 'scripts', 'snippets', 'extras', 'tools', 'import-export', 'docs', 'premium' );
$whippet_active_tab = isset( $_GET['whippet_tab'] ) ? sanitize_key( wp_unslash( $_GET['whippet_tab'] ) ) : 'dashboard';
if ( ! in_array( $whippet_active_tab, $whippet_valid_tabs, true ) ) {
	$whippet_active_tab = 'dashboard';
}

?>
<div class="wrap whippet-admin" x-data="{
	activeTab: (function() {
		var serverTab = '<?php echo esc_js( $whippet_active_tab ); ?>';
		var h = window.location.hash.slice(1) || serverTab || localStorage.getItem('whippet_tab') || 'dashboard';
		var valid = ['dashboard','performance','analytics','fonts','lazyload','pages','scripts','snippets','extras','tools','import-export','docs','premium'];
		return valid.indexOf(h) !== -1 ? h : serverTab;
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
				<span><?php esc_html_e( 'Script Manager', 'whippet' ); ?></span>
			</button>

			<button class="wa-nav-btn" :class="activeTab === 'snippets' ? 'active' : ''" @click="setTab('snippets')">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M20 7l-8 8-4-4"/><path d="M14 7h7v7"/><path d="M4 4h7v7"/>
				</svg>
				<span><?php esc_html_e( 'Snippets', 'whippet' ); ?></span>
			</button>

			<button class="wa-nav-btn" :class="activeTab === 'extras' ? 'active' : ''" @click="setTab('extras')">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 2v20M2 12h20"/>
				</svg>
				<span><?php esc_html_e( 'Extras', 'whippet' ); ?></span>
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

			<div class="wa-nav-divider"></div>

			<button class="wa-nav-btn wa-nav-btn--premium" :class="activeTab === 'premium' ? 'active' : ''" @click="setTab('premium')">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
				</svg>
				<span><?php esc_html_e( 'Premium', 'whippet' ); ?></span>
			</button>

		</nav>
	</aside>
	<!-- /sidebar -->

	<!-- ── Main Content ───────────────────────────────────────── -->
	<div class="wa-content">

		<!-- ============================================================
		     DASHBOARD TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'dashboard'" style="<?php echo 'dashboard' === $whippet_active_tab ? '' : 'display:none'; ?>"
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
		<div class="wa-panel" x-show="activeTab === 'performance'" style="<?php echo 'performance' === $whippet_active_tab ? '' : 'display:none'; ?>"
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
		<div class="wa-panel" x-show="activeTab === 'analytics'" style="<?php echo 'analytics' === $whippet_active_tab ? '' : 'display:none'; ?>"
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
		<div class="wa-panel" x-show="activeTab === 'fonts'" style="<?php echo 'fonts' === $whippet_active_tab ? '' : 'display:none'; ?>"
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
		<div class="wa-panel" x-show="activeTab === 'lazyload'" style="<?php echo 'lazyload' === $whippet_active_tab ? '' : 'display:none'; ?>"
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
				if ( isset( $_POST['submit'] ) && isset( $_POST['whippet-images-settings-form'] ) ) {
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
		<div class="wa-panel" x-show="activeTab === 'pages'" style="<?php echo 'pages' === $whippet_active_tab ? '' : 'display:none'; ?>"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2><?php esc_html_e( 'Preload', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Preload pages so they appear to load instantly on click.', 'whippet' ); ?></p>
			</div>

			<div class="wa-card">
				<?php
				if ( isset( $_POST['submit'] ) && isset( $_POST['whippet_pages_settings_form'] ) ) {
					echo '<div class="notice notice-success is-dismissible" style="margin:1rem 1.25rem 0;"><p>' . esc_html__( 'Settings saved.', 'whippet' ) . '</p></div>';
				}
				whippet_pages_settings();
				?>
			</div>
		</div>

		<!-- ============================================================
		     SCRIPTS TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'scripts'" style="<?php echo 'scripts' === $whippet_active_tab ? '' : 'display:none'; ?>"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2><?php esc_html_e( 'Script Manager', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Delay JavaScript, combine matching rules with regex, and safely test changes before going live.', 'whippet' ); ?></p>
			</div>

			<div class="wa-card">
				<?php whippet_scripts_view_settings(); ?>
			</div>
		</div>

		<!-- ============================================================
		     SNIPPETS TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'snippets'" style="<?php echo 'snippets' === $whippet_active_tab ? '' : 'display:none'; ?>"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2><?php esc_html_e( 'Code Snippets', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Add and manage PHP, CSS, JS, and HTML snippets with file or inline delivery, optimization flags, conditions, and safe mode.', 'whippet' ); ?></p>
			</div>

		<?php
		$whippet_stab = isset( $_GET['whippet_stab'] ) ? sanitize_text_field( wp_unslash( $_GET['whippet_stab'] ) ) : 'editor';
		$snippet_tabs = array(
			'editor' => __( 'Add New Snippet', 'whippet' ),
			'list'   => __( 'Snippets', 'whippet' ),
		);
		$snippet_count = 0;
		if ( class_exists( '\Whippet\SnippetManager' ) ) {
			$snippet_count = count( \Whippet\SnippetManager::instance()->load_snippets() );
		}
		?>
		<div class="wa-subtabs">
			<?php foreach ( $snippet_tabs as $key => $label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'whippet', 'whippet_stab' => $key ), admin_url( 'tools.php' ) ) . '#snippets' ); ?>"
				   class="wa-subtab-btn <?php echo $whippet_stab === $key ? 'active' : ''; ?>">
					<?php echo esc_html( $label ); ?>
					<?php if ( 'list' === $key && $snippet_count > 0 ) : ?>
						<sup class="wa-subtab-badge"><?php echo esc_html( (string) $snippet_count ); ?></sup>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>

			<div class="wa-card">
				<?php
				if ( function_exists( '\Whippet\whippet_render_snippets_manager' ) ) {
					\Whippet\whippet_render_snippets_manager();
				} else {
					echo '<div style="padding:1.25rem;"><p style="color:#64748b;font-size:.875rem;">' . esc_html__( 'Snippet manager module not loaded.', 'whippet' ) . '</p></div>';
				}
				?>
			</div>
		</div>

		<!-- ============================================================
		     EXTRAS TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'extras'" style="<?php echo 'extras' === $whippet_active_tab ? '' : 'display:none'; ?>"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2><?php esc_html_e( 'Extras', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Header/body/footer code, cookie warning bar, maintenance mode, post and page cloner, social sharing, and WooCommerce utility toggles.', 'whippet' ); ?></p>
			</div>

			<div class="wa-card">
				<?php
				if ( function_exists( '\Whippet\whippet_render_extras_settings' ) ) {
					\Whippet\whippet_render_extras_settings();
				} else {
					echo '<div style="padding:1.25rem;"><p style="color:#64748b;font-size:.875rem;">' . esc_html__( 'Extras module not loaded.', 'whippet' ) . '</p></div>';
				}
				?>
			</div>
		</div>

		<!-- ============================================================
		     TOOLS TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'tools'" style="<?php echo 'tools' === $whippet_active_tab ? '' : 'display:none'; ?>"
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
		<div class="wa-panel" x-show="activeTab === 'import-export'" style="<?php echo 'import-export' === $whippet_active_tab ? '' : 'display:none'; ?>"
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
		<div class="wa-panel" x-show="activeTab === 'docs'" style="<?php echo 'docs' === $whippet_active_tab ? '' : 'display:none'; ?>"
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

		<!-- ============================================================
		     PREMIUM TAB
		     ============================================================ -->
		<div class="wa-panel" x-show="activeTab === 'premium'" style="<?php echo 'premium' === $whippet_active_tab ? '' : 'display:none'; ?>"
			 x-transition:enter="wa-fade-in" x-transition:enter-start="wa-fade-start" x-transition:enter-end="wa-fade-end">

			<div class="wa-panel-hd">
				<h2>
					<?php esc_html_e( 'Premium', 'whippet' ); ?>
					<span class="wa-premium-badge"><?php esc_html_e( 'Premium', 'whippet' ); ?></span>
				</h2>
		<p><?php esc_html_e( 'Premium features for advanced performance — image optimisation, delivery, and critical CSS generation.', 'whippet' ); ?></p>
			</div>

		<?php
		$whippet_ptab = isset( $_GET['whippet_ptab'] ) ? sanitize_text_field( $_GET['whippet_ptab'] ) : 'image-engine';
		$premium_tabs = [
			'image-engine' => __( 'Image Engine', 'whippet' ),
			'critical-css' => __( 'Critical CSS', 'whippet' ),
		];
		?>
		<div class="wa-subtabs">
			<?php foreach ( $premium_tabs as $key => $label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'whippet', 'whippet_ptab' => $key ], admin_url( 'tools.php' ) ) . '#premium' ); ?>"
				   class="wa-subtab-btn <?php echo $whippet_ptab === $key ? 'active' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endforeach; ?>
		</div>

	<?php if ( 'image-engine' === $whippet_ptab ) : ?>

		<?php
		$ie            = class_exists( 'Whippet_Image_Engine' ) ? Whippet_Image_Engine::get_instance() : null;
		$ie_configured = $ie && $ie->is_configured();

		$ie_stats       = $ie_configured ? $ie->get_last_run_stats() : null;
		$total_images   = ( ! is_wp_error( $ie_stats ) && $ie_stats ) ? absint( $ie_stats['total_images'] ?? 0 ) : 0;
		$ie_processed   = ( ! is_wp_error( $ie_stats ) && $ie_stats ) ? absint( $ie_stats['processed_images'] ?? 0 ) : 0;
		$original_bytes = ( ! is_wp_error( $ie_stats ) && $ie_stats ) ? absint( $ie_stats['original_bytes'] ?? 0 ) : 0;
		$saved_bytes    = ( ! is_wp_error( $ie_stats ) && $ie_stats ) ? absint( $ie_stats['saved_bytes'] ?? 0 ) : 0;

		if ( ! function_exists( 'ie_format_bytes' ) ) {
			function ie_format_bytes( int $bytes ): string {
				if ( $bytes >= 1073741824 ) return number_format( $bytes / 1073741824, 1 ) . ' GB';
				if ( $bytes >= 1048576 )    return number_format( $bytes / 1048576, 1 ) . ' MB';
				if ( $bytes >= 1024 )       return number_format( $bytes / 1024, 1 ) . ' KB';
				return $bytes . ' B';
			}
		}
		?>

		<!-- ── Image Optimization card ──────────────────────────────────── -->
		<div class="wa-card wa-ie-stats-card" style="margin-bottom:1rem;">
			<div class="wa-ie-stats-hd">
				<div class="wa-ie-stats-hd-left">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
					</svg>
					<span><?php esc_html_e( 'Image Optimization', 'whippet' ); ?></span>
				</div>
				<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'whippet', 'whippet_ptab' => 'image-engine' ], admin_url( 'tools.php' ) ) . '#premium' ); ?>" class="wa-ie-refresh" title="<?php esc_attr_e( 'Refresh stats', 'whippet' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
					</svg>
				</a>
			</div>
			<div class="wa-ie-stat-row">
				<div class="wa-ie-stat-label">
					<span class="wa-ie-stat-dot wa-ie-stat-dot--green"></span>
					<?php esc_html_e( 'Images Processed', 'whippet' ); ?>
				</div>
				<div class="wa-ie-stat-value">
					<?php if ( $ie_configured ) : ?>
						<?php echo $total_images ? esc_html( $ie_processed . ' / ' . $total_images ) : esc_html( (string) $ie_processed ); ?>
					<?php else : ?>
						<span style="color:#94a3b8"><?php esc_html_e( 'Not connected', 'whippet' ); ?></span>
					<?php endif; ?>
				</div>
			</div>
			<div class="wa-ie-stat-row">
				<div class="wa-ie-stat-label">
					<span class="wa-ie-stat-dot wa-ie-stat-dot--amber"></span>
					<?php esc_html_e( 'Total Image Size', 'whippet' ); ?>
				</div>
				<div class="wa-ie-stat-value">
					<?php if ( $ie_configured && $original_bytes ) : ?>
						<?php echo esc_html( ie_format_bytes( $original_bytes ) . ' / ' . ie_format_bytes( $saved_bytes ) . ' saved' ); ?>
					<?php elseif ( $ie_configured ) : ?>
						<span style="color:#94a3b8">&mdash;</span>
					<?php else : ?>
						<span style="color:#94a3b8"><?php esc_html_e( 'Not connected', 'whippet' ); ?></span>
					<?php endif; ?>
				</div>
			</div>
			<div class="wa-ie-stats-footer">
				<button type="button" id="ie-sync-btn" class="wa-ie-optimize-btn"
				        <?php echo $ie_configured ? '' : 'disabled title="' . esc_attr__( 'Enter your API key first.', 'whippet' ) . '"'; ?>>
					<?php esc_html_e( 'Optimise Images', 'whippet' ); ?>
				</button>
				<span id="ie-sync-msg" class="wa-ie-sync-msg"></span>
			</div>
		</div>

		<!-- ── Image Settings + API Connection ──────────────────────────── -->
		<form method="post" action="options.php">
			<?php settings_fields( 'image_engine' ); ?>

			<div class="wa-card" style="margin-bottom:1rem;">
				<div class="wa-card-section-title"><?php esc_html_e( 'Image Settings', 'whippet' ); ?></div>

				<div class="wa-ie-setting-row">
					<div class="wa-ie-setting-info">
						<div class="wa-ie-setting-label"><?php esc_html_e( 'Image Format', 'whippet' ); ?></div>
						<div class="wa-ie-setting-desc"><?php esc_html_e( 'Auto lets Image Engine pick the best format per image — e.g. AVIF where it saves more, WebP elsewhere', 'whippet' ); ?></div>
					</div>
					<div class="wa-ie-toggle-group">
						<?php
						$cur_fmt = get_option( 'ie_format_filter', '' );
						foreach ( [ '' => 'Auto', 'webp' => 'WebP', 'avif' => 'AVIF' ] as $val => $label ) :
						?>
						<label class="wa-ie-toggle-opt <?php echo $cur_fmt === $val ? 'active' : ''; ?>">
							<input type="radio" name="ie_format_filter" value="<?php echo esc_attr( $val ); ?>" <?php checked( $cur_fmt, $val ); ?> />
							<?php echo esc_html( $label ); ?>
						</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="wa-ie-setting-row">
					<div class="wa-ie-setting-info">
						<div class="wa-ie-setting-label"><?php esc_html_e( 'Compression Type', 'whippet' ); ?></div>
						<div class="wa-ie-setting-desc"><?php esc_html_e( 'Control the balance between image quality and file size', 'whippet' ); ?></div>
					</div>
					<div class="wa-ie-toggle-group">
						<?php
						$cur_loss = get_option( 'ie_lossless', 'false' );
						foreach ( [ 'true' => 'Lossless', 'false' => 'Lossy' ] as $val => $label ) :
						?>
						<label class="wa-ie-toggle-opt <?php echo $cur_loss === $val ? 'active' : ''; ?>">
							<input type="radio" name="ie_lossless" value="<?php echo esc_attr( $val ); ?>" <?php checked( $cur_loss, $val ); ?> />
							<?php echo esc_html( $label ); ?>
						</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="wa-ie-setting-row">
					<div class="wa-ie-setting-info">
						<div class="wa-ie-setting-label"><?php esc_html_e( 'Auto-optimise Media Library Uploads', 'whippet' ); ?></div>
						<div class="wa-ie-setting-desc"><?php esc_html_e( 'Automatically send new media library image uploads to Image Engine after WordPress finishes generating their attachment metadata.', 'whippet' ); ?></div>
					</div>
					<div class="wa-ie-toggle-group">
						<?php
						$ie_auto_optimize = (int) get_option( 'ie_auto_optimize', 1 );
						foreach ( [ 1 => 'Enabled', 0 => 'Disabled' ] as $val => $label ) :
						?>
						<label class="wa-ie-toggle-opt <?php echo $ie_auto_optimize === $val ? 'active' : ''; ?>">
							<input type="radio" name="ie_auto_optimize" value="<?php echo esc_attr( (string) $val ); ?>" <?php checked( $ie_auto_optimize, $val ); ?> />
							<?php echo esc_html( $label ); ?>
						</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="wa-ie-setting-row">
					<div class="wa-ie-setting-info">
						<div class="wa-ie-setting-label"><?php esc_html_e( 'Front-end Rewriting', 'whippet' ); ?></div>
						<div class="wa-ie-setting-desc"><?php esc_html_e( 'Rewrites full page HTML so Elementor output, theme markup, and inline background images can be matched by URL instead of only standard wp-image classes.', 'whippet' ); ?></div>
					</div>
					<div class="wa-ie-toggle-group">
						<?php
						$ie_frontend_rewrite = (int) get_option( 'ie_frontend_rewrite', 1 );
						foreach ( [ 1 => 'Enabled', 0 => 'Disabled' ] as $val => $label ) :
						?>
						<label class="wa-ie-toggle-opt <?php echo $ie_frontend_rewrite === $val ? 'active' : ''; ?>">
							<input type="radio" name="ie_frontend_rewrite" value="<?php echo esc_attr( (string) $val ); ?>" <?php checked( $ie_frontend_rewrite, $val ); ?> />
							<?php echo esc_html( $label ); ?>
						</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="wa-ie-setting-row" style="display:block;">
					<div class="wa-ie-setting-info">
						<div class="wa-ie-setting-label"><label for="ie_rewrite_source_patterns"><?php esc_html_e( 'Selected Directories or URLs', 'whippet' ); ?></label></div>
						<div class="wa-ie-setting-desc"><?php esc_html_e( 'Only attempt broad front-end rewrites for image URLs that contain one of these values. Leave blank to use uploads plus the active theme directories.', 'whippet' ); ?></div>
					</div>
					<div style="margin-top:.75rem;max-width:720px;">
						<?php
						$ie_rewrite_source_patterns = get_option( 'ie_rewrite_source_patterns', [] );
						$ie_rewrite_source_patterns = is_array( $ie_rewrite_source_patterns ) ? implode( "\n", $ie_rewrite_source_patterns ) : (string) $ie_rewrite_source_patterns;
						?>
						<textarea name="ie_rewrite_source_patterns" id="ie_rewrite_source_patterns" rows="5" class="large-text code"><?php echo esc_textarea( $ie_rewrite_source_patterns ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Examples: /wp-content/uploads/, /wp-content/themes/your-theme/assets/, https://example.com/wp-content/uploads/hero/', 'whippet' ); ?></p>
					</div>
				</div>

				<div class="wa-ie-setting-row" style="display:block;border-bottom:none;">
					<div class="wa-ie-setting-info">
						<div class="wa-ie-setting-label"><label for="ie_rewrite_page_patterns"><?php esc_html_e( 'Rewrite Only On Page URLs', 'whippet' ); ?></label></div>
						<div class="wa-ie-setting-desc"><?php esc_html_e( 'Optional request URL filters for the full-page rewrite pass. Add one path or URL fragment per line to limit where broader detection runs.', 'whippet' ); ?></div>
					</div>
					<div style="margin-top:.75rem;max-width:720px;">
						<?php
						$ie_rewrite_page_patterns = get_option( 'ie_rewrite_page_patterns', [] );
						$ie_rewrite_page_patterns = is_array( $ie_rewrite_page_patterns ) ? implode( "\n", $ie_rewrite_page_patterns ) : (string) $ie_rewrite_page_patterns;
						?>
						<textarea name="ie_rewrite_page_patterns" id="ie_rewrite_page_patterns" rows="5" class="large-text code"><?php echo esc_textarea( $ie_rewrite_page_patterns ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Examples: /shop/, /landing-pages/, https://example.com/pricing', 'whippet' ); ?></p>
					</div>
				</div>

			</div>

		<div class="wa-card" style="margin-bottom:1rem;">
			<div class="wa-card-section-title"><?php esc_html_e( 'API Connection', 'whippet' ); ?></div>
			<div class="wa-ie-setting-row" style="border-bottom:none;">
				<div class="wa-ie-setting-info">
					<div class="wa-ie-setting-label"><label for="ie_api_key"><?php esc_html_e( 'API Key', 'whippet' ); ?></label></div>
					<?php $ie_api_key = get_option( 'ie_api_key', '' ); ?>
					<?php if ( $ie_api_key ) : ?>
						<div class="wa-ie-setting-desc" style="color:#10b981;">&#10003; <?php esc_html_e( 'API key is saved.', 'whippet' ); ?></div>
					<?php else : ?>
						<div class="wa-ie-setting-desc" style="color:#ef4444;">&#9888; <?php esc_html_e( 'No API key saved — requests will be rejected with 401.', 'whippet' ); ?></div>
					<?php endif; ?>
				</div>
				<div style="display:flex;align-items:center;gap:.5rem;">
					<input type="password" name="ie_api_key" id="ie_api_key"
					       value="<?php echo esc_attr( $ie_api_key ); ?>"
					       class="regular-text" autocomplete="off" />
					<button type="button" style="background:none;border:none;cursor:pointer;padding:0;color:#64748b;"
					        onclick="var f=document.getElementById('ie_api_key');f.type=f.type==='password'?'text':'password';"
					        title="<?php esc_attr_e( 'Show / hide', 'whippet' ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
					</button>
				</div>
			</div>
		</div>

			<div class="wa-actions">
				<?php submit_button( __( 'Save Settings', 'whippet' ), 'primary', 'submit', false ); ?>
			</div>
		</form>

		<!-- ── Danger Zone ──────────────────────────────────────────────── -->
		<div class="wa-ie-danger-zone" style="margin-top:1rem;">
			<div class="wa-ie-danger-hd">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
				</svg>
				<?php esc_html_e( 'Danger Zone', 'whippet' ); ?>
			</div>

			<div class="wa-ie-danger-row">
				<div class="wa-ie-danger-info">
					<div class="wa-ie-danger-label"><?php esc_html_e( 'Restore Original Images', 'whippet' ); ?></div>
					<div class="wa-ie-danger-desc"><?php esc_html_e( 'Reverts images with preserved originals back to their original files by deleting optimised variants', 'whippet' ); ?></div>
				</div>
				<button type="button" id="ie-restore-btn" class="wa-ie-danger-btn"
				        <?php echo $ie_configured ? '' : 'disabled'; ?>
				        data-nonce="<?php echo esc_attr( wp_create_nonce( 'ie_bulk_action' ) ); ?>">
					<?php esc_html_e( 'Restore Originals', 'whippet' ); ?>
				</button>
			</div>

			<div class="wa-ie-danger-row" style="border-bottom:none;">
				<div class="wa-ie-danger-info">
					<div class="wa-ie-danger-label"><?php esc_html_e( 'Remove Original Images Permanently', 'whippet' ); ?></div>
					<div class="wa-ie-danger-desc"><?php esc_html_e( 'Permanently deletes original files and keeps only optimised images to save disk space. Deleted originals cannot be restored later.', 'whippet' ); ?></div>
				</div>
				<button type="button" id="ie-delete-originals-btn" class="wa-ie-danger-btn"
				        <?php echo $ie_configured ? '' : 'disabled'; ?>
				        data-nonce="<?php echo esc_attr( wp_create_nonce( 'ie_bulk_action' ) ); ?>">
					<?php esc_html_e( 'Delete Originals', 'whippet' ); ?>
				</button>
			</div>

			<div id="ie-danger-msg" style="padding:.75rem 1.25rem;font-size:.8125rem;color:#64748b;display:none;"></div>
		</div>

		<script>
		(function () {
			document.querySelectorAll('.wa-ie-toggle-group input[type="radio"]').forEach(function(radio) {
				radio.addEventListener('change', function() {
					var group = this.closest('.wa-ie-toggle-group');
					group.querySelectorAll('.wa-ie-toggle-opt').forEach(function(opt) { opt.classList.remove('active'); });
					this.closest('.wa-ie-toggle-opt').classList.add('active');
				});
			});

			var syncBtn = document.getElementById('ie-sync-btn');
			var syncMsg = document.getElementById('ie-sync-msg');
			if (syncBtn && !syncBtn.disabled) {
				syncBtn.addEventListener('click', function() {
					syncBtn.disabled = true;
					syncMsg.textContent = 'Working\u2026';
					fetch(window.ajaxurl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: new URLSearchParams({ action: 'ie_sync_all', _wpnonce: '<?php echo esc_js( wp_create_nonce( 'ie_sync_all' ) ); ?>' })
					})
					.then(function(r) { return r.json(); })
					.then(function(d) { syncMsg.textContent = d.data && d.data.message ? d.data.message : 'Done.'; syncBtn.disabled = false; })
					.catch(function() { syncMsg.textContent = 'Request failed.'; syncBtn.disabled = false; });
				});
			}

			function dangerAction(action, btn, confirmMsg) {
				if (!confirm(confirmMsg)) return;
				var nonce = btn.dataset.nonce;
				var msgEl = document.getElementById('ie-danger-msg');
				btn.disabled = true;
				msgEl.style.display = 'block';
				msgEl.textContent = 'Working\u2026';
				fetch(window.ajaxurl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams({ action: action, _wpnonce: nonce })
				})
				.then(function(r) { return r.json(); })
				.then(function(d) { msgEl.textContent = d.data && d.data.message ? d.data.message : 'Done.'; btn.disabled = false; })
				.catch(function() { msgEl.textContent = 'Request failed.'; btn.disabled = false; });
			}

			var restoreBtn = document.getElementById('ie-restore-btn');
			if (restoreBtn && !restoreBtn.disabled) {
				restoreBtn.addEventListener('click', function() {
					dangerAction('ie_bulk_restore', this, 'Restore all eligible optimised images to their original files? Optimised variants will be removed.');
				});
			}

			var deleteBtn = document.getElementById('ie-delete-originals-btn');
			if (deleteBtn && !deleteBtn.disabled) {
				deleteBtn.addEventListener('click', function() {
					dangerAction('ie_bulk_delete_originals', this, 'Permanently delete all original files? This cannot be undone.');
				});
			}
		})();
		</script>

		<?php elseif ( 'critical-css' === $whippet_ptab ) :

		$cce_enabled     = get_option( 'cce_enabled', 1 );
		$cce_api_key     = get_option( 'cce_api_key', '' );
		$cce_healthy     = CCE_API::health();
		$cce_site_id     = get_option( 'cce_site_id', '' );
		$cce_scan_id     = get_option( 'cce_scan_id', '' );
		$cce_scan_status = get_option( 'cce_scan_status', '' );
		$cce_post_types  = CCE_Admin::enabled_post_types();
		$cce_cluster_ids = get_option( 'cce_cluster_ids', [] );
		$all_public_types = CCE_Admin::available_post_type_objects();
	?>

		<!-- Critical CSS feature header -->
		<div class="wa-premium-feature-hd">
			<div class="wa-premium-feature-icon">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
				</svg>
			</div>
			<div>
				<h3><?php esc_html_e( 'Critical CSS Engine', 'whippet' ); ?></h3>
				<p><?php esc_html_e( 'Generates and injects above-the-fold CSS for every post/page template, served via your self-hosted Critical CSS Engine service.', 'whippet' ); ?></p>
			</div>
		</div>

		<div style="margin-bottom:1rem;padding:10px 14px;background:<?php echo $cce_healthy ? '#d1fae5' : '#fee2e2'; ?>;border-left:4px solid <?php echo $cce_healthy ? '#10b981' : '#ef4444'; ?>;border-radius:4px;font-size:.8125rem;">
			<strong><?php esc_html_e( 'Service status:', 'whippet' ); ?></strong>
			<?php echo $cce_healthy ? esc_html__( 'Connected', 'whippet' ) : esc_html__( 'Unreachable — ensure the service is running', 'whippet' ); ?>
		</div>

		<div class="wa-card" style="margin-bottom:1rem;">
			<div class="wa-card-section-title"><?php esc_html_e( 'Settings', 'whippet' ); ?></div>
			<form method="post" action="options.php">
				<?php settings_fields( 'cce_settings' ); ?>
				<table class="form-table" role="presentation">
					<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Critical CSS', 'whippet' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="cce_enabled" value="1" <?php checked( $cce_enabled ); ?>>
								<?php esc_html_e( 'Inject critical CSS on the front end', 'whippet' ); ?>
							</label>
						</td>
					</tr>
				<tr>
					<th scope="row"><label for="cce_api_key"><?php esc_html_e( 'API Key', 'whippet' ); ?></label></th>
					<td>
						<div style="display:flex;align-items:center;gap:.5rem;">
						<input id="cce_api_key" name="cce_api_key" type="password" class="regular-text"
						       value="<?php echo esc_attr( $cce_api_key ); ?>" autocomplete="off">
							<button type="button" style="background:none;border:none;cursor:pointer;padding:0;color:#64748b;"
							        onclick="var f=document.getElementById('cce_api_key');f.type=f.type==='password'?'text':'password';"
							        title="<?php esc_attr_e( 'Show / hide', 'whippet' ); ?>">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
							</button>
						</div>
						<?php if ( $cce_api_key ) : ?>
							<p class="description" style="color:#10b981;">&#10003; <?php esc_html_e( 'API key is saved.', 'whippet' ); ?></p>
						<?php else : ?>
							<p class="description" style="color:#ef4444;">&#9888; <?php esc_html_e( 'No API key saved — requests will be rejected with 401.', 'whippet' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Post Types', 'whippet' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( $all_public_types as $pt_slug => $pt_obj ) : ?>
							<label style="display:inline-flex;align-items:center;gap:.35rem;margin-right:1rem;">
								<input type="checkbox" name="cce_post_types[]"
								       value="<?php echo esc_attr( $pt_slug ); ?>"
								       <?php checked( in_array( $pt_slug, (array) $cce_post_types, true ) ); ?>>
								<?php echo esc_html( $pt_obj->labels->singular_name ); ?>
							</label>
							<?php endforeach; ?>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Critical CSS will only be auto-generated on save for the checked post types.', 'whippet' ); ?></p>
					</td>
				</tr>
				</tbody>
			</table>
				<div class="wa-actions" style="display:flex;align-items:center;gap:.75rem;">
					<?php submit_button( __( 'Save Settings', 'whippet' ), 'primary', 'submit', false ); ?>
					<button type="button" id="cce-test-btn" class="button button-secondary">
						<?php esc_html_e( 'Test Connection', 'whippet' ); ?>
					</button>
				</div>
			</form>

			<div id="cce-test-result" style="display:none;margin:0 1.25rem 1.25rem;padding:.75rem 1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;font-size:.8125rem;font-family:monospace;white-space:pre-wrap;"></div>

			<script>
			(function(){
				var btn = document.getElementById('cce-test-btn');
				var out = document.getElementById('cce-test-result');
				if (!btn) return;
				btn.addEventListener('click', function(){
					btn.disabled = true;
					btn.textContent = 'Testing\u2026';
					out.style.display = 'none';
					fetch(window.ajaxurl, {
						method: 'POST',
						headers: {'Content-Type':'application/x-www-form-urlencoded'},
						body: new URLSearchParams({
							action: 'cce_test',
							nonce:  '<?php echo esc_js( wp_create_nonce( 'cce_nonce' ) ); ?>'
						})
					})
					.then(function(r){ return r.json(); })
					.then(function(res){
						btn.disabled = false;
						btn.textContent = 'Test Connection';
						if (!res.success){ out.style.display='block'; out.style.color='#333'; out.textContent='Error: '+res.data; return; }
						var d = res.data;
						function fmt(p, label) {
							var ok = (typeof p.code === 'number' && p.code >= 200 && p.code < 300);
							return (ok ? '\u2705' : '\u274c') + ' ' + label + '\n   Status : ' + p.code + '\n   Body   : ' + (p.body || '(empty)');
						}
						var anyOk = [d.bearer, d.rawAuth, d.xApiKey].some(function(p){ return typeof p.code === 'number' && p.code >= 200 && p.code < 300; });
						var lines = [
							'API Key : ' + d.apiKeyDisplay + (d.apiKeySet ? '  (' + d.apiKeyLength + ' chars)' : '  \u26a0 not saved yet'),
							d.looksMasked ? 'Warning : Saved key looks masked. Paste the full key from the Critical CSS Engine and save again.' : '',
							'',
							'\u2014 GET /health (no auth) \u2014',
							'Status  : ' + d.healthCode + '  Body: ' + (d.healthBody || '(empty)'),
							'',
							'\u2014 POST /v1/scan auth probes \u2014',
							fmt(d.bearer,   'Authorization: Bearer <key>'),
							fmt(d.rawAuth,  'Authorization: <key>       '),
							fmt(d.xApiKey,  'X-API-Key: <key>           '),
						];
						out.style.display = 'block';
						out.style.color = '#333';
						out.textContent = lines.filter(Boolean).join('\n');
					})
					.catch(function(e){ btn.disabled=false; btn.textContent='Test Connection'; out.style.display='block'; out.style.color='#ef4444'; out.textContent='Request failed: '+e; });
				});
			})();
			</script>
		</div>

		<div class="wa-card" style="margin-bottom:1rem;">
			<div class="wa-card-section-title"><?php esc_html_e( 'Full Site Scan', 'whippet' ); ?></div>
			<p style="padding:0 1.25rem;font-size:.8125rem;color:#64748b;">
				<?php esc_html_e( 'Crawls every page, clusters templates, and generates critical CSS in bulk — one file per template cluster.', 'whippet' ); ?>
				<?php if ( $cce_scan_id ) : ?>
					<br><?php esc_html_e( 'Last scan ID:', 'whippet' ); ?> <code><?php echo esc_html( $cce_scan_id ); ?></code>
					<?php if ( $cce_scan_status ) : ?>
						&nbsp;<span style="color:<?php echo $cce_scan_status === 'completed' ? '#10b981' : '#f59e0b'; ?>;">(<?php echo esc_html( $cce_scan_status ); ?>)</span>
					<?php endif; ?>
				<?php endif; ?>
			</p>
			<div style="padding:0 1.25rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
				<button type="button" id="cce-scan-btn" class="button button-secondary" <?php echo $cce_healthy ? '' : 'disabled title="' . esc_attr__( 'Service unreachable. Ensure the service is running.', 'whippet' ) . '"'; ?>>
					<?php esc_html_e( 'Start Full Scan', 'whippet' ); ?>
				</button>
				<span id="cce-scan-status" style="font-size:.8125rem;color:#64748b;"></span>
			</div>

			<div id="cce-clusters-section" style="padding:0 1.25rem 1.25rem;<?php echo empty( $cce_cluster_ids ) ? 'display:none;' : ''; ?>">
				<div style="font-size:.75rem;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;"><?php esc_html_e( 'Template Clusters', 'whippet' ); ?></div>
				<table style="width:100%;border-collapse:collapse;font-size:.8125rem;">
					<thead>
						<tr style="border-bottom:1px solid #e2e8f0;">
							<th style="text-align:left;padding:.35rem .5rem;color:#64748b;font-weight:500;"><?php esc_html_e( 'Cluster', 'whippet' ); ?></th>
							<th style="text-align:right;padding:.35rem .5rem;color:#64748b;font-weight:500;"><?php esc_html_e( 'Posts', 'whippet' ); ?></th>
							<th style="text-align:right;padding:.35rem .5rem;color:#64748b;font-weight:500;"><?php esc_html_e( 'Size', 'whippet' ); ?></th>
							<th style="text-align:right;padding:.35rem .5rem;color:#64748b;font-weight:500;"><?php esc_html_e( 'Reduction', 'whippet' ); ?></th>
							<th style="text-align:center;padding:.35rem .5rem;color:#64748b;font-weight:500;"><?php esc_html_e( 'Status', 'whippet' ); ?></th>
						</tr>
					</thead>
					<tbody id="cce-clusters-body">
					<?php foreach ( $cce_cluster_ids as $cid ) :
						$ckey    = md5( $cid );
						$cmeta   = get_option( 'cce_cluster_meta_'    . $ckey, [] );
						$cstatus = get_option( 'cce_cluster_status_'  . $ckey, 'pending' );
						$cbytes  = (int) get_option( 'cce_cluster_bytes_'   . $ckey, 0 );
						$csavings = (float) get_option( 'cce_cluster_savings_' . $ckey, 0 );
						$color   = $cstatus === 'completed' ? '#10b981' : ( $cstatus === 'failed' ? '#ef4444' : '#f59e0b' );
					?>
					<tr style="border-bottom:1px solid #f1f5f9;">
						<td style="padding:.4rem .5rem;font-family:monospace;"><?php echo esc_html( $cid ); ?></td>
						<td style="padding:.4rem .5rem;text-align:right;color:#64748b;"><?php echo esc_html( $cmeta['post_count'] ?? '—' ); ?></td>
						<td style="padding:.4rem .5rem;text-align:right;color:#64748b;"><?php echo $cbytes ? esc_html( number_format( $cbytes ) . ' B' ) : '—'; ?></td>
						<td style="padding:.4rem .5rem;text-align:right;color:#64748b;"><?php echo $csavings ? esc_html( $csavings . '%' ) : '—'; ?></td>
						<td style="padding:.4rem .5rem;text-align:center;"><span style="color:<?php echo esc_attr( $color ); ?>;font-weight:500;"><?php echo esc_html( $cstatus ); ?></span></td>
					</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="wa-card" style="margin-bottom:1rem;">
			<div class="wa-card-section-title"><?php esc_html_e( 'Regenerate All', 'whippet' ); ?></div>
			<p style="padding:0 1.25rem;font-size:.8125rem;color:#64748b;">
				<?php esc_html_e( 'Re-queue a fresh critical CSS job for every published post and page. Use after a theme update or major layout change.', 'whippet' ); ?>
			</p>
			<div style="padding:0 1.25rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
				<button type="button" id="cce-regen-all-btn" class="button button-secondary" <?php echo $cce_healthy ? '' : 'disabled title="' . esc_attr__( 'Service unreachable. Ensure the service is running.', 'whippet' ) . '"'; ?>>
					<?php esc_html_e( 'Regenerate All Posts', 'whippet' ); ?>
				</button>
				<span id="cce-regen-all-status" style="font-size:.8125rem;color:#64748b;"></span>
			</div>
		</div>

		<?php if ( $cce_site_id ) : ?>
		<div class="wa-card" style="margin-bottom:1rem;">
			<div class="wa-card-section-title"><?php esc_html_e( 'Purge CSS Cache', 'whippet' ); ?></div>
			<p style="padding:0 1.25rem;font-size:.8125rem;color:#64748b;">
				<?php esc_html_e( 'Site ID:', 'whippet' ); ?> <code><?php echo esc_html( $cce_site_id ); ?></code>
			</p>
			<div style="padding:0 1.25rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
				<button type="button" id="cce-purge-btn" class="button button-secondary"
				        data-site-id="<?php echo esc_attr( $cce_site_id ); ?>">
					<?php esc_html_e( 'Purge Cache', 'whippet' ); ?>
				</button>
				<span id="cce-purge-status" style="font-size:.8125rem;color:#64748b;"></span>
			</div>
		</div>
		<?php endif; ?>

		<script>
		(function() {
			var ajaxUrl = window.ajaxurl;
			var nonce   = '<?php echo esc_js( wp_create_nonce( 'cce_nonce' ) ); ?>';

		var scanBtn         = document.getElementById('cce-scan-btn');
		var scanStatus      = document.getElementById('cce-scan-status');
		var clustersSection = document.getElementById('cce-clusters-section');
		var clustersBody    = document.getElementById('cce-clusters-body');
		var clusterPollTimer = null;

		function cceFormatBytes(bytes) {
			if (!bytes) return '\u2014';
			return Number(bytes).toLocaleString() + ' B';
		}

		function cceRenderClusters(data) {
			if (!clustersSection || !clustersBody) return;
			if (!data || !data.hasClusters) {
				clustersSection.style.display = 'none';
				clustersBody.innerHTML = '';
				return;
			}

			clustersSection.style.display = '';
			clustersBody.innerHTML = data.clusters.map(function(cluster) {
				var savings = cluster.savings ? cluster.savings + '%' : '\u2014';
				var count = cluster.postCount || '\u2014';
				return '<tr style="border-bottom:1px solid #f1f5f9;">'
					+ '<td style="padding:.4rem .5rem;font-family:monospace;">' + cluster.id + '</td>'
					+ '<td style="padding:.4rem .5rem;text-align:right;color:#64748b;">' + count + '</td>'
					+ '<td style="padding:.4rem .5rem;text-align:right;color:#64748b;">' + cceFormatBytes(cluster.bytes) + '</td>'
					+ '<td style="padding:.4rem .5rem;text-align:right;color:#64748b;">' + savings + '</td>'
					+ '<td style="padding:.4rem .5rem;text-align:center;"><span style="color:' + cluster.statusColor + ';font-weight:500;">' + cluster.status + '</span></td>'
					+ '</tr>';
			}).join('');
		}

		function cceRefreshClusterStatuses(keepPolling) {
			fetch(ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({ action: 'cce_cluster_status', nonce: nonce })
			})
			.then(function(r) { return r.json(); })
			.then(function(res) {
				if (!res.success) return;
				cceRenderClusters(res.data);
				if (keepPolling && res.data.hasPendingClusters) {
					clusterPollTimer = setTimeout(function() {
						cceRefreshClusterStatuses(true);
					}, 5000);
				} else if (keepPolling) {
					clusterPollTimer = null;
				}
			})
			.catch(function() {
				if (keepPolling) {
					clusterPollTimer = setTimeout(function() {
						cceRefreshClusterStatuses(true);
					}, 5000);
				}
			});
		}

		function cceStartClusterPolling() {
			if (clusterPollTimer) return;
			cceRefreshClusterStatuses(true);
		}

		function ccePollScanUntilDone(jobId, scanId, attempts) {
			if (attempts > 60) {
				scanStatus.style.color = '#f59e0b';
				scanStatus.textContent = 'Scan is taking longer than expected \u2014 it will continue in the background.';
				scanBtn.disabled = false;
				scanBtn.textContent = '<?php echo esc_js( __( 'Start Full Scan', 'whippet' ) ); ?>';
				return;
			}
			setTimeout(function () {
				fetch(ajaxUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams({ action: 'cce_poll_scan', nonce: nonce, job_id: jobId, scan_id: scanId })
				})
				.then(function(r) { return r.json(); })
				.then(function(res) {
					if (!res.success) {
						scanStatus.style.color = '#ef4444';
						scanStatus.textContent = 'Poll error: ' + res.data;
						scanBtn.disabled = false;
						scanBtn.textContent = '<?php echo esc_js( __( 'Start Full Scan', 'whippet' ) ); ?>';
						return;
					}
					var s = res.data.status || '';
					if (s === 'completed') {
						scanStatus.style.color = '#10b981';
						scanStatus.textContent = 'Scan complete \u2014 cluster CSS generation queued.';
						scanBtn.disabled = false;
						scanBtn.textContent = '<?php echo esc_js( __( 'Start Full Scan', 'whippet' ) ); ?>';
						cceStartClusterPolling();
					} else if (s === 'failed') {
						scanStatus.style.color = '#ef4444';
						scanStatus.textContent = 'Scan failed.';
						scanBtn.disabled = false;
						scanBtn.textContent = '<?php echo esc_js( __( 'Retry Scan', 'whippet' ) ); ?>';
					} else {
						var pct = res.data.progress ? ' (' + res.data.progress + '%)' : '';
						scanStatus.style.color = '#f59e0b';
						scanStatus.textContent = 'Scanning\u2026 ' + s + pct;
						ccePollScanUntilDone(jobId, scanId, attempts + 1);
					}
				})
				.catch(function() { ccePollScanUntilDone(jobId, scanId, attempts + 1); });
			}, 5000);
		}

		if (scanBtn && !scanBtn.disabled) {
			scanBtn.addEventListener('click', function () {
				scanBtn.disabled = true;
				scanBtn.textContent = '<?php echo esc_js( __( 'Scanning\u2026', 'whippet' ) ); ?>';
				scanStatus.style.color = '#64748b';
				scanStatus.textContent = '';
				fetch(ajaxUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams({ action: 'cce_scan', nonce: nonce })
				})
				.then(function(r) { return r.json(); })
				.then(function(res) {
					if (!res.success) {
						scanStatus.style.color = '#ef4444';
						scanStatus.textContent = 'Error: ' + res.data;
						scanBtn.disabled = false;
						scanBtn.textContent = '<?php echo esc_js( __( 'Retry Scan', 'whippet' ) ); ?>';
						return;
					}
					scanStatus.style.color = '#f59e0b';
					scanStatus.textContent = 'Scan started \u2014 polling for completion\u2026';
					ccePollScanUntilDone(res.data.jobId, res.data.scanId || '', 0);
				})
				.catch(function() {
					scanStatus.style.color = '#ef4444';
					scanStatus.textContent = '<?php echo esc_js( __( 'Request failed.', 'whippet' ) ); ?>';
					scanBtn.disabled = false;
					scanBtn.textContent = '<?php echo esc_js( __( 'Start Full Scan', 'whippet' ) ); ?>';
				});
			});
		}

		if (clustersBody && clustersBody.textContent.indexOf('pending') !== -1) {
			cceStartClusterPolling();
		}

		var regenAllBtn    = document.getElementById('cce-regen-all-btn');
		var regenAllStatus = document.getElementById('cce-regen-all-status');
		if (regenAllBtn && !regenAllBtn.disabled) {
			regenAllBtn.addEventListener('click', function () {
				if (!confirm('<?php echo esc_js( __( 'Re-queue critical CSS for every published post? This will start background jobs for all posts.', 'whippet' ) ); ?>')) return;
				regenAllBtn.disabled = true;
				regenAllBtn.textContent = '<?php echo esc_js( __( 'Queueing\u2026', 'whippet' ) ); ?>';
				regenAllStatus.style.color = '#64748b';
				regenAllStatus.textContent = '';
				fetch(ajaxUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams({ action: 'cce_regenerate_all', nonce: nonce })
				})
				.then(function(r) { return r.json(); })
				.then(function(res) {
					regenAllBtn.disabled = false;
					regenAllBtn.textContent = '<?php echo esc_js( __( 'Regenerate All Posts', 'whippet' ) ); ?>';
					if (res.success) {
						regenAllStatus.style.color = '#10b981';
						regenAllStatus.textContent = res.data.queued + ' jobs queued.';
					} else {
						regenAllStatus.style.color = '#ef4444';
						regenAllStatus.textContent = 'Error: ' + res.data;
					}
				})
				.catch(function() {
					regenAllBtn.disabled = false;
					regenAllBtn.textContent = '<?php echo esc_js( __( 'Regenerate All Posts', 'whippet' ) ); ?>';
					regenAllStatus.style.color = '#ef4444';
					regenAllStatus.textContent = '<?php echo esc_js( __( 'Request failed.', 'whippet' ) ); ?>';
				});
			});
		}

			var purgeBtn    = document.getElementById('cce-purge-btn');
			var purgeStatus = document.getElementById('cce-purge-status');
			if (purgeBtn) {
				purgeBtn.addEventListener('click', function () {
					var siteId = purgeBtn.getAttribute('data-site-id');
					purgeBtn.disabled = true;
					purgeBtn.textContent = '<?php echo esc_js( __( 'Purging\u2026', 'whippet' ) ); ?>';
					fetch(ajaxUrl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: new URLSearchParams({ action: 'cce_purge', nonce: nonce, site_id: siteId })
					})
					.then(function(r) { return r.json(); })
					.then(function(res) {
						purgeStatus.style.color = res.success ? '#10b981' : '#ef4444';
						purgeStatus.textContent = res.success
							? 'Cache purged \u2014 ' + (res.data.entriesDeleted || 0) + ' entries deleted.'
							: 'Error: ' + res.data;
						purgeBtn.disabled = false;
						purgeBtn.textContent = '<?php echo esc_js( __( 'Purge Cache', 'whippet' ) ); ?>';
					});
				});
			}
		})();
		</script>

		<?php endif; // $whippet_ptab ?>

	</div>

	</div>
	<!-- /wa-content -->

</div>
<!-- /wa-shell -->
</div>
<!-- /wrap.whippet-admin -->
