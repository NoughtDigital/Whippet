<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'WHIPPET_MU_VERSION' ) ) {
	define( 'WHIPPET_MU_VERSION', '1.0.0' );
}

function whippet_scripts_install_mu_plugin() {
	if ( ! defined( 'WHIPPET_PATH' ) || ! defined( 'WPMU_PLUGIN_DIR' ) ) {
		return array( 'ok' => false, 'message' => __( 'MU plugin directory or Whippet path not available.', 'whippet' ) );
	}
	$source = WHIPPET_PATH . 'inc/whippet_mu.php';
	$dest   = trailingslashit( WPMU_PLUGIN_DIR ) . 'whippet_mu.php';
	if ( ! is_file( $source ) || ! is_readable( $source ) ) {
		return array( 'ok' => false, 'message' => __( 'MU plugin source file not found.', 'whippet' ) );
	}
	if ( ! is_dir( WPMU_PLUGIN_DIR ) || ! is_writable( WPMU_PLUGIN_DIR ) ) {
		return array( 'ok' => false, 'message' => __( 'mu-plugins directory is not writable. Copy whippet_mu.php from the plugin inc/ folder to wp-content/mu-plugins/ manually.', 'whippet' ) );
	}
	$copied = @copy( $source, $dest );
	if ( ! $copied ) {
		return array( 'ok' => false, 'message' => __( 'Could not copy MU plugin file. Copy whippet_mu.php from the plugin inc/ folder to wp-content/mu-plugins/ manually.', 'whippet' ) );
	}
	update_option( 'whippet_mu_version', WHIPPET_MU_VERSION );
	return array( 'ok' => true, 'message' => __( 'MU plugin installed.', 'whippet' ) );
}

function whippet_scripts_mu_plugin_status() {
	if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
		return array( 'exists' => false, 'version_ok' => false );
	}
	$file = trailingslashit( WPMU_PLUGIN_DIR ) . 'whippet_mu.php';
	$exists = is_file( $file ) && is_readable( $file );
	if ( ! $exists ) {
		return array( 'exists' => false, 'version_ok' => false );
	}
	$content = @file_get_contents( $file, false, null, 0, 2048 );
	$version_ok = (bool) preg_match( "/define\s*\(\s*['\"]WHIPPET_MU_VERSION['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $m ) && isset( $m[1] ) && $m[1] === WHIPPET_MU_VERSION;
	return array( 'exists' => true, 'version_ok' => $version_ok );
}

function whippet_scripts_format_list( $list ) {
    $list = sanitize_textarea_field( wp_unslash( $list ) );
    $list = trim( $list );
    return $list ? array_map( 'trim', explode( "\n", str_replace( "\r", "", $list ) ) ) : array();
}

function whippet_scripts_list_to_textarea( $value ) {
    if ( ! is_array( $value ) ) {
        return '';
    }

    $value = array_filter(
        array_map(
            static function( $item ) {
                return trim( (string) $item );
            },
            $value
        )
    );

    return esc_textarea( implode( "\n", $value ) );
}

function whippet_scripts_reset_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Unauthorised.', 'whippet' ) );
	}
	check_ajax_referer( 'whippet_scripts_reset', 'nonce' );

	global $wpdb;
	$tables = array( 'whippet_disabled', 'whippet_enabled', 'whippet_p_disabled', 'whippet_p_enabled' );
	foreach ( $tables as $table ) {
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}{$table}" );
	}
	delete_option( 'whippet_p_mu_plugins' );

	wp_send_json_success( array( 'message' => __( 'Script Manager has been reset.', 'whippet' ) ) );
}
add_action( 'wp_ajax_whippet_scripts_reset', 'whippet_scripts_reset_handler' );

