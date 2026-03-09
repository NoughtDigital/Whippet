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
	<div class="whippet-documentation-content max-w-6xl mx-auto space-y-8">
		<div class="bg-slate-50 border border-slate-200 rounded-xl p-6 md:p-8 shadow-sm">
			<div class="md:flex md:items-start md:justify-between md:gap-8">
				<div class="md:max-w-3xl">
					<h3 class="font-bold text-2xl md:text-3xl mb-3 text-gray-900">
						<?php esc_html_e( 'Whippet Documentation', 'whippet' ); ?>
					</h3>
					<p class="text-base text-gray-700 leading-relaxed mb-4">
						<?php esc_html_e( 'Whippet helps you remove front-end bloat, keep more assets local, and apply performance changes with less guesswork. The safest approach is to make small changes, test the front end, and clear caches after each save.', 'whippet' ); ?>
					</p>
					<p class="text-sm text-slate-600 leading-relaxed">
						<?php esc_html_e( 'Use this page as a practical guide: what to turn on first, what each tab does, and how to avoid the most common mistakes.', 'whippet' ); ?>
					</p>
				</div>
				<div class="mt-6 md:mt-0 md:w-80 bg-white border border-slate-200 rounded-lg p-5">
					<div class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">
						<?php esc_html_e( 'Best order to work in', 'whippet' ); ?>
					</div>
					<ol class="list-decimal list-inside space-y-2 text-sm text-slate-700">
						<li><?php esc_html_e( 'Export your current settings first', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Apply low-risk Dashboard changes', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Set up caching and compression', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Configure Analytics, Fonts, Lazy Load, and Preload', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Use Script Manager last for page-specific clean-up', 'whippet' ); ?></li>
					</ol>
				</div>
			</div>
		</div>

		<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
			<div class="xl:col-span-2 bg-white border border-gray-200 rounded-xl p-6 md:p-8 shadow-sm">
				<h3 class="font-bold text-xl md:text-2xl mb-4 text-gray-900"><?php esc_html_e( 'Quick Start', 'whippet' ); ?></h3>
				<ol class="list-decimal list-inside space-y-4 text-sm md:text-base text-gray-700">
					<li class="leading-relaxed">
						<strong class="text-gray-900"><?php esc_html_e( 'Back up first.', 'whippet' ); ?></strong>
						<?php esc_html_e( ' Use Import / Export before making large changes so you can restore a known working setup in seconds.', 'whippet' ); ?>
					</li>
					<li class="leading-relaxed">
						<strong class="text-gray-900"><?php esc_html_e( 'Start with safe wins.', 'whippet' ); ?></strong>
						<?php esc_html_e( ' Dashboard options such as disabling emojis, embeds, query strings, and feed links are usually low risk and easy to test.', 'whippet' ); ?>
					</li>
					<li class="leading-relaxed">
						<strong class="text-gray-900"><?php esc_html_e( 'Move on to delivery improvements.', 'whippet' ); ?></strong>
						<?php esc_html_e( ' Page caching, compression, lazy loading, and local analytics usually deliver bigger speed gains than aggressive script removal alone.', 'whippet' ); ?>
					</li>
					<li class="leading-relaxed">
						<strong class="text-gray-900"><?php esc_html_e( 'Use Script Manager carefully.', 'whippet' ); ?></strong>
						<?php esc_html_e( ' Disable files per URL first. Only move to wider rules when you are certain a file is not needed anywhere else.', 'whippet' ); ?>
					</li>
					<li class="leading-relaxed">
						<strong class="text-gray-900"><?php esc_html_e( 'Clear every cache after saving.', 'whippet' ); ?></strong>
						<?php esc_html_e( ' That includes any page cache, server cache, CDN cache, and browser cache if you are not seeing changes immediately.', 'whippet' ); ?>
					</li>
				</ol>
			</div>

			<div class="bg-amber-50 border border-amber-200 rounded-xl p-6 shadow-sm">
				<h3 class="font-bold text-xl mb-4 text-amber-900"><?php esc_html_e( 'Golden Rules', 'whippet' ); ?></h3>
				<ul class="space-y-3 text-sm text-amber-900 list-disc list-inside">
					<li><?php esc_html_e( 'Test on staging if the site is live or business critical.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Change one area at a time so regressions are obvious.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Check the front end while logged out as well as logged in.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'If something breaks, roll back the last change before trying another.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Use the Tools tab to confirm whether the server supports Brotli and WebP.', 'whippet' ); ?></li>
				</ul>
			</div>
		</div>

		<div>
			<h3 class="font-bold text-xl md:text-2xl mb-6 text-gray-900"><?php esc_html_e( 'What Each Tab Does', 'whippet' ); ?></h3>
			<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-5">
				<div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-lg mb-2 text-gray-900"><?php esc_html_e( 'Dashboard', 'whippet' ); ?></h4>
					<p class="text-sm text-gray-600 leading-relaxed"><?php esc_html_e( 'Turns off unnecessary WordPress features such as emojis, embeds, feed links, header tags, and selected WooCommerce extras. This is the best place to start.', 'whippet' ); ?></p>
				</div>
				<div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-lg mb-2 text-gray-900"><?php esc_html_e( 'Performance', 'whippet' ); ?></h4>
					<p class="text-sm text-gray-600 leading-relaxed"><?php esc_html_e( 'Handles bigger site-wide gains such as caching, compression, image optimisation, database clean-up, and server-related tuning.', 'whippet' ); ?></p>
				</div>
				<div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-lg mb-2 text-gray-900"><?php esc_html_e( 'Analytics', 'whippet' ); ?></h4>
					<p class="text-sm text-gray-600 leading-relaxed"><?php esc_html_e( 'Lets you host analytics files locally and optionally disable tracking for logged-in admins. Useful for privacy, fewer third-party requests, and improved page speed.', 'whippet' ); ?></p>
				</div>
				<div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-lg mb-2 text-gray-900"><?php esc_html_e( 'Fonts', 'whippet' ); ?></h4>
					<p class="text-sm text-gray-600 leading-relaxed"><?php esc_html_e( 'Reduces external font overhead by removing Google Fonts or improving perceived loading with font-display swap.', 'whippet' ); ?></p>
				</div>
				<div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-lg mb-2 text-gray-900"><?php esc_html_e( 'Lazy Load', 'whippet' ); ?></h4>
					<p class="text-sm text-gray-600 leading-relaxed"><?php esc_html_e( 'Controls image loading behaviour, delivery, responsiveness, compression, CDN options, and WebP support where available.', 'whippet' ); ?></p>
				</div>
				<div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-lg mb-2 text-gray-900"><?php esc_html_e( 'Preload', 'whippet' ); ?></h4>
					<p class="text-sm text-gray-600 leading-relaxed"><?php esc_html_e( 'Preloads likely next-page visits so navigation feels faster. Tune delays and request limits carefully if your server is already under load.', 'whippet' ); ?></p>
				</div>
				<div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-lg mb-2 text-gray-900"><?php esc_html_e( 'Script Manager', 'whippet' ); ?></h4>
					<p class="text-sm text-gray-600 leading-relaxed"><?php esc_html_e( 'Disables CSS and JavaScript on specific pages, URLs, or broader rules. Powerful, but it should usually be the last step rather than the first.', 'whippet' ); ?></p>
				</div>
				<div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-lg mb-2 text-gray-900"><?php esc_html_e( 'Snippets and Extras', 'whippet' ); ?></h4>
					<p class="text-sm text-gray-600 leading-relaxed"><?php esc_html_e( 'Adds custom code and utility features such as maintenance mode, cookie notices, social sharing, and other one-off enhancements.', 'whippet' ); ?></p>
				</div>
				<div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-lg mb-2 text-gray-900"><?php esc_html_e( 'Tools, Import / Export, and Premium', 'whippet' ); ?></h4>
					<p class="text-sm text-gray-600 leading-relaxed"><?php esc_html_e( 'Use Tools to check server capabilities, Import / Export for backups and migration, and Premium for advanced image optimisation or Critical CSS workflows.', 'whippet' ); ?></p>
				</div>
			</div>
		</div>

		<div class="bg-white p-6 md:p-8 rounded-xl border border-gray-200 shadow-sm">
			<h3 class="font-bold text-xl md:text-2xl mb-4 text-gray-900"><?php esc_html_e( 'Recommended Setup Flow', 'whippet' ); ?></h3>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
				<div class="bg-slate-50 border border-slate-200 rounded-lg p-5">
					<h4 class="font-semibold text-lg mb-3 text-gray-900"><?php esc_html_e( 'Phase 1: Safe foundation', 'whippet' ); ?></h4>
					<ul class="space-y-2 text-sm text-gray-700 list-disc list-inside">
						<li><?php esc_html_e( 'Export current settings.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Apply low-risk Dashboard clean-up options.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Enable caching and compression where supported.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Check the Tools tab for HTTPS, Brotli, and WebP support.', 'whippet' ); ?></li>
					</ul>
				</div>
				<div class="bg-slate-50 border border-slate-200 rounded-lg p-5">
					<h4 class="font-semibold text-lg mb-3 text-gray-900"><?php esc_html_e( 'Phase 2: Asset delivery', 'whippet' ); ?></h4>
					<ul class="space-y-2 text-sm text-gray-700 list-disc list-inside">
						<li><?php esc_html_e( 'Move analytics files local if you use Google Analytics.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Reduce or replace Google Fonts where possible.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Enable Lazy Load and tune image delivery.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Use Preload conservatively if your hosting is limited.', 'whippet' ); ?></li>
					</ul>
				</div>
				<div class="bg-slate-50 border border-slate-200 rounded-lg p-5 md:col-span-2">
					<h4 class="font-semibold text-lg mb-3 text-gray-900"><?php esc_html_e( 'Phase 3: Fine-tuning with Script Manager', 'whippet' ); ?></h4>
					<p class="text-sm text-gray-700 leading-relaxed mb-3">
						<?php esc_html_e( 'Only start here once the easy site-wide wins are in place. Script Manager is most useful for plugin assets that load where they are not needed, such as sliders, forms, maps, or WooCommerce files on non-shop pages.', 'whippet' ); ?>
					</p>
					<ul class="space-y-2 text-sm text-gray-700 list-disc list-inside">
						<li><?php esc_html_e( 'Disable one file at a time.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Prefer current URL rules before global rules.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Test key journeys straight away: menus, forms, cart, search, and mobile navigation.', 'whippet' ); ?></li>
					</ul>
				</div>
			</div>
		</div>

		<div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
			<div class="bg-white p-6 md:p-8 rounded-xl border border-gray-200 shadow-sm">
				<h3 class="font-bold text-xl md:text-2xl mb-4 text-gray-900"><?php esc_html_e( 'Using the Script Manager Safely', 'whippet' ); ?></h3>
				<ol class="list-decimal list-inside space-y-3 text-sm md:text-base text-gray-700">
					<li><?php esc_html_e( 'Open the front end of the exact page you want to optimise while logged in as an administrator.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Launch Whippet from the admin bar and inspect the loaded CSS and JavaScript files.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Look for plugin files that are clearly unrelated to the current page.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Disable a single file and save.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Refresh, then test visible layout, interactive elements, forms, and any plugin features on that page.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Only widen the rule once you know the file is not required elsewhere.', 'whippet' ); ?></li>
				</ol>
				<div class="mt-5 bg-red-50 border border-red-200 rounded-lg p-4">
					<p class="text-sm text-red-900 leading-relaxed">
						<strong><?php esc_html_e( 'Do not start by disabling dozens of files at once.', 'whippet' ); ?></strong>
						<?php esc_html_e( ' If something breaks, it becomes much harder to identify the cause.', 'whippet' ); ?>
					</p>
				</div>
			</div>

			<div class="bg-white p-6 md:p-8 rounded-xl border border-gray-200 shadow-sm">
				<h3 class="font-bold text-xl md:text-2xl mb-4 text-gray-900"><?php esc_html_e( 'Common Jobs', 'whippet' ); ?></h3>
				<div class="space-y-4 text-sm md:text-base text-gray-700">
					<div>
						<h4 class="font-semibold text-gray-900 mb-1"><?php esc_html_e( 'I want faster first loads', 'whippet' ); ?></h4>
						<p class="leading-relaxed"><?php esc_html_e( 'Start with caching, compression, local analytics, font clean-up, and image optimisation before touching page-specific scripts.', 'whippet' ); ?></p>
					</div>
					<div>
						<h4 class="font-semibold text-gray-900 mb-1"><?php esc_html_e( 'I want fewer third-party requests', 'whippet' ); ?></h4>
						<p class="leading-relaxed"><?php esc_html_e( 'Review Analytics and Fonts first, then check whether embeds, maps, and external widget scripts can be reduced or conditionally disabled.', 'whippet' ); ?></p>
					</div>
					<div>
						<h4 class="font-semibold text-gray-900 mb-1"><?php esc_html_e( 'I want safer roll-outs', 'whippet' ); ?></h4>
						<p class="leading-relaxed"><?php esc_html_e( 'Export settings, make a single change, clear caches, test logged out, and keep notes on what you changed before moving on.', 'whippet' ); ?></p>
					</div>
					<div>
						<h4 class="font-semibold text-gray-900 mb-1"><?php esc_html_e( 'I want to reduce server load', 'whippet' ); ?></h4>
						<p class="leading-relaxed"><?php esc_html_e( 'Be conservative with Preload, disable analytics for logged-in admins, review Heartbeat settings, and avoid aggressive features that create extra background work without measuring the result.', 'whippet' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<div>
			<h3 class="font-bold text-xl md:text-2xl mb-6 text-gray-900"><?php esc_html_e( 'Troubleshooting', 'whippet' ); ?></h3>
			<div class="space-y-4">
				<div class="bg-white p-5 md:p-6 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-base md:text-lg mb-2 text-gray-900"><?php esc_html_e( 'The site looks broken after disabling a script or style', 'whippet' ); ?></h4>
					<p class="text-sm md:text-base text-gray-700 leading-relaxed"><?php esc_html_e( 'Undo the last rule first. Then re-test with cache cleared. If the issue disappears, re-apply the change more narrowly, such as on the current URL instead of everywhere.', 'whippet' ); ?></p>
				</div>
				<div class="bg-white p-5 md:p-6 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-base md:text-lg mb-2 text-gray-900"><?php esc_html_e( 'I saved changes but nothing changed on the front end', 'whippet' ); ?></h4>
					<p class="text-sm md:text-base text-gray-700 leading-relaxed"><?php esc_html_e( 'A cache is usually the reason. Purge any caching plugin, server cache, CDN cache, and browser cache, then test again in a private window.', 'whippet' ); ?></p>
				</div>
				<div class="bg-white p-5 md:p-6 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-base md:text-lg mb-2 text-gray-900"><?php esc_html_e( 'The Script Manager does not appear in the admin bar', 'whippet' ); ?></h4>
					<p class="text-sm md:text-base text-gray-700 leading-relaxed"><?php esc_html_e( 'Make sure you are viewing the front end while logged in with administrator access. The manager does not appear for visitors or for users without the required capability.', 'whippet' ); ?></p>
				</div>
				<div class="bg-white p-5 md:p-6 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-base md:text-lg mb-2 text-gray-900"><?php esc_html_e( 'Preload is causing extra traffic or server pressure', 'whippet' ); ?></h4>
					<p class="text-sm md:text-base text-gray-700 leading-relaxed"><?php esc_html_e( 'Increase the delay, lower the requests per second, or switch to hover-only preloading. Also consider disabling preload for logged-in admins.', 'whippet' ); ?></p>
				</div>
				<div class="bg-white p-5 md:p-6 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-base md:text-lg mb-2 text-gray-900"><?php esc_html_e( 'Analytics is not recording visits', 'whippet' ); ?></h4>
					<p class="text-sm md:text-base text-gray-700 leading-relaxed"><?php esc_html_e( 'Check that the tracking ID is correct, confirm the selected tracking method matches your needs, clear caches, and test in a browser without ad blockers.', 'whippet' ); ?></p>
				</div>
				<div class="bg-white p-5 md:p-6 rounded-xl border border-gray-200 shadow-sm">
					<h4 class="font-semibold text-base md:text-lg mb-2 text-gray-900"><?php esc_html_e( 'Brotli or WebP shows as unavailable', 'whippet' ); ?></h4>
					<p class="text-sm md:text-base text-gray-700 leading-relaxed"><?php esc_html_e( 'That support depends on your PHP extensions or server stack. Use the Tools tab to confirm what is available before enabling features that rely on it.', 'whippet' ); ?></p>
				</div>
			</div>
		</div>

		<div class="bg-green-50 border border-green-200 rounded-xl p-6 md:p-8 shadow-sm">
			<h3 class="font-bold text-xl md:text-2xl mb-4 text-green-900"><?php esc_html_e( 'Safe Roll-out Checklist', 'whippet' ); ?></h3>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm md:text-base text-green-900">
				<div><?php esc_html_e( 'Export settings before major changes.', 'whippet' ); ?></div>
				<div><?php esc_html_e( 'Change one area at a time.', 'whippet' ); ?></div>
				<div><?php esc_html_e( 'Clear all caches after each save.', 'whippet' ); ?></div>
				<div><?php esc_html_e( 'Test the front end while logged out.', 'whippet' ); ?></div>
				<div><?php esc_html_e( 'Check forms, menus, search, and checkout flows.', 'whippet' ); ?></div>
				<div><?php esc_html_e( 'Measure impact with Lighthouse or PageSpeed Insights.', 'whippet' ); ?></div>
			</div>
		</div>

		<div class="bg-white p-6 md:p-8 rounded-xl border border-gray-200 shadow-sm">
			<h3 class="font-bold text-xl md:text-2xl mb-4 text-gray-900"><?php esc_html_e( 'Need More Help?', 'whippet' ); ?></h3>
			<p class="text-base text-gray-700 mb-5 leading-relaxed">
				<?php esc_html_e( 'If you need updates, support, or product information, visit the Whippet site below.', 'whippet' ); ?>
			</p>
			<div class="flex flex-wrap gap-4">
				<a href="https://hashbangcode.com/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200 text-sm md:text-base font-medium shadow-sm hover:shadow-md">
					<?php esc_html_e( 'Visit Website', 'whippet' ); ?>
				</a>
			</div>
		</div>
	</div>
	<?php
}