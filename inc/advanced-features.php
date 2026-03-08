<?php
/**
 * Whippet Advanced Features
 *
 * Header/body/footer code, cookie bar, maintenance mode, cloner,
 * social sharing buttons, and WooCommerce utility toggles.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdvancedFeatures {

	private static $instance = null;
	private $options_cache = null;
	private $share_styles_printed = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_runtime_hooks' ) );
		add_action( 'init', array( $this, 'handle_admin_form_submission' ) );
		add_action( 'wp_head', array( $this, 'render_global_head_code' ), 100 );
		add_action( 'wp_body_open', array( $this, 'render_global_body_code' ), 1 );
		add_action( 'wp_footer', array( $this, 'render_global_footer_code' ), 100 );
		add_action( 'wp_footer', array( $this, 'render_cookie_bar' ), 5 );
		add_shortcode( 'whippet_share_buttons', array( $this, 'share_buttons_shortcode' ) );

		add_filter( 'post_row_actions', array( $this, 'add_clone_link_to_post_rows' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'add_clone_link_to_post_rows' ), 10, 2 );
		add_action( 'admin_action_whippet_clone_post', array( $this, 'handle_clone_post_action' ) );
	}

	private function get_options() {
		if ( null === $this->options_cache ) {
			$this->options_cache = (array) get_option( 'whippet_options', array() );
		}
		return $this->options_cache;
	}

	private function update_options( $new_values ) {
		$options = $this->get_options();
		$options = array_merge( $options, $new_values );
		update_option( 'whippet_options', $options );
		$this->options_cache = $options;
	}

	private function is_safe_mode() {
		return isset( $_COOKIE[ SnippetManager::SAFE_MODE_COOKIE ] ) && '1' === $_COOKIE[ SnippetManager::SAFE_MODE_COOKIE ];
	}

	private function get_default_cookie_warning_text() {
		return __( 'This website uses both technical cookies, essential for you to browse the website and use its features, and third-party cookies we use for marketing and data analytics purposes, as explained in our <a href="/cookies/">cookie policy</a>.', 'whippet' );
	}

	private function normalize_maintenance_status( $status ) {
		$status = sanitize_key( (string) $status );
		if ( in_array( $status, array( 'online', 'coming_soon', 'maintenance' ), true ) ) {
			return $status;
		}
		return 'online';
	}

	private function ensure_maintenance_token( $token = '' ) {
		$token = sanitize_key( (string) $token );
		if ( '' === $token ) {
			$token = wp_generate_password( 24, false, false );
		}
		return sanitize_key( $token );
	}

	public function register_runtime_hooks() {
		$options = $this->get_options();

		$maintenance_status = $this->normalize_maintenance_status( $options['maintenance_mode_status'] ?? ( ! empty( $options['maintenance_mode_enabled'] ) ? 'maintenance' : 'online' ) );
		if ( 'online' !== $maintenance_status && ! is_admin() ) {
			add_action( 'template_redirect', array( $this, 'maybe_render_maintenance_page' ), 1 );
		}

		if ( ! empty( $options['social_share_enabled'] ) && ! is_admin() ) {
			add_filter( 'the_content', array( $this, 'inject_share_buttons_into_content' ) );
			add_action( 'wp_footer', array( $this, 'render_floating_share_buttons' ), 30 );
		}

		if ( class_exists( 'WooCommerce' ) ) {
			if ( ! empty( $options['woo_fix_free_shipping'] ) ) {
				add_filter( 'woocommerce_package_rates', array( $this, 'filter_free_shipping_rates' ), 100, 2 );
			}
			if ( ! empty( $options['woo_variable_price_from'] ) ) {
				add_filter( 'woocommerce_variable_price_html', array( $this, 'render_variable_price_from_html' ), 20, 2 );
				add_filter( 'woocommerce_variable_sale_price_html', array( $this, 'render_variable_price_from_html' ), 20, 2 );
			}
			if ( ! empty( $options['woo_hide_trailing_zeros'] ) ) {
				add_filter( 'woocommerce_price_trim_zeros', '__return_true' );
			}
			if ( ! empty( $options['woo_disable_unique_sku'] ) ) {
				add_filter( 'wc_product_has_unique_sku', '__return_false', 99 );
			}
			if ( ! empty( $options['woo_disable_skus'] ) ) {
				add_filter( 'wc_product_sku_enabled', '__return_false', 99 );
				add_action( 'admin_head', array( $this, 'hide_woo_sku_field_in_admin' ) );
			}

			if ( ! empty( $options['woo_redirect_checkout_after_add'] ) ) {
				add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'redirect_to_checkout_after_add' ) );
			}
			if ( ! empty( $options['woo_hide_related_products'] ) ) {
				remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
			}
			if ( ! empty( $options['woo_hide_sku'] ) ) {
				add_filter( 'wc_product_sku_enabled', '__return_false' );
			}
		}
	}

	public function handle_admin_form_submission() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( empty( $_POST['whippet_extras_action'] ) || 'save_extras' !== $_POST['whippet_extras_action'] ) {
			return;
		}
		if ( empty( $_POST['whippet_extras_nonce'] ) || ! wp_verify_nonce( $_POST['whippet_extras_nonce'], 'whippet_extras_nonce' ) ) {
			return;
		}

		$raw = isset( $_POST['whippet_options'] ) ? (array) wp_unslash( $_POST['whippet_options'] ) : array();
		$new = array(
			'global_head_code'                  => isset( $raw['global_head_code'] ) ? (string) $raw['global_head_code'] : '',
			'global_body_code'                  => isset( $raw['global_body_code'] ) ? (string) $raw['global_body_code'] : '',
			'global_footer_code'                => isset( $raw['global_footer_code'] ) ? (string) $raw['global_footer_code'] : '',
			'cookie_bar_enabled'                => ! empty( $raw['cookie_bar_enabled'] ) ? '1' : '0',
			'cookie_bar_message'                => isset( $raw['cookie_bar_message'] ) ? (string) $raw['cookie_bar_message'] : '',
			'cookie_bar_required_button_text'   => isset( $raw['cookie_bar_required_button_text'] ) ? sanitize_text_field( $raw['cookie_bar_required_button_text'] ) : '',
			'cookie_bar_button_text'            => isset( $raw['cookie_bar_button_text'] ) ? sanitize_text_field( $raw['cookie_bar_button_text'] ) : '',
			'cookie_bar_theme'                  => isset( $raw['cookie_bar_theme'] ) ? sanitize_key( $raw['cookie_bar_theme'] ) : 'modern_dark',
			'cookie_bar_background_color'       => isset( $raw['cookie_bar_background_color'] ) ? sanitize_hex_color( $raw['cookie_bar_background_color'] ) : '',
			'cookie_bar_privacy_url'            => isset( $raw['cookie_bar_privacy_url'] ) ? esc_url_raw( $raw['cookie_bar_privacy_url'] ) : '',
			'maintenance_mode_enabled'          => ! empty( $raw['maintenance_mode_enabled'] ) ? '1' : '0',
			'maintenance_mode_status'           => $this->normalize_maintenance_status( $raw['maintenance_mode_status'] ?? 'online' ),
			'maintenance_mode_heading'          => isset( $raw['maintenance_mode_heading'] ) ? sanitize_text_field( $raw['maintenance_mode_heading'] ) : '',
			'maintenance_mode_message'          => isset( $raw['maintenance_mode_message'] ) ? sanitize_textarea_field( $raw['maintenance_mode_message'] ) : '',
			'maintenance_mode_bypass_token'     => isset( $raw['maintenance_mode_bypass_token'] ) ? sanitize_key( $raw['maintenance_mode_bypass_token'] ) : '',
			'maintenance_mode_content_page'     => isset( $raw['maintenance_mode_content_page'] ) ? absint( $raw['maintenance_mode_content_page'] ) : 0,
			'social_share_enabled'              => ! empty( $raw['social_share_enabled'] ) ? '1' : '0',
			'social_share_title'                => isset( $raw['social_share_title'] ) ? sanitize_text_field( $raw['social_share_title'] ) : '',
			'social_share_buttons'              => isset( $raw['social_share_buttons'] ) ? array_values( array_filter( array_map( 'sanitize_key', (array) $raw['social_share_buttons'] ) ) ) : array(),
			'social_share_pos_before'           => ! empty( $raw['social_share_pos_before'] ) ? '1' : '0',
			'social_share_pos_after'            => ! empty( $raw['social_share_pos_after'] ) ? '1' : '0',
			'social_share_pos_floating'         => ! empty( $raw['social_share_pos_floating'] ) ? '1' : '0',
			'social_share_networks'             => isset( $raw['social_share_networks'] ) ? sanitize_text_field( $raw['social_share_networks'] ) : '',
			'woo_fix_free_shipping'             => ! empty( $raw['woo_fix_free_shipping'] ) ? '1' : '0',
			'woo_variable_price_from'           => ! empty( $raw['woo_variable_price_from'] ) ? '1' : '0',
			'woo_hide_trailing_zeros'           => ! empty( $raw['woo_hide_trailing_zeros'] ) ? '1' : '0',
			'woo_disable_unique_sku'            => ! empty( $raw['woo_disable_unique_sku'] ) ? '1' : '0',
			'woo_disable_skus'                  => ! empty( $raw['woo_disable_skus'] ) ? '1' : '0',
			'woo_redirect_checkout_after_add'   => ! empty( $raw['woo_redirect_checkout_after_add'] ) ? '1' : '0',
			'woo_hide_related_products'         => ! empty( $raw['woo_hide_related_products'] ) ? '1' : '0',
			'woo_hide_sku'                      => ! empty( $raw['woo_hide_sku'] ) ? '1' : '0',
		);

		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$new['global_head_code']   = wp_kses_post( $new['global_head_code'] );
			$new['global_body_code']   = wp_kses_post( $new['global_body_code'] );
			$new['global_footer_code'] = wp_kses_post( $new['global_footer_code'] );
		}
		$new['cookie_bar_message'] = wp_kses_post( $new['cookie_bar_message'] );
		if ( ! in_array( $new['cookie_bar_theme'], array( 'modern_dark', 'modern_light' ), true ) ) {
			$new['cookie_bar_theme'] = 'modern_dark';
		}
		if ( empty( $new['cookie_bar_background_color'] ) ) {
			$new['cookie_bar_background_color'] = '';
		}
		$allowed_share_buttons = array( 'x', 'facebook', 'linkedin', 'whatsapp', 'pinterest' );
		$new['social_share_buttons'] = array_values( array_intersect( (array) $new['social_share_buttons'], $allowed_share_buttons ) );
		if ( empty( $new['social_share_buttons'] ) ) {
			$new['social_share_buttons'] = array( 'x', 'facebook', 'linkedin', 'whatsapp', 'pinterest' );
		}
		// Legacy compatibility key.
		$new['social_share_networks'] = implode( ',', $new['social_share_buttons'] );
		if ( ! empty( $raw['maintenance_mode_regenerate_token'] ) ) {
			$new['maintenance_mode_bypass_token'] = '';
		}
		$new['maintenance_mode_bypass_token'] = $this->ensure_maintenance_token( $new['maintenance_mode_bypass_token'] );
		$new['maintenance_mode_enabled'] = 'online' === $new['maintenance_mode_status'] ? '0' : '1';

		$this->update_options( $new );
		add_settings_error( 'whippet_extras', 'whippet_extras_saved', __( 'Extras settings saved.', 'whippet' ), 'success' );
	}

	public function render_global_head_code() {
		if ( is_admin() || $this->is_safe_mode() ) {
			return;
		}
		$options = $this->get_options();
		if ( ! empty( $options['global_head_code'] ) ) {
			echo "\n" . $options['global_head_code'] . "\n";
		}
	}

	public function render_global_body_code() {
		if ( is_admin() || $this->is_safe_mode() ) {
			return;
		}
		$options = $this->get_options();
		if ( ! empty( $options['global_body_code'] ) ) {
			echo "\n" . $options['global_body_code'] . "\n";
		}
	}

	public function render_global_footer_code() {
		if ( is_admin() || $this->is_safe_mode() ) {
			return;
		}
		$options = $this->get_options();
		if ( ! empty( $options['global_footer_code'] ) ) {
			echo "\n" . $options['global_footer_code'] . "\n";
		}
	}

	public function render_cookie_bar() {
		if ( is_admin() || $this->is_safe_mode() ) {
			return;
		}
		$options = $this->get_options();
		if ( empty( $options['cookie_bar_enabled'] ) || ! empty( $_COOKIE['whippet_cookie_accepted'] ) || ! empty( $_COOKIE['whippet_cookie_required'] ) ) {
			return;
		}

		$message         = ! empty( $options['cookie_bar_message'] ) ? $options['cookie_bar_message'] : $this->get_default_cookie_warning_text();
		$required_text   = ! empty( $options['cookie_bar_required_button_text'] ) ? $options['cookie_bar_required_button_text'] : __( 'Accept required cookies', 'whippet' );
		$button_text     = ! empty( $options['cookie_bar_button_text'] ) ? $options['cookie_bar_button_text'] : __( 'Accept cookies', 'whippet' );
		$privacy_url     = ! empty( $options['cookie_bar_privacy_url'] ) ? $options['cookie_bar_privacy_url'] : '';
		$theme           = ! empty( $options['cookie_bar_theme'] ) ? $options['cookie_bar_theme'] : 'modern_dark';
		$dark            = 'modern_light' !== $theme;
		$custom_bg       = ! empty( $options['cookie_bar_background_color'] ) ? sanitize_hex_color( $options['cookie_bar_background_color'] ) : '';
		$bg_color        = $custom_bg ? $custom_bg : ( $dark ? '#111827' : '#ffffff' );
		$text_color      = $dark ? '#f8fafc' : '#0f172a';
		$shadow          = $dark ? '0 8px 20px rgba(0,0,0,.2)' : '0 8px 20px rgba(15,23,42,.12)';
		$required_bg     = $dark ? '#334155' : '#e2e8f0';
		$required_color  = $dark ? '#f8fafc' : '#0f172a';
		$link_color      = $dark ? '#93c5fd' : '#1d4ed8';
		?>
		<style>
		#whippet-cookie-bar .whippet-cookie-message a,
		#whippet-cookie-bar .whippet-cookie-privacy-link{
			color:<?php echo esc_attr( $link_color ); ?>;
			text-decoration:underline;
			font-weight:600;
		}
		</style>
		<div id="whippet-cookie-bar" style="position:fixed;left:12px;right:12px;bottom:12px;z-index:99999;background:<?php echo esc_attr( $bg_color ); ?>;color:<?php echo esc_attr( $text_color ); ?>;padding:12px 14px;border-radius:10px;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;box-shadow:<?php echo esc_attr( $shadow ); ?>;font-size:14px;<?php echo $dark ? '' : 'border:1px solid #cbd5e1;'; ?>">
			<div class="whippet-cookie-message" style="flex:1 1 320px;min-width:220px;line-height:1.5;"><?php echo wp_kses_post( $message ); ?></div>
			<div style="display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:8px;">
				<?php if ( $privacy_url ) : ?>
					<a class="whippet-cookie-privacy-link" href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Privacy', 'whippet' ); ?></a>
				<?php endif; ?>
				<button type="button" id="whippet-cookie-required" style="background:<?php echo esc_attr( $required_bg ); ?>;border:0;color:<?php echo esc_attr( $required_color ); ?>;padding:10px 14px;border-radius:8px;cursor:pointer;line-height:1.2;white-space:nowrap;"><?php echo esc_html( $required_text ); ?></button>
				<button type="button" id="whippet-cookie-accept" style="background:#3b82f6;border:0;color:#fff;padding:10px 14px;border-radius:8px;cursor:pointer;line-height:1.2;white-space:nowrap;"><?php echo esc_html( $button_text ); ?></button>
			</div>
		</div>
		<script>
		(function(){
			var requiredBtn = document.getElementById('whippet-cookie-required');
			var btn = document.getElementById('whippet-cookie-accept');
			function removeBar(){
				var bar = document.getElementById('whippet-cookie-bar');
				if(bar){bar.remove();}
			}
			if(requiredBtn){
				requiredBtn.addEventListener('click', function(){
					document.cookie = 'whippet_cookie_required=1; path=/; max-age=' + (60*60*24*365) + '; SameSite=Lax';
					removeBar();
				});
			}
			if(!btn){return;}
			btn.addEventListener('click', function(){
				document.cookie = 'whippet_cookie_accepted=1; path=/; max-age=' + (60*60*24*365) + '; SameSite=Lax';
				removeBar();
			});
		})();
		</script>
		<?php
	}

	public function maybe_render_maintenance_page() {
		$options = $this->get_options();
		$status  = $this->normalize_maintenance_status( $options['maintenance_mode_status'] ?? ( ! empty( $options['maintenance_mode_enabled'] ) ? 'maintenance' : 'online' ) );
		if ( 'online' === $status ) {
			return;
		}

		$is_preview = current_user_can( 'manage_options' ) && isset( $_GET['whippet_preview_maintenance'] );
		if ( current_user_can( 'manage_options' ) && ! $is_preview ) {
			return;
		}

		if ( ! empty( $_COOKIE['whippet_maintenance_bypass'] ) ) {
			return;
		}

		$token   = ! empty( $options['maintenance_mode_bypass_token'] ) ? $options['maintenance_mode_bypass_token'] : '';

		if ( $token && isset( $_GET['whippet_access'] ) && hash_equals( $token, sanitize_key( wp_unslash( $_GET['whippet_access'] ) ) ) ) {
			setcookie( 'whippet_maintenance_bypass', '1', time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			return;
		}

		$is_login = isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'];
		if ( $is_login ) {
			return;
		}

		if ( 'maintenance' === $status ) {
			status_header( 503 );
			nocache_headers();
			header( 'Retry-After: 3600', true );
			header( 'X-Robots-Tag: noindex, nofollow', true );
		} else {
			status_header( 200 );
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}

		$title   = ! empty( $options['maintenance_mode_heading'] ) ? $options['maintenance_mode_heading'] : __( 'We are improving the site', 'whippet' );
		$message = ! empty( $options['maintenance_mode_message'] ) ? $options['maintenance_mode_message'] : __( 'Please check back shortly.', 'whippet' );
		$content_page_id = ! empty( $options['maintenance_mode_content_page'] ) ? absint( $options['maintenance_mode_content_page'] ) : 0;
		$content_page    = $content_page_id ? get_post( $content_page_id ) : null;
		$page_title      = ( $content_page && 'publish' === $content_page->post_status ) ? get_the_title( $content_page ) : '';
		$page_content    = ( $content_page && 'publish' === $content_page->post_status ) ? apply_filters( 'the_content', $content_page->post_content ) : '';
		?>
		<!doctype html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo esc_html( $title ); ?></title>
			<style>
				body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#0f172a;color:#e2e8f0;display:grid;min-height:100vh;place-items:center}
				.wrap{max-width:700px;padding:32px;text-align:center}
				h1{font-size:34px;line-height:1.2;margin:0 0 12px}
				p{font-size:16px;line-height:1.65;color:#94a3b8}
			</style>
		</head>
		<body>
			<div class="wrap">
				<h1><?php echo esc_html( $title ); ?></h1>
				<p><?php echo esc_html( $message ); ?></p>
				<?php if ( $page_content ) : ?>
					<div style="margin-top:1rem;text-align:left;background:#111827;border:1px solid #334155;border-radius:10px;padding:1rem;">
						<?php if ( $page_title ) : ?>
							<h2 style="margin-top:0;color:#f8fafc;"><?php echo esc_html( $page_title ); ?></h2>
						<?php endif; ?>
						<div style="color:#cbd5e1;"><?php echo wp_kses_post( $page_content ); ?></div>
					</div>
				<?php endif; ?>
			</div>
		</body>
		</html>
		<?php
		exit;
	}

	public function add_clone_link_to_post_rows( $actions, $post ) {
		if ( ! current_user_can( 'edit_posts' ) || 'trash' === $post->post_status ) {
			return $actions;
		}
		$url = wp_nonce_url(
			admin_url( 'admin.php?action=whippet_clone_post&post=' . $post->ID ),
			'whippet_clone_post_' . $post->ID
		);
		$actions['whippet_clone'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicate', 'whippet' ) . '</a>';
		return $actions;
	}

	public function handle_clone_post_action() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'whippet' ) );
		}
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		if ( ! $post_id || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'whippet_clone_post_' . $post_id ) ) {
			wp_die( esc_html__( 'Invalid request.', 'whippet' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( esc_html__( 'Post not found.', 'whippet' ) );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => $post->post_type,
				'post_status'  => 'draft',
				'post_title'   => $post->post_title . ' (Copy)',
				'post_content' => $post->post_content,
				'post_excerpt' => $post->post_excerpt,
				'post_author'  => get_current_user_id(),
				'post_parent'  => $post->post_parent,
				'menu_order'   => $post->menu_order,
			)
		);
		if ( is_wp_error( $new_id ) || ! $new_id ) {
			wp_die( esc_html__( 'Failed to clone post.', 'whippet' ) );
		}

		$meta = get_post_meta( $post_id );
		foreach ( $meta as $key => $values ) {
			if ( '_edit_lock' === $key || '_edit_last' === $key ) {
				continue;
			}
			foreach ( $values as $value ) {
				add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
			}
		}

		$taxonomies = get_object_taxonomies( $post->post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $terms ) ) {
				wp_set_object_terms( $new_id, $terms, $taxonomy );
			}
		}

		wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
		exit;
	}

	public function share_buttons_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'url'   => get_permalink(),
				'title' => get_the_title(),
			),
			$atts
		);
		return $this->build_share_buttons_markup( (string) $atts['url'], (string) $atts['title'], 'shortcode' );
	}

	public function inject_share_buttons_into_content( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		if ( post_password_required() ) {
			return $content;
		}

		$options = $this->get_options();
		$before  = ! empty( $options['social_share_pos_before'] );
		$after   = ! isset( $options['social_share_pos_after'] ) || ! empty( $options['social_share_pos_after'] );
		$url     = get_permalink();
		$title   = get_the_title();

		$prefix = $before ? $this->build_share_buttons_markup( $url, $title, 'before' ) : '';
		$suffix = $after ? $this->build_share_buttons_markup( $url, $title, 'after' ) : '';
		return $prefix . $content . $suffix;
	}

	private function get_active_share_buttons( $options ) {
		if ( ! empty( $options['social_share_buttons'] ) && is_array( $options['social_share_buttons'] ) ) {
			return array_values( array_filter( array_map( 'sanitize_key', $options['social_share_buttons'] ) ) );
		}
		if ( ! empty( $options['social_share_networks'] ) ) {
			return array_values( array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', strtolower( (string) $options['social_share_networks'] ) ) ) ) ) );
		}
		return array( 'x', 'facebook', 'linkedin', 'whatsapp', 'pinterest' );
	}

	private function render_share_styles() {
		if ( $this->share_styles_printed ) {
			return;
		}
		$this->share_styles_printed = true;
		echo '<style id="whippet-share-styles">.whippet-share{margin-top:14px}.whippet-share-title{font-size:14px;font-weight:600;margin:0 0 8px}.whippet-share-row{display:flex;gap:8px;flex-wrap:wrap}.whippet-share-btn{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:34px;padding:0 10px;border-radius:8px;background:#0f172a;color:#fff;text-decoration:none;font-size:13px;line-height:1}.whippet-share-btn:hover{opacity:.92}.whippet-share--after-mobile-hide{display:none}@media (min-width:782px){.whippet-share--after-mobile-hide{display:block}}.whippet-share-floating{position:fixed;left:0;right:0;bottom:0;z-index:9997;background:#fff;border-top:1px solid #e2e8f0;padding:8px 10px;display:flex;justify-content:center}@media (min-width:782px){.whippet-share-floating{display:none}}</style>';
	}

	private function build_share_buttons_markup( $url, $title, $position = 'after' ) {
		$options  = $this->get_options();
		$networks = $this->get_active_share_buttons( $options );
		$u        = rawurlencode( $url );
		$t        = rawurlencode( $title );
		$post_type_obj = get_post_type_object( get_post_type() );
		$post_type_label = $post_type_obj && ! empty( $post_type_obj->labels->singular_name ) ? $post_type_obj->labels->singular_name : __( 'post', 'whippet' );
		$title_tpl = ! empty( $options['social_share_title'] ) ? $options['social_share_title'] : __( 'Share this %%post_type%%', 'whippet' );
		$display_title = str_replace( '%%post_type%%', strtolower( $post_type_label ), $title_tpl );

		$map = array(
			'x'         => array( 'label' => 'X/Twitter', 'url' => 'https://x.com/intent/tweet?url=' . $u . '&text=' . $t ),
			'facebook'  => array( 'label' => 'Facebook', 'url' => 'https://www.facebook.com/sharer/sharer.php?u=' . $u ),
			'linkedin'  => array( 'label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u ),
			'whatsapp'  => array( 'label' => 'WhatsApp', 'url' => 'https://api.whatsapp.com/send?text=' . rawurlencode( $title . ' ' . $url ) ),
			'pinterest' => array( 'label' => 'Pinterest', 'url' => 'https://pinterest.com/pin/create/button/?url=' . $u . '&description=' . $t ),
		);

		$this->render_share_styles();
		$wrapper_class = 'whippet-share';
		if ( 'after' === $position ) {
			$wrapper_class .= ' whippet-share--after-mobile-hide';
		}
		if ( 'floating' === $position ) {
			$wrapper_class = 'whippet-share-floating';
		}
		$html = '<div class="' . esc_attr( $wrapper_class ) . '">';
		if ( 'floating' !== $position && '' !== trim( $display_title ) ) {
			$html .= '<div class="whippet-share-title">' . esc_html( $display_title ) . '</div>';
		}
		$html .= '<div class="whippet-share-row">';
		foreach ( $networks as $network ) {
			if ( ! isset( $map[ $network ] ) ) {
				continue;
			}
			$html .= '<a class="whippet-share-btn whippet-share-' . esc_attr( $network ) . '" target="_blank" rel="noopener noreferrer" href="' . esc_url( $map[ $network ]['url'] ) . '">' . esc_html( $map[ $network ]['label'] ) . '</a>';
		}
		$html .= '</div></div>';
		return $html;
	}

	public function render_floating_share_buttons() {
		if ( ! is_singular() || is_admin() || post_password_required() ) {
			return;
		}
		$options = $this->get_options();
		if ( empty( $options['social_share_enabled'] ) || empty( $options['social_share_pos_floating'] ) ) {
			return;
		}
		echo $this->build_share_buttons_markup( get_permalink(), get_the_title(), 'floating' );
	}

	public function redirect_to_checkout_after_add() {
		return wc_get_checkout_url();
	}

	public function filter_free_shipping_rates( $rates, $package ) {
		$allowed_methods = array( 'free_shipping', 'local_pickup' );
		$has_free_shipping = false;
		foreach ( $rates as $rate ) {
			if ( in_array( $rate->method_id, $allowed_methods, true ) && 'free_shipping' === $rate->method_id ) {
				$has_free_shipping = true;
				break;
			}
		}
		if ( ! $has_free_shipping ) {
			return $rates;
		}

		foreach ( $rates as $rate_id => $rate ) {
			if ( ! in_array( $rate->method_id, $allowed_methods, true ) ) {
				unset( $rates[ $rate_id ] );
			}
		}
		return $rates;
	}

	public function render_variable_price_from_html( $price_html, $product ) {
		if ( ! $product || ! method_exists( $product, 'get_variation_price' ) ) {
			return $price_html;
		}
		$min_price = $product->get_variation_price( 'min', true );
		if ( '' === $min_price || null === $min_price ) {
			return $price_html;
		}
		return sprintf(
			/* translators: %s is a WooCommerce formatted price */
			esc_html__( 'Price from %s', 'whippet' ),
			wp_kses_post( wc_price( $min_price ) )
		);
	}

	public function hide_woo_sku_field_in_admin() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}
		echo '<style>#_sku, .form-field._sku_field, .show_if_simple .form-field._sku_field, .show_if_variable .form-field._sku_field{display:none !important;}</style>';
	}
}

