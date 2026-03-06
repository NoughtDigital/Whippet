<?php

/**
 * Whippet Documentation
 *
 * @category Whippet
 * @package  Whippet
 * @author   Jake Henshall
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.hashbangcode.com/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the tutorials page
 */
function whippet_tutorials_page() {
	?>
	<div class="whippet-documentation-content max-w-5xl mx-auto space-y-8">
		<!-- Getting Started Section -->
		<div>
			<div class="bg-blue-50 border-l-4 border-blue-500 p-6 md:p-8 rounded-lg shadow-sm">
				<h3 class="font-bold text-xl md:text-2xl mb-4 text-gray-900">
					<?php esc_html_e( 'Getting Started', 'whippet' ); ?>
				</h3>
				<p class="text-base text-gray-700 mb-6 leading-relaxed">
					<?php esc_html_e( 'Whippet helps you optimise your WordPress site by disabling unnecessary scripts, styles, and features. Start by reviewing the Dashboard settings and enable the options that suit your needs.', 'whippet' ); ?>
				</p>
				<ul class="list-disc list-inside text-sm md:text-base text-gray-700 space-y-3 ml-2">
					<li class="leading-relaxed"><?php esc_html_e( 'Navigate to the Dashboard tab to configure performance options', 'whippet' ); ?></li>
					<li class="leading-relaxed"><?php esc_html_e( 'Use the Script Manager (admin bar) to disable scripts on specific pages', 'whippet' ); ?></li>
					<li class="leading-relaxed"><?php esc_html_e( 'Configure local analytics in the Analytics tab for improved privacy', 'whippet' ); ?></li>
					<li class="leading-relaxed"><?php esc_html_e( 'Use Import/Export to backup or migrate your settings', 'whippet' ); ?></li>
					<li class="leading-relaxed"><?php esc_html_e( 'Export your settings regularly as a backup', 'whippet' ); ?></li>
				</ul>
			</div>

		<!-- Features Overview -->
		<div>
			<h3 class="font-bold text-xl md:text-2xl mb-6 text-gray-900"><?php esc_html_e( 'Key Features', 'whippet' ); ?></h3>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
				<div class="bg-white p-5 md:p-6 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
					<h4 class="font-semibold text-base md:text-lg mb-3 text-gray-900">
						<?php esc_html_e( 'Script & Style Manager', 'whippet' ); ?>
					</h4>
					<p class="text-sm md:text-base text-gray-600 leading-relaxed">
						<?php esc_html_e( 'Disable CSS and JavaScript files on a per-page basis. Access the Script Manager via the admin bar when viewing your site.', 'whippet' ); ?>
					</p>
				</div>

				<div class="bg-white p-5 md:p-6 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
					<h4 class="font-semibold text-base md:text-lg mb-3 text-gray-900">
						<?php esc_html_e( 'Local Analytics', 'whippet' ); ?>
					</h4>
					<p class="text-sm md:text-base text-gray-600 leading-relaxed">
						<?php esc_html_e( 'Host Google Analytics locally for improved privacy, faster loading times, and GDPR compliance.', 'whippet' ); ?>
					</p>
				</div>

				<div class="bg-white p-5 md:p-6 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
					<h4 class="font-semibold text-base md:text-lg mb-3 text-gray-900">
						<?php esc_html_e( 'Import & Export', 'whippet' ); ?>
					</h4>
					<p class="text-sm md:text-base text-gray-600 leading-relaxed">
						<?php esc_html_e( 'Backup and restore your settings easily. Perfect for migrating configurations between sites.', 'whippet' ); ?>
					</p>
				</div>

				<div class="bg-white p-5 md:p-6 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
					<h4 class="font-semibold text-base md:text-lg mb-3 text-gray-900">
						<?php esc_html_e( 'Performance Optimisation', 'whippet' ); ?>
					</h4>
					<p class="text-sm md:text-base text-gray-600 leading-relaxed">
						<?php esc_html_e( 'Disable emojis, embeds, query strings, and other unnecessary WordPress features to reduce page load times.', 'whippet' ); ?>
					</p>
				</div>
			</div>
		</div>

		<!-- Dashboard Settings Guide -->
		<div>
			<h3 class="font-bold text-xl md:text-2xl mb-6 text-gray-900"><?php esc_html_e( 'Dashboard Settings Guide', 'whippet' ); ?></h3>
			<div class="bg-gray-50 p-6 md:p-8 rounded-lg border border-gray-200 shadow-sm">
				<div class="space-y-6 md:space-y-8">
					<div>
						<h4 class="font-semibold text-base md:text-lg mb-4 text-gray-900"><?php esc_html_e( 'General Options', 'whippet' ); ?></h4>
						<ul class="list-disc list-inside text-sm md:text-base text-gray-700 space-y-3 ml-2">
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Disable Emojis:', 'whippet' ); ?></strong> <?php esc_html_e( 'Removes WordPress emoji scripts and styles to reduce HTTP requests.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Remove Query Strings:', 'whippet' ); ?></strong> <?php esc_html_e( 'Strips version numbers from CSS and JS URLs for better caching.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Remove Comments:', 'whippet' ); ?></strong> <?php esc_html_e( 'Removes HTML comments from the page source.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Disable Embeds:', 'whippet' ); ?></strong> <?php esc_html_e( 'Disables oEmbed functionality to prevent external requests.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Disable Google Maps:', 'whippet' ); ?></strong> <?php esc_html_e( 'Prevents Google Maps scripts from loading unless needed.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Remove jQuery Migrate:', 'whippet' ); ?></strong> <?php esc_html_e( 'Removes the jQuery migrate script if your theme and plugins don\'t require it.', 'whippet' ); ?></li>
						</ul>
					</div>

					<div>
						<h4 class="font-semibold text-base md:text-lg mb-4 text-gray-900"><?php esc_html_e( 'Header Tags', 'whippet' ); ?></h4>
						<ul class="list-disc list-inside text-sm md:text-base text-gray-700 space-y-3 ml-2">
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Remove RSD Link:', 'whippet' ); ?></strong> <?php esc_html_e( 'Removes Really Simple Discovery link if you don\'t use XML-RPC.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Remove Shortlink:', 'whippet' ); ?></strong> <?php esc_html_e( 'Removes WordPress shortlink from the header.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Remove REST API Links:', 'whippet' ); ?></strong> <?php esc_html_e( 'Removes REST API links from the header if not needed.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Remove wlwmanifest Link:', 'whippet' ); ?></strong> <?php esc_html_e( 'Removes Windows Live Writer manifest link.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Remove RSS Feed Links:', 'whippet' ); ?></strong> <?php esc_html_e( 'Removes RSS feed links from the header.', 'whippet' ); ?></li>
						</ul>
					</div>

					<div>
						<h4 class="font-semibold text-base md:text-lg mb-4 text-gray-900"><?php esc_html_e( 'Admin Settings', 'whippet' ); ?></h4>
						<ul class="list-disc list-inside text-sm md:text-base text-gray-700 space-y-3 ml-2">
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Limit Post Revisions:', 'whippet' ); ?></strong> <?php esc_html_e( 'Controls how many post revisions WordPress stores. Reducing this saves database space.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Autosave Interval:', 'whippet' ); ?></strong> <?php esc_html_e( 'Adjusts how frequently WordPress autosaves your content while editing.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Disable Heartbeat:', 'whippet' ); ?></strong> <?php esc_html_e( 'Controls the WordPress Heartbeat API which can cause high server load.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Heartbeat Frequency:', 'whippet' ); ?></strong> <?php esc_html_e( 'Adjusts how often the Heartbeat API runs if enabled.', 'whippet' ); ?></li>
						</ul>
					</div>

					<div>
						<h4 class="font-semibold text-base md:text-lg mb-4 text-gray-900"><?php esc_html_e( 'WooCommerce Options', 'whippet' ); ?></h4>
						<ul class="list-disc list-inside text-sm md:text-base text-gray-700 space-y-3 ml-2">
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Disable Scripts:', 'whippet' ); ?></strong> <?php esc_html_e( 'Disables WooCommerce scripts on non-WooCommerce pages.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Disable Cart Fragmentation:', 'whippet' ); ?></strong> <?php esc_html_e( 'Disables the cart fragments AJAX functionality.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Disable Status Meta Box:', 'whippet' ); ?></strong> <?php esc_html_e( 'Removes the WooCommerce status meta box from the dashboard.', 'whippet' ); ?></li>
							<li class="leading-relaxed"><strong class="text-gray-900"><?php esc_html_e( 'Disable Widgets:', 'whippet' ); ?></strong> <?php esc_html_e( 'Removes WooCommerce widgets from the widgets panel.', 'whippet' ); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<!-- Script Manager Guide -->
		<div>
			<h3 class="font-bold text-xl md:text-2xl mb-6 text-gray-900"><?php esc_html_e( 'Using the Script Manager', 'whippet' ); ?></h3>
			<div class="bg-white p-6 md:p-8 rounded-lg border border-gray-200 shadow-sm">
				<p class="text-base text-gray-700 mb-6 leading-relaxed">
					<?php esc_html_e( 'The Script Manager allows you to disable CSS and JavaScript files on specific pages or content types. Access it via the "Whippet" item in the WordPress admin bar when viewing your site.', 'whippet' ); ?>
				</p>
				<div class="space-y-6">
					<div>
						<h4 class="font-semibold text-base md:text-lg mb-4 text-gray-900"><?php esc_html_e( 'How to Use:', 'whippet' ); ?></h4>
						<ol class="list-decimal list-inside text-sm md:text-base text-gray-700 space-y-3 ml-2">
							<li class="leading-relaxed"><?php esc_html_e( 'Visit any page on your site while logged in as an administrator', 'whippet' ); ?></li>
							<li class="leading-relaxed"><?php esc_html_e( 'Click "Whippet" in the admin bar to open the Script Manager panel', 'whippet' ); ?></li>
							<li class="leading-relaxed"><?php esc_html_e( 'Browse through the list of CSS and JavaScript files loaded on that page', 'whippet' ); ?></li>
							<li class="leading-relaxed"><?php esc_html_e( 'Select "Disable" for any files you want to remove', 'whippet' ); ?></li>
							<li class="leading-relaxed"><?php esc_html_e( 'Choose where to disable: "Current URL" or "Everywhere"', 'whippet' ); ?></li>
							<li class="leading-relaxed"><?php esc_html_e( 'Set exceptions if you need the file on specific content types', 'whippet' ); ?></li>
							<li class="leading-relaxed"><?php esc_html_e( 'Click "Save changes" to apply your settings', 'whippet' ); ?></li>
						</ol>
					</div>
					<div class="bg-yellow-50 border-l-4 border-yellow-400 p-5 md:p-6 rounded-lg">
						<p class="text-sm md:text-base text-yellow-800 leading-relaxed">
							<strong class="font-semibold"><?php esc_html_e( 'Important:', 'whippet' ); ?></strong> <?php esc_html_e( 'Always test your site after disabling scripts. Some files may be required for your theme or plugins to function correctly. Start by disabling files on specific pages rather than everywhere.', 'whippet' ); ?>
						</p>
					</div>
				</div>
			</div>
		</div>

		<!-- Troubleshooting -->
		<div>
			<h3 class="font-bold text-xl md:text-2xl mb-6 text-gray-900"><?php esc_html_e( 'Troubleshooting', 'whippet' ); ?></h3>
			<div class="space-y-4 md:space-y-5">
				<div class="bg-white p-5 md:p-6 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
					<h4 class="font-semibold text-base md:text-lg mb-3 text-gray-900"><?php esc_html_e( 'My site looks broken after disabling scripts', 'whippet' ); ?></h4>
					<p class="text-sm md:text-base text-gray-700 leading-relaxed">
						<?php esc_html_e( 'Some scripts are required for your theme or plugins. Use the Script Manager to re-enable the files you disabled. Start by disabling scripts on specific pages rather than globally.', 'whippet' ); ?>
					</p>
				</div>

				<div class="bg-white p-5 md:p-6 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
					<h4 class="font-semibold text-base md:text-lg mb-3 text-gray-900"><?php esc_html_e( 'Script Manager not appearing in admin bar', 'whippet' ); ?></h4>
					<p class="text-sm md:text-base text-gray-700 leading-relaxed">
						<?php esc_html_e( 'Make sure you\'re logged in as an administrator and viewing the frontend of your site. The Script Manager only appears for users with manage_options capability.', 'whippet' ); ?>
					</p>
				</div>

				<div class="bg-white p-5 md:p-6 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
					<h4 class="font-semibold text-base md:text-lg mb-3 text-gray-900"><?php esc_html_e( 'Settings not saving', 'whippet' ); ?></h4>
					<p class="text-sm md:text-base text-gray-700 leading-relaxed">
						<?php esc_html_e( 'Check that you have the correct permissions and that your WordPress database tables are properly created. Try deactivating and reactivating the plugin.', 'whippet' ); ?>
					</p>
				</div>

				<div class="bg-white p-5 md:p-6 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
					<h4 class="font-semibold text-base md:text-lg mb-3 text-gray-900"><?php esc_html_e( 'Local Analytics not working', 'whippet' ); ?></h4>
					<p class="text-sm md:text-base text-gray-700 leading-relaxed">
						<?php esc_html_e( 'Ensure you\'ve entered a valid Google Analytics tracking ID in the Analytics tab. The local GA file will be automatically downloaded and cached.', 'whippet' ); ?>
					</p>
				</div>
			</div>
		</div>

		<!-- Best Practices -->
		<div>
			<h3 class="font-bold text-xl md:text-2xl mb-6 text-gray-900"><?php esc_html_e( 'Best Practices', 'whippet' ); ?></h3>
			<div class="bg-green-50 border-l-4 border-green-500 p-6 md:p-8 rounded-lg shadow-sm">
				<ul class="space-y-4 text-sm md:text-base text-gray-700 list-disc list-inside ml-2">
					<li class="leading-relaxed"><?php esc_html_e( 'Always test changes on a staging site before applying to production', 'whippet' ); ?></li>
					<li class="leading-relaxed"><?php esc_html_e( 'Export your settings regularly as a backup before making major changes', 'whippet' ); ?></li>
					<li class="leading-relaxed"><?php esc_html_e( 'Start by disabling scripts on specific pages rather than globally', 'whippet' ); ?></li>
					<li class="leading-relaxed"><?php esc_html_e( 'Use browser developer tools to identify which scripts are actually needed', 'whippet' ); ?></li>
					<li class="leading-relaxed"><?php esc_html_e( 'Monitor your site\'s performance after making changes using tools like Google PageSpeed Insights', 'whippet' ); ?></li>
				</ul>
			</div>
		</div>

		<!-- Support -->
		<div>
			<h3 class="font-bold text-xl md:text-2xl mb-6 text-gray-900"><?php esc_html_e( 'Need Help?', 'whippet' ); ?></h3>
			<div class="bg-white p-6 md:p-8 rounded-lg border border-gray-200 shadow-sm">
				<p class="text-base text-gray-700 mb-6 leading-relaxed">
					<?php esc_html_e( 'For additional support, documentation, and updates, please visit:', 'whippet' ); ?>
				</p>
				<div class="flex flex-wrap gap-4">
					<a href="https://hashbangcode.com/" target="_blank" class="inline-flex items-center px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200 text-sm md:text-base font-medium shadow-sm hover:shadow-md">
						<?php esc_html_e( 'Visit Website', 'whippet' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
	<?php
}