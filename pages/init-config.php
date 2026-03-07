<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Set default config on plugin load if not set
function whippet_pages_set_default_config() {
    if (WHIPPET_PAGES_VERSION !== get_option('WHIPPET_PAGES_VERSION')) {
        if (get_option('whippet_pages_config_delay') === false)
            update_option('whippet_pages_config_delay', 0);
        if (get_option('whippet_pages_config_ignore_keywords') === false)
            update_option('whippet_pages_config_ignore_keywords', ['/wp-admin','/wp-login.php','/cart','/checkout','add-to-cart','logout','#','?','.png','.jpeg','.jpg','.gif','.svg','.webp']);
        if (get_option('whippet_pages_config_max_rps') === false)
            update_option('whippet_pages_config_max_rps', 3);
        if (get_option('whippet_pages_config_hover_delay') === false)
            update_option('whippet_pages_config_hover_delay', 50);
        if (get_option('whippet_pages_config_disable_on_login') === false)
            update_option('whippet_pages_config_disable_on_login', true);
        update_option('WHIPPET_PAGES_VERSION', WHIPPET_PAGES_VERSION);
    }
}
add_action('plugins_loaded', 'whippet_pages_set_default_config');