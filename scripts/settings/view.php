<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<h2>Whippet Scripts settings</h2>

<?php
    include('settings.php');

    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';

    if (isset($_POST['submit'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Settings have been saved! Please clear cache if you\'re using a cache plugin</p></div>';
    }
?>

<h2 class="nav-tab-wrapper">
    <a href="?page=whippet-scripts&tab=settings"
        class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
</h2>

<?php
    switch ($active_tab) {
        case 'settings':
            whippet_scripts_view_settings();
            break;
        default:
            whippet_scripts_view_settings();
    }
?>