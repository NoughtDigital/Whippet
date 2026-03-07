<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Register settings menu

function whippet_images_register_settings_menu() {
    add_options_page('Whippet Images', 'Whippet Images', 'manage_options', 'whippet-images', 'whippet_images_settings_view');
}
add_action('admin_menu', 'whippet_images_register_settings_menu');

// Settings page
function whippet_images_settings_view() {
    // Validate nonce
    if (isset($_POST['submit']) && !wp_verify_nonce($_POST['whippet-images-settings-form'], 'whippet-images')) {
        echo '<div class="notice notice-error"><p>Nonce verification failed</p></div>';
        exit;
    }

    // Settings
    include 'settings.php';
}
