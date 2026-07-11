<?php
/**
 * Settings snapshots and restoration.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsSnapshots {
	const OPTION = 'whippet_settings_snapshots';

	public static function boot() {
		add_action( 'update_option_whippet_options', array( __CLASS__, 'snapshot_before_update' ), 5, 2 );
		add_action( 'whippet_settings_imported', array( __CLASS__, 'capture' ), 10, 1 );
		add_action( 'wp_ajax_whippet_snapshot_list', array( __CLASS__, 'ajax_list' ) );
		add_action( 'wp_ajax_whippet_snapshot_restore', array( __CLASS__, 'ajax_restore' ) );
	}

	private static function key_options() {
		return array(
			'whippet_analytics_id',
			'whippet_analytics_method',
			'whippet_fonts_enabled',
			'whippet_images_enable_lazyloading',
			'whippet_pages_config_delay',
			'whippet_scripts_timeout',
			'whippet_scripts_include_list',
			'whippet_scripts_disabled_pages',
			'whippet_scripts_regex_rules',
		);
	}

	public static function snapshot_before_update( $old_value, $new_value ) {
		if ( $old_value === $new_value || get_transient( 'whippet_restoring_snapshot' ) ) {
			return;
		}
		self::capture( 'before_settings_update', $old_value );
	}

	public static function capture( $reason = 'manual', $core = null ) {
		$data = array( 'whippet_options' => null === $core ? get_option( 'whippet_options', array() ) : $core );
		foreach ( self::key_options() as $key ) {
			$data[ $key ] = get_option( $key, null );
		}
		$snapshots = (array) get_option( self::OPTION, array() );
		array_unshift(
			$snapshots,
			array(
				'id'      => function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'snap_', true ),
				'time'    => gmdate( 'c' ),
				'reason'  => sanitize_key( $reason ),
				'user_id' => get_current_user_id(),
				'data'    => $data,
			)
		);
		$limit = max( 1, min( 20, (int) apply_filters( 'whippet_snapshot_limit', 10 ) ) );
		update_option( self::OPTION, array_slice( $snapshots, 0, $limit ), false );
	}

	private static function authorise() {
		check_ajax_referer( 'whippet_snapshots', 'nonce' );
		if ( ! Helper::user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'whippet' ) ), 403 );
		}
	}

	public static function ajax_list() {
		self::authorise();
		wp_send_json_success( (array) get_option( self::OPTION, array() ) );
	}

	public static function ajax_restore() {
		self::authorise();
		$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		foreach ( (array) get_option( self::OPTION, array() ) as $snapshot ) {
			if ( empty( $snapshot['id'] ) || ! hash_equals( (string) $snapshot['id'], $id ) || empty( $snapshot['data'] ) ) {
				continue;
			}
			self::capture( 'before_restore' );
			set_transient( 'whippet_restoring_snapshot', 1, MINUTE_IN_SECONDS );
			foreach ( $snapshot['data'] as $key => $value ) {
				if ( 'whippet_options' === $key || in_array( $key, self::key_options(), true ) ) {
					update_option( $key, $value, false );
				}
			}
			delete_transient( 'whippet_restoring_snapshot' );
			Helper::audit_log( 'settings_snapshot_restored', array( 'snapshot_id' => $id ) );
			wp_send_json_success( array( 'message' => __( 'Settings restored.', 'whippet' ) ) );
		}
		wp_send_json_error( array( 'message' => __( 'Snapshot not found.', 'whippet' ) ), 404 );
	}

	public static function render_ui() {
		$snapshots = (array) get_option( self::OPTION, array() );
		?>
		<div class="wa-card">
			<div class="wa-card-section-title"><?php esc_html_e( 'Settings snapshots', 'whippet' ); ?></div>
			<?php if ( empty( $snapshots ) ) : ?>
				<p><?php esc_html_e( 'Snapshots are created automatically before settings changes and after imports.', 'whippet' ); ?></p>
			<?php else : ?>
				<ul>
				<?php foreach ( $snapshots as $snapshot ) : ?>
					<li><strong><?php echo esc_html( wp_date( 'j F Y, H:i', strtotime( $snapshot['time'] ) ) ); ?></strong> — <?php echo esc_html( str_replace( '_', ' ', $snapshot['reason'] ) ); ?>
						<button type="button" class="button button-small" onclick="if(confirm('<?php echo esc_js( __( 'Restore this snapshot?', 'whippet' ) ); ?>'))fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'whippet_snapshot_restore',nonce:'<?php echo esc_js( wp_create_nonce( 'whippet_snapshots' ) ); ?>',id:'<?php echo esc_js( $snapshot['id'] ); ?>'})}).then(()=>location.reload())"><?php esc_html_e( 'Restore', 'whippet' ); ?></button>
					</li>
				<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}
}
