<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Register settings menu
function whippet_scripts_register_settings_menu() {
    add_options_page('Whippet Scripts', 'Whippet Scripts', 'manage_options', 'whippet-scripts', 'whippet_scripts_view_view');
}
add_action('admin_menu', 'whippet_scripts_register_settings_menu');

// Settings page
function whippet_scripts_view_view() {
    // Validate nonce
    if (isset($_POST['submit']) && !wp_verify_nonce($_POST['whippet-scripts-settings-form'], 'whippet-scripts')) {
        echo '<div class="notice notice-error"><p>Nonce verification failed</p></div>';
        exit;
    }

    // Settings
    include 'view.php';
}
