<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_head', 'whippet_images_add_resource_hints' );
function whippet_images_add_resource_hints() {
	if ( get_option( 'whippet_images_enable_cdn' ) ) {
		?>
<link rel="dns-prefetch" href="https://wsrv.nl/">
<link rel="preconnect" href="https://wsrv.nl/" crossorigin>
		<?php
	}
}