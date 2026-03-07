<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Add credit links in plugins list
function whippet_images_add_shortcuts($links) {
    $plugin_shortcuts[] = '<a href="'.admin_url('options-general.php?page=whippet-images').'">Settings</a>';
    return array_merge($links, $plugin_shortcuts);
}
