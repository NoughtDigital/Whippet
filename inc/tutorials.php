<?php

/**
 * Whippet Documentation
 *
 * @category Whippet
 * @package  Whippet
 * @author   Jake Henshall
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://nought.digital
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the tutorials / documentation page.
 */
function whippet_tutorials_page() {
	?>
	<div class="wa-docs" x-data="{
		section: 'doc-start',
		go(id) {
			this.section = id;
			var el = document.getElementById(id);
			if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	}">

		<nav class="wa-docs-toc" aria-label="<?php esc_attr_e( 'Documentation sections', 'whippet' ); ?>">
			<button type="button" class="wa-docs-toc__btn" :class="section === 'doc-start' && 'active'" @click="go('doc-start')"><?php esc_html_e( 'Quick start', 'whippet' ); ?></button>
			<button type="button" class="wa-docs-toc__btn" :class="section === 'doc-tabs' && 'active'" @click="go('doc-tabs')"><?php esc_html_e( 'Every tab', 'whippet' ); ?></button>
			<button type="button" class="wa-docs-toc__btn" :class="section === 'doc-cache' && 'active'" @click="go('doc-cache')"><?php esc_html_e( 'Caching', 'whippet' ); ?></button>
			<button type="button" class="wa-docs-toc__btn" :class="section === 'doc-scripts' && 'active'" @click="go('doc-scripts')"><?php esc_html_e( 'Script Manager', 'whippet' ); ?></button>
			<button type="button" class="wa-docs-toc__btn" :class="section === 'doc-confusable' && 'active'" @click="go('doc-confusable')"><?php esc_html_e( 'Lookalikes', 'whippet' ); ?></button>
			<button type="button" class="wa-docs-toc__btn" :class="section === 'doc-jobs' && 'active'" @click="go('doc-jobs')"><?php esc_html_e( 'Common jobs', 'whippet' ); ?></button>
			<button type="button" class="wa-docs-toc__btn" :class="section === 'doc-troubleshoot' && 'active'" @click="go('doc-troubleshoot')"><?php esc_html_e( 'Troubleshooting', 'whippet' ); ?></button>
			<button type="button" class="wa-docs-toc__btn" :class="section === 'doc-checklist' && 'active'" @click="go('doc-checklist')"><?php esc_html_e( 'Checklist', 'whippet' ); ?></button>
		</nav>

		<!-- ── Quick start ─────────────────────────────────────────────── -->
		<section class="wa-docs-section" id="doc-start">
			<div class="wa-docs-hero">
				<div class="wa-docs-hero__main">
					<h3><?php esc_html_e( 'Make Whippet work hard without breaking the site', 'whippet' ); ?></h3>
					<p><?php esc_html_e( 'Whippet removes front-end bloat, keeps more assets local, and gives you page-level control over CSS and JavaScript. The safe habit is always the same: export settings, change one area, clear caches, then test while logged out.', 'whippet' ); ?></p>
				</div>
				<div class="wa-docs-hero__aside">
					<div class="wa-docs-kicker"><?php esc_html_e( 'Best order to work in', 'whippet' ); ?></div>
					<ol class="wa-docs-steps">
						<li><?php esc_html_e( 'Export settings (Import / Export)', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Low-risk Dashboard clean-up', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Page caching + compression', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Analytics, Fonts, Lazy Load, Preload', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Script Manager last, page by page', 'whippet' ); ?></li>
					</ol>
				</div>
			</div>

			<div class="wa-docs-grid wa-docs-grid--3">
				<article class="wa-docs-card">
					<h4><?php esc_html_e( '1. Back up first', 'whippet' ); ?></h4>
					<p><?php esc_html_e( 'Use Import / Export before large changes so you can restore a known-good setup in seconds. Tools also keeps automatic settings snapshots you can roll back to.', 'whippet' ); ?></p>
				</article>
				<article class="wa-docs-card">
					<h4><?php esc_html_e( '2. Take the easy wins', 'whippet' ); ?></h4>
					<p><?php esc_html_e( 'Dashboard toggles (emojis, embeds, query strings, feed links) and Performance page caching usually deliver more speed than aggressive script removal alone.', 'whippet' ); ?></p>
				</article>
				<article class="wa-docs-card">
					<h4><?php esc_html_e( '3. Fine-tune carefully', 'whippet' ); ?></h4>
					<p><?php esc_html_e( 'Script Manager is powerful. Prefer Current URL rules, enable Testing Mode on live sites, and never disable dozens of files in one go.', 'whippet' ); ?></p>
				</article>
			</div>

			<div class="wa-docs-callout wa-docs-callout--warn">
				<strong><?php esc_html_e( 'Golden rules', 'whippet' ); ?></strong>
				<ul>
					<li><?php esc_html_e( 'Test on staging if the site is live or business-critical.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Change one area at a time so regressions are obvious.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Always check the front end while logged out, not only as an admin.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'If something breaks, undo the last change before trying another.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Use Tools to confirm HTTPS, Brotli, and WebP support before relying on them.', 'whippet' ); ?></li>
				</ul>
			</div>
		</section>

		<!-- ── Every tab ───────────────────────────────────────────────── -->
		<section class="wa-docs-section" id="doc-tabs">
			<div class="wa-docs-section__hd">
				<h3><?php esc_html_e( 'What each tab does', 'whippet' ); ?></h3>
				<p><?php esc_html_e( 'A practical map of every Whippet panel — what it is for, what to turn on, and what to watch for.', 'whippet' ); ?></p>
			</div>

			<div class="wa-docs-feature-list">

				<details class="wa-docs-feature" open>
					<summary>
						<span class="wa-docs-feature__title"><?php esc_html_e( 'Dashboard', 'whippet' ); ?></span>
						<span class="wa-docs-feature__tag"><?php esc_html_e( 'Start here', 'whippet' ); ?></span>
					</summary>
					<div class="wa-docs-feature__body">
						<p><?php esc_html_e( 'Turns off unnecessary WordPress front-end features to reduce page weight. Includes impact metrics, an activity log, and enable/disable all.', 'whippet' ); ?></p>
						<div class="wa-docs-cols">
							<div>
								<h5><?php esc_html_e( 'Useful options', 'whippet' ); ?></h5>
								<ul>
									<li><?php esc_html_e( 'Disable Emojis, Embeds, Google Maps, jQuery Migrate', 'whippet' ); ?></li>
									<li><?php esc_html_e( 'Remove Query Strings, Comments, Shortlink, REST / RSD / feed links', 'whippet' ); ?></li>
									<li><?php esc_html_e( 'Disable Self Pingbacks, RSS Feeds, XML-RPC, Hide WP Version', 'whippet' ); ?></li>
									<li><?php esc_html_e( 'WooCommerce: unload scripts on non-shop pages, cart fragments, status meta box, widgets', 'whippet' ); ?></li>
								</ul>
							</div>
							<div>
								<h5><?php esc_html_e( 'Watch out for', 'whippet' ); ?></h5>
								<ul>
									<li><?php esc_html_e( 'XML-RPC — needed by some remote publishing / mobile apps.', 'whippet' ); ?></li>
									<li><?php esc_html_e( 'RSS — breaks feed readers and newsletter tools that subscribe via feeds.', 'whippet' ); ?></li>
									<li><?php esc_html_e( 'WooCommerce Disable Scripts — can break cart/shop UI if those assets are still needed off-shop pages.', 'whippet' ); ?></li>
								</ul>
							</div>
						</div>
					</div>
				</details>

				<details class="wa-docs-feature">
					<summary>
						<span class="wa-docs-feature__title"><?php esc_html_e( 'Performance', 'whippet' ); ?></span>
						<span class="wa-docs-feature__tag"><?php esc_html_e( 'Biggest gains', 'whippet' ); ?></span>
					</summary>
					<div class="wa-docs-feature__body">
						<p><?php esc_html_e( 'Site-wide delivery: page cache, compression, minification, images, database clean-up, and admin/server tuning.', 'whippet' ); ?></p>
						<div class="wa-docs-cols">
							<div>
								<h5><?php esc_html_e( 'Caching & delivery', 'whippet' ); ?></h5>
								<ul>
									<li><?php esc_html_e( 'Page Caching, expiry, separate mobile cache, cache preloading', 'whippet' ); ?></li>
									<li><?php esc_html_e( 'Apache Direct-Serve — serve cached HTML without PHP (Apache + mod_rewrite)', 'whippet' ); ?></li>
									<li><?php esc_html_e( 'Exclude URLs / bypass cookies; Clear Cache Now; Clear & Preload', 'whippet' ); ?></li>
									<li><?php esc_html_e( 'Browser cache headers, Gzip, CDN rewrite, resource hints', 'whippet' ); ?></li>
								</ul>
							</div>
							<div>
								<h5><?php esc_html_e( 'Assets, images & DB', 'whippet' ); ?></h5>
								<ul>
									<li><?php esc_html_e( 'Minify / Defer CSS & JS with exclusion lists', 'whippet' ); ?></li>
									<li><?php esc_html_e( 'YouTube/Vimeo facades, LCP image priority, lazy-render HTML', 'whippet' ); ?></li>
									<li><?php esc_html_e( 'Image quality, local WebP conversion, bulk compress, preload fonts', 'whippet' ); ?></li>
									<li><?php esc_html_e( 'Scheduled DB clean-up (revisions, spam, transients, orphaned meta)', 'whippet' ); ?></li>
								</ul>
							</div>
						</div>
						<div class="wa-docs-note">
							<?php esc_html_e( 'Cart, checkout, my-account URLs and WooCommerce cookies are auto-excluded from page cache when caching is enabled. Still add custom exclusions for other dynamic pages.', 'whippet' ); ?>
						</div>
					</div>
				</details>

				<details class="wa-docs-feature">
					<summary>
						<span class="wa-docs-feature__title"><?php esc_html_e( 'Analytics', 'whippet' ); ?></span>
					</summary>
					<div class="wa-docs-feature__body">
						<p><?php esc_html_e( 'Serve Google Analytics from your own domain using bundled scripts — fewer third-party requests and better privacy.', 'whippet' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Enter a GA4 Tracking ID (G-XXXXXXXXXX).', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Choose Gtag.js (~90KB) or Minimal Analytics.js (~1.4KB — default).', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Disable for logged-in admins is on by default so you do not skew reports.', 'whippet' ); ?></li>
						</ul>
						<div class="wa-docs-note">
							<?php esc_html_e( 'Scripts load from the plugin (footer, deferred). Clear page/CDN cache after changing the ID or script type.', 'whippet' ); ?>
						</div>
					</div>
				</details>

				<details class="wa-docs-feature">
					<summary>
						<span class="wa-docs-feature__title"><?php esc_html_e( 'Fonts', 'whippet' ); ?></span>
					</summary>
					<div class="wa-docs-feature__body">
						<p><?php esc_html_e( 'Cut Google Fonts overhead by removing them, self-hosting, or applying font-display: swap.', 'whippet' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Remove Google Fonts — replace with system fonts (default on).', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Self-host Google Fonts — download into uploads (disabled while Remove is on).', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'font-display: swap and Clear self-hosted font cache on save.', 'whippet' ); ?></li>
						</ul>
						<div class="wa-docs-callout wa-docs-callout--warn wa-docs-callout--compact">
							<?php esc_html_e( 'Choose Remove or Self-host — not both. Remove wins if both are enabled.', 'whippet' ); ?>
						</div>
					</div>
				</details>

				<details class="wa-docs-feature">
					<summary>
						<span class="wa-docs-feature__title"><?php esc_html_e( 'Lazy Load', 'whippet' ); ?></span>
					</summary>
					<div class="wa-docs-feature__body">
						<p><?php esc_html_e( 'Lazy-load images and optionally route delivery through a CDN. Five subtabs:', 'whippet' ); ?></p>
						<ol class="wa-docs-steps wa-docs-steps--inline">
							<li><strong><?php esc_html_e( 'Lazy Load', 'whippet' ); ?></strong> — <?php esc_html_e( 'Native / JS / Native+JS, bottom margin, exclude keywords.', 'whippet' ); ?></li>
							<li><strong><?php esc_html_e( 'CDN', 'whippet' ); ?></strong> — <?php esc_html_e( 'Enable wsrv.nl CDN + exclusions.', 'whippet' ); ?></li>
							<li><strong><?php esc_html_e( 'Responsiveness', 'whippet' ); ?></strong> — <?php esc_html_e( 'srcset (requires CDN).', 'whippet' ); ?></li>
							<li><strong><?php esc_html_e( 'Compression', 'whippet' ); ?></strong> — <?php esc_html_e( 'On-the-fly quality (requires CDN).', 'whippet' ); ?></li>
							<li><strong><?php esc_html_e( 'WebP', 'whippet' ); ?></strong> — <?php esc_html_e( 'On-the-fly WebP via CDN when the browser supports it.', 'whippet' ); ?></li>
						</ol>
						<div class="wa-docs-note">
							<?php esc_html_e( 'Enable CDN before Responsiveness, Compression, or WebP. Exclude above-the-fold / LCP images with keywords. This is separate from Performance → WebP Conversion (local GD/Imagick).', 'whippet' ); ?>
						</div>
					</div>
				</details>

				<details class="wa-docs-feature">
					<summary>
						<span class="wa-docs-feature__title"><?php esc_html_e( 'Preload', 'whippet' ); ?></span>
					</summary>
					<div class="wa-docs-feature__body">
						<p><?php esc_html_e( 'Prefetch likely next-page visits on idle or hover so navigation feels instant. This is not the same as cache preloading or font preloading.', 'whippet' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Delay to start (0–30s idle), max requests per second, hover-only mode, hover delay.', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Ignore keywords for logout, cart, checkout, admin, and external links.', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Disable for logged-in admins when logged-in traffic bypasses cache.', 'whippet' ); ?></li>
						</ul>
						<div class="wa-docs-callout wa-docs-callout--warn wa-docs-callout--compact">
							<?php esc_html_e( 'Start with 0s delay and ~3 requests/second on shared hosting. Unlimited RPS can spike CPU.', 'whippet' ); ?>
						</div>
					</div>
				</details>

				<details class="wa-docs-feature">
					<summary>
						<span class="wa-docs-feature__title"><?php esc_html_e( 'Script Manager', 'whippet' ); ?></span>
						<span class="wa-docs-feature__tag"><?php esc_html_e( 'Use last', 'whippet' ); ?></span>
					</summary>
					<div class="wa-docs-feature__body">
						<p><?php esc_html_e( 'Disable CSS and JavaScript per URL, post type, or regex — and optionally whole plugins in MU Mode. Configure behaviour here; apply rules from the front-end admin bar.', 'whippet' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Testing Mode — rules apply only to logged-in admins (preview with ?whippet_preview_scripts=1).', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Display Archives / Dependencies, MU Mode (BETA), Reset Script Manager.', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Script adviser — paste handles for suggestions; never auto-applies.', 'whippet' ); ?></li>
						</ul>
						<p><a href="#doc-scripts" class="wa-docs-link" @click.prevent="go('doc-scripts')"><?php esc_html_e( 'Full Script Manager walkthrough →', 'whippet' ); ?></a></p>
					</div>
				</details>

				<details class="wa-docs-feature">
					<summary>
						<span class="wa-docs-feature__title"><?php esc_html_e( 'Snippets', 'whippet' ); ?></span>
					</summary>
					<div class="wa-docs-feature__body">
						<p><?php esc_html_e( 'Flat-file PHP, CSS, JS, or HTML snippets with conditions, locations, and delivery options. Stored under uploads/whippet-snippets/.', 'whippet' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Locations: Head, Body Open, Footer — inline or file delivery.', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Conditions: front page, page slug, post type, URL contains, logged-in, and more.', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'JS optimisations: minify, defer, async, delay until interaction; CSS preload.', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Allow PHP execution is off by default. Safe Mode cookie disables snippet output for one hour.', 'whippet' ); ?></li>
						</ul>
					</div>
				</details>

				<details class="wa-docs-feature">
					<summary>
						<span class="wa-docs-feature__title"><?php esc_html_e( 'Extras', 'whippet' ); ?></span>
					</summary>
					<div class="wa-docs-feature__body">
						<p><?php esc_html_e( 'Site utilities that sit alongside performance work.', 'whippet' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Global Head / Body / Footer code injection.', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Simple cookie notice (not a full consent platform).', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Maintenance / Coming soon modes with a Magic Link bypass.', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Social sharing buttons and WooCommerce Utils (free shipping, price from, trailing zeros, SKUs).', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Post/page Duplicate appears as a row action on list screens.', 'whippet' ); ?></li>
						</ul>
					</div>
				</details>

				<details class="wa-docs-feature">
					<summary>
						<span class="wa-docs-feature__title"><?php esc_html_e( 'Tools', 'whippet' ); ?></span>
					</summary>
					<div class="wa-docs-feature__body">
						<p><?php esc_html_e( 'Diagnostics and operational utilities.', 'whippet' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Checks: HTTPS, PHP Brotli, WebP (GD / Imagick), server info, Cache-Control header probe.', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Asset integrity, settings snapshots, one-hour recovery-mode bypass URL.', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Cloudflare / Bunny purge credentials (fire when Whippet cache clears).', 'whippet' ); ?></li>
							<li><?php esc_html_e( 'Anomaly alerts (email / Slack) and optional anonymised rule telemetry.', 'whippet' ); ?></li>
						</ul>
					</div>
				</details>

				<details class="wa-docs-feature">
					<summary>
						<span class="wa-docs-feature__title"><?php esc_html_e( 'Import / Export', 'whippet' ); ?></span>
					</summary>
					<div class="wa-docs-feature__body">
						<p><?php esc_html_e( 'Download or restore the full Whippet configuration as JSON — options, analytics, fonts, lazy load, preload, Script Manager rules, and more.', 'whippet' ); ?></p>
						<div class="wa-docs-note">
							<?php esc_html_e( 'Snippet files under uploads may need copying separately. Retest cache exclusions and CDN URLs after importing onto a different domain.', 'whippet' ); ?>
						</div>
					</div>
				</details>

				<details class="wa-docs-feature">
					<summary>
						<span class="wa-docs-feature__title"><?php esc_html_e( 'Premium', 'whippet' ); ?></span>
						<span class="wa-docs-feature__tag"><?php esc_html_e( 'Coming soon', 'whippet' ); ?></span>
					</summary>
					<div class="wa-docs-feature__body">
						<p><?php esc_html_e( 'Preview of Whippet Cloud Premium. Controls stay locked until Cloud Connect / an active plan enables them.', 'whippet' ); ?></p>
						<ul>
							<li><strong><?php esc_html_e( 'Image Engine', 'whippet' ); ?></strong> — <?php esc_html_e( 'Cloud image optimisation (Auto/WebP/AVIF), compression type, front-end rewriting, restore originals.', 'whippet' ); ?></li>
							<li><strong><?php esc_html_e( 'Critical CSS', 'whippet' ); ?></strong> — <?php esc_html_e( 'Generate critical CSS, remove unused CSS (with safelist), full-site scan, regenerate, purge.', 'whippet' ); ?></li>
						</ul>
						<div class="wa-docs-note">
							<?php esc_html_e( 'Join the waitlist from the Premium tab. Saving API keys while locked will not activate the features.', 'whippet' ); ?>
						</div>
					</div>
				</details>

			</div>
		</section>

		<!-- ── Caching deep dive ───────────────────────────────────────── -->
		<section class="wa-docs-section" id="doc-cache">
			<div class="wa-docs-section__hd">
				<h3><?php esc_html_e( 'Caching deep dive', 'whippet' ); ?></h3>
				<p><?php esc_html_e( 'Page caching is usually the single biggest speed win. Set it up properly before chasing micro-optimisations.', 'whippet' ); ?></p>
			</div>

			<div class="wa-docs-grid wa-docs-grid--2">
				<article class="wa-docs-card">
					<h4><?php esc_html_e( 'How Whippet page cache works', 'whippet' ); ?></h4>
					<ul class="wa-docs-bullets">
						<li><?php esc_html_e( 'Cached HTML lives under wp-content/cache/whippet/.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Brotli variants (.html.br) are written when PHP Brotli is available — check Tools.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Cache clears automatically on post save, theme switch, menu update, Customiser save, and plugin activate/deactivate.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Clear & Preload empties the cache then warms URLs from your sitemap via WP-Cron.', 'whippet' ); ?></li>
					</ul>
				</article>
				<article class="wa-docs-card">
					<h4><?php esc_html_e( 'Recommended setup', 'whippet' ); ?></h4>
					<ol class="wa-docs-steps">
						<li><?php esc_html_e( 'Enable Page Caching with a sensible expiry (24 hours is a solid default).', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'On Apache hosts, enable Apache Direct-Serve so cached pages skip PHP entirely.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Add any custom dynamic paths to Exclude URLs (beyond Woo auto-excludes).', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Configure Cloudflare or Bunny purge keys in Tools if you use those CDNs.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Save a recovery-mode URL from Tools before aggressive experiments.', 'whippet' ); ?></li>
					</ol>
				</article>
			</div>

			<div class="wa-docs-callout wa-docs-callout--danger">
				<strong><?php esc_html_e( 'Common caching mistakes', 'whippet' ); ?></strong>
				<ul>
					<li><?php esc_html_e( 'Caching personalised or checkout pages without exclusions.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Expecting Apache Direct-Serve on Nginx or when .htaccess is not writable.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Thinking Brotli compresses live PHP HTML — it is for cached files, not runtime responses.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Forgetting to purge third-party / CDN caches after saving settings.', 'whippet' ); ?></li>
				</ul>
			</div>
		</section>

		<!-- ── Script Manager ──────────────────────────────────────────── -->
		<section class="wa-docs-section" id="doc-scripts">
			<div class="wa-docs-section__hd">
				<h3><?php esc_html_e( 'Using the Script Manager safely', 'whippet' ); ?></h3>
				<p><?php esc_html_e( 'Do this after site-wide wins are in place. It is ideal for plugin assets that load where they are not needed — forms, sliders, maps, or WooCommerce files on non-shop pages.', 'whippet' ); ?></p>
			</div>

			<div class="wa-docs-card wa-docs-card--wide">
				<h4><?php esc_html_e( 'Front-end workflow', 'whippet' ); ?></h4>
				<ol class="wa-docs-steps">
					<li><?php esc_html_e( 'Log in as an administrator and open the exact front-end page you want to optimise.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Click Whippet in the admin bar to open the floating Script Manager.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Inspect loaded plugins, theme, and miscellaneous CSS/JS files.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Disable one file at a time. Prefer Current URL before Everywhere.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Save, refresh, then test layout, menus, forms, cart, search, and mobile navigation.', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Only widen the rule once you are sure the file is not required elsewhere.', 'whippet' ); ?></li>
				</ol>
			</div>

			<div class="wa-docs-grid wa-docs-grid--2">
				<article class="wa-docs-card">
					<h4><?php esc_html_e( 'Disable scopes', 'whippet' ); ?></h4>
					<ul class="wa-docs-bullets">
						<li><?php esc_html_e( 'Everywhere', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Current URL', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Posts / Pages / other public post types', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Regex URL match', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Optional Enable overrides on specific scopes', 'whippet' ); ?></li>
					</ul>
				</article>
				<article class="wa-docs-card">
					<h4><?php esc_html_e( 'Extra views & modes', 'whippet' ); ?></h4>
					<ul class="wa-docs-bullets">
						<li><?php esc_html_e( 'Global View — review or delete all rules site-wide.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Testing Mode — live experiments without affecting visitors.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'MU Mode (BETA) — disable whole plugins via mu-plugin; review existing rules first.', 'whippet' ); ?></li>
						<li><?php esc_html_e( 'Reset — truncates Script Manager tables if you need a clean slate.', 'whippet' ); ?></li>
					</ul>
				</article>
			</div>

			<div class="wa-docs-callout wa-docs-callout--danger">
				<strong><?php esc_html_e( 'Do not disable dozens of files at once.', 'whippet' ); ?></strong>
				<p><?php esc_html_e( 'If something breaks, it becomes much harder to identify the cause. Undo the last rule first, clear caches, and re-apply more narrowly.', 'whippet' ); ?></p>
			</div>
		</section>

		<!-- ── Lookalikes ──────────────────────────────────────────────── -->
		<section class="wa-docs-section" id="doc-confusable">
			<div class="wa-docs-section__hd">
				<h3><?php esc_html_e( 'Features that sound alike', 'whippet' ); ?></h3>
				<p><?php esc_html_e( 'Whippet has a few similarly named tools. Pick the right one before you toggle.', 'whippet' ); ?></p>
			</div>

			<div class="wa-docs-table-wrap">
				<table class="wa-docs-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'whippet' ); ?></th>
							<th><?php esc_html_e( 'Tab', 'whippet' ); ?></th>
							<th><?php esc_html_e( 'What it actually does', 'whippet' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php esc_html_e( 'Preload', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Preload', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Prefetch next-page HTML on link hover / idle.', 'whippet' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Cache Preloading', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Performance', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Warm the Whippet page cache from your sitemap via cron.', 'whippet' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Preload Fonts', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Performance', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Adds preload link tags for font file URLs.', 'whippet' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'WebP Conversion', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Performance', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Local WebP generation via GD / Imagick.', 'whippet' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'WebP (Lazy Load)', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Lazy Load', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'On-the-fly WebP through the CDN when supported.', 'whippet' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Defer JavaScript', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Performance', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Adds the defer attribute to enqueued scripts.', 'whippet' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'WooCommerce (Dashboard)', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Dashboard', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Performance toggles — unload scripts/fragments on non-shop pages.', 'whippet' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'WooCommerce Utils', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Extras', 'whippet' ); ?></td>
							<td><?php esc_html_e( 'Shop UX helpers — free shipping, price display, SKUs.', 'whippet' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</section>

		<!-- ── Common jobs ─────────────────────────────────────────────── -->
		<section class="wa-docs-section" id="doc-jobs">
			<div class="wa-docs-section__hd">
				<h3><?php esc_html_e( 'Common jobs', 'whippet' ); ?></h3>
				<p><?php esc_html_e( 'Start from the outcome you want, then follow the path.', 'whippet' ); ?></p>
			</div>

			<div class="wa-docs-grid wa-docs-grid--2">
				<article class="wa-docs-card">
					<h4><?php esc_html_e( 'I want faster first loads', 'whippet' ); ?></h4>
					<p><?php esc_html_e( 'Page caching → compression → local analytics → font clean-up → image / Lazy Load work. Only then touch Script Manager.', 'whippet' ); ?></p>
				</article>
				<article class="wa-docs-card">
					<h4><?php esc_html_e( 'I want fewer third-party requests', 'whippet' ); ?></h4>
					<p><?php esc_html_e( 'Analytics and Fonts first. Then review embeds, maps, and external widgets — disable globally on Dashboard or per page in Script Manager.', 'whippet' ); ?></p>
				</article>
				<article class="wa-docs-card">
					<h4><?php esc_html_e( 'I want safer roll-outs', 'whippet' ); ?></h4>
					<p><?php esc_html_e( 'Export settings, use Testing Mode for Script Manager, save a Tools recovery URL, change one area, clear caches, test logged out.', 'whippet' ); ?></p>
				</article>
				<article class="wa-docs-card">
					<h4><?php esc_html_e( 'I want to reduce server load', 'whippet' ); ?></h4>
					<p><?php esc_html_e( 'Be conservative with Preload RPS, disable analytics for admins, tune Heartbeat in Performance, and avoid Unlimited request rates on shared hosting.', 'whippet' ); ?></p>
				</article>
				<article class="wa-docs-card">
					<h4><?php esc_html_e( 'I run WooCommerce', 'whippet' ); ?></h4>
					<p><?php esc_html_e( 'Rely on auto cache excludes for cart/checkout/account. Use Dashboard Woo toggles carefully. Fine-tune leftover assets with Script Manager on non-shop pages. Shop UX tweaks live under Extras.', 'whippet' ); ?></p>
				</article>
				<article class="wa-docs-card">
					<h4><?php esc_html_e( 'Something already broke', 'whippet' ); ?></h4>
					<p><?php esc_html_e( 'Undo the last change or restore a Tools snapshot / Import backup. Open the recovery-mode URL if caching or rewrites are in the way, then re-apply changes more narrowly.', 'whippet' ); ?></p>
				</article>
			</div>
		</section>

		<!-- ── Troubleshooting ─────────────────────────────────────────── -->
		<section class="wa-docs-section" id="doc-troubleshoot">
			<div class="wa-docs-section__hd">
				<h3><?php esc_html_e( 'Troubleshooting', 'whippet' ); ?></h3>
				<p><?php esc_html_e( 'Most issues come down to cache, scope, or disabling something the page still needs.', 'whippet' ); ?></p>
			</div>

			<div class="wa-docs-faq">
				<details class="wa-docs-faq__item" open>
					<summary><?php esc_html_e( 'The site looks broken after disabling a script or style', 'whippet' ); ?></summary>
					<p><?php esc_html_e( 'Undo the last rule first. Clear caches. If the issue disappears, re-apply on Current URL only. Check jQuery-dependent plugins before disabling shared libraries.', 'whippet' ); ?></p>
				</details>
				<details class="wa-docs-faq__item">
					<summary><?php esc_html_e( 'I saved changes but the front end did not change', 'whippet' ); ?></summary>
					<p><?php esc_html_e( 'Purge Whippet cache, any caching plugin, server cache, CDN cache, and test in a private window. Cached HTML is the usual culprit.', 'whippet' ); ?></p>
				</details>
				<details class="wa-docs-faq__item">
					<summary><?php esc_html_e( 'Script Manager does not appear in the admin bar', 'whippet' ); ?></summary>
					<p><?php esc_html_e( 'You must be on the front end, logged in, with manage_options capability. It does not appear for visitors or in wp-admin.', 'whippet' ); ?></p>
				</details>
				<details class="wa-docs-faq__item">
					<summary><?php esc_html_e( 'Preload is causing extra traffic or server pressure', 'whippet' ); ?></summary>
					<p><?php esc_html_e( 'Increase the delay, lower requests per second, switch to hover-only, add ignore keywords for cart/checkout/logout, and disable for logged-in admins.', 'whippet' ); ?></p>
				</details>
				<details class="wa-docs-faq__item">
					<summary><?php esc_html_e( 'Analytics is not recording visits', 'whippet' ); ?></summary>
					<p><?php esc_html_e( 'Confirm a GA4 G- ID, clear caches, test without ad blockers, and remember Disable for logged-in admins means your own visits will not appear.', 'whippet' ); ?></p>
				</details>
				<details class="wa-docs-faq__item">
					<summary><?php esc_html_e( 'Brotli or WebP shows as unavailable', 'whippet' ); ?></summary>
					<p><?php esc_html_e( 'That depends on PHP extensions / the image library. Use the Tools tab to confirm support before enabling features that rely on it. Ask your host if something is missing.', 'whippet' ); ?></p>
				</details>
				<details class="wa-docs-faq__item">
					<summary><?php esc_html_e( 'Lazy Load Responsiveness / Compression / WebP shows an error', 'whippet' ); ?></summary>
					<p><?php esc_html_e( 'Those subtabs require the Lazy Load CDN to be enabled first.', 'whippet' ); ?></p>
				</details>
				<details class="wa-docs-faq__item">
					<summary><?php esc_html_e( 'Apache Direct-Serve does nothing', 'whippet' ); ?></summary>
					<p><?php esc_html_e( 'It needs Apache with mod_rewrite and a writable .htaccess. It will not apply on Nginx or many managed hosts that ignore .htaccess.', 'whippet' ); ?></p>
				</details>
				<details class="wa-docs-faq__item">
					<summary><?php esc_html_e( 'A snippet broke the site', 'whippet' ); ?></summary>
					<p><?php esc_html_e( 'Enable Safe Mode (one-hour cookie disables snippet output), then edit or disable the offending snippet. Keep Allow PHP execution off unless you trust the code.', 'whippet' ); ?></p>
				</details>
				<details class="wa-docs-faq__item">
					<summary><?php esc_html_e( 'Premium controls do nothing', 'whippet' ); ?></summary>
					<p><?php esc_html_e( 'Whippet Cloud Premium is still launching. The tab is a preview until Cloud Connect / an active plan unlocks the controls. Join the waitlist to be notified.', 'whippet' ); ?></p>
				</details>
			</div>
		</section>

		<!-- ── Checklist ───────────────────────────────────────────────── -->
		<section class="wa-docs-section" id="doc-checklist">
			<div class="wa-docs-section__hd">
				<h3><?php esc_html_e( 'Safe roll-out checklist', 'whippet' ); ?></h3>
				<p><?php esc_html_e( 'Run through this whenever you make meaningful changes.', 'whippet' ); ?></p>
			</div>

			<div class="wa-docs-checklist">
				<div class="wa-docs-check"><?php esc_html_e( 'Export settings (and note any Tools snapshots).', 'whippet' ); ?></div>
				<div class="wa-docs-check"><?php esc_html_e( 'Copy the recovery-mode bypass URL from Tools.', 'whippet' ); ?></div>
				<div class="wa-docs-check"><?php esc_html_e( 'Confirm HTTPS, Brotli, and WebP support on the Tools tab.', 'whippet' ); ?></div>
				<div class="wa-docs-check"><?php esc_html_e( 'Change one area at a time.', 'whippet' ); ?></div>
				<div class="wa-docs-check"><?php esc_html_e( 'Clear Whippet cache plus any plugin / server / CDN caches.', 'whippet' ); ?></div>
				<div class="wa-docs-check"><?php esc_html_e( 'Test the front end while logged out (private window).', 'whippet' ); ?></div>
				<div class="wa-docs-check"><?php esc_html_e( 'Check forms, menus, search, cart, and checkout flows.', 'whippet' ); ?></div>
				<div class="wa-docs-check"><?php esc_html_e( 'Measure with Lighthouse or PageSpeed Insights after the dust settles.', 'whippet' ); ?></div>
			</div>

			<div class="wa-docs-footer-card">
				<div>
					<h4><?php esc_html_e( 'Need more help?', 'whippet' ); ?></h4>
					<p><?php esc_html_e( 'For product updates and support, visit the Whippet site.', 'whippet' ); ?></p>
				</div>
				<a class="wa-docs-btn" href="https://nought.digital" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Visit website', 'whippet' ); ?>
				</a>
			</div>
		</section>

	</div>
	<?php
}
