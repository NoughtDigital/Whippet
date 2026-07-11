<?php
/**
 * Multisite network controls.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Multisite {
	public static function boot() {
		if ( ! is_multisite() ) {
			return;
		}
		add_action( 'network_admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'network_admin_edit_whippet_push_preset', array( __CLASS__, 'push_preset' ) );
	}

	public static function menu() {
		add_submenu_page( 'settings.php', __( 'Whippet', 'whippet' ), __( 'Whippet', 'whippet' ), 'manage_network_options', 'whippet-network', array( __CLASS__, 'render' ) );
	}

	public static function push_preset() {
		check_admin_referer( 'whippet_network_preset' );
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'whippet' ) );
		}
		$keys = array( 'disable_emojis', 'remove_query_strings', 'disable_embeds', 'remove_rsd_link', 'remove_shortlink', 'remove_wlwmanifest_link', 'remove_feed_links', 'hide_wp_version', 'remove_jquery_migrate' );
		foreach ( get_sites( array( 'number' => 0, 'fields' => 'ids' ) ) as $site_id ) {
			switch_to_blog( (int) $site_id );
			$options = (array) get_option( 'whippet_options', array() );
			foreach ( $keys as $key ) {
				$options[ $key ] = '1';
			}
			update_option( 'whippet_options', $options, false );
			restore_current_blog();
		}
		wp_safe_redirect( add_query_arg( 'updated', '1', network_admin_url( 'settings.php?page=whippet-network' ) ) );
		exit;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Whippet network settings', 'whippet' ); ?></h1>
			<p><?php echo esc_html( sprintf( __( '%d sites are available on this network.', 'whippet' ), (int) get_blog_count() ) ); ?></p>
			<form action="<?php echo esc_url( network_admin_url( 'edit.php?action=whippet_push_preset' ) ); ?>" method="post">
				<?php wp_nonce_field( 'whippet_network_preset' ); ?>
				<?php submit_button( __( 'Push safe preset to all sites', 'whippet' ) ); ?>
			</form>
			<ul>
				<?php foreach ( get_sites( array( 'number' => 100 ) ) as $site ) : ?>
					<li><?php echo esc_html( get_blog_details( $site->blog_id )->blogname ); ?> — <code><?php echo esc_html( $site->domain . $site->path ); ?></code></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