AdvancedFeatures::instance();

function whippet_render_extras_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$options = (array) get_option( 'whippet_options', array() );
	$default_cookie_warning_text = __( 'This website uses both technical cookies, essential for you to browse the website and use its features, and third-party cookies we use for marketing and data analytics purposes, as explained in our <a href="/cookies/">cookie policy</a>.', 'whippet' );
	$cookie_theme = ! empty( $options['cookie_bar_theme'] ) ? $options['cookie_bar_theme'] : 'modern_dark';
	$cookie_dark  = 'modern_light' !== $cookie_theme;
	$cookie_bg    = ! empty( $options['cookie_bar_background_color'] ) ? sanitize_hex_color( $options['cookie_bar_background_color'] ) : ( $cookie_dark ? '#111827' : '#ffffff' );
	$maintenance_status = isset( $options['maintenance_mode_status'] ) ? sanitize_key( (string) $options['maintenance_mode_status'] ) : ( ! empty( $options['maintenance_mode_enabled'] ) ? 'maintenance' : 'online' );
	if ( ! in_array( $maintenance_status, array( 'online', 'coming_soon', 'maintenance' ), true ) ) {
		$maintenance_status = 'online';
	}
	$maintenance_token = isset( $options['maintenance_mode_bypass_token'] ) ? sanitize_key( (string) $options['maintenance_mode_bypass_token'] ) : '';
	if ( '' === $maintenance_token ) {
		$maintenance_token = sanitize_key( wp_generate_password( 24, false, false ) );
	}
	$maintenance_preview_url = add_query_arg( 'whippet_preview_maintenance', '1', home_url( '/' ) );
	$maintenance_magic_url = add_query_arg( 'whippet_access', $maintenance_token, home_url( '/' ) );
	$maintenance_pages = get_pages( array( 'sort_column' => 'post_title', 'post_status' => array( 'publish' ) ) );
	settings_errors( 'whippet_extras' );
	?>
	<div style="padding:1.25rem;">
		<form method="post">
			<?php wp_nonce_field( 'whippet_extras_nonce', 'whippet_extras_nonce' ); ?>
			<input type="hidden" name="whippet_extras_action" value="save_extras">

			<h3 style="margin:0 0 .5rem;"><?php esc_html_e( 'Global Header, Body, Footer Code', 'whippet' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Add inline code or script/style tags globally. Head prints in <head>, body prints after <body>, footer prints before </body>.', 'whippet' ); ?></p>
			<table class="form-table" role="presentation">
				<tbody>
				<tr>
					<th><label for="global_head_code"><?php esc_html_e( 'Head Code', 'whippet' ); ?></label></th>
					<td><textarea id="global_head_code" name="whippet_options[global_head_code]" rows="5" class="large-text code"><?php echo esc_textarea( $options['global_head_code'] ?? '' ); ?></textarea></td>
				</tr>
				<tr>
					<th><label for="global_body_code"><?php esc_html_e( 'Body Code', 'whippet' ); ?></label></th>
					<td><textarea id="global_body_code" name="whippet_options[global_body_code]" rows="5" class="large-text code"><?php echo esc_textarea( $options['global_body_code'] ?? '' ); ?></textarea></td>
				</tr>
				<tr>
					<th><label for="global_footer_code"><?php esc_html_e( 'Footer Code', 'whippet' ); ?></label></th>
					<td><textarea id="global_footer_code" name="whippet_options[global_footer_code]" rows="5" class="large-text code"><?php echo esc_textarea( $options['global_footer_code'] ?? '' ); ?></textarea></td>
				</tr>
				</tbody>
			</table>

			<hr>
			<h3><?php esc_html_e( 'Cookie & GDPR Warning', 'whippet' ); ?></h3>
			<table class="form-table" role="presentation">
				<tbody>
				<tr>
					<th><?php esc_html_e( 'Cookie alert status', 'whippet' ); ?></th>
					<td>
						<label style="display:inline-flex;align-items:center;gap:.35rem;margin-right:1rem;">
							<input type="radio" name="whippet_options[cookie_bar_enabled]" value="1" <?php checked( ! empty( $options['cookie_bar_enabled'] ) ); ?>> <?php esc_html_e( 'Enabled', 'whippet' ); ?>
						</label>
						<label style="display:inline-flex;align-items:center;gap:.35rem;">
							<input type="radio" name="whippet_options[cookie_bar_enabled]" value="0" <?php checked( empty( $options['cookie_bar_enabled'] ) ); ?>> <?php esc_html_e( 'Disabled', 'whippet' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><label for="cookie_bar_message"><?php esc_html_e( 'Cookie warning text', 'whippet' ); ?></label></th>
					<td>
						<textarea id="cookie_bar_message" name="whippet_options[cookie_bar_message]" rows="4" class="large-text"><?php echo esc_textarea( $options['cookie_bar_message'] ?? $default_cookie_warning_text ); ?></textarea>
						<div style="margin-top:.35rem;">
							<a href="#" id="whippet-cookie-restore-default"><?php esc_html_e( 'Restore default warning text', 'whippet' ); ?></a>
						</div>
					</td>
				</tr>
				<tr>
					<th><label for="cookie_bar_required_button_text"><?php esc_html_e( 'Accept required cookies text', 'whippet' ); ?></label></th>
					<td><input id="cookie_bar_required_button_text" name="whippet_options[cookie_bar_required_button_text]" type="text" class="regular-text" value="<?php echo esc_attr( $options['cookie_bar_required_button_text'] ?? '' ); ?>"></td>
				</tr>
				<tr>
					<th><label for="cookie_bar_button_text"><?php esc_html_e( 'Accept button text', 'whippet' ); ?></label></th>
					<td><input id="cookie_bar_button_text" name="whippet_options[cookie_bar_button_text]" type="text" class="regular-text" value="<?php echo esc_attr( $options['cookie_bar_button_text'] ?? '' ); ?>"></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Cookie bar theme', 'whippet' ); ?></th>
					<td>
						<label style="display:inline-flex;align-items:center;gap:.35rem;margin-right:1rem;">
							<input type="radio" name="whippet_options[cookie_bar_theme]" value="modern_light" <?php checked( 'modern_light', $cookie_theme ); ?>> <?php esc_html_e( 'Modern Light', 'whippet' ); ?>
						</label>
						<label style="display:inline-flex;align-items:center;gap:.35rem;">
							<input type="radio" name="whippet_options[cookie_bar_theme]" value="modern_dark" <?php checked( 'modern_dark', $cookie_theme ); ?>> <?php esc_html_e( 'Modern Dark', 'whippet' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><label for="cookie_bar_background_color"><?php esc_html_e( 'Background color', 'whippet' ); ?></label></th>
					<td>
						<input id="cookie_bar_background_color" name="whippet_options[cookie_bar_background_color]" type="color" value="<?php echo esc_attr( $cookie_bg ); ?>">
						<input id="cookie_bar_background_color_text" type="text" class="regular-text" style="max-width:110px;" value="<?php echo esc_attr( $cookie_bg ); ?>">
						<p class="description"><?php esc_html_e( 'Pick a custom banner background color. Leave default for theme presets.', 'whippet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="cookie_bar_privacy_url"><?php esc_html_e( 'Privacy URL', 'whippet' ); ?></label></th>
					<td><input id="cookie_bar_privacy_url" name="whippet_options[cookie_bar_privacy_url]" type="url" class="regular-text" value="<?php echo esc_attr( $options['cookie_bar_privacy_url'] ?? '' ); ?>"></td>
				</tr>
				</tbody>
			</table>
			<div style="margin-top:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;">
				<div style="font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;"><?php esc_html_e( 'Cookie banner preview', 'whippet' ); ?></div>
				<div id="whippet-cookie-preview" style="background:<?php echo esc_attr( $cookie_bg ); ?>;color:<?php echo esc_attr( $cookie_dark ? '#f8fafc' : '#0f172a' ); ?>;padding:12px 14px;border-radius:10px;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;<?php echo $cookie_dark ? '' : 'border:1px solid #cbd5e1;'; ?>">
					<div id="whippet-cookie-preview-message" style="flex:1 1 320px;min-width:220px;line-height:1.5;"><?php echo wp_kses_post( $options['cookie_bar_message'] ?? $default_cookie_warning_text ); ?></div>
					<div style="display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:8px;">
						<button id="whippet-cookie-preview-required" type="button" style="background:<?php echo esc_attr( $cookie_dark ? '#334155' : '#e2e8f0' ); ?>;border:0;color:<?php echo esc_attr( $cookie_dark ? '#f8fafc' : '#0f172a' ); ?>;padding:10px 14px;border-radius:8px;line-height:1.2;white-space:nowrap;"><?php echo esc_html( ! empty( $options['cookie_bar_required_button_text'] ) ? $options['cookie_bar_required_button_text'] : __( 'Accept required cookies', 'whippet' ) ); ?></button>
						<button id="whippet-cookie-preview-accept" type="button" style="background:#3b82f6;border:0;color:#fff;padding:10px 14px;border-radius:8px;line-height:1.2;white-space:nowrap;"><?php echo esc_html( ! empty( $options['cookie_bar_button_text'] ) ? $options['cookie_bar_button_text'] : __( 'Accept cookies', 'whippet' ) ); ?></button>
					</div>
				</div>
			</div>
			<script>
			(function(){
				var restore = document.getElementById('whippet-cookie-restore-default');
				var message = document.getElementById('cookie_bar_message');
				var requiredInput = document.getElementById('cookie_bar_required_button_text');
				var acceptInput = document.getElementById('cookie_bar_button_text');
				var bgInput = document.getElementById('cookie_bar_background_color');
				var bgTextInput = document.getElementById('cookie_bar_background_color_text');
				var themeInputs = document.querySelectorAll('input[name="whippet_options[cookie_bar_theme]"]');
				var preview = document.getElementById('whippet-cookie-preview');
				var previewMessage = document.getElementById('whippet-cookie-preview-message');
				var previewRequired = document.getElementById('whippet-cookie-preview-required');
				var previewAccept = document.getElementById('whippet-cookie-preview-accept');
				var defaultText = <?php echo wp_json_encode( $default_cookie_warning_text ); ?>;
				var darkDefaultBg = '#111827';
				var lightDefaultBg = '#ffffff';
				function activeTheme(){
					var current = 'modern_dark';
					themeInputs.forEach(function(input){ if(input.checked){ current = input.value; } });
					return current;
				}
				function normalizeHex(v){
					if(!v){ return ''; }
					return /^#[0-9a-fA-F]{6}$/.test(v) ? v : '';
				}
				function syncPreview(){
					if(!preview){ return; }
					var theme = activeTheme();
					var dark = theme !== 'modern_light';
					var linkColor = dark ? '#93c5fd' : '#1d4ed8';
					var customBg = normalizeHex(bgInput ? bgInput.value : '');
					var bg = customBg || (dark ? darkDefaultBg : lightDefaultBg);
					preview.style.background = bg;
					preview.style.color = dark ? '#f8fafc' : '#0f172a';
					preview.style.border = dark ? '' : '1px solid #cbd5e1';
					if(previewRequired){
						previewRequired.style.background = dark ? '#334155' : '#e2e8f0';
						previewRequired.style.color = dark ? '#f8fafc' : '#0f172a';
						previewRequired.textContent = (requiredInput && requiredInput.value.trim()) ? requiredInput.value : '<?php echo esc_js( __( 'Accept required cookies', 'whippet' ) ); ?>';
					}
					if(previewAccept){
						previewAccept.textContent = (acceptInput && acceptInput.value.trim()) ? acceptInput.value : '<?php echo esc_js( __( 'Accept cookies', 'whippet' ) ); ?>';
					}
					if(previewMessage && message){
						previewMessage.innerHTML = message.value.trim() ? message.value : defaultText;
						previewMessage.querySelectorAll('a').forEach(function(link){
							link.style.color = linkColor;
							link.style.textDecoration = 'underline';
							link.style.fontWeight = '600';
						});
					}
					if(bgTextInput){
						bgTextInput.value = bgInput ? bgInput.value : bg;
					}
				}
				if(!restore || !message){return;}
				restore.addEventListener('click', function(e){
					e.preventDefault();
					message.value = defaultText;
					syncPreview();
				});
				['input','change'].forEach(function(evt){
					message.addEventListener(evt, syncPreview);
					if(requiredInput){ requiredInput.addEventListener(evt, syncPreview); }
					if(acceptInput){ acceptInput.addEventListener(evt, syncPreview); }
					if(bgInput){ bgInput.addEventListener(evt, syncPreview); }
					if(bgTextInput){
						bgTextInput.addEventListener(evt, function(){
							var normalized = normalizeHex(bgTextInput.value.trim());
							if(normalized && bgInput){ bgInput.value = normalized; }
							syncPreview();
						});
					}
					themeInputs.forEach(function(input){ input.addEventListener(evt, syncPreview); });
				});
				syncPreview();
			})();
			</script>

			<hr>
			<h3><?php esc_html_e( 'Maintenance Mode', 'whippet' ); ?></h3>
			<p class="description" style="margin-top:0;"><?php esc_html_e( 'Performance impact: this section stores all settings in a single autoloaded configuration variable.', 'whippet' ); ?></p>
			<table class="form-table" role="presentation">
				<tbody>
				<tr>
					<th><?php esc_html_e( 'Set site status', 'whippet' ); ?></th>
					<td>
						<label style="display:block;margin-bottom:.45rem;"><input type="radio" name="whippet_options[maintenance_mode_status]" value="online" <?php checked( 'online', $maintenance_status ); ?>> <?php esc_html_e( 'Online — WordPress works as usual', 'whippet' ); ?></label>
						<label style="display:block;margin-bottom:.45rem;"><input type="radio" name="whippet_options[maintenance_mode_status]" value="coming_soon" <?php checked( 'coming_soon', $maintenance_status ); ?>> <?php esc_html_e( 'Coming soon — Site closed. All pages have a meta robots noindex, nofollow', 'whippet' ); ?></label>
						<label style="display:block;"><input type="radio" name="whippet_options[maintenance_mode_status]" value="maintenance" <?php checked( 'maintenance', $maintenance_status ); ?>> <?php esc_html_e( 'Maintenance — Site closed. All pages return 503 Service unavailable', 'whippet' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Magic Link', 'whippet' ); ?></th>
					<td>
						<a id="whippet-maintenance-magic-link" href="<?php echo esc_url( $maintenance_magic_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $maintenance_magic_url ); ?></a>
						<button type="button" class="button button-secondary" id="whippet-copy-magic-link"><?php esc_html_e( 'copy to clipboard', 'whippet' ); ?></button>
						<label class="button button-secondary" for="whippet-maintenance-regenerate"><?php esc_html_e( 'change secret token', 'whippet' ); ?></label>
						<input type="checkbox" id="whippet-maintenance-regenerate" name="whippet_options[maintenance_mode_regenerate_token]" value="1" style="display:none;">
						<input type="hidden" name="whippet_options[maintenance_mode_bypass_token]" value="<?php echo esc_attr( $maintenance_token ); ?>">
						<p class="description"><?php esc_html_e( 'Use this link to grant someone access to the website when it is in maintenance mode.', 'whippet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="maintenance_mode_content_page"><?php esc_html_e( 'Choose a page for the content', 'whippet' ); ?></label></th>
					<td>
						<select id="maintenance_mode_content_page" name="whippet_options[maintenance_mode_content_page]">
							<option value="0"><?php esc_html_e( 'Use default content', 'whippet' ); ?></option>
							<?php foreach ( $maintenance_pages as $page ) : ?>
								<option value="<?php echo esc_attr( (string) $page->ID ); ?>" <?php selected( (int) ( $options['maintenance_mode_content_page'] ?? 0 ), (int) $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
						<a class="button button-secondary" href="<?php echo esc_url( $maintenance_preview_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Preview', 'whippet' ); ?></a>
					</td>
				</tr>
				<tr>
					<th><label for="maintenance_mode_heading"><?php esc_html_e( 'Heading', 'whippet' ); ?></label></th>
					<td><input id="maintenance_mode_heading" name="whippet_options[maintenance_mode_heading]" type="text" class="regular-text" value="<?php echo esc_attr( $options['maintenance_mode_heading'] ?? '' ); ?>"></td>
				</tr>
				<tr>
					<th><label for="maintenance_mode_message"><?php esc_html_e( 'Message', 'whippet' ); ?></label></th>
					<td><textarea id="maintenance_mode_message" name="whippet_options[maintenance_mode_message]" rows="4" class="large-text"><?php echo esc_textarea( $options['maintenance_mode_message'] ?? '' ); ?></textarea></td>
				</tr>
				</tbody>
			</table>
			<script>
			(function(){
				var copyBtn = document.getElementById('whippet-copy-magic-link');
				var linkEl = document.getElementById('whippet-maintenance-magic-link');
				if(copyBtn && linkEl && navigator.clipboard){
					copyBtn.addEventListener('click', function(){
						navigator.clipboard.writeText(linkEl.textContent.trim());
					});
				}
			})();
			</script>

			<hr>
			<h3><?php esc_html_e( 'Social Sharing Buttons', 'whippet' ); ?></h3>
			<p class="description" style="margin-top:0;"><?php esc_html_e( 'Performance impact: optimized mode with no external icon font file and no additional database query during normal requests.', 'whippet' ); ?></p>
			<table class="form-table" role="presentation">
				<tbody>
				<tr>
					<th><?php esc_html_e( 'Sharing buttons status', 'whippet' ); ?></th>
					<td>
						<label style="display:block;margin-bottom:.45rem;"><input type="radio" name="whippet_options[social_share_enabled]" value="1" <?php checked( ! empty( $options['social_share_enabled'] ) ); ?>> <?php esc_html_e( 'Enabled', 'whippet' ); ?></label>
						<label style="display:block;"><input type="radio" name="whippet_options[social_share_enabled]" value="0" <?php checked( empty( $options['social_share_enabled'] ) ); ?>> <?php esc_html_e( 'Disabled', 'whippet' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><label for="social_share_title"><?php esc_html_e( 'Bottom buttons title', 'whippet' ); ?></label></th>
					<td>
						<input id="social_share_title" name="whippet_options[social_share_title]" type="text" class="regular-text" value="<?php echo esc_attr( $options['social_share_title'] ?? __( 'Share this %%post_type%%', 'whippet' ) ); ?>">
						<p class="description"><?php esc_html_e( 'Leave blank to remove. The %%post_type%% placeholder is replaced by the post type name.', 'whippet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Active share buttons', 'whippet' ); ?></th>
					<td>
						<?php
						$active_share_buttons = ! empty( $options['social_share_buttons'] ) && is_array( $options['social_share_buttons'] )
							? $options['social_share_buttons']
							: array_filter( array_map( 'trim', explode( ',', (string) ( $options['social_share_networks'] ?? 'x,facebook,linkedin,whatsapp,pinterest' ) ) ) );
						$share_buttons_map = array(
							'x' => __( 'X/Twitter', 'whippet' ),
							'facebook' => __( 'Facebook', 'whippet' ),
							'linkedin' => __( 'LinkedIn', 'whippet' ),
							'whatsapp' => __( 'WhatsApp (only on mobile devices)', 'whippet' ),
							'pinterest' => __( 'Pinterest', 'whippet' ),
						);
						foreach ( $share_buttons_map as $slug => $label ) :
						?>
							<label style="display:block;margin-bottom:.45rem;">
								<input type="checkbox" name="whippet_options[social_share_buttons][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $active_share_buttons, true ) ); ?>>
								<?php echo esc_html( $label ); ?>
							</label>
						<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Sharing buttons position', 'whippet' ); ?></th>
					<td>
						<label style="display:block;margin-bottom:.45rem;">
							<input type="checkbox" name="whippet_options[social_share_pos_before]" value="1" <?php checked( ! empty( $options['social_share_pos_before'] ) ); ?>>
							<?php esc_html_e( 'At the beginning of the content', 'whippet' ); ?>
						</label>
						<label style="display:block;margin-bottom:.45rem;">
							<input type="checkbox" name="whippet_options[social_share_pos_after]" value="1" <?php checked( ! isset( $options['social_share_pos_after'] ) || ! empty( $options['social_share_pos_after'] ) ); ?>>
							<?php esc_html_e( 'At the end of the content (hidden on mobile)', 'whippet' ); ?>
						</label>
						<label style="display:block;">
							<input type="checkbox" name="whippet_options[social_share_pos_floating]" value="1" <?php checked( ! isset( $options['social_share_pos_floating'] ) || ! empty( $options['social_share_pos_floating'] ) ); ?>>
							<?php esc_html_e( 'Floating footer (mobile only)', 'whippet' ); ?>
						</label>
					</td>
				</tr>
				</tbody>
			</table>

			<hr>
			<h3><?php esc_html_e( 'WooCommerce Utils', 'whippet' ); ?></h3>
			<p class="description" style="margin-top:0;"><?php esc_html_e( 'Performance impact: this section stores all settings in a single autoloaded configuration variable.', 'whippet' ); ?></p>
			<table class="form-table" role="presentation">
				<tbody>
				<tr>
					<td colspan="2" style="padding:0;">
						<table class="widefat striped" style="margin:0;border:none;">
							<thead>
								<tr>
									<th style="width:240px;"><?php esc_html_e( 'Option', 'whippet' ); ?></th>
									<th><?php esc_html_e( 'Explanation', 'whippet' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><label><input type="checkbox" name="whippet_options[woo_fix_free_shipping]" value="1" <?php checked( ! empty( $options['woo_fix_free_shipping'] ) ); ?>> <?php esc_html_e( 'Fix free shipping', 'whippet' ); ?></label></td>
									<td><?php esc_html_e( 'Hides paid shipping methods from checkout when free shipping is available. Keeps "Free shipping" and "local Pickup".', 'whippet' ); ?></td>
								</tr>
								<tr>
									<td><label><input type="checkbox" name="whippet_options[woo_variable_price_from]" value="1" <?php checked( ! empty( $options['woo_variable_price_from'] ) ); ?>> <?php esc_html_e( 'Variable price from', 'whippet' ); ?></label></td>
									<td><?php esc_html_e( 'Replaces the price interval on variable products with a "Price from" label.', 'whippet' ); ?></td>
								</tr>
								<tr>
									<td><label><input type="checkbox" name="whippet_options[woo_hide_trailing_zeros]" value="1" <?php checked( ! empty( $options['woo_hide_trailing_zeros'] ) ); ?>> <?php esc_html_e( 'Hide trailing zeros', 'whippet' ); ?></label></td>
									<td><?php esc_html_e( 'Hides trailing zeros on prices. Shows $5.00 as $5', 'whippet' ); ?></td>
								</tr>
								<tr>
									<td><label><input type="checkbox" name="whippet_options[woo_disable_unique_sku]" value="1" <?php checked( ! empty( $options['woo_disable_unique_sku'] ) ); ?>> <?php esc_html_e( 'Disable unique SKU', 'whippet' ); ?></label></td>
									<td><?php esc_html_e( 'Allows you to use the same SKU in multiple products or product variations.', 'whippet' ); ?></td>
								</tr>
								<tr>
									<td><label><input type="checkbox" name="whippet_options[woo_disable_skus]" value="1" <?php checked( ! empty( $options['woo_disable_skus'] ) ); ?>> <?php esc_html_e( 'Disable SKUs', 'whippet' ); ?></label></td>
									<td><?php esc_html_e( 'Removes the SKU field in both the backend and frontend of your store.', 'whippet' ); ?></td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
				</tbody>
			</table>

			<p><button class="button button-primary" type="submit"><?php esc_html_e( 'Save Settings', 'whippet' ); ?></button></p>
		</form>
	</div>
	<?php
}
