<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Add credit links in plugins list
function whippet_scripts_add_shortcuts($links) {
    $plugin_shortcuts = array(
        '<a href="'.admin_url('options-general.php?page=whippet-scripts').'">Settings</a>'
    );
    return array_merge($links, $plugin_shortcuts);
}
