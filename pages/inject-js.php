<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Embed the scripts we need for this plugin
function whippet_pages_enqueue_scripts() {

    if ( get_option( 'whippet_pages_config_disable_on_login' ) && current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
        return;
    }

    $delay       = absint( get_option( 'whippet_pages_config_delay', 0 ) );
    $keywords    = get_option( 'whippet_pages_config_ignore_keywords', array() );
    $max_rps     = absint( get_option( 'whippet_pages_config_max_rps', 3 ) );
    $hover_delay = absint( get_option( 'whippet_pages_config_hover_delay', 50 ) );

    wp_enqueue_script( 'whippet-pages', plugin_dir_url( __FILE__ ) . 'whippet-pages.min.js', array(), WHIPPET_PAGES_VERSION, true );
    wp_add_inline_script(
        'whippet-pages',
        'window.FPConfig={delay:' . $delay
            . ',ignoreKeywords:' . wp_json_encode( is_array( $keywords ) ? $keywords : array() )
            . ',maxRPS:' . $max_rps
            . ',hoverDelay:' . $hover_delay . '};',
        'before'
    );
}
add_action('wp_enqueue_scripts', 'whippet_pages_enqueue_scripts');

// Add defer attribute to Whippet Pages script tag
function whippet_pages_add_defer($tag, $handle) {
    if ('whippet-pages' === $handle && false === strpos($tag, 'defer')) {
        $tag = preg_replace(':(?=></script>):', ' defer', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'whippet_pages_add_defer', 10, 2);
