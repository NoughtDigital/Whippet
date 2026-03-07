<?php
namespace Whippet;
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class Settings {

function __construct() {
	add_action('admin_init', array( $this, 'whippet_settings' ) );
}

/**
* Register settings + options
* @return [type] [description]
*/
public function whippet_settings() {
	if(get_option('whippet_options') == false) {
		add_option('whippet_options', apply_filters( 'whippet_default_options', $this->whippet_default_options()));
	}

	/**
	* Requests Primary Section
	* @var [type]
	*/
	add_settings_section('whippet_options', '', array($this, 'whippet_options_callback'), 'whippet_options');

	/**
	* Disable Emojis
	* @var [type]
	*/
	add_settings_field(
		'disable_emojis',
		$this->whippet_title('Disable Emojis', 'disable_emojis'),
		array($this, 'whippet_print_input'),
		'whippet_options',
		'whippet_options',
		array(
			'id' => 'disable_emojis'
		)
	);

	/**
	* Remove Query Strings
	* @var [type]
	*/
	add_settings_field(
		'remove_query_strings',
		$this->whippet_title('Remove Query Strings', 'remove_query_strings'),
		array($this, 'whippet_print_input' ),
		'whippet_options',
		'whippet_options',
		array(
			'id' => 'remove_query_strings'
		)
	);

	/**
	* Remove Comments
	* @var [type]
	*/
	add_settings_field(
		'remove_comments',
		$this->whippet_title('Remove Comments', 'remove_comments'),
		array($this, 'whippet_print_input' ),
		'whippet_options',
		'whippet_options',
		array(
			'id' => 'remove_comments'
		)
	);

	/**
	* Disable Embeds
	* @var [type]
	*/
	add_settings_field(
		'disable_embeds',
		$this->whippet_title('Disable Embeds', 'disable_embeds'),
		array($this, 'whippet_print_input' ),
		'whippet_options',
		'whippet_options',
		array(
			'id' => 'disable_embeds'
		)
	);

	/**
	* Disable Google Maps
	* @var [type]
	*/
	add_settings_field(
		'disable_google_maps',
		$this->whippet_title('Disable Google Maps', 'disable_google_maps'),
		array($this, 'whippet_print_input' ),
		'whippet_options',
		'whippet_options',
		array(
			'id' => 'disable_google_maps'
		)
	);

	/**
	* Remove jQuery Migrate
	* @var [type]
	*/
	add_settings_field(
		'remove_jquery_migrate',
		$this->whippet_title('Remove jQuery Migrate', 'remove_jquery_migrate'),
		array($this, 'whippet_print_input'),
		'whippet_options',
		'whippet_options',
		array(
			'id' => 'remove_jquery_migrate'
		)
	);

	/**
	* Tags Primary Section
	* @var [type]
	*/
	add_settings_section('whippet_tags', 'Tags', array($this, 'whippet_tags_callback'), 'whippet_options');

	/**
	* Remove RSD Link
	* @var [type]
	*/
	add_settings_field(
		'remove_rsd_link',
		$this->whippet_title('Remove RSD Link', 'remove_rsd_link'),
		array($this, 'whippet_print_input'),
		'whippet_options',
		'whippet_tags',
		array(
			'id' => 'remove_rsd_link'
		)
	);

	/**
	* Remove Shortlink
	* @var [type]
	*/
	add_settings_field(
		'remove_shortlink',
		$this->whippet_title('Remove Shortlink', 'remove_shortlink'),
		array($this, 'whippet_print_input' ),
		'whippet_options',
		'whippet_tags',
		array(
			'id' => 'remove_shortlink'
		)
	);

	/**
	* Remove REST API Links
	* @var [type]
	*/
	add_settings_field(
		'remove_rest_api_links',
		$this->whippet_title('Remove REST API Links', 'remove_rest_api_links'),
		array($this, 'whippet_print_input' ),
		'whippet_options',
		'whippet_tags',
		array(
			'id' => 'remove_rest_api_links'
		)
	);

	/**
	* Remove wlmanifest Link
	* @var [type]
	*/
	add_settings_field(
		'remove_wlwmanifest_link',
		$this->whippet_title('Remove wlwmanifest Link', 'remove_wlwmanifest_link'),
		array($this, 'whippet_print_input' ),
		'whippet_options',
		'whippet_tags',
		array(
			'id' => 'remove_wlwmanifest_link'
		)
	);

	/**
	* Remove Feed Links
	* @var [type]
	*/
	add_settings_field(
		'remove_feed_links',
		$this->whippet_title('Remove RSS Feed Links', 'remove_feed_links'),
		array($this, 'whippet_print_input' ),
		'whippet_options',
		'whippet_tags',
		array(
			'id' => 'remove_feed_links'
		)
	);

	/**
	* Admin Primary Section
	* @var [type]
	*/
	add_settings_section('whippet_admin', 'Admin', array($this, 'whippet_admin_callback'), 'whippet_performance');

	/**
	* Limit Post Revisions
	* @var [type]
	*/
	add_settings_field(
		'limit_post_revisions',
		$this->whippet_title( __( 'Limit Post Revisions', 'whippet' ), 'limit_post_revisions' ),
		array($this, 'whippet_print_input'),
		'whippet_performance',
		'whippet_admin',
		array(
			'id' => 'limit_post_revisions',
			'input' => 'select',
			'options' => array(
				'' => 'Default',
				'false' => 'Disable Post Revisions',
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'4' => '4',
				'5' => '5',
				'10' => '10',
				'15' => '15',
				'20' => '20',
				'25' => '25',
				'30' => '30'
			)
		)
	);

	/**
	* Autosave Interval
	* @var [type]
	*/
	add_settings_field(
		'autosave_interval',
		$this->whippet_title( __( 'Autosave Interval', 'whippet' ), 'autosave_interval' ),
		array($this, 'whippet_print_input'),
		'whippet_performance',
		'whippet_admin',
		array(
			'id' => 'autosave_interval',
			'input' => 'select',
			'options' => array(
				'' => '1 Minute (Default)',
				'120' => '2 Minutes',
				'180' => '3 Minutes',
				'240' => '4 Minutes',
				'300' => '5 Minutes'
			)
		)
	);

	/**
	* Disable Heartbeat
	* @var [type]
	*/
	add_settings_field(
		'disable_heartbeat',
		$this->whippet_title( __( 'Disable Heartbeat', 'whippet' ), 'disable_heartbeat' ),
		array($this, 'whippet_print_input'),
		'whippet_performance',
		'whippet_admin',
		array(
			'id' => 'disable_heartbeat',
			'input' => 'select',
			'options' => array(
				'' => 'Default',
				'disable_everywhere' => 'Disable Everywhere',
				'allow_posts' => 'Only Allow When Editing Posts/Pages'
			)
		)
	);

	/**
	* Heartbeat Frequency
	* @var [type]
	*/
	add_settings_field(
		'heartbeat_frequency',
		$this->whippet_title( __( 'Heartbeat Frequency', 'whippet' ), 'heartbeat_frequency' ),
		array($this, 'whippet_print_input'),
		'whippet_performance',
		'whippet_admin',
		array(
			'id' => 'heartbeat_frequency',
			'input' => 'select',
			'options' => array(
				'' => '15 Seconds (Default)',
				'30' => '30 Seconds',
				'45' => '45 Seconds',
				'60' => '60 Seconds'
			)
		)
	);

	/**
	* Misc Primary Section
	* @var [type]
	*/
	add_settings_section('whippet_misc', 'Misc', array($this, 'whippet_misc_callback' ), 'whippet_options');

	/**
	* Disable Self Pingbacks
	* @var [type]
	*/
	add_settings_field(
		'disable_self_pingbacks',
		$this->whippet_title('Disable Self Pingbacks', 'disable_self_pingbacks'),
		array($this, 'whippet_print_input'),
		'whippet_options',
		'whippet_misc',
		array(
			'id' => 'disable_self_pingbacks'
		)
	);

	/**
	* Disable RSS Feeds
	* @var [type]
	*/
	add_settings_field(
		'disable_rss_feeds',
		$this->whippet_title('Disable RSS Feeds', 'disable_rss_feeds'),
		array($this, 'whippet_print_input'),
		'whippet_options',
		'whippet_misc',
		array(
			'id' => 'disable_rss_feeds'
		)
	);

	/**
	* Disable XML-RPC
	* @var [type]
	*/
	add_settings_field(
		'disable_xmlrpc',
		$this->whippet_title('Disable XML-RPC', 'disable_xmlrpc'),
		array($this, 'whippet_print_input'),
		'whippet_options',
		'whippet_misc',
		array(
			'id' => 'disable_xmlrpc'
		)
	);

	/**
	* Hide WP Version
	* @var [type]
	*/
	add_settings_field(
		'hide_wp_version',
		$this->whippet_title('Hide WP Version', 'hide_wp_version'),
		array($this, 'whippet_print_input'),
		'whippet_options',
		'whippet_misc',
		array(
			'id' => 'hide_wp_version'
		)
	);

	/**
	* WooCommerce Options Section
	* @var [type]
	*/
	add_settings_section('whippet_woocommerce', 'WooCommerce', array($this, 'whippet_woocommerce_callback'), 'whippet_options');

	/**
	* Disable WooCommerce Scripts
	* @var [type]
	*/
	add_settings_field(
		'disable_woocommerce_scripts',
		$this->whippet_title('Disable Scripts', 'disable_woocommerce_scripts'),
		array($this, 'whippet_print_input'),
		'whippet_options',
		'whippet_woocommerce',
		array(
			'id' => 'disable_woocommerce_scripts'
		)
	);

	/**
	* Disable WooCommerce Cart Fragmentation
	* @var [type]
	*/
	add_settings_field(
		'disable_woocommerce_cart_fragmentation',
		$this->whippet_title('Disable Cart Fragmentation', 'disable_woocommerce_cart_fragmentation'),
		array($this, 'whippet_print_input'),
		'whippet_options',
		'whippet_woocommerce',
		array(
			'id' => 'disable_woocommerce_cart_fragmentation'
		)
	);

	/**
	* Disable WooCommerce Status Meta Box
	* @var [type]
	*/
	add_settings_field(
		'disable_woocommerce_status',
		$this->whippet_title('Disable Status Meta Box', 'disable_woocommerce_status'),
		array($this, 'whippet_print_input'),
		'whippet_options',
		'whippet_woocommerce',
		array(
			'id' => 'disable_woocommerce_status'
		)
	);

	/**
	* Disable WooCommerce Widgets
	* @var [type]
	*/
	add_settings_field(
		'disable_woocommerce_widgets',
		$this->whippet_title('Disable Widgets', 'disable_woocommerce_widgets'),
		array($this, 'whippet_print_input'),
		'whippet_options',
		'whippet_woocommerce',
		array(
			'id' => 'disable_woocommerce_widgets'
		)
	);

	/**
	* Performance Section
	*/
	add_settings_section( 'whippet_performance', 'Performance', array( $this, 'whippet_performance_callback' ), 'whippet_performance' );

	/**
	* Enable Page Cache
	*/
	add_settings_field(
		'enable_page_cache',
		$this->whippet_title( __( 'Page Caching', 'whippet' ), 'enable_page_cache' ),
		array( $this, 'whippet_print_input' ),
		'whippet_performance',
		'whippet_performance',
		array( 'id' => 'enable_page_cache' )
	);

	/**
	* Page Cache TTL
	*/
	add_settings_field(
		'page_cache_ttl',
		$this->whippet_title( __( 'Cache Expiry', 'whippet' ), 'page_cache_ttl' ),
		array( $this, 'whippet_print_input' ),
		'whippet_performance',
		'whippet_performance',
		array(
			'id'      => 'page_cache_ttl',
			'input'   => 'select',
			'options' => array(
				'3600'   => '1 Hour',
				'21600'  => '6 Hours',
				'43200'  => '12 Hours',
				'86400'  => '24 Hours (Default)',
				'604800' => '1 Week',
			),
		)
	);

	/**
	* Separate Mobile Cache
	*/
	add_settings_field(
		'cache_mobile_separate',
		$this->whippet_title( __( 'Separate Mobile Cache', 'whippet' ), 'cache_mobile_separate' ),
		array( $this, 'whippet_print_input' ),
		'whippet_performance',
		'whippet_performance',
		array( 'id' => 'cache_mobile_separate' )
	);

	/**
	* Cache Preloading
	*/
	add_settings_field(
		'cache_preload',
		$this->whippet_title( __( 'Cache Preloading', 'whippet' ), 'cache_preload' ),
		array( $this, 'whippet_print_input' ),
		'whippet_performance',
		'whippet_performance',
		array( 'id' => 'cache_preload' )
	);

	/**
	* .htaccess Direct-Serve
	*/
	add_settings_field(
		'cache_htaccess',
		$this->whippet_title( __( 'Apache Direct-Serve (.htaccess)', 'whippet' ), 'cache_htaccess' ),
		array( $this, 'whippet_print_input' ),
		'whippet_performance',
		'whippet_performance',
		array( 'id' => 'cache_htaccess' )
	);

	/**
	* Cache Exclude URLs
	*/
	add_settings_field(
		'cache_exclude_urls',
		$this->whippet_title( __( 'Exclude URLs from Cache', 'whippet' ), 'cache_exclude_urls' ),
		array( $this, 'whippet_render_cache_exclude_urls' ),
		'whippet_options',
		'whippet_performance'
	);

	/**
	* Cache Exclude Cookies
	*/
	add_settings_field(
		'cache_exclude_cookies',
		$this->whippet_title( __( 'Bypass Cache for Cookies', 'whippet' ), 'cache_exclude_cookies' ),
		array( $this, 'whippet_render_cache_exclude_cookies' ),
		'whippet_options',
		'whippet_performance'
	);

	/**
	* Cache Stats + Controls
	*/
	add_settings_field(
		'whippet_cache_controls',
		__( 'Cache Controls', 'whippet' ),
		array( $this, 'whippet_render_cache_controls' ),
		'whippet_options',
		'whippet_performance'
	);

	/**
	* Enable Browser Cache
	*/
	add_settings_field(
		'enable_browser_cache',
		$this->whippet_title( __( 'Browser Cache Headers', 'whippet' ), 'enable_browser_cache' ),
		array( $this, 'whippet_print_input' ),
		'whippet_performance',
		'whippet_performance',
		array( 'id' => 'enable_browser_cache' )
	);

	/**
	* Browser Cache TTL
	*/
	add_settings_field(
		'browser_cache_ttl',
		$this->whippet_title( __( 'Browser Cache Duration', 'whippet' ), 'browser_cache_ttl' ),
		array( $this, 'whippet_print_input' ),
		'whippet_performance',
		'whippet_performance',
		array(
			'id'      => 'browser_cache_ttl',
			'input'   => 'select',
			'options' => array(
				'3600'    => '1 Hour',
				'86400'   => '1 Day (Default)',
				'604800'  => '1 Week',
				'2592000' => '1 Month',
			),
		)
	);

	/**
	* Enable Gzip / Brotli
	*/
	add_settings_field(
		'enable_gzip',
		$this->whippet_title( __( 'Gzip / Brotli Compression', 'whippet' ), 'enable_gzip' ),
		array( $this, 'whippet_render_gzip_field' ),
		'whippet_options',
		'whippet_performance'
	);

	/**
	* Image Compression Quality
	*/
	add_settings_field(
		'image_quality',
		$this->whippet_title( __( 'Image Compression Quality', 'whippet' ), 'image_quality' ),
		array( $this, 'whippet_print_input' ),
		'whippet_performance',
		'whippet_performance',
		array(
			'id'      => 'image_quality',
			'input'   => 'select',
			'options' => array(
				''   => 'Default (82)',
				'50' => '50 — Aggressive',
				'60' => '60 — High',
				'70' => '70 — Good',
				'80' => '80 — Balanced',
				'90' => '90 — Light',
			),
		)
	);

	/**
	* WebP Conversion
	*/
	add_settings_field(
		'enable_webp_conversion',
		$this->whippet_title( __( 'WebP Conversion', 'whippet' ), 'enable_webp_conversion' ),
		array( $this, 'whippet_print_input' ),
		'whippet_performance',
		'whippet_performance',
		array( 'id' => 'enable_webp_conversion' )
	);

	/**
	* WebP Quality
	*/
	add_settings_field(
		'webp_quality',
		$this->whippet_title( __( 'WebP Quality', 'whippet' ), 'webp_quality' ),
		array( $this, 'whippet_print_input' ),
		'whippet_performance',
		'whippet_performance',
		array(
			'id'      => 'webp_quality',
			'input'   => 'select',
			'options' => array(
				'60' => '60 — Aggressive',
				'70' => '70 — Good',
				'80' => '80 — Balanced (Default)',
				'90' => '90 — Light',
			),
		)
	);

	/**
	* Bulk Image Compress Button
	*/
	add_settings_field(
		'whippet_bulk_compress_btn',
		__( 'Bulk Image Compress', 'whippet' ),
		array( $this, 'whippet_render_bulk_compress_button' ),
		'whippet_options',
		'whippet_performance'
	);

	/**
	* Preload Fonts
	*/
	add_settings_field(
		'preload_fonts',
		$this->whippet_title( __( 'Preload Fonts', 'whippet' ), 'preload_fonts' ),
		array( $this, 'whippet_render_preload_fonts_textarea' ),
		'whippet_options',
		'whippet_performance'
	);

	/**
	* DB Cleanup Schedule
	*/
	add_settings_field(
		'db_cleanup_schedule',
		$this->whippet_title( __( 'Auto DB Cleanup', 'whippet' ), 'db_cleanup_schedule' ),
		array( $this, 'whippet_print_input' ),
		'whippet_performance',
		'whippet_performance',
		array(
			'id'      => 'db_cleanup_schedule',
			'input'   => 'select',
			'options' => array(
				''        => 'Manual Only',
				'daily'   => 'Daily',
				'weekly'  => 'Weekly',
				'monthly' => 'Monthly',
			),
		)
	);

	/**
	* DB Cleanup Button (preview + run)
	*/
	add_settings_field(
		'whippet_db_cleanup_btn',
		__( 'Database Cleanup', 'whippet' ),
		array( $this, 'whippet_render_db_cleanup_button' ),
		'whippet_options',
		'whippet_performance'
	);

	register_setting( 'whippet_options',      'whippet_options', array( $this, 'whippet_sanitize_extras' ) );
	register_setting( 'whippet_performance', 'whippet_options', array( $this, 'whippet_sanitize_extras' ) );

}

/**
 * Options default values
 * @return [type] [description]
 */
public function whippet_default_options() {
	$defaults = array(
		'disable_emojis' => "0",
		'remove_query_strings' => "0",
		'remove_comments' => "0",
		'disable_embeds' => "0",
		'disable_google_maps' => "0",
		'remove_jquery_migrate' => "0",
		'remove_rsd_link' => "0",
		'remove_shortlink' => "0",
		'remove_rest_api_links' => "0",
		'remove_wlwmanifest_link' => "0",
		'remove_feed_links' => "0",
		'disable_xmlrpc' => "0",
		'hide_wp_version' => "0",
		'disable_rss_feeds' => "0",
		'disable_self_pingbacks' => "0",
		'disable_heartbeat' => "",
		'heartbeat_frequency' => "",
		'limit_post_revisions' => "",
		'autosave_interval' => "",
		'disable_woocommerce_scripts' => "0",
		'disable_woocommerce_cart_fragmentation' => "0",
		'disable_woocommerce_status' => "0",
		'disable_woocommerce_widgets' => "0",
		'enable_page_cache'       => '0',
		'page_cache_ttl'          => '86400',
		'cache_mobile_separate'   => '0',
		'cache_preload'           => '0',
		'cache_htaccess'          => '0',
		'cache_exclude_urls'      => '',
		'cache_exclude_cookies'   => '',
		'enable_browser_cache'    => '0',
		'browser_cache_ttl'       => '86400',
		'enable_gzip'             => '0',
		'image_quality'           => '',
		'enable_webp_conversion'  => '0',
		'webp_quality'            => '80',
		'preload_fonts'           => array(),
		'db_cleanup_schedule'     => '',
	);
	return apply_filters( 'whippet_default_options', $defaults );

}

/**
 * Generic section callback.
 *
 * @param string $text Description text for the section.
 */
private function render_section_description( $text ) {
	echo '<p class="whippet-subheading text-xs leading-normal italic pb-2 border-b border-gray-300">' . esc_html( $text ) . '</p>';
}

public function whippet_options_callback() {
	$this->render_section_description( __( 'Select which performance options you would like to enable.', 'whippet' ) );
}

public function whippet_tags_callback() {
	$this->render_section_description( __( 'Select which performance options you would like to enable.', 'whippet' ) );
}

public function whippet_admin_callback() {
	$this->render_section_description( __( 'Select which performance options you would like to enable.', 'whippet' ) );
}

public function whippet_misc_callback() {
	$this->render_section_description( __( 'Select which performance options you would like to enable.', 'whippet' ) );
}

public function whippet_woocommerce_callback() {
	$this->render_section_description( __( 'Disable specific elements of WooCommerce.', 'whippet' ) );
}

public function whippet_performance_callback() {
	$this->render_section_description( __( 'Core performance features: caching, compression, image quality and font preloading.', 'whippet' ) );
}

/**
 * Print form inputs
 * @param  [type] $args [description]
 * @return [type]       [description]
 */
public function whippet_print_input($args) {
	if(!empty($args['option'])) {
		$option = $args['option'];
		$options = get_option($args['option']);
	}
	else {
		$option = 'whippet_options';
		$options = get_option('whippet_options');
	}

	echo "<div class='whippet-input-wrapper'>";

			//Text
			if(!empty($args['input']) && ($args['input'] == 'text' || $args['input'] == 'color')) {
				$value = isset($options[$args['id']]) ? esc_attr($options[$args['id']]) : '';
				echo "<input type='text' id='" . esc_attr($args['id']) . "' name='" . esc_attr($option) . "[" . esc_attr($args['id']) . "]' value='" . $value . "' />";
			}

			//Select
			elseif(!empty($args['input']) && $args['input'] == 'select') {
				echo "<select id='" . esc_attr($args['id']) . "' name='" . esc_attr($option) . "[" . esc_attr($args['id']) . "]'>";
					foreach($args['options'] as $value => $title) {
						$selected = (!empty($options[$args['id']]) && $options[$args['id']] == $value) ? ' selected' : '';
						echo "<option value='" . esc_attr($value) . "'" . $selected . ">" . esc_html($title) . "</option>";
					}
				echo "</select>";
			}

			//Checkbox + Toggle
			else {
					$checked = (!empty($options[$args['id']]) && $options[$args['id']] == "1") ? ' checked' : '';
					echo "<input type='checkbox' id='" . esc_attr($args['id']) . "' name='" . esc_attr($option) . "[" . esc_attr($args['id']) . "]' value='1' style='display: block; margin: 0px;'" . $checked . ">";
			}

		echo "</div>";

		if(!empty($args['tooltip'])) {
			echo "<p class='description'>" . esc_html($args['tooltip']) . "</p>";
		}
}


//sanitize extras
public static function whippet_sanitize_extras($values) {
	if(!empty($values['dns_prefetch'])) {
		$text = trim($values['dns_prefetch']);
		$text_array = explode("\n", $text);
		$text_array = array_filter($text_array, 'trim');
		$values['dns_prefetch'] = $text_array;
	}

	// Sanitize preload_fonts textarea → array of URLs.
	if ( isset( $values['preload_fonts'] ) && is_string( $values['preload_fonts'] ) ) {
		$lines = explode( "\n", trim( $values['preload_fonts'] ) );
		$urls  = array();
		foreach ( $lines as $line ) {
			$url = esc_url_raw( trim( $line ) );
			if ( ! empty( $url ) ) {
				$urls[] = $url;
			}
		}
		$values['preload_fonts'] = $urls;
	}

	// Sanitize cache_exclude_urls: strip tags, trim lines, preserve newlines.
	if ( isset( $values['cache_exclude_urls'] ) ) {
		$lines = explode( "\n", $values['cache_exclude_urls'] );
		$clean = array_filter( array_map( function( $l ) { return sanitize_text_field( trim( $l ) ); }, $lines ) );
		$values['cache_exclude_urls'] = implode( "\n", $clean );
	}

	// Sanitize cache_exclude_cookies.
	if ( isset( $values['cache_exclude_cookies'] ) ) {
		$lines = explode( "\n", $values['cache_exclude_cookies'] );
		$clean = array_filter( array_map( function( $l ) { return sanitize_text_field( trim( $l ) ); }, $lines ) );
		$values['cache_exclude_cookies'] = implode( "\n", $clean );
	}

	return $values;
}

/**
 * Render the Clear Page Cache button.
 */
public function whippet_render_clear_cache_button() {
	$nonce = wp_create_nonce( 'whippet_cache_nonce' );
	echo '<button type="button" id="whippet-clear-cache-btn" class="button button-secondary" data-nonce="' . esc_attr( $nonce ) . '">'
		. esc_html__( 'Clear Cache Now', 'whippet' )
		. '</button>';
	echo '<span id="whippet-clear-cache-result" style="margin-left:10px;font-style:italic;"></span>';
	?>
	<script>
	(function(){
		var btn = document.getElementById('whippet-clear-cache-btn');
		if (!btn) return;
		btn.addEventListener('click', function(){
			var result = document.getElementById('whippet-clear-cache-result');
			result.textContent = '<?php echo esc_js( __( 'Clearing…', 'whippet' ) ); ?>';
			var fd = new FormData();
			fd.append('action', 'whippet_clear_cache');
			fd.append('nonce', btn.dataset.nonce);
			fetch(ajaxurl, {method:'POST', body:fd})
				.then(function(r){ return r.json(); })
				.then(function(data){
					result.textContent = data.success ? data.data.message : (data.data ? data.data.message : '<?php echo esc_js( __( 'Error.', 'whippet' ) ); ?>');
				})
				.catch(function(){ result.textContent = '<?php echo esc_js( __( 'Request failed.', 'whippet' ) ); ?>'; });
		});
	})();
	</script>
	<?php
}

/**
 * Render the Preload Fonts textarea.
 */
public function whippet_render_preload_fonts_textarea() {
	$options = get_option( 'whippet_options', array() );
	$fonts   = ! empty( $options['preload_fonts'] ) && is_array( $options['preload_fonts'] ) ? $options['preload_fonts'] : array();
	$value   = implode( "\n", array_map( 'esc_url', $fonts ) );
	echo '<textarea id="preload_fonts" name="whippet_options[preload_fonts]" rows="4" cols="60" placeholder="https://example.com/fonts/myfont.woff2" style="font-family:monospace;font-size:12px;">' . esc_textarea( $value ) . '</textarea>';
	echo '<p class="description">' . esc_html__( 'One font URL per line. Injects &lt;link rel="preload"&gt; tags in &lt;head&gt;.', 'whippet' ) . '</p>';
}

/**
 * Render the Database Cleanup section: preview + run buttons, last-run info.
 */
public function whippet_render_db_cleanup_button() {
	$nonce    = wp_create_nonce( 'whippet_db_cleanup_nonce' );
	$last_run = get_option( 'whippet_db_cleanup_last_run', array() );
	?>
	<?php if ( ! empty( $last_run['time'] ) ) : ?>
	<p style="font-size:12px;color:#6b7280;margin-bottom:8px;">
		<?php
		printf(
			/* translators: 1: human time diff, 2: rows deleted */
			esc_html__( 'Last run: %1$s ago — %2$d rows deleted.', 'whippet' ),
			esc_html( human_time_diff( $last_run['time'] ) ),
			(int) $last_run['total']
		);
		?>
	</p>
	<?php endif; ?>
	<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
		<button type="button" id="whippet-db-preview-btn" class="button button-secondary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<?php esc_html_e( 'Preview', 'whippet' ); ?>
		</button>
		<button type="button" id="whippet-db-cleanup-btn" class="button button-secondary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<?php esc_html_e( 'Run DB Cleanup', 'whippet' ); ?>
		</button>
	</div>
	<span id="whippet-db-cleanup-result" style="display:block;margin-top:6px;font-size:12px;font-style:italic;"></span>
	<div id="whippet-db-cleanup-details" style="margin-top:4px;font-size:12px;color:#6b7280;"></div>
	<script>
	(function(){
		var nonce      = '<?php echo esc_js( $nonce ); ?>';
		var labels = {
			revisions:        '<?php echo esc_js( __( 'Revisions', 'whippet' ) ); ?>',
			auto_drafts:      '<?php echo esc_js( __( 'Auto-drafts', 'whippet' ) ); ?>',
			trashed_posts:    '<?php echo esc_js( __( 'Trashed posts', 'whippet' ) ); ?>',
			spam_comments:    '<?php echo esc_js( __( 'Spam comments', 'whippet' ) ); ?>',
			trashed_comments: '<?php echo esc_js( __( 'Trashed comments', 'whippet' ) ); ?>',
			transients:       '<?php echo esc_js( __( 'Expired transients', 'whippet' ) ); ?>',
			orphaned_meta:    '<?php echo esc_js( __( 'Orphaned post meta', 'whippet' ) ); ?>',
		};

		function renderDetails(data) {
			var parts = [];
			var src = data.results || data.counts || {};
			for (var key in src) {
				parts.push((labels[key] || key) + ': ' + src[key]);
			}
			document.getElementById('whippet-db-cleanup-details').textContent = parts.join(' | ');
		}

		document.getElementById('whippet-db-preview-btn').addEventListener('click', function(){
			var result = document.getElementById('whippet-db-cleanup-result');
			document.getElementById('whippet-db-cleanup-details').textContent = '';
			result.textContent = '<?php echo esc_js( __( 'Counting rows…', 'whippet' ) ); ?>';
			var fd = new FormData();
			fd.append('action', 'whippet_preview_db_cleanup');
			fd.append('nonce', nonce);
			fetch(ajaxurl, {method:'POST', body:fd})
				.then(function(r){ return r.json(); })
				.then(function(d){
					if (d.success) { result.textContent = d.data.message; renderDetails(d.data); }
					else { result.textContent = '<?php echo esc_js( __( 'Error.', 'whippet' ) ); ?>'; }
				})
				.catch(function(){ result.textContent = '<?php echo esc_js( __( 'Request failed.', 'whippet' ) ); ?>'; });
		});

		document.getElementById('whippet-db-cleanup-btn').addEventListener('click', function(){
			if (!confirm('<?php echo esc_js( __( 'This will permanently delete database rows. Continue?', 'whippet' ) ); ?>')) return;
			var result = document.getElementById('whippet-db-cleanup-result');
			document.getElementById('whippet-db-cleanup-details').textContent = '';
			result.textContent = '<?php echo esc_js( __( 'Running…', 'whippet' ) ); ?>';
			var fd = new FormData();
			fd.append('action', 'whippet_run_db_cleanup');
			fd.append('nonce', nonce);
			fetch(ajaxurl, {method:'POST', body:fd})
				.then(function(r){ return r.json(); })
				.then(function(d){
					if (d.success) { result.textContent = d.data.message; renderDetails(d.data); }
					else { result.textContent = (d.data && d.data.message) ? d.data.message : '<?php echo esc_js( __( 'Error.', 'whippet' ) ); ?>'; }
				})
				.catch(function(){ result.textContent = '<?php echo esc_js( __( 'Request failed.', 'whippet' ) ); ?>'; });
		});
	})();
	</script>
	<?php
}

/**
 * Render the Gzip / Brotli field with a note about Brotli availability.
 */
public function whippet_render_gzip_field() {
	$options = get_option( 'whippet_options', array() );
	$checked = ( ! empty( $options['enable_gzip'] ) && '1' === $options['enable_gzip'] ) ? ' checked' : '';
	$brotli  = function_exists( 'brotli_compress' );
	echo "<div style='display:table;width:100%;'><div class='whippet-input-wrapper'>";
	echo "<input type='checkbox' id='enable_gzip' name='whippet_options[enable_gzip]' value='1' style='display:block;margin:0;'" . $checked . ">";
	echo "</div>";
	if ( $brotli ) {
		echo '<p style="font-size:12px;font-style:italic;color:#16a34a;">' . esc_html__( 'Brotli extension detected — cached pages will also be saved as .br files for maximum compression.', 'whippet' ) . '</p>';
	} else {
		echo '<p style="font-size:12px;font-style:italic;color:#6b7280;">' . esc_html__( 'PHP Gzip only. For Brotli support install the php-brotli PECL extension on your server.', 'whippet' ) . '</p>';
	}
	echo "</div>";
}

/**
 * Render the cache exclusion URLs textarea.
 */
public function whippet_render_cache_exclude_urls() {
	$options = get_option( 'whippet_options', array() );
	$value   = ! empty( $options['cache_exclude_urls'] ) ? $options['cache_exclude_urls'] : '';
	echo '<textarea id="cache_exclude_urls" name="whippet_options[cache_exclude_urls]" rows="4" cols="60" placeholder="/my-account/&#10;/checkout/&#10;/cart/" style="font-family:monospace;font-size:12px;">' . esc_textarea( $value ) . '</textarea>';
	echo '<p class="description">' . esc_html__( 'One URL path per line (partial match). Pages containing these strings will never be cached.', 'whippet' ) . '</p>';
}

/**
 * Render the cache exclusion cookies textarea.
 */
public function whippet_render_cache_exclude_cookies() {
	$options = get_option( 'whippet_options', array() );
	$value   = ! empty( $options['cache_exclude_cookies'] ) ? $options['cache_exclude_cookies'] : '';
	echo '<textarea id="cache_exclude_cookies" name="whippet_options[cache_exclude_cookies]" rows="3" cols="60" placeholder="my_plugin_cart&#10;logged_in_user" style="font-family:monospace;font-size:12px;">' . esc_textarea( $value ) . '</textarea>';
	echo '<p class="description">' . esc_html__( 'One cookie name per line. If any of these cookies are set, the cache will be bypassed for that visitor. WooCommerce cart/checkout cookies are always bypassed automatically.', 'whippet' ) . '</p>';
}

/**
 * Render cache stats, Clear Cache and Preload Cache buttons.
 */
public function whippet_render_cache_controls() {
	$nonce = wp_create_nonce( 'whippet_cache_nonce' );
	?>
	<div id="whippet-cache-stats" style="margin-bottom:8px;font-size:12px;color:#6b7280;font-style:italic;">
		<?php esc_html_e( 'Loading cache stats…', 'whippet' ); ?>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
		<button type="button" id="whippet-clear-cache-btn" class="button button-secondary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<?php esc_html_e( 'Clear Cache Now', 'whippet' ); ?>
		</button>
		<button type="button" id="whippet-preload-cache-btn" class="button button-secondary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<?php esc_html_e( 'Clear &amp; Preload Cache', 'whippet' ); ?>
		</button>
	</div>
	<span id="whippet-cache-action-result" style="margin-top:6px;display:block;font-size:12px;font-style:italic;"></span>
	<script>
	(function(){
		var nonce = '<?php echo esc_js( $nonce ); ?>';

		function fetchStats() {
			var fd = new FormData();
			fd.append('action', 'whippet_cache_stats');
			fd.append('nonce', nonce);
			fetch(ajaxurl, {method:'POST', body:fd})
				.then(function(r){ return r.json(); })
				.then(function(d){
					if (d.success) {
						document.getElementById('whippet-cache-stats').textContent =
							'<?php echo esc_js( __( 'Cached pages:', 'whippet' ) ); ?> ' + d.data.count +
							' — <?php echo esc_js( __( 'Total size:', 'whippet' ) ); ?> ' + d.data.size;
					}
				});
		}
		fetchStats();

		document.getElementById('whippet-clear-cache-btn').addEventListener('click', function(){
			var result = document.getElementById('whippet-cache-action-result');
			result.textContent = '<?php echo esc_js( __( 'Clearing…', 'whippet' ) ); ?>';
			var fd = new FormData();
			fd.append('action', 'whippet_clear_cache');
			fd.append('nonce', nonce);
			fetch(ajaxurl, {method:'POST', body:fd})
				.then(function(r){ return r.json(); })
				.then(function(d){
					result.textContent = d.success ? d.data.message : '<?php echo esc_js( __( 'Error.', 'whippet' ) ); ?>';
					fetchStats();
				})
				.catch(function(){ result.textContent = '<?php echo esc_js( __( 'Request failed.', 'whippet' ) ); ?>'; });
		});

		document.getElementById('whippet-preload-cache-btn').addEventListener('click', function(){
			var result = document.getElementById('whippet-cache-action-result');
			result.textContent = '<?php echo esc_js( __( 'Starting preload…', 'whippet' ) ); ?>';
			var fd = new FormData();
			fd.append('action', 'whippet_preload_cache');
			fd.append('nonce', nonce);
			fetch(ajaxurl, {method:'POST', body:fd})
				.then(function(r){ return r.json(); })
				.then(function(d){
					result.textContent = d.success ? d.data.message : '<?php echo esc_js( __( 'Error.', 'whippet' ) ); ?>';
					setTimeout(fetchStats, 5000);
				})
				.catch(function(){ result.textContent = '<?php echo esc_js( __( 'Request failed.', 'whippet' ) ); ?>'; });
		});
	})();
	</script>
	<?php
}

/**
 * Render the Bulk Image Compress button with preview and progress bar.
 */
public function whippet_render_bulk_compress_button() {
	$nonce = wp_create_nonce( 'whippet_image_optimizer_nonce' );
	?>
	<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
		<button type="button" id="whippet-bulk-preview-btn" class="button button-secondary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<?php esc_html_e( 'Preview', 'whippet' ); ?>
		</button>
		<button type="button" id="whippet-bulk-compress-btn" class="button button-secondary" data-nonce="<?php echo esc_attr( $nonce ); ?>" disabled>
			<?php esc_html_e( 'Run Bulk Compress', 'whippet' ); ?>
		</button>
	</div>
	<div id="whippet-bulk-progress-wrap" style="display:none;margin-top:8px;">
		<div style="background:#e5e7eb;border-radius:4px;height:8px;width:300px;max-width:100%;">
			<div id="whippet-bulk-progress-bar" style="background:#2563eb;height:8px;border-radius:4px;width:0%;transition:width 0.3s;"></div>
		</div>
	</div>
	<div id="whippet-bulk-result" style="margin-top:6px;font-size:12px;font-style:italic;"></div>
	<script>
	(function(){
		var nonce     = '<?php echo esc_js( $nonce ); ?>';
		var total     = 0;
		var previewBtn = document.getElementById('whippet-bulk-preview-btn');
		var runBtn     = document.getElementById('whippet-bulk-compress-btn');
		var result     = document.getElementById('whippet-bulk-result');
		var progress   = document.getElementById('whippet-bulk-progress-bar');
		var progressWrap = document.getElementById('whippet-bulk-progress-wrap');

		previewBtn.addEventListener('click', function(){
			result.textContent = '<?php echo esc_js( __( 'Counting images…', 'whippet' ) ); ?>';
			var fd = new FormData();
			fd.append('action', 'whippet_bulk_compress_preview');
			fd.append('nonce', nonce);
			fetch(ajaxurl, {method:'POST', body:fd})
				.then(function(r){ return r.json(); })
				.then(function(d){
					if (d.success) {
						total = d.data.total;
						result.textContent = d.data.message;
						runBtn.disabled = total === 0;
					} else {
						result.textContent = '<?php echo esc_js( __( 'Error getting count.', 'whippet' ) ); ?>';
					}
				});
		});

		runBtn.addEventListener('click', function(){
			if (!confirm('<?php echo esc_js( __( 'This will re-compress and regenerate thumbnails for all images. This cannot be undone. Continue?', 'whippet' ) ); ?>')) return;
			runBtn.disabled = true;
			previewBtn.disabled = true;
			progressWrap.style.display = 'block';
			processBatch(0);
		});

		function processBatch(batch) {
			var fd = new FormData();
			fd.append('action', 'whippet_bulk_compress');
			fd.append('nonce', nonce);
			fd.append('batch', batch);
			fetch(ajaxurl, {method:'POST', body:fd})
				.then(function(r){ return r.json(); })
				.then(function(d){
					if (d.success) {
						result.textContent = d.data.message;
						if (total > 0) {
							var pct = Math.min(100, Math.round((d.data.next_batch * 10 / total) * 100));
							progress.style.width = pct + '%';
						}
						if (!d.data.done) {
							processBatch(d.data.next_batch);
						} else {
							progress.style.width = '100%';
							result.textContent = '<?php echo esc_js( __( 'Done! All images processed.', 'whippet' ) ); ?>';
							previewBtn.disabled = false;
						}
					} else {
						result.textContent = '<?php echo esc_js( __( 'Error during processing.', 'whippet' ) ); ?>';
						previewBtn.disabled = false;
					}
				})
				.catch(function(){
					result.textContent = '<?php echo esc_js( __( 'Request failed.', 'whippet' ) ); ?>';
					previewBtn.disabled = false;
				});
		}
	})();
	</script>
	<?php
}

/**
 * Format a field title as a label.
 *
 * @param  string $title Field title.
 * @param  string $id    Field ID.
 * @return string
 */
public function whippet_title( $title, $id ) {
	return '<label for="' . esc_attr( $id ) . '">' . esc_html( $title ) . '</label>';
}

}

new Settings;
