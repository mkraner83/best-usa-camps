<?php
/**
 * Admin Credentials Editor (separate page to avoid touching existing edit form).
 *
 * @package CreativeDBS\CampMgmt
 */
namespace CreativeDBS\CampMgmt;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Admin_Creds {

    public static function register_menu() {
        // Parent slug is the main plugin page slug.
        $parent_slug = 'creativedbs-camp-mgmt';
        add_submenu_page(
            $parent_slug,
            __( 'Camp Credentials', 'creativedbs-camp-mgmt' ),
            __( 'Credentials', 'creativedbs-camp-mgmt' ),
            'manage_options',
            'creativedbs-camp-mgmt-creds',
            [ __CLASS__, 'render_page' ],
            99
        );
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'creativedbs-camp-mgmt' ) );
        }

        global $wpdb;
        $table       = $wpdb->prefix . 'camp_management';
        $creds_table = $wpdb->prefix . 'camp_credentials';

        $camp_id = isset( $_GET['camp'] ) ? intval( $_GET['camp'] ) : 0;
        $camp    = null;
        if ( $camp_id ) {
            $camp = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $camp_id ) );
        }

        // Handle save
        if ( isset( $_POST['cdbs_creds_nonce'] ) && wp_verify_nonce( $_POST['cdbs_creds_nonce'], 'cdbs_save_creds_' . $camp_id ) ) {

            $internal_link = isset($_POST['internal_link']) ? esc_url_raw( $_POST['internal_link'] ) : '';
            $username      = isset($_POST['cred_username']) ? sanitize_text_field( $_POST['cred_username'] ) : '';
            $password_raw  = isset($_POST['cred_password']) ? (string) $_POST['cred_password'] : '';

            if ( $camp_id ) {
                // Update internal_link on camp row
                $wpdb->update( $table, [ 'internal_link' => $internal_link ], [ 'id' => $camp_id ] );

                // Upsert credentials by unique_key
                $unique_key = $camp ? $camp->unique_key : $wpdb->get_var( $wpdb->prepare( "SELECT unique_key FROM {$table} WHERE id=%d", $camp_id ) );
                if ( $unique_key ) {
                    $existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$creds_table} WHERE unique_key=%s", $unique_key ) );
                    $data = [ 'username' => $username, 'updated_at' => current_time('mysql') ];
                    if ( $password_raw !== '' ) {
                        $data['secret_enc'] = Helpers::enc_encrypt( $password_raw );
                    }
                    if ( $existing_id ) {
                        $wpdb->update( $creds_table, $data, [ 'id' => intval( $existing_id ) ] );
                    } else {
                        $data['unique_key'] = $unique_key;
                        $wpdb->insert( $creds_table, $data );
                    }
                    // Reload $camp values
                    $camp = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $camp_id ) );
                }
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Saved.', 'creativedbs-camp-mgmt' ) . '</p></div>';
        }

        // Load current creds for display
        $cred_username = '';
        $cred_mask     = '';
        if ( $camp ) {
            $cred_row = $wpdb->get_row( $wpdb->prepare( "SELECT username, secret_enc FROM {$creds_table} WHERE unique_key=%s", $camp->unique_key ) );
            if ( $cred_row ) {
                $cred_username = $cred_row->username ?: '';
                $plain         = Helpers::enc_decrypt( $cred_row->secret_enc );
                $cred_mask     = Helpers::mask_secret( $plain );
            }
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Camp Credentials', 'creativedbs-camp-mgmt' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Edit Internal Link, Username and Password for a single camp. Password is encrypted at rest.', 'creativedbs-camp-mgmt' ); ?></p>

            <form method="get" style="margin:1em 0 2em;">
    <input type="hidden" name="page" value="creativedbs-camp-mgmt-creds" />
    <?php
    // Build a Camp Name dropdown (value=id) sorted by name.
    $camps = $wpdb->get_results( "SELECT id, camp_name FROM {$table} ORDER BY camp_name ASC" );
    ?>
    <label for="camp_select"><?php esc_html_e('Camp:', 'creativedbs-camp-mgmt'); ?></label>
    <select id="camp_select" name="camp">
        <option value=""><?php esc_html_e('Select a camp...', 'creativedbs-camp-mgmt'); ?></option>
        <?php foreach ( (array) $camps as $c ) : ?>
            <option value="<?php echo intval( $c->id ); ?>" <?php selected( $camp_id, $c->id ); ?>><?php echo esc_html( $c->camp_name . " (ID {$c->id})" ); ?></option>
        <?php endforeach; ?>
    </select>
    <button class="button"><?php esc_html_e('Load', 'creativedbs-camp-mgmt'); ?></button>
    <?php if ( $camp ) : ?>
        <span style="margin-left:12px;"><strong><?php echo esc_html( $camp->camp_name ); ?></strong> (ID <?php echo intval( $camp->id ); ?>)</span>
    <?php endif; ?>
