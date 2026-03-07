<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Set default config on plugin load if not set
function whippet_analytics_set_default_config() {

    if (WHIPPET_ANALYTICS_VERSION !== get_option('WHIPPET_ANALYTICS_VERSION')) {
        
        if (get_option('whippet_analytics_method') === false)
            update_option('whippet_analytics_method', "minimal-analytics");

        if (get_option('whippet_analytics_disable_on_login') === false)
            update_option('whippet_analytics_disable_on_login', true);
            
        update_option('WHIPPET_ANALYTICS_VERSION', WHIPPET_ANALYTICS_VERSION);
    }
}

add_action('plugins_loaded', 'whippet_analytics_set_default_config');