function whippet_scripts_view_settings() {

    if ( isset( $_POST['submit'] ) && isset( $_POST['whippet-scripts-settings-form'] ) ) {
        if ( ! current_user_can( 'manage_options' )
            || ! wp_verify_nonce( $_POST['whippet-scripts-settings-form'], 'whippet-scripts' ) ) {
            wp_die( esc_html__( 'Unauthorised request.', 'whippet' ) );
        }

        update_option( 'whippet_scripts_display_archives', ! empty( $_POST['whippet_scripts_display_archives'] ) ? 1 : 0 );
        update_option( 'whippet_scripts_display_deps', ! empty( $_POST['whippet_scripts_display_deps'] ) ? 1 : 0 );
        update_option( 'whippet_scripts_testing_mode', ! empty( $_POST['whippet_scripts_testing_mode'] ) ? 1 : 0 );

        $mu_mode_new = ! empty( $_POST['whippet_scripts_mu_mode'] );
        update_option( 'whippet_scripts_mu_mode', $mu_mode_new ? 1 : 0 );
        if ( $mu_mode_new ) {
            $install = whippet_scripts_install_mu_plugin();
            set_transient( 'whippet_mu_install_result', $install, 30 );
        }

        update_option( 'whippet_scripts_hide_disclaimer', ! empty( $_POST['whippet_scripts_hide_disclaimer'] ) ? 1 : 0 );
    }

    $display_archives = (bool) get_option( 'whippet_scripts_display_archives', 0 );
    $display_deps     = (bool) get_option( 'whippet_scripts_display_deps', 1 );
    $testing_mode     = (bool) get_option( 'whippet_scripts_testing_mode', 0 );
    $mu_mode          = (bool) get_option( 'whippet_scripts_mu_mode', 0 );
    $hide_disclaimer  = (bool) get_option( 'whippet_scripts_hide_disclaimer', 0 );

    $testing_preview_url = add_query_arg( 'whippet_preview_scripts', '1', home_url( '/' ) );
    $mu_status = whippet_scripts_mu_plugin_status();
    $mu_install_result = get_transient( 'whippet_mu_install_result' );
    if ( false !== $mu_install_result ) {
        delete_transient( 'whippet_mu_install_result' );
    }

    ?>
<?php if ( false !== $mu_install_result && isset( $mu_install_result['ok'] ) && ! $mu_install_result['ok'] ) : ?>
    <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $mu_install_result['message'] ); ?></p></div>
<?php endif; ?>
<?php if ( isset( $_POST['submit'] ) && isset( $_POST['whippet-scripts-settings-form'] ) ) : ?>
    <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'whippet' ); ?></p></div>
<?php endif; ?>
<?php if ( $mu_mode && ( ! $mu_status['exists'] || ! $mu_status['version_ok'] ) ) : ?>
    <div class="notice notice-warning"><p><strong><?php esc_html_e( 'Whippet:', 'whippet' ); ?></strong>
    <?php
    if ( ! $mu_status['exists'] ) {
        esc_html_e( 'MU plugin file not found. Toggle MU mode off and save, then turn it back on to reinstall, or copy inc/whippet_mu.php to wp-content/mu-plugins/ manually.', 'whippet' );
    } else {
        esc_html_e( 'MU plugin version mismatch. Toggle MU mode off and save, then turn it back on to reinstall, or replace the file in mu-plugins manually.', 'whippet' );
    }
    ?>
    </p></div>
