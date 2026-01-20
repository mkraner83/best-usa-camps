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

            if ( $camp_id ) {
                // Update internal_link on camp row
                $wpdb->update( $table, [ 'internal_link' => $internal_link ], [ 'id' => $camp_id ] );
                // Reload $camp values
                $camp = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $camp_id ) );
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Saved.', 'creativedbs-camp-mgmt' ) . '</p></div>';
        }

        // Load current creds for display
        $wp_user       = null;
        $wp_user_login = '';
        $wp_user_email = '';
        
        if ( $camp ) {
            // Check for WordPress user if wordpress_user_id exists
            if ( ! empty( $camp->wordpress_user_id ) ) {
                $wp_user = get_user_by( 'id', intval( $camp->wordpress_user_id ) );
                if ( $wp_user ) {
                    $wp_user_login = $wp_user->user_login;
                    $wp_user_email = $wp_user->user_email;
                }
            }
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Camp Credentials', 'creativedbs-camp-mgmt' ); ?></h1>
            <p class="description"><?php esc_html_e( 'View WordPress user account and set custom camp page URL for a single camp.', 'creativedbs-camp-mgmt' ); ?></p>

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
                            <th scope="row"><label for="internal_link"><?php esc_html_e('Camp Page URL', 'creativedbs-camp-mgmt'); ?></label></th>
                            <td>
                                <input type="url" class="regular-text" id="internal_link" name="internal_link" value="<?php echo esc_attr( $camp->internal_link ); ?>" placeholder="https://example.com/camp-page/" />
                                <p class="description"><?php esc_html_e('Custom camp page URL shown in search results. Leave blank to use default.', 'creativedbs-camp-mgmt'); ?></p>
                            </td>
                        </tr>
                        
                        <?php if ( $wp_user ) : ?>
                        <tr style="background-color:#f0f8ff; border-left: 4px solid #0073aa;">
                            <th scope="row"><strong><?php esc_html_e('WordPress User', 'creativedbs-camp-mgmt'); ?></strong></th>
                            <td>
                                <p style="margin:0;">
                                    <strong><?php esc_html_e('Username:', 'creativedbs-camp-mgmt'); ?></strong> <code><?php echo esc_html( $wp_user_login ); ?></code><br/>
                                    <strong><?php esc_html_e('Email:', 'creativedbs-camp-mgmt'); ?></strong> <code><?php echo esc_html( $wp_user_email ); ?></code><br/>
                                    <small style="color:#666;">
                                        <?php esc_html_e('Auto-created from import. ', 'creativedbs-camp-mgmt'); ?>
                                        <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . intval( $wp_user->ID ) ) ); ?>" target="_blank">
                                            <?php esc_html_e('Edit User →', 'creativedbs-camp-mgmt'); ?>
                                        </a>
                                    </small>
                                </p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <p><button class="button button-primary"><?php esc_html_e('Save', 'creativedbs-camp-mgmt'); ?></button></p>
            </form>
            <?php else : ?>
                <p><?php esc_html_e('Select a camp and click Load to manage its settings.', 'creativedbs-camp-mgmt'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}


// Clean up - no longer need manual credentials AJAX handler or footer script