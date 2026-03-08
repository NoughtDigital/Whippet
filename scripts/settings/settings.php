<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

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

function whippet_scripts_view_settings() {

    if ( isset( $_POST['submit'] ) ) {
        if ( ! current_user_can( 'manage_options' )
            || ! isset( $_POST['whippet-scripts-settings-form'] )
            || ! wp_verify_nonce( $_POST['whippet-scripts-settings-form'], 'whippet-scripts' ) ) {
            wp_die( esc_html__( 'Unauthorized request.', 'whippet' ) );
        }

        update_option( 'whippet_scripts_timeout', absint( $_POST['whippet_scripts_timeout'] ?? 5 ) );
        update_option( 'whippet_scripts_include_list', whippet_scripts_format_list( $_POST['whippet_scripts_include_list'] ?? '' ) );
        update_option( 'whippet_scripts_disabled_pages', whippet_scripts_format_list( $_POST['whippet_scripts_disabled_pages'] ?? '' ) );
        update_option( 'whippet_scripts_regex_rules', whippet_scripts_format_list( $_POST['whippet_scripts_regex_rules'] ?? '' ) );
        update_option( 'whippet_scripts_disabled_pages_regex', whippet_scripts_format_list( $_POST['whippet_scripts_disabled_pages_regex'] ?? '' ) );

        $regex_operator = sanitize_text_field( wp_unslash( $_POST['whippet_scripts_regex_operator'] ?? 'any' ) );
        if ( ! in_array( $regex_operator, array( 'any', 'all' ), true ) ) {
            $regex_operator = 'any';
        }
        update_option( 'whippet_scripts_regex_operator', $regex_operator );

        update_option( 'whippet_scripts_mu_mode', ! empty( $_POST['whippet_scripts_mu_mode'] ) ? 1 : 0 );
        update_option( 'whippet_scripts_testing_mode', ! empty( $_POST['whippet_scripts_testing_mode'] ) ? 1 : 0 );
    }

    $timeout = esc_attr( get_option( 'whippet_scripts_timeout', 5 ) );

    $include_list = whippet_scripts_list_to_textarea( get_option( 'whippet_scripts_include_list', array() ) );
    $regex_rules = whippet_scripts_list_to_textarea( get_option( 'whippet_scripts_regex_rules', array() ) );
    $disabled_pages = whippet_scripts_list_to_textarea( get_option( 'whippet_scripts_disabled_pages', array() ) );
    $disabled_pages_regex = whippet_scripts_list_to_textarea( get_option( 'whippet_scripts_disabled_pages_regex', array() ) );
    $regex_operator = get_option( 'whippet_scripts_regex_operator', 'any' );
    $mu_mode = (bool) get_option( 'whippet_scripts_mu_mode', 0 );
    $testing_mode = (bool) get_option( 'whippet_scripts_testing_mode', 0 );
    $testing_preview_url = add_query_arg( 'whippet_preview_scripts', '1', home_url( '/' ) );


    ?>
<form method="POST">
    <?php wp_nonce_field('whippet-scripts', 'whippet-scripts-settings-form'); ?>
    <table class="form-table" role="presentation">
    <tbody>
        <tr>
            <th scope="row"><label>Include Keywords</label></th>
            <td>
                <textarea name="whippet_scripts_include_list" rows="4" cols="50"><?php echo $include_list ?></textarea>
                <p class="description">Keywords that identify scripts that should load on user interaction. One keyword per line.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label>Regex Script Rules</label></th>
            <td>
                <textarea name="whippet_scripts_regex_rules" rows="4" cols="50"><?php echo $regex_rules; ?></textarea>
                <p class="description">Disable any combination of scripts using regex. One pattern per line. Supports either plain text (auto-converted to regex) or full patterns like <code>/gtag|analytics/i</code>.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label>Regex Combination Mode</label></th>
            <td>
                <select name="whippet_scripts_regex_operator">
                    <option value="any" <?php selected( $regex_operator, 'any' ); ?>>Any pattern matches</option>
                    <option value="all" <?php selected( $regex_operator, 'all' ); ?>>All patterns must match</option>
                </select>
                <p class="description">Choose how multiple regex rules are combined when matching scripts or URLs.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label>Timeout</label></th>
            <td>
                <select name="whippet_scripts_timeout" value="<?php echo $timeout; ?>">
                    <option value="1" <?php if ($timeout == 1) {echo 'selected';} ?>>1s</option>
                    <option value="2" <?php if ($timeout == 2) {echo 'selected';} ?>>2s</option>
                    <option value="3" <?php if ($timeout == 3) {echo 'selected';} ?>>3s</option>
                    <option value="4" <?php if ($timeout == 4) {echo 'selected';} ?>>4s</option>
                    <option value="5" <?php if ($timeout == 5) {echo 'selected';} ?>>5s</option>
                    <option value="6" <?php if ($timeout == 6) {echo 'selected';} ?>>6s</option>
                    <option value="7" <?php if ($timeout == 7) {echo 'selected';} ?>>7s</option>
                    <option value="8" <?php if ($timeout == 8) {echo 'selected';} ?>>8s</option>
                    <option value="9" <?php if ($timeout == 9) {echo 'selected';} ?>>9s</option>
                    <option value="10" <?php if ($timeout == 10) {echo 'selected';} ?>>10s</option>
                    <option value="5000" <?php if ($timeout == 5000) {echo 'selected';} ?>>Never</option>
                </select>
            <p class="description">Load scripts after a timeout when there is no user interaction</p>
            <td>
        </tr>
        <tr>
            <th scope="row"><label>Disable on pages</label></th>
            <td>
                <textarea name="whippet_scripts_disabled_pages" rows="4" cols="50"><?php echo $disabled_pages; ?></textarea>
                <p class="description">Keywords of URLs where Whippet Scripts should be disabled</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label>Disable URLs (Regex)</label></th>
            <td>
                <textarea name="whippet_scripts_disabled_pages_regex" rows="4" cols="50"><?php echo $disabled_pages_regex; ?></textarea>
                <p class="description">Regex URL rules where Script Manager should not run (for example: <code>/checkout|cart|my-account/i</code>).</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="whippet_scripts_mu_mode">MU Mode</label></th>
            <td>
                <input type="checkbox" name="whippet_scripts_mu_mode" id="whippet_scripts_mu_mode" value="1" <?php checked( $mu_mode ); ?> />
                <p class="description">MU mode prepares Script Manager for must-use integrations so advanced logic can also control inline code, plugin execution and query-level behaviour.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="whippet_scripts_testing_mode">Testing Mode (New)</label></th>
            <td>
                <input type="checkbox" name="whippet_scripts_testing_mode" id="whippet_scripts_testing_mode" value="1" <?php checked( $testing_mode ); ?> />
                <p class="description">
                    Safely preview your configuration before applying it to visitors. While enabled, Script Manager runs only for admins using:
                    <br><code><?php echo esc_url( $testing_preview_url ); ?></code>
                </p>
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
