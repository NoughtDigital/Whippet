<?php
function whippet_analytics_settings() {

    if ( isset( $_POST['submit'] ) && isset( $_POST['whippet_analytics_settings_form'] ) ) {
        if ( ! current_user_can( 'manage_options' )
            || ! wp_verify_nonce( $_POST['whippet_analytics_settings_form'], 'whippet_analytics' ) ) {
            wp_die( esc_html__( 'Unauthorised request.', 'whippet' ) );
        }

        update_option( 'whippet_analytics_id', sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ) );
        update_option( 'whippet_analytics_method', sanitize_text_field( wp_unslash( $_POST['method'] ?? '' ) ) );
        update_option( 'whippet_analytics_disable_on_login', ! empty( $_POST['disable_on_login'] ) ? 1 : 0 );

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings have been saved! Please clear cache if you\'re using a cache plugin.', 'whippet' ) . '</p></div>';
    }

    $id = esc_attr(get_option('whippet_analytics_id'));
    $method = esc_attr(get_option('whippet_analytics_method'));
    $disable_on_login = get_option('whippet_analytics_disable_on_login');
?>
<form method="POST">
    <?php wp_nonce_field( 'whippet_analytics', 'whippet_analytics_settings_form' ); ?>
    <table class="form-table" role="presentation">
    <tbody>
        <tr>
            <th scope="row"><label>Google Analytics Tracking ID</label></th>
            <td>
                <input name="id" type="text" value="<?php echo $id; ?>" placeholder="G-XXXXXXXXXX"/>
                <p class="description"><a href="https://support.google.com/analytics/answer/9539598?hl=en" target="_blank">Where to find tracking ID?</a></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label>Tracking script</label></th>
            <td>
                <label>
                    <input type="radio" name="method" value="gtag.js" <?php checked( $method, 'gtag.js' ); ?>>
                    Gtag.js (90KB)
                </label><br>
                <label>
                    <input type="radio" name="method" value="minimal-analytics" <?php checked( $method, 'minimal-analytics' ); ?>>
                    Minimal Analytics.js (1.4KB)
                </label>
                <p class="description">Minimal Analytics is lightweight; Gtag.js supports advanced features like AdWords.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label>Disable for logged in admins</label></th>
            <td>
                <input name="disable_on_login" type="checkbox" value="1" <?php if($disable_on_login) echo "checked"; ?>>
            </td>
        </tr>
    </tbody>
    </table>

    <p class="submit">
        <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
    </p>
</form>
<?php
}