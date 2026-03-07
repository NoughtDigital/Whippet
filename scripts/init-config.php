<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Set default config on plugin load if not set
function whippet_scripts_set_default_config() {

    if (WHIPPET_SCRIPTS_VERSION !== get_option('WHIPPET_SCRIPTS_VERSION')) {
        
        if (get_option('whippet_scripts_timeout') === false)
            update_option('whippet_scripts_timeout', 5);

        if (get_option('whippet_scripts_include_list') === false)
            update_option('whippet_scripts_include_list', []);

        if (get_option('whippet_scripts_disabled_pages') === false)
            update_option('whippet_scripts_disabled_pages', []);

        update_option('WHIPPET_SCRIPTS_VERSION', WHIPPET_SCRIPTS_VERSION);
    }
}

add_action('plugins_loaded', 'whippet_scripts_set_default_config');
