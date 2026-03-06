<?php
/**
 * Modern Admin Page Template
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
		var valid = ['dashboard','analytics','fonts','lazyload','pages','scripts','import-export','docs'].indexOf(h) !== -1;
		return valid ? h : 'dashboard';
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
}">

	<!-- Header -->
	<div class="px-2 mt-8">
		<div class="flex -mx-2 mb-4">
			<div class="w-full px-2">
				<div class="flex items-center justify-between">
					<div>
						<h1 class="text-xl font-bold"><?php esc_html_e( 'Whippet', 'whippet' ); ?></h1>
						<p class="text-sm"><?php esc_html_e( 'Disable scripts and styles conditionally to improve performance', 'whippet' ); ?></p>
					</div>
					<div class="text-sm">
						<?php printf( esc_html__( 'Version %s', 'whippet' ), esc_html( WHIPPET_VERSION ) ); ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Tabs Navigation -->
		<div class="whippet-tabs-bar">
			<div class="whippet-tabs-list">
				<button 
					@click="setTab('dashboard')" 
					:class="activeTab === 'dashboard' ? 'tab-active' : 'tab-inactive'" 
					class="tab-button">
					<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
					</svg>
					<?php esc_html_e( 'Dashboard', 'whippet' ); ?>
				</button>
				<button 
					@click="setTab('analytics')" 
					:class="activeTab === 'analytics' ? 'tab-active' : 'tab-inactive'" 
					class="tab-button">
					<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
					</svg>
					<?php esc_html_e( 'Analytics', 'whippet' ); ?>
				</button>
				<button 
					@click="setTab('fonts')" 
					:class="activeTab === 'fonts' ? 'tab-active' : 'tab-inactive'" 
					class="tab-button">
					<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12 17h7"/>
					</svg>
					<?php esc_html_e( 'Fonts', 'whippet' ); ?>
				</button>
				<button 
					@click="setTab('lazyload')" 
					:class="activeTab === 'lazyload' ? 'tab-active' : 'tab-inactive'" 
					class="tab-button">
					<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
					</svg>
					<?php esc_html_e( 'Lazy Load', 'whippet' ); ?>
				</button>
				<button 
					@click="setTab('pages')" 
					:class="activeTab === 'pages' ? 'tab-active' : 'tab-inactive'" 
					class="tab-button">
					<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
					</svg>
					<?php esc_html_e( 'Pages', 'whippet' ); ?>
				</button>
				<button 
					@click="setTab('scripts')" 
					:class="activeTab === 'scripts' ? 'tab-active' : 'tab-inactive'" 
					class="tab-button">
					<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
					</svg>
					<?php esc_html_e( 'Scripts', 'whippet' ); ?>
				</button>
				<button 
					@click="setTab('import-export')" 
					:class="activeTab === 'import-export' ? 'tab-active' : 'tab-inactive'" 
					class="tab-button">
					<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
					</svg>
					<?php esc_html_e( 'Import / Export', 'whippet' ); ?>
				</button>
				<button 
					@click="setTab('docs')" 
					:class="activeTab === 'docs' ? 'tab-active' : 'tab-inactive'" 
					class="tab-button">
					<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
					</svg>
					<?php esc_html_e( 'Documentation', 'whippet' ); ?>
				</button>
			</div>

			<!-- Tab Content -->
			<div class="whippet-tabs-content">
				
				<!-- Dashboard Tab -->
				<div x-show="activeTab === 'dashboard'" 
					 x-transition:enter="transition ease-out duration-200"
					 x-transition:enter-start="opacity-0"
					 x-transition:enter-end="opacity-100"
					 x-transition:leave="transition ease-in duration-150"
					 x-transition:leave-start="opacity-100"
					 x-transition:leave-end="opacity-0"
					 style="display: none;">
					<div class="mb-6">
						<h2 class="text-lg font-bold mb-2"><?php esc_html_e( 'Dashboard', 'whippet' ); ?></h2>
						<p class="text-sm text-gray-600 mb-4">
							<?php esc_html_e( 'Manage your script and style settings to optimize your site performance.', 'whippet' ); ?>
						</p>
					</div>
					
					<form method="post" action="options.php" class="whippet-form" id="whippet-dashboard-form">
						<?php
						settings_fields( 'whippet_options' );
						do_settings_sections( 'whippet_options' );
						?>
						<div class="mt-4" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
							<?php submit_button( __( 'Save Settings', 'whippet' ), 'primary', 'submit', false ); ?>
							<button type="button" class="button" @click="selectAllDashboard()"><?php esc_html_e( 'Select all', 'whippet' ); ?></button>
							<button type="button" class="button" @click="deselectAllDashboard()"><?php esc_html_e( 'Deselect all', 'whippet' ); ?></button>
						</div>
					</form>
				</div>

				<!-- Analytics Tab -->
				<div x-show="activeTab === 'analytics'" 
					 x-transition:enter="transition ease-out duration-200"
					 x-transition:enter-start="opacity-0"
					 x-transition:enter-end="opacity-100"
					 x-transition:leave="transition ease-in duration-150"
					 x-transition:leave-start="opacity-100"
					 x-transition:leave-end="opacity-0"
					 style="display: none;">
					<div class="mb-6">
						<h2 class="text-lg font-bold mb-2"><?php esc_html_e( 'Analytics', 'whippet' ); ?></h2>
						<p class="text-sm text-gray-600 mb-4">
							<?php esc_html_e( 'Configure your local analytics settings for improved privacy and performance.', 'whippet' ); ?>
						</p>
					</div>
					
					<div class="whippet-form">
						<?php
						if ( function_exists( 'flying_analytics_settings' ) ) {
							flying_analytics_settings();
						} else {
							echo '<div class="notice notice-info inline"><p>';
							esc_html_e( 'Analytics settings will be displayed here.', 'whippet' );
							echo '</p></div>';
						}
						?>
					</div>
				</div>

				<!-- Fonts Tab -->
				<div x-show="activeTab === 'fonts'"
					 x-transition:enter="transition ease-out duration-200"
					 x-transition:enter-start="opacity-0"
					 x-transition:enter-end="opacity-100"
					 x-transition:leave="transition ease-in duration-150"
					 x-transition:leave-start="opacity-100"
					 x-transition:leave-end="opacity-0"
					 style="display: none;">
					<div class="mb-6">
						<h2 class="text-lg font-bold mb-2"><?php esc_html_e( 'Fonts', 'whippet' ); ?></h2>
						<p class="text-sm text-gray-600 mb-4">
							<?php esc_html_e( 'Remove Google Fonts and use system fonts to reduce requests and improve performance.', 'whippet' ); ?>
						</p>
					</div>
					<?php
					if ( isset( $_POST['whippet_fonts_save'] ) && check_admin_referer( 'whippet_fonts', 'whippet_fonts_nonce' ) ) {
						update_option( 'whippet_fonts_enabled', ! empty( $_POST['whippet_fonts_enabled'] ) );
						update_option( 'whippet_fonts_display_swap', ! empty( $_POST['whippet_fonts_display_swap'] ) );
						echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'whippet' ) . '</p></div>';
					}
					$fonts_enabled   = get_option( 'whippet_fonts_enabled', true );
					$fonts_swap     = get_option( 'whippet_fonts_display_swap', false );
					?>
					<form method="post" class="whippet-form">
						<?php wp_nonce_field( 'whippet_fonts', 'whippet_fonts_nonce' ); ?>
						<table class="form-table" role="presentation">
							<tbody>
							<tr>
								<th scope="row"><?php esc_html_e( 'Remove Google Fonts', 'whippet' ); ?></th>
								<td>
									<label for="whippet_fonts_enabled"><input type="checkbox" name="whippet_fonts_enabled" id="whippet_fonts_enabled" value="1" <?php checked( $fonts_enabled ); ?> /> <?php esc_html_e( 'Replace Google Fonts with system fonts', 'whippet' ); ?></label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Add font-display:swap', 'whippet' ); ?></th>
								<td>
									<label for="whippet_fonts_display_swap"><input type="checkbox" name="whippet_fonts_display_swap" id="whippet_fonts_display_swap" value="1" <?php checked( $fonts_swap ); ?> /> <?php esc_html_e( 'Add font-display:swap to Google Fonts so text remains visible during font load', 'whippet' ); ?></label>
									<p class="description"><?php esc_html_e( 'Only applies when "Remove Google Fonts" is unchecked.', 'whippet' ); ?></p>
								</td>
							</tr>
							</tbody>
						</table>
						<p class="submit"><button type="submit" name="whippet_fonts_save" class="button button-primary"><?php esc_html_e( 'Save Settings', 'whippet' ); ?></button></p>
					</form>
				</div>

				<!-- Lazy Load Tab -->
				<div x-show="activeTab === 'lazyload'"
					 x-transition:enter="transition ease-out duration-200"
					 x-transition:enter-start="opacity-0"
					 x-transition:enter-end="opacity-100"
					 x-transition:leave="transition ease-in duration-150"
					 x-transition:leave-start="opacity-100"
					 x-transition:leave-end="opacity-0"
					 style="display: none;">
					<div class="mb-6">
						<h2 class="text-lg font-bold mb-2"><?php esc_html_e( 'Lazy Load', 'whippet' ); ?></h2>
						<p class="text-sm text-gray-600 mb-4">
							<?php esc_html_e( 'Lazy load images and optimise delivery.', 'whippet' ); ?>
						</p>
					</div>
					<?php
					$whippet_ltab = isset( $_GET['whippet_ltab'] ) ? sanitize_text_field( $_GET['whippet_ltab'] ) : 'lazyload';
					$lazyload_tabs = array( 'lazyload' => __( 'Lazy load', 'whippet' ), 'cdn' => __( 'CDN', 'whippet' ), 'responsiveness' => __( 'Responsiveness', 'whippet' ), 'compression' => __( 'Compression', 'whippet' ), 'webp' => __( 'WebP', 'whippet' ) );
					?>
					<div class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 1rem;">
						<?php foreach ( $lazyload_tabs as $key => $label ) : ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'whippet', 'whippet_ltab' => $key ), admin_url( 'tools.php' ) ) . '#lazyload' ); ?>" class="nav-tab <?php echo $whippet_ltab === $key ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
						<?php endforeach; ?>
					</div>
					<?php
					if ( isset( $_POST['submit'] ) ) {
						echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved. Clear cache if you use a cache plugin.', 'whippet' ) . '</p></div>';
					}
					switch ( $whippet_ltab ) {
						case 'cdn': flying_pages_settings_cdn(); break;
						case 'compression': flying_pages_settings_compression(); break;
						case 'responsiveness': flying_pages_settings_responsiveness(); break;
						case 'webp': flying_pages_settings_webp(); break;
						default: flying_pages_settings_lazy_load(); break;
					}
					?>
				</div>

				<!-- Pages Tab -->
				<div x-show="activeTab === 'pages'"
					 x-transition:enter="transition ease-out duration-200"
					 x-transition:enter-start="opacity-0"
					 x-transition:enter-end="opacity-100"
					 x-transition:leave="transition ease-in duration-150"
					 x-transition:leave-start="opacity-100"
					 x-transition:leave-end="opacity-0"
					 style="display: none;">
					<div class="mb-6">
						<h2 class="text-lg font-bold mb-2"><?php esc_html_e( 'Pages', 'whippet' ); ?></h2>
						<p class="text-sm text-gray-600 mb-4">
							<?php esc_html_e( 'Preload pages for faster navigation.', 'whippet' ); ?>
						</p>
					</div>
					<?php
					$whippet_ptab = isset( $_GET['whippet_ptab'] ) ? sanitize_text_field( $_GET['whippet_ptab'] ) : 'settings';
					$pages_tabs = array( 'settings' => __( 'Settings', 'whippet' ), 'compatibility' => __( 'Compatibility', 'whippet' ) );
					?>
					<div class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 1rem;">
						<?php foreach ( $pages_tabs as $key => $label ) : ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'whippet', 'whippet_ptab' => $key ), admin_url( 'tools.php' ) ) . '#pages' ); ?>" class="nav-tab <?php echo $whippet_ptab === $key ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
						<?php endforeach; ?>
					</div>
					<?php
					switch ( $whippet_ptab ) {
						case 'compatibility': flying_pages_compatibility(); break;
						default: flying_pages_settings(); break;
					}
					?>
				</div>

				<!-- Scripts Tab -->
				<div x-show="activeTab === 'scripts'"
					 x-transition:enter="transition ease-out duration-200"
					 x-transition:enter-start="opacity-0"
					 x-transition:enter-end="opacity-100"
					 x-transition:leave="transition ease-in duration-150"
					 x-transition:leave-start="opacity-100"
					 x-transition:leave-end="opacity-0"
					 style="display: none;">
					<div class="mb-6">
						<h2 class="text-lg font-bold mb-2"><?php esc_html_e( 'Scripts', 'whippet' ); ?></h2>
						<p class="text-sm text-gray-600 mb-4">
							<?php esc_html_e( 'Delay JavaScript to reduce render-blocking.', 'whippet' ); ?>
						</p>
					</div>
					<?php
					$whippet_stab = isset( $_GET['whippet_stab'] ) ? sanitize_text_field( $_GET['whippet_stab'] ) : 'settings';
					$scripts_tabs = array( 'settings' => __( 'Settings', 'whippet' ) );
					?>
					<div class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 1rem;">
						<?php foreach ( $scripts_tabs as $key => $label ) : ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'whippet', 'whippet_stab' => $key ), admin_url( 'tools.php' ) ) . '#scripts' ); ?>" class="nav-tab <?php echo $whippet_stab === $key ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
						<?php endforeach; ?>
					</div>
					<?php
					switch ( $whippet_stab ) {
						default: flying_scripts_view_settings(); break;
					}
					?>
				</div>

				<!-- Import/Export Tab -->
				<div x-show="activeTab === 'import-export'" 
					 x-transition:enter="transition ease-out duration-200"
					 x-transition:enter-start="opacity-0"
					 x-transition:enter-end="opacity-100"
					 x-transition:leave="transition ease-in duration-150"
					 x-transition:leave-start="opacity-100"
					 x-transition:leave-end="opacity-0"
					 style="display: none;">
					<div class="mb-6">
						<h2 class="text-lg font-bold mb-2"><?php esc_html_e( 'Import / Export', 'whippet' ); ?></h2>
						<p class="text-sm text-gray-600 mb-4">
							<?php esc_html_e( 'Backup and restore your Whippet settings.', 'whippet' ); ?>
						</p>
					</div>
					
					<div class="whippet-cards-grid">
						<!-- Export Settings -->
						<div class="whippet-card">
							<h3>
								<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #3b82f6;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
								<?php esc_html_e( 'Export Settings', 'whippet' ); ?>
							</h3>
							<p class="text-sm mb-4" style="color: #4b5563;"><?php esc_html_e( 'Export the plugin settings for this site as a .json file. This allows you to easily import the configuration into another site.', 'whippet' ); ?></p>
							<form method="post">
								<input type="hidden" name="whippet_action" value="export_settings" />
								<?php wp_nonce_field( 'whippet_export_nonce', 'whippet_export_nonce' ); ?>
								<button type="submit" name="submit" class="button button-primary button-large w-full">
									<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
									<?php esc_html_e( 'Download Export File', 'whippet' ); ?>
								</button>
							</form>
						</div>

						<!-- Import Settings -->
						<div class="whippet-card">
							<h3>
								<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #16a34a;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
								<?php esc_html_e( 'Import Settings', 'whippet' ); ?>
							</h3>
							<p class="text-sm mb-4" style="color: #4b5563;"><?php esc_html_e( 'Import the plugin settings from a .json file. This file can be obtained by exporting the settings on another site.', 'whippet' ); ?></p>
							<form method="post" enctype="multipart/form-data" id="whippet-import-form">
								<div
									class="whippet-drop-zone"
									@drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files"
									@dragover.prevent="isDragging = true"
									@dragleave.prevent="isDragging = false"
									:style="isDragging ? 'border-color: #3b82f6; background: #eff6ff;' : ''"
									style="border: 2px dashed #d1d5db; border-radius: 8px; margin-bottom: 1rem; transition: border-color 0.15s, background 0.15s;">
									<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #9ca3af;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
									<p class="text-sm mb-2" style="color: #4b5563;"><span style="font-weight: 600;"><?php esc_html_e( 'Drop your file here', 'whippet' ); ?></span> <?php esc_html_e( 'or', 'whippet' ); ?></p>
									<label for="import_file" style="cursor: pointer; color: #3b82f6; font-weight: 600;"><?php esc_html_e( 'browse to upload', 'whippet' ); ?></label>
									<input type="file" name="import_file" id="import_file" x-ref="fileInput" class="hidden" accept=".json" />
									<p class="text-xs mt-2" style="color: #6b7280;"><?php esc_html_e( 'JSON files only', 'whippet' ); ?></p>
								</div>
								<input type="hidden" name="whippet_action" value="import_settings" />
								<?php wp_nonce_field( 'whippet_import_nonce', 'whippet_import_nonce' ); ?>
								<button type="submit" name="submit" class="button button-primary button-large w-full">
									<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
									<?php esc_html_e( 'Import Settings', 'whippet' ); ?>
								</button>
							</form>
						</div>
					</div>
				</div>

				<!-- Documentation Tab -->
				<div x-show="activeTab === 'docs'" 
					 x-transition:enter="transition ease-out duration-200"
					 x-transition:enter-start="opacity-0"
					 x-transition:enter-end="opacity-100"
					 x-transition:leave="transition ease-in duration-150"
					 x-transition:leave-start="opacity-100"
					 x-transition:leave-end="opacity-0"
					 style="display: none;">
					<div class="mb-6">
						<h2 class="text-lg font-bold mb-2"><?php esc_html_e( 'Documentation', 'whippet' ); ?></h2>
						<p class="text-sm text-gray-600 mb-4">
							<?php esc_html_e( 'Learn how to use Whippet and fix common issues.', 'whippet' ); ?>
						</p>
					</div>
					
					<div class="whippet-documentation">
						<?php
						if ( function_exists( 'whippet_tutorials_page' ) ) {
							whippet_tutorials_page();
						} else {
							?>
							<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
								<div class="bg-blue-500 p-6 rounded">
									<div class="text-center text-white">
										<svg class="w-16 h-16 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
											<path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
										</svg>
										<h3 class="font-bold text-xl mb-2"><?php esc_html_e( 'Getting Started', 'whippet' ); ?></h3>
										<p class="text-sm mb-4"><?php esc_html_e( 'Learn the basics of Whippet and how to optimize your site.', 'whippet' ); ?></p>
										<button type="button" @click="setTab('docs')" class="inline-block bg-blue-700 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded transition-colors">
											<?php esc_html_e( 'Read Documentation', 'whippet' ); ?>
										</button>
			</div>
		</div>

								<div class="bg-gray-100 p-6 rounded border">
									<h3 class="font-bold text-lg mb-3"><?php esc_html_e( 'Quick Tips', 'whippet' ); ?></h3>
									<ul class="space-y-2 text-sm">
										<li class="flex items-start">
											<span class="text-blue-500 mr-2">•</span>
											<span><?php esc_html_e( 'Disable unused scripts to improve page load times', 'whippet' ); ?></span>
										</li>
										<li class="flex items-start">
											<span class="text-blue-500 mr-2">•</span>
											<span><?php esc_html_e( 'Use local analytics for better privacy', 'whippet' ); ?></span>
										</li>
										<li class="flex items-start">
											<span class="text-blue-500 mr-2">•</span>
											<span><?php esc_html_e( 'Export your settings regularly as backup', 'whippet' ); ?></span>
										</li>
										<li class="flex items-start">
											<span class="text-blue-500 mr-2">•</span>
											<span><?php esc_html_e( 'Use Import/Export to migrate settings between sites', 'whippet' ); ?></span>
										</li>
										<li class="flex items-start">
											<span class="text-blue-500 mr-2">•</span>
											<span><?php esc_html_e( 'Test changes on a staging site first', 'whippet' ); ?></span>
										</li>
									</ul>
								</div>
							</div>
							<?php
						}
						?>
					</div>
				</div>

		</div>
	</div>
</div>

</div>
