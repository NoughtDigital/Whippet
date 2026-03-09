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

        if (get_option('whippet_scripts_regex_rules') === false)
            update_option('whippet_scripts_regex_rules', []);

        if (get_option('whippet_scripts_disabled_pages_regex') === false)
            update_option('whippet_scripts_disabled_pages_regex', []);

        if (get_option('whippet_scripts_regex_operator') === false)
            update_option('whippet_scripts_regex_operator', 'any');

        if (get_option('whippet_scripts_mu_mode') === false)
            update_option('whippet_scripts_mu_mode', 0);

        if (get_option('whippet_scripts_testing_mode') === false)
            update_option('whippet_scripts_testing_mode', 0);

        if (get_option('whippet_scripts_display_archives') === false)
            update_option('whippet_scripts_display_archives', 0);

        if (get_option('whippet_scripts_display_deps') === false)
            update_option('whippet_scripts_display_deps', 1);

        if (get_option('whippet_scripts_hide_disclaimer') === false)
            update_option('whippet_scripts_hide_disclaimer', 0);

        update_option('WHIPPET_SCRIPTS_VERSION', WHIPPET_SCRIPTS_VERSION);
    }
}

add_action('plugins_loaded', 'whippet_scripts_set_default_config');