</form>

            <?php if ( $camp ) : ?>
            <form method="post">
                <?php wp_nonce_field( 'cdbs_save_creds_' . $camp_id, 'cdbs_creds_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="internal_link"><?php esc_html_e('Internal Link', 'creativedbs-camp-mgmt'); ?></label></th>
                            <td>
                                <input type="url" class="regular-text" id="internal_link" name="internal_link" value="<?php echo esc_attr( $camp->internal_link ); ?>" placeholder="https://example.com/admin/camp/..." />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cred_username"><?php esc_html_e('Username', 'creativedbs-camp-mgmt'); ?></label></th>
                            <td>
                                <input type="text" class="regular-text" id="cred_username" name="cred_username" value="<?php echo esc_attr( $cred_username ); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cred_password"><?php esc_html_e('Password', 'creativedbs-camp-mgmt'); ?></label></th>
                            <td>
                                <input type="password" class="regular-text" id="cred_password" name="cred_password" value="" placeholder="<?php echo esc_attr( $cred_mask ); ?>" />
                                <label style="margin-left:10px;"><input type="checkbox" id="toggle_pw" /> <?php esc_html_e('Show', 'creativedbs-camp-mgmt'); ?></label>
                                <button type="button" class="button" id="cdbs-reveal-btn"
                                        data-camp="<?php echo intval( $camp->id ); ?>"
                                        data-nonce="<?php echo esc_attr( wp_create_nonce( 'cdbs_reveal_secret_' . intval( $camp->id ) ) ); ?>"
                                        style="margin-left:10px;"><?php esc_html_e('Reveal current', 'creativedbs-camp-mgmt'); ?></button>
                                <p class="description"><?php esc_html_e('Leave blank to keep the existing password.', 'creativedbs-camp-mgmt'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p><button class="button button-primary"><?php esc_html_e('Save', 'creativedbs-camp-mgmt'); ?></button></p>
            </form>
            <script>
            (function(){
                var t = document.getElementById('toggle_pw');
                var i = document.getElementById('cred_password');
                if (t && i) {
                    t.addEventListener('change', function(){ i.type = this.checked ? 'text' : 'password'; });
                }
            })();
            </script>
            <?php else : ?>
                <p><?php esc_html_e('Enter a Camp ID and click Load to edit credentials.', 'creativedbs-camp-mgmt'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}


// === Reveal current password (AJAX) ===
if ( ! function_exists( 'cdbs_reveal_secret_handler' ) ) {
    function cdbs_reveal_secret_handler() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'forbidden', 403 );
        }
        $camp_id = isset( $_POST['camp'] ) ? intval( $_POST['camp'] ) : 0;
        $nonce   = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
        if ( ! $camp_id || ! wp_verify_nonce( $nonce, 'cdbs_reveal_secret_' . $camp_id ) ) {
            wp_send_json_error( 'bad_nonce', 400 );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'camp_management';
        $unique_key = $wpdb->get_var( $wpdb->prepare( "SELECT unique_key FROM {$table} WHERE id=%d", $camp_id ) );
        if ( ! $unique_key ) {
            wp_send_json_error( 'not_found', 404 );
        }
        $creds_table = $wpdb->prefix . 'camp_credentials';
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT secret_enc FROM {$creds_table} WHERE unique_key=%s", $unique_key ) );
        if ( ! $row || empty( $row->secret_enc ) ) {
            wp_send_json_error( 'no_secret', 404 );
        }
        $plain = Helpers::enc_decrypt( $row->secret_enc );
        wp_send_json_success( [ 'password' => $plain ] );
    }
}
add_action( 'wp_ajax_cdbs_reveal_secret', __NAMESPACE__ . '\\cdbs_reveal_secret_handler' );

// Print page-scoped JS only on our credentials page
function cdbs_creds_footer_js() {
    if ( ! is_admin() ) return;
    $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
    if ( $page !== 'creativedbs-camp-mgmt-creds' ) return;
    ?>
    <script>
    (function(){
      var t = document.getElementById('toggle_pw');
      var i = document.getElementById('cred_password');
      if (t && i) { t.addEventListener('change', function(){ i.type = this.checked ? 'text' : 'password'; }); }
      var btn = document.getElementById('cdbs-reveal-btn');
      if (!btn) return;
      btn.addEventListener('click', function(e){
        e.preventDefault();
        var fd = new FormData();
        fd.append('action','cdbs_reveal_secret');
        fd.append('camp', btn.getAttribute('data-camp'));
        fd.append('nonce', btn.getAttribute('data-nonce'));
        fetch(ajaxurl, { method:'POST', credentials:'same-origin', body: fd })
          .then(r => r.json())
          .then(data => {
            if (data && data.success && data.data && typeof data.data.password === 'string') {
              i.type = 'text'; i.value = data.data.password; i.focus();
            } else { alert(data && data.data ? ('Error: ' + (data.data || 'Unable to reveal password.')) : 'Unable to reveal password.'); }
          })
          .catch(() => alert('Network error.'));
      });
    })();
    </script>
    <?php
}
add_action( 'admin_print_footer_scripts', __NAMESPACE__ . '\\cdbs_creds_footer_js' );