=== Whippet ===

Contributors: JaHe, noughtdigital, hashbangcode
Plugin Name: Whippet
Plugin URI: https://whippetwp.com/
Tags: performance, cache, script manager, optimisation, speed, critical css, webp, lazy load, woocommerce
Author URI: https://www.hashbangcode.com/
Author: Jake Henshall
Donate link: https://whippetwp.com/
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

All-in-one WordPress performance: remove bloat, cache pages, control scripts, optimise images, and connect optional Cloud premium services.

== Description ==

Whippet helps you make WordPress faster with a guided setup, safe presets, and deep asset control.

= Core features =

* Dashboard toggles to disable unused WordPress features (emojis, embeds, XML-RPC, feeds, and more)
* File-based page cache with optional Gzip/Brotli variants and exclusions
* Browser cache headers and local WebP conversion
* Script Manager with per-URL / per-post-type rules and optional MU-plugin mode
* Lazy load, font optimisation, and link preload
* Local analytics hosting helpers for Google Analytics
* Code snippets (PHP snippets off by default)
* Database cleanup, import/export, recovery mode, and settings snapshots
* Tools diagnostics, activity log, conflict detection, and impact metrics
* WooCommerce-aware cache exclusions and utilities

= Premium / Cloud =

Optional Image Engine and Critical CSS services can be connected from the Premium tab when you have an API key for Whippet Cloud.

= Safe by design =

* First-run setup wizard with a conservative Safe preset
* Recovery mode to bypass front-end optimisations if something breaks
* Settings snapshots for rollback
* Hardened import allowlists and audit logging

== Installation ==

1. Upload the `whippet` folder to `/wp-content/plugins/` or install via Plugins → Add New
2. Activate Whippet
3. Open Tools → Whippet and complete the setup wizard
4. Export a backup before making aggressive Script Manager changes

== Frequently Asked Questions ==

= Will Whippet conflict with WP Rocket or LiteSpeed Cache? =

Whippet detects common cache plugins and warns you. Prefer one page cache at a time.

= Is the cookie notice a full GDPR consent manager? =

No. It is a simple notice only. Use a dedicated CMP for regulatory consent.

= Are PHP snippets enabled by default? =

No. PHP snippet execution is opt-in and disabled until you explicitly enable it.

= Where is the activity log? =

Tools → Whippet → Dashboard shows recent audited actions. Destructive actions are also written to the PHP error log.

== Screenshots ==

1. Unified Tools → Whippet admin shell
2. Performance and cache controls
3. Script Manager
4. Premium Image Engine and Critical CSS
5. Setup wizard and Tools diagnostics

== Changelog ==

= 1.1.0 =
* Setup wizard, Safe preset, conflict detection
* Impact metrics, settings snapshots, recovery mode
* Activity log UI, CDN purge hooks, anomaly alerts
* Script Manager adviser, WooCommerce auto-excludes
* Cloud connect UI, capabilities, WP-CLI, multisite helpers
* Hardened Critical CSS TLS verification and settings import schema
* Legacy Settings menus redirected to the unified shell
* Updated WordPress.org listing metadata

= 1.0.2 =
* Security hardening, PHPUnit suite, CI workflow

= 1.0.1 =
* Completely remove comments
* UX / UI update
* Script Manager fix (WordPress 5.0+)
* Disable the core-embed/wordpress block type

= 1.0.0 =
* Initial release
