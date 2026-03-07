<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function whippet_images_settings_webp() {
    $enable_cdn = get_option('whippet_images_enable_cdn');

    if(!$enable_cdn) echo '<br/><div class="notice notice-error is-dismissible"><p>CDN must be enabled for WebP images</p></div>';
?>

    <p>Images are converted to WebP on the fly if the browser supports it. You don't have to do anything 😉<p>
<?php
}