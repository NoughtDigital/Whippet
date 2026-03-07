<?php
// Standalone settings menu (not used by Whippet; Whippet shows analytics in its own tab).
function whippet_analytics_register_settings_menu() {
	add_options_page( __( 'Analytics', 'whippet' ), __( 'Analytics', 'whippet' ), 'manage_options', 'whippet-analytics', 'whippet_analytics_settings_view' );
}
add_action( 'admin_menu', 'whippet_analytics_register_settings_menu' );

function whippet_analytics_settings_view() {
	include 'settings.php';

	$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'settings';
?>

<h2><?php esc_html_e( 'Analytics', 'whippet' ); ?></h2>
<h2 class="nav-tab-wrapper">
	<a href="?page=whippet-analytics&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Settings', 'whippet' ); ?></a>
</h2>

<?php
    switch ($active_tab) {
        case 'settings':
            whippet_analytics_settings();
            break;
        default:
            whippet_analytics_settings();
    }
}
?>