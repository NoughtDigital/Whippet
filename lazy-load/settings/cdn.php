<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function whippet_images_settings_cdn() {

    if ( isset( $_POST['submit'] ) && isset( $_POST['wll_section'] ) && 'cdn' === $_POST['wll_section'] ) {
        if ( ! current_user_can( 'manage_options' )
            || ! isset( $_POST['whippet-images-settings-form'] )
            || ! wp_verify_nonce( $_POST['whippet-images-settings-form'], 'whippet-images' ) ) {
            wp_die( esc_html__( 'Unauthorized request.', 'whippet' ) );
        }

        update_option( 'whippet_images_enable_cdn', ! empty( $_POST['enable_cdn'] ) ? 1 : 0 );
        $raw_keywords = isset( $_POST['cdn_exclude_keywords'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cdn_exclude_keywords'] ) ) : '';
        $keywords = trim( $raw_keywords ) ? array_map( 'trim', explode( "\n", str_replace( "\r", "", $raw_keywords ) ) ) : array();
        update_option( 'whippet_images_cdn_exclude_keywords', $keywords );
    }

    $enable_cdn = get_option('whippet_images_enable_cdn');

    $cdn_exclude_keywords = get_option('whippet_images_cdn_exclude_keywords');
    $cdn_exclude_keywords = implode("\n", $cdn_exclude_keywords);
    $cdn_exclude_keywords = esc_textarea($cdn_exclude_keywords);

    ?>
    
    <form method="POST">
        <?php wp_nonce_field('whippet-images', 'whippet-images-settings-form'); ?>
        <input type="hidden" name="wll_section" value="cdn">
        <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label>Enable CDN</label></th>
                <td>
                    <input name="enable_cdn" type="checkbox" value="1" <?php if ($enable_cdn) {echo "checked";} ?>>
                    <p class="description">Use <a href="https://wsrv.nl" target="_blank">wsrv.nl</a> CDN to deliver images</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Exclude Keywords</label></th>
                <td>
                    <textarea name="cdn_exclude_keywords" rows="4" cols="50"><?php echo $cdn_exclude_keywords; ?></textarea>
                    <p class="description">The list of keywords that should be excluded from adding CDN. Add keywords in new lines</p>
                </td>
            </tr>
        </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
            <a href="https://wsrv.nl" target="_blank" class="button">wsrv.nl CDN Info</a>
        </p>
    </form>
<?php
}