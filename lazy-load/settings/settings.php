<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<h2>Whippet Images settings</h2>

<?php
    include('lazyload.php');
    include('cdn.php');
    include('compression.php');
    include('responsiveness.php');
    include('webp.php');

    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'lazyload';

    if (isset($_POST['submit'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Settings have been saved! Please clear cache if you\'re using a cache plugin</p></div>';
    }
?>

<h2 class="nav-tab-wrapper">
    <a href="?page=whippet-images&tab=lazyload"
        class="nav-tab <?php echo $active_tab == 'lazyload' ? 'nav-tab-active' : ''; ?>">Lazy load</a>
    <a href="?page=whippet-images&tab=cdn"
        class="nav-tab <?php echo $active_tab == 'cdn' ? 'nav-tab-active' : ''; ?>">CDN</a>
    <a href="?page=whippet-images&tab=responsiveness"
        class="nav-tab <?php echo $active_tab == 'responsiveness' ? 'nav-tab-active' : ''; ?>">Responsiveness</a>
    <a href="?page=whippet-images&tab=compression"
        class="nav-tab <?php echo $active_tab == 'compression' ? 'nav-tab-active' : ''; ?>">Compression</a>
    <a href="?page=whippet-images&tab=webp"
        class="nav-tab <?php echo $active_tab == 'webp' ? 'nav-tab-active' : ''; ?>">WebP</a>
</h2>

<?php
    switch ($active_tab) {
        case 'lazyload':
            whippet_images_settings_lazy_load();
            break;
        case 'cdn':
            whippet_images_settings_cdn();
            break;
        case 'compression':
            whippet_images_settings_compression();
            break;
        case 'responsiveness':
            whippet_images_settings_responsiveness();
            break;
        case 'webp':
            whippet_images_settings_webp();
            break;
        default:
            whippet_images_settings_lazy_load();
    }
?>