<?php
/**
 * First-run setup wizard.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Guided setup for new installs.
 */
class SetupWizard {

	/**
	 * Boot hooks.
	 */
	public static function boot() {
		add_action( 'admin_notices', array( __CLASS__, 'maybe_render' ) );
		add_action( 'wp_ajax_whippet_wizard_apply_safe_preset', array( __CLASS__, 'ajax_apply_safe_preset' ) );
		add_action( 'wp_ajax_whippet_wizard_complete', array( __CLASS__, 'ajax_complete' ) );
		add_action( 'wp_ajax_whippet_wizard_dismiss', array( __CLASS__, 'ajax_dismiss' ) );
	}

	/**
	 * Authorise AJAX.
	 */
	private static function authorise() {
		check_ajax_referer( 'whippet_setup_wizard', 'nonce' );
		if ( ! Helper::user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to configure Whippet.', 'whippet' ) ), 403 );
		}
	}

	/**
	 * Keys written by the Safe preset.
	 *
	 * @return array<int,string>
	 */
	public static function safe_preset_keys() {
		return array(
			'disable_emojis',
			'remove_query_strings',
			'disable_embeds',
			'remove_rsd_link',
			'remove_shortlink',
			'remove_wlwmanifest_link',
			'remove_feed_links',
			'hide_wp_version',
			'remove_jquery_migrate',
		);
	}

	/**
	 * Render wizard on the Whippet admin screen when incomplete.
	 */
	public static function maybe_render() {
		if ( get_option( 'whippet_setup_completed' ) || get_option( 'whippet_setup_dismissed' ) || ! Helper::user_can_manage() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'tools_page_whippet' !== $screen->id ) {
			return;
		}
		self::render();
	}

	/**
	 * Apply Safe preset.
	 */
	public static function ajax_apply_safe_preset() {
		self::authorise();
		$options = (array) get_option( 'whippet_options', array() );
		foreach ( self::safe_preset_keys() as $key ) {
			$options[ $key ] = '1';
		}
		if ( ! ConflictDetector::has_page_cache_conflict() && ! empty( $_POST['enable_page_cache'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$options['enable_page_cache'] = '1';
		}
		if ( ! empty( $_POST['enable_gzip'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$options['enable_gzip'] = '1';
		}
		if ( ! empty( $_POST['enable_browser_cache'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$options['enable_browser_cache'] = '1';
		}
		update_option( 'whippet_options', $options, false );
		update_option( 'whippet_setup_progress', array( 'step' => 3, 'at' => gmdate( 'c' ) ), false );
		Helper::audit_log( 'setup_safe_preset_applied', array( 'keys' => self::safe_preset_keys() ) );
		wp_send_json_success( array( 'message' => __( 'Safe preset applied.', 'whippet' ) ) );
	}

	/**
	 * Mark setup complete.
	 */
	public static function ajax_complete() {
		self::authorise();
		update_option( 'whippet_setup_completed', gmdate( 'c' ), false );
		delete_option( 'whippet_setup_dismissed' );
		delete_option( 'whippet_setup_progress' );
		Helper::audit_log( 'setup_completed' );
		wp_send_json_success();
	}

	/**
	 * Dismiss wizard.
	 */
	public static function ajax_dismiss() {
		self::authorise();
		update_option( 'whippet_setup_dismissed', gmdate( 'c' ), false );
		Helper::audit_log( 'setup_dismissed' );
		wp_send_json_success();
	}

	/**
	 * Render multi-step wizard markup.
	 */
	public static function render() {
		$nonce     = wp_create_nonce( 'whippet_setup_wizard' );
		$conflict  = ConflictDetector::has_page_cache_conflict();
		$conflicts = ConflictDetector::get_conflicts();
		$export_url = admin_url( 'tools.php?page=whippet#import-export' );
		?>
		<div class="notice notice-info whippet-setup-wizard" role="dialog" aria-label="<?php esc_attr_e( 'Whippet setup wizard', 'whippet' ); ?>" x-data="{step:1,busy:false,message:''}" style="padding:1.25rem 1.5rem;">
			<div x-show="step===1">
				<h2 style="margin-top:0;"><?php esc_html_e( 'Welcome to Whippet', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Complete this short setup to apply safe performance wins without touching Script Manager, MU mode, or PHP snippets.', 'whippet' ); ?></p>
				<ol style="margin-left:1.25rem;">
					<li><?php esc_html_e( 'Export a settings backup', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Apply the Safe preset', 'whippet' ); ?></li>
					<li><?php esc_html_e( 'Review Tools diagnostics any time', 'whippet' ); ?></li>
				</ol>
				<?php if ( $conflicts ) : ?>
					<p><strong><?php esc_html_e( 'Detected optimiser plugins:', 'whippet' ); ?></strong> <?php echo esc_html( implode( ', ', wp_list_pluck( $conflicts, 'name' ) ) ); ?></p>
				<?php endif; ?>
				<p>
					<button type="button" class="button button-primary" @click="step=2"><?php esc_html_e( 'Start setup', 'whippet' ); ?></button>
					<button type="button" class="button" @click="fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'whippet_wizard_dismiss',nonce:'<?php echo esc_js( $nonce ); ?>'})}).then(()=>location.reload())"><?php esc_html_e( 'Dismiss for now', 'whippet' ); ?></button>
				</p>
			</div>
			<div x-show="step===2" style="display:none">
				<h2 style="margin-top:0;"><?php esc_html_e( 'Back up first', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'Export your current Whippet settings before applying changes so you can restore quickly if needed.', 'whippet' ); ?></p>
				<p>
					<a class="button" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Open Import / Export', 'whippet' ); ?></a>
					<button type="button" class="button button-primary" @click="step=3"><?php esc_html_e( 'I have a backup — continue', 'whippet' ); ?></button>
				</p>
			</div>
			<div x-show="step===3" style="display:none">
				<h2 style="margin-top:0;"><?php esc_html_e( 'Safe performance preset', 'whippet' ); ?></h2>
				<p><?php esc_html_e( 'This removes low-value WordPress output such as emojis, embeds, legacy links, and jQuery Migrate.', 'whippet' ); ?></p>
				<label><input type="checkbox" x-ref="gzip" checked> <?php esc_html_e( 'Enable Gzip support', 'whippet' ); ?></label><br>
				<label><input type="checkbox" x-ref="browser" checked> <?php esc_html_e( 'Enable browser caching', 'whippet' ); ?></label><br>
				<label><input type="checkbox" x-ref="cache" <?php disabled( $conflict ); ?>> <?php esc_html_e( 'Enable Whippet page cache', 'whippet' ); ?></label>
				<?php if ( $conflict ) : ?>
					<p><?php esc_html_e( 'Page cache stays off because another cache plugin is active. Choose one page cache to avoid conflicts.', 'whippet' ); ?></p>
				<?php endif; ?>
				<p>
					<button type="button" class="button button-primary" :disabled="busy" @click="busy=true;fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'whippet_wizard_apply_safe_preset',nonce:'<?php echo esc_js( $nonce ); ?>',enable_gzip:$refs.gzip.checked?1:0,enable_browser_cache:$refs.browser.checked?1:0,enable_page_cache:$refs.cache.checked?1:0})}).then(r=>r.json()).then(r=>{busy=false;if(r.success){message=r.data.message;step=4}else{message=(r.data&&r.data.message)||'Error';}})">
						<?php esc_html_e( 'Apply Safe preset', 'whippet' ); ?>
					</button>
				</p>
			</div>
			<div x-show="step===4" style="display:none">
				<h2 style="margin-top:0;"><?php esc_html_e( 'You are ready', 'whippet' ); ?></h2>
				<p x-text="message"></p>
				<p><?php esc_html_e( 'Next: capture an Impact baseline from the Dashboard, then use Script Manager carefully on individual URLs.', 'whippet' ); ?></p>
				<button type="button" class="button button-primary" @click="fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'whippet_wizard_complete',nonce:'<?php echo esc_js( $nonce ); ?>'})}).then(()=>location.reload())"><?php esc_html_e( 'Finish setup', 'whippet' ); ?></button>
			</div>
		</div>
		<?php
	}
}
