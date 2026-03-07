<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function whippet_images_settings_responsiveness() {
    if ( isset( $_POST['submit'] ) && isset( $_POST['wll_section'] ) && 'responsiveness' === $_POST['wll_section'] ) {
        if ( ! current_user_can( 'manage_options' )
            || ! isset( $_POST['whippet-images-settings-form'] )
            || ! wp_verify_nonce( $_POST['whippet-images-settings-form'], 'whippet-images' ) ) {
            wp_die( esc_html__( 'Unauthorized request.', 'whippet' ) );
        }

        update_option( 'whippet_images_enable_responsive_images', ! empty( $_POST['enable_responsive_images'] ) ? 1 : 0 );
    }

    $enable_cdn = get_option('whippet_images_enable_cdn');
    $enable_responsive_images = get_option('whippet_images_enable_responsive_images');

    if(!$enable_cdn) echo '<br/><div class="notice notice-error is-dismissible"><p>CDN must be enabled for Responsiveness</p></div>';
    
    ?>
    <form method="POST">
        <?php wp_nonce_field('whippet-images', 'whippet-images-settings-form'); ?>
        <input type="hidden" name="wll_section" value="responsiveness">
        <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label>Enable responsive images</label></th>
                <td>
                    <input name="enable_responsive_images" type="checkbox" value="1" <?php if ($enable_responsive_images) {echo "checked";} ?>>
                    <p class="description">Use srcset to deliver responsive (adaptive) images</p>
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