<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Set default config on plugin load if not set
function whippet_images_set_default_config() {

    if (WHIPPET_IMAGES_VERSION !== get_option('WHIPPET_IMAGES_VERSION')) {
        
        // Lazy loading
        if (get_option('whippet_images_enable_lazyloading') === false)
            update_option('whippet_images_enable_lazyloading', true);

        if (get_option('whippet_images_lazymethod') === false) 
            update_option('whippet_images_lazymethod', "javascript");

        if (get_option('whippet_images_margin') === false)
            update_option('whippet_images_margin', 500);

        if (get_option('whippet_images_exclude_keywords') === false)
            update_option('whippet_images_exclude_keywords', ['your-logo.png']);

        // CDN
        if (get_option('whippet_images_enable_cdn') === false)
            update_option('whippet_images_enable_cdn', true);
        
        if (get_option('whippet_images_cdn_exclude_keywords') === false)
            update_option('whippet_images_cdn_exclude_keywords', []);

        // Compression
        if (get_option('whippet_images_enable_compression') === false)
            update_option('whippet_images_enable_compression', true);
        
        if (get_option('whippet_images_quality') === false)
            update_option('whippet_images_quality', 100);

        // Responsiveness
        if (get_option('whippet_images_enable_responsive_images') === false)
            update_option('whippet_images_enable_responsive_images', true);
            
        update_option('WHIPPET_IMAGES_VERSION', WHIPPET_IMAGES_VERSION);
    }
}

add_action('plugins_loaded', 'whippet_images_set_default_config');
