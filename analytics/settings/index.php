<?php
// Standalone settings menu (not used by Whippet; Whippet shows analytics in its own tab).
function flying_analytics_register_settings_menu() {
	add_options_page( __( 'Analytics', 'whippet' ), __( 'Analytics', 'whippet' ), 'manage_options', 'whippet-analytics', 'flying_analytics_settings_view' );
}
add_action( 'admin_menu', 'flying_analytics_register_settings_menu' );

function flying_analytics_settings_view() {
	include 'settings.php';
	include 'faq.php';
	include 'optimize-more.php';

	$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'settings';
?>

<h2><?php esc_html_e( 'Analytics', 'whippet' ); ?></h2>
<h2 class="nav-tab-wrapper">
	<a href="?page=whippet-analytics&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Settings', 'whippet' ); ?></a>
	<a href="?page=whippet-analytics&tab=faq" class="nav-tab <?php echo $active_tab === 'faq' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'FAQs', 'whippet' ); ?></a>
	<a href="?page=whippet-analytics&tab=optimize-more" class="nav-tab <?php echo $active_tab === 'optimize-more' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Optimize more', 'whippet' ); ?></a>
</h2>

<?php
    switch ($active_tab) {
        case 'settings':
            flying_analytics_settings();
            break;
        case 'faq':
            flying_analytics_faq();
            break;
        case 'optimize-more':
            flying_analytics_optimize_more();
            break;
        default:
            flying_analytics_settings();
    }
}
?>