<?php

// Add credit links in plugins list
function flying_images_add_shortcuts($links) {
    $plugin_shortcuts[] = '<a href="'.admin_url('options-general.php?page=flying-images').'">Settings</a>';

    if (!defined('FLYING_PRESS_VERSION')) {
        $plugin_shortcuts[] =
      '<a href="https://flyingpress.com?ref=flying-images" target="_blank" style="color:#3db634;">Get FlyingPress</a>';
    }

    return array_merge($links, $plugin_shortcuts);
}
