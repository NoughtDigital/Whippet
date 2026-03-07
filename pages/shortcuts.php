<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Add links in plugins list
function whippet_pages_add_action_links($links) {
    $plugin_shortcuts = array(
        '<a href="'.admin_url('options-general.php?page=whippet-pages').'">Settings</a>',
    );
    return array_merge($links, $plugin_shortcuts);
}