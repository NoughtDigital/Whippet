<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Register settings menu
function whippet_pages_register_settings_menu() {
    add_options_page('Whippet Pages', 'Whippet Pages', 'manage_options', 'whippet-pages', 'whippet_pages_settings_view');
}
add_action('admin_menu', 'whippet_pages_register_settings_menu');

// Settings page
function whippet_pages_settings_view() {
    include('settings.php');
    include('compatibility.php');
    
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
?>

<h2>Whippet Pages settings</h2>
<h2 class="nav-tab-wrapper">
    <a href="?page=whippet-pages&tab=settings"
        class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
    <a href="?page=whippet-pages&tab=compatibility"
        class="nav-tab <?php echo $active_tab == 'compatibility' ? 'nav-tab-active' : ''; ?>">Compatibility</a>
</h2>

<?php
    switch ($active_tab) {
        case 'settings':
            whippet_pages_settings();
            break;
        case 'compatibility':
            whippet_pages_compatibility();
            break;
        default:
            whippet_pages_settings();
  
    }
}
?>