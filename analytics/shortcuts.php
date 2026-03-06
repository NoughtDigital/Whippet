<?php
// Plugin action links (used only if analytics run as standalone; Whippet uses load.php).
function flying_analytics_add_shortcuts( $links ) {
	$plugin_shortcuts = array(
		'<a href="' . admin_url( 'tools.php?page=whippet' ) . '">' . __( 'Settings', 'whippet' ) . '</a>',
	);
	return array_merge( $links, $plugin_shortcuts );
}