<?php endif; ?>
<style>
.wsm-toggle-wrap { display: inline-block; margin-bottom: 4px; }
.wsm-toggle { position: relative; display: inline-block; width: 44px; height: 24px; }
.wsm-toggle .wsm-toggle-input { opacity: 0; width: 0; height: 0; position: absolute; }
.wsm-toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #c3c4c7; border-radius: 24px; transition: 0.2s; }
.wsm-toggle-slider::before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: #fff; border-radius: 50%; transition: 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.2); }
.wsm-toggle-input:checked + .wsm-toggle-slider { background-color: #2271b1; }
.wsm-toggle-input:checked + .wsm-toggle-slider::before { transform: translateX(20px); }
.wsm-beta { display: inline-block; margin-left: 6px; padding: 2px 6px; font-size: 10px; font-weight: 600; text-transform: uppercase; color: #fff; background: #d63638; border-radius: 2px; vertical-align: middle; }
.wsm-warning.inline { border-left-color: #dba617; }
.wsm-success { margin: 0.5em 0 0; }
.wsm-reset-btn { background: #d63638 !important; border-color: #d63638 !important; color: #fff !important; }
.wsm-reset-btn:hover { background: #b32d2e !important; border-color: #b32d2e !important; }
.wsm-reset-msg { margin-left: 10px; font-size: 13px; }
</style>
<form method="POST">
    <?php wp_nonce_field('whippet-scripts', 'whippet-scripts-settings-form'); ?>
    <table class="form-table" role="presentation">
    <tbody>
        <tr>
            <th scope="row"><?php esc_html_e( 'Display Archives', 'whippet' ); ?></th>
            <td>
                <div class="wsm-toggle-wrap">
                    <label class="wsm-toggle">
                        <input type="checkbox" name="whippet_scripts_display_archives" id="whippet_scripts_display_archives" value="1" <?php checked( $display_archives ); ?> class="wsm-toggle-input" />
                        <span class="wsm-toggle-slider"></span>
                    </label>
                </div>
                <p class="description"><?php esc_html_e( 'Add WordPress archives to your Script Manager selection options. Archive posts will no longer be grouped by their post type.', 'whippet' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Display Dependencies', 'whippet' ); ?></th>
            <td>
                <div class="wsm-toggle-wrap">
                    <label class="wsm-toggle">
                        <input type="checkbox" name="whippet_scripts_display_deps" id="whippet_scripts_display_deps" value="1" <?php checked( $display_deps ); ?> class="wsm-toggle-input" />
                        <span class="wsm-toggle-slider"></span>
                    </label>
                </div>
                <p class="description"><?php esc_html_e( 'Show dependencies for each script.', 'whippet' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Testing Mode', 'whippet' ); ?></th>
            <td>
                <div class="wsm-toggle-wrap">
                    <label class="wsm-toggle">
                        <input type="checkbox" name="whippet_scripts_testing_mode" id="whippet_scripts_testing_mode" value="1" <?php checked( $testing_mode ); ?> class="wsm-toggle-input" />
                        <span class="wsm-toggle-slider"></span>
                    </label>
                </div>
                <p class="description"><?php esc_html_e( 'Restrict your Script Manager configuration to logged-in admins only.', 'whippet' ); ?>
                    <a href="<?php echo esc_url( admin_url( 'tools.php?page=whippet-tutorials' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View Documentation', 'whippet' ); ?></a>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <?php esc_html_e( 'MU Mode', 'whippet' ); ?>
                <span class="wsm-beta"><?php esc_html_e( 'BETA', 'whippet' ); ?></span>
            </th>
            <td>
                <div class="wsm-toggle-wrap">
                    <label class="wsm-toggle">
                        <input type="checkbox" name="whippet_scripts_mu_mode" id="whippet_scripts_mu_mode" value="1" <?php checked( $mu_mode ); ?> class="wsm-toggle-input" />
                        <span class="wsm-toggle-slider"></span>
                    </label>
                </div>
                <p class="description"><?php esc_html_e( 'Must-use (MU) mode requires elevated permissions and a file to be copied into the mu-plugins directory. This gives you more control and the ability to disable plugin queries, inline CSS, etc.', 'whippet' ); ?>
                    <a href="<?php echo esc_url( admin_url( 'tools.php?page=whippet-tutorials' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View Documentation', 'whippet' ); ?></a>
                </p>
                <div class="notice notice-warning inline wsm-warning" style="margin: 0.75em 0;">
                    <p><strong><?php esc_html_e( 'Warning:', 'whippet' ); ?></strong> <?php esc_html_e( 'Any previous plugin-level disables will now disable the entire plugin. Please review your existing Script Manager configuration before enabling this option.', 'whippet' ); ?></p>
                </div>
                <?php if ( false !== $mu_install_result && isset( $mu_install_result['ok'] ) && $mu_install_result['ok'] ) : ?>
                    <p class="wsm-success" style="color: #00a32a; font-weight: 500;"><?php echo esc_html( $mu_install_result['message'] ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Hide Disclaimer', 'whippet' ); ?></th>
            <td>
                <div class="wsm-toggle-wrap">
                    <label class="wsm-toggle">
                        <input type="checkbox" name="whippet_scripts_hide_disclaimer" id="whippet_scripts_hide_disclaimer" value="1" <?php checked( $hide_disclaimer ); ?> class="wsm-toggle-input" />
                        <span class="wsm-toggle-slider"></span>
                    </label>
                </div>
                <p class="description"><?php esc_html_e( 'Hide the disclaimer message box across all Script Manager views.', 'whippet' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Reset Script Manager', 'whippet' ); ?></th>
            <td>
                <button type="button" id="whippet-scripts-reset-btn" class="button wsm-reset-btn"><?php esc_html_e( 'Reset Script Manager', 'whippet' ); ?></button>
                <span id="whippet-scripts-reset-msg" class="wsm-reset-msg"></span>
                <p class="description"><?php esc_html_e( 'Remove and reset all of your existing Script Manager settings.', 'whippet' ); ?></p>
            </td>
        </tr>
    </tbody>
    </table>
    <p class="submit">
        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'whippet' ); ?>">
    </p>
</form>
<script>
(function(){
    var btn = document.getElementById('whippet-scripts-reset-btn');
    var msg = document.getElementById('whippet-scripts-reset-msg');
    if (!btn) return;
    btn.addEventListener('click', function(){
        if (!confirm('<?php echo esc_js( __( 'Are you sure? This will remove all Script Manager disabled/enabled rules. This cannot be undone.', 'whippet' ) ); ?>')) return;
        btn.disabled = true;
        msg.textContent = '<?php echo esc_js( __( 'Resetting...', 'whippet' ) ); ?>';
        msg.style.color = '#64748b';
        fetch(window.ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'whippet_scripts_reset',
                nonce: '<?php echo esc_js( wp_create_nonce( 'whippet_scripts_reset' ) ); ?>'
            })
        })
        .then(function(r){ return r.json(); })
        .then(function(res){
            btn.disabled = false;
            if (res.success) {
                msg.style.color = '#00a32a';
                msg.textContent = res.data.message;
            } else {
                msg.style.color = '#d63638';
                msg.textContent = res.data || '<?php echo esc_js( __( 'Reset failed.', 'whippet' ) ); ?>';
            }
        })
        .catch(function(){
            btn.disabled = false;
            msg.style.color = '#d63638';
            msg.textContent = '<?php echo esc_js( __( 'Request failed.', 'whippet' ) ); ?>';
        });
    });
})();
</script>
<?php
}
