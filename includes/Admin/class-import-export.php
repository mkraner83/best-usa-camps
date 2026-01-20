<?php
/**
 * Import/Export admin functionality for CreativeDBS Camp Management.
 * 
 * Handles CSV import/export of camp data with:
 * - Duplicate detection and update mode
 * - Automatic WordPress user creation
 * - Comprehensive validation and error reporting
 * - Progress tracking and detailed logging
 * 
 * @package CreativeDBS\CampMgmt\Admin
 */

namespace CreativeDBS\CampMgmt\Admin;

use CreativeDBS\CampMgmt\DB;

defined( 'ABSPATH' ) || exit;

class Import_Export {

    const ACTION_EXPORT = 'cdbs_export_csv';
    const ACTION_IMPORT = 'cdbs_import_csv';
    const NONCE_EXPORT = 'cdbs_export_nonce';
    const NONCE_IMPORT = 'cdbs_import_nonce';
    const DELIMITER = '|';
    const MAX_FILE_SIZE = 10485760; // 10MB in bytes

    /**
     * Constructor - Hook into WordPress.
     */
    public function __construct() {
        add_action( 'admin_post_' . self::ACTION_EXPORT, [ $this, 'handle_export' ] );
        add_action( 'admin_post_nopriv_' . self::ACTION_EXPORT, [ $this, 'handle_export' ] );
    }

    /**
     * Render the import/export admin page.
     */
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized access' );
        }

        $results = null;
        $error = null;

        // Handle import postback
        if ( ! empty( $_POST[ self::ACTION_IMPORT ] ) ) {
            if ( ! isset( $_POST[ self::NONCE_IMPORT ] ) || ! wp_verify_nonce( $_POST[ self::NONCE_IMPORT ], self::NONCE_IMPORT ) || empty( $_FILES['csv_file']['tmp_name'] ) ) {
                $error = 'Invalid request or no file selected.';
            } else {
                $results = self::do_import( $_FILES['csv_file'] );
            }
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Camp Management - Import / Export', 'creativedbs' ); ?></h1>

            <?php if ( ! is_null( $error ) ) : ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html( $error ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( ! is_null( $results ) ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <strong>Import Complete:</strong><br/>
                        ✓ Inserted: <?php echo intval( $results['inserted'] ); ?><br/>
                        ✓ Updated: <?php echo intval( $results['updated'] ); ?><br/>
                        ✓ Skipped: <?php echo intval( $results['skipped'] ); ?><br/>
                        ✓ Users Created: <?php echo intval( $results['users_created'] ); ?><br/>
                        ✗ Errors: <?php echo intval( $results['errors'] ); ?>
                    </p>
                    <?php if ( ! empty( $results['error_details'] ) ) : ?>
                        <details>
                            <summary>View Error Details</summary>
                            <pre><?php echo esc_html( implode( "\n", $results['error_details'] ) ); ?></pre>
                        </details>
                    <?php endif; ?>
                    <?php if ( ! empty( $results['password_csv'] ) ) : ?>
                        <p>
                            <a href="<?php echo esc_url( $results['password_csv'] ); ?>" class="button button-primary" download>
                                Download User Passwords CSV
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- EXPORT SECTION -->
            <hr/>
            <h2><?php esc_html_e( 'Export Camps', 'creativedbs' ); ?></h2>
            <p><?php esc_html_e( 'Download a CSV of all camps with camp types, weeks, and user links.', 'creativedbs' ); ?></p>
            <p>
                <a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => self::ACTION_EXPORT ], admin_url( 'admin-post.php' ) ), self::NONCE_EXPORT ) ); ?>">
                    <?php esc_html_e( 'Export CSV', 'creativedbs' ); ?>
                </a>
            </p>

            <!-- IMPORT SECTION -->
            <hr/>
            <h2><?php esc_html_e( 'Import Camps', 'creativedbs' ); ?></h2>
            <p><?php esc_html_e( 'Upload a CSV file to import camps. Rows with existing unique_key will be skipped by default.', 'creativedbs' ); ?></p>

            <form method="post" enctype="multipart/form-data" class="cdbs-import-form">
                <?php wp_nonce_field( self::NONCE_IMPORT, self::NONCE_IMPORT ); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="csv_file"><?php esc_html_e( 'CSV File', 'creativedbs' ); ?></label>
                        </th>
                        <td>
                            <input type="file" id="csv_file" name="csv_file" accept=".csv" required />
                            <p class="description">
                                <?php esc_html_e( 'Maximum file size: 10MB. Required columns: camp_name, email, camp_directors.', 'creativedbs' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="import_mode"><?php esc_html_e( 'Import Mode', 'creativedbs' ); ?></label>
                        </th>
                        <td>
                            <select id="import_mode" name="import_mode">
                                <option value="skip"><?php esc_html_e( 'Skip existing (default)', 'creativedbs' ); ?></option>
                                <option value="update"><?php esc_html_e( 'Update existing camps', 'creativedbs' ); ?></option>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Skip: Keep existing camps. Update: Overwrite matching camps by unique_key.', 'creativedbs' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="dry_run"><?php esc_html_e( 'Dry Run', 'creativedbs' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="dry_run" name="dry_run" value="1" />
                                <?php esc_html_e( 'Preview import without saving (show what would happen)', 'creativedbs' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="submit" name="<?php echo esc_attr( self::ACTION_IMPORT ); ?>" value="1" class="button button-primary">
                        <?php esc_html_e( 'Import CSV', 'creativedbs' ); ?>
                    </button>
                </p>
            </form>

            <style>
                .cdbs-import-form table { max-width: 600px; }
                .cdbs-import-form select { min-width: 200px; }
                details { margin: 10px 0; padding: 10px; background: #f5f5f5; border-radius: 4px; }
                pre { background: white; padding: 10px; border-radius: 4px; overflow-x: auto; }
            </style>
        </div>
        <?php
    }

    /**
     * Handle CSV export.
     */
    public function handle_export() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        if ( ! isset( $_GET[ self::NONCE_EXPORT ] ) || ! wp_verify_nonce( $_GET[ self::NONCE_EXPORT ], self::NONCE_EXPORT ) ) {
            wp_die( 'Bad nonce' );
        }

        // Clear output buffers
        while ( ob_get_level() ) {
            @ob_end_clean();
        }

        if ( function_exists( 'nocache_headers' ) ) {
            nocache_headers();
        }

        @ignore_user_abort( true );

        // Set headers
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=camp-export-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv' );
        header( 'X-Content-Type-Options: nosniff' );

        $out = fopen( 'php://output', 'w' );
        if ( ! $out ) {
            wp_die( 'Could not open output stream' );
        }

        // Write headers
        $cols = [
            'unique_key', 'camp_name', 'opening_day', 'closing_day', 
            'minprice_2026', 'maxprice_2026', 'activities', 'email', 'phone', 
            'website', 'camp_directors', 'address', 'city', 'state', 'zip', 
            'about_camp', 'photos', 'logo', 'search_image', 'approved', 
            'created_at', 'updated_at', 'camp_types', 'weeks', 'wordpress_user_id'
        ];
        fputcsv( $out, $cols );

        // Fetch and export camps
        global $wpdb;
        $table = DB::table_camps();
        $rows = DB::get_results( "SELECT * FROM {$table} ORDER BY id ASC" );

        if ( $rows ) {
            foreach ( $rows as $row ) {
                // Get linked types
                $type_ids = DB::get_results(
                    "SELECT id, name FROM " . DB::table_type_terms() . " WHERE id IN (SELECT type_id FROM " . DB::table_camp_type_pivot() . " WHERE camp_id = %d)",
                    [ $row->id ]
                );
                $type_names = wp_list_pluck( $type_ids, 'name' );

                // Get linked weeks
                $week_ids = DB::get_results(
                    "SELECT id, name FROM " . DB::table_week_terms() . " WHERE id IN (SELECT week_id FROM " . DB::table_camp_week_pivot() . " WHERE camp_id = %d)",
                    [ $row->id ]
                );
                $week_names = wp_list_pluck( $week_ids, 'name' );

                // Build row
                $line = [
                    $row->unique_key,
                    $row->camp_name,
                    $row->opening_day,
                    $row->closing_day,
                    $row->minprice_2026,
                    $row->maxprice_2026,
                    $row->activities,
                    $row->email,
                    $row->phone,
                    $row->website,
                    $row->camp_directors,
                    $row->address,
                    $row->city,
                    $row->state,
                    $row->zip,
                    $row->about_camp,
                    $row->photos,
                    $row->logo,
                    $row->search_image,
                    $row->approved,
                    $row->created_at,
                    $row->updated_at,
                    implode( self::DELIMITER . ' ', $type_names ),
                    implode( self::DELIMITER . ' ', $week_names ),
                    $row->wordpress_user_id ?? ''
                ];

                fputcsv( $out, $line );
            }
        }

        fclose( $out );
        exit;
    }

    /**
     * Handle CSV import.
     *
     * @param array $file File from $_FILES.
     * @return array Results array with statistics.
     */
    public static function do_import( $file ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return [ 'error' => 'Unauthorized' ];
        }

        // Validate file
        if ( $file['size'] > self::MAX_FILE_SIZE ) {
            return [ 'error' => 'File too large (max 10MB)' ];
        }

        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            return [ 'error' => 'File upload error: ' . $file['error'] ];
        }

        $tmp_path = $file['tmp_name'];
        $fh = fopen( $tmp_path, 'r' );
        if ( ! $fh ) {
            return [ 'error' => 'Could not open uploaded file' ];
        }

        // Initialize results
        $results = [
            'inserted'      => 0,
            'updated'       => 0,
            'skipped'       => 0,
            'users_created' => 0,
            'errors'        => 0,
            'error_details' => [],
            'password_csv'  => null,
            'passwords'     => []
        ];

        // Get import mode
        $import_mode = isset( $_POST['import_mode'] ) ? sanitize_text_field( $_POST['import_mode'] ) : 'skip';
        $dry_run = ! empty( $_POST['dry_run'] );

        // Parse header
        $header = fgetcsv( $fh );
        $col_map = [];
        foreach ( $header as $i => $col ) {
            $col_map[ strtolower( trim( $col ) ) ] = $i;
        }

        $row_num = 1; // Header is row 1

        while ( ( $row = fgetcsv( $fh ) ) !== false ) {
            $row_num++;

            // Get value helper
            $get = function( $key ) use ( $col_map, $row ) {
                $k = strtolower( $key );
                return isset( $col_map[ $k ] ) ? trim( $row[ $col_map[ $k ] ] ) : '';
            };

            // Extract fields
            $unique_key = $get( 'unique_key' );
            $camp_name = $get( 'camp_name' );
            $email = $get( 'email' );
            $camp_directors = $get( 'camp_directors' );

            // Validate required fields
            if ( empty( $camp_name ) ) {
                $results['error_details'][] = "Row $row_num: Missing camp_name";
                $results['errors']++;
                continue;
            }

            if ( empty( $email ) ) {
                $results['error_details'][] = "Row $row_num: Missing email";
                $results['errors']++;
                continue;
            }

            if ( empty( $camp_name ) && empty( $camp_directors ) ) {
                $results['error_details'][] = "Row $row_num: Cannot create user without camp_name or camp_directors";
                $results['errors']++;
                continue;
            }

            // Validate email format
            if ( ! is_email( $email ) ) {
                $results['error_details'][] = "Row $row_num: Invalid email format: $email";
                $results['errors']++;
                continue;
            }

            // Check for existing camp by unique_key
            $exists = false;
            $camp_id = null;

            if ( ! empty( $unique_key ) ) {
                $camp_id = DB::get_var(
                    "SELECT id FROM " . DB::table_camps() . " WHERE unique_key = %s",
                    [ $unique_key ]
                );
                $exists = ! empty( $camp_id );
            }

            // Handle duplicate logic
            if ( $exists && 'skip' === $import_mode ) {
                $results['skipped']++;
                continue;
            }

            // Generate unique_key if missing
            if ( empty( $unique_key ) ) {
                $unique_key = md5( uniqid( 'camp_', true ) );
            }

            // Parse prices (strip $ and ,)
            $minprice = $get( 'minprice_2026' );
            $minprice = floatval( str_replace( [ '$', ',' ], '', $minprice ) ) ?: null;

            $maxprice = $get( 'maxprice_2026' );
            $maxprice = floatval( str_replace( [ '$', ',' ], '', $maxprice ) ) ?: null;

            // Prepare camp data
            $camp_data = [
                'unique_key'     => $unique_key,
                'camp_name'      => sanitize_text_field( $camp_name ),
                'opening_day'    => $get( 'opening_day' ) ?: null,
                'closing_day'    => $get( 'closing_day' ) ?: null,
                'minprice_2026'  => $minprice,
                'maxprice_2026'  => $maxprice,
                'activities'     => $get( 'activities' ) ?: '',
                'email'          => sanitize_email( $email ),
                'phone'          => sanitize_text_field( $get( 'phone' ) ),
                'website'        => esc_url_raw( $get( 'website' ) ),
                'camp_directors' => sanitize_textarea_field( $camp_directors ),
                'address'        => sanitize_text_field( $get( 'address' ) ),
                'city'           => sanitize_text_field( $get( 'city' ) ),
                'state'          => sanitize_text_field( $get( 'state' ) ),
                'zip'            => sanitize_text_field( $get( 'zip' ) ),
                'about_camp'     => wp_kses_post( $get( 'about_camp' ) ),
                'photos'         => $get( 'photos' ) ?: '',
                'logo'           => esc_url_raw( $get( 'logo' ) ),
                'search_image'   => esc_url_raw( $get( 'search_image' ) ),
                'approved'       => intval( $get( 'approved' ) ) ? 1 : 0,
            ];

            // Don't overwrite timestamps on update
            if ( ! $exists ) {
                $camp_data['created_at'] = current_time( 'mysql' );
                $camp_data['updated_at'] = current_time( 'mysql' );
            } else {
                $camp_data['updated_at'] = current_time( 'mysql' );
            }

            // Skip actual save if dry run
            if ( ! $dry_run ) {
                global $wpdb;
                $table = DB::table_camps();

                if ( $exists ) {
                    // Update existing camp
                    $update_ok = $wpdb->update( $table, $camp_data, [ 'id' => $camp_id ] );
                    if ( false === $update_ok ) {
                        $results['error_details'][] = "Row $row_num: Database update failed";
                        $results['errors']++;
                        continue;
                    }
                    $results['updated']++;
                } else {
                    // Insert new camp
                    $insert_ok = $wpdb->insert( $table, $camp_data );
                    if ( false === $insert_ok ) {
                        $results['error_details'][] = "Row $row_num: Database insert failed";
                        $results['errors']++;
                        continue;
                    }
                    $camp_id = intval( $wpdb->insert_id );
                    $results['inserted']++;
                }

                // Link terms (types, weeks, activities)
                self::link_terms( $camp_id, $get( 'camp_types' ), $get( 'weeks' ), $get( 'activities' ) );

                // Create WordPress user (only for new camps or first insertion)
                if ( ! $exists ) {
                    $user_result = self::create_user_from_directors( $camp_directors, $email, $get( 'website' ), $camp_name );
                    if ( is_wp_error( $user_result ) ) {
                        $results['error_details'][] = "Row $row_num: User creation failed: " . $user_result->get_error_message();
                    } elseif ( is_array( $user_result ) ) {
                        $user_id = $user_result['user_id'];
                        // Link user to camp
                        $wpdb->update( $table, [ 'wordpress_user_id' => $user_id ], [ 'id' => $camp_id ] );
                        $results['users_created']++;

                        // Store the actual password that was set on the user
                        $results['passwords'][] = [
                            'camp_name' => $camp_name,
                            'username'  => $user_result['username'],
                            'email'     => $email,
                            'password'  => $user_result['password']
                        ];
                    }
                }
            }
        }

        fclose( $fh );

        // Generate password CSV if users were created
        if ( ! empty( $results['passwords'] ) && ! $dry_run ) {
            $results['password_csv'] = self::generate_password_csv( $results['passwords'] );
        }

        return $results;
    }

    /**
     * Link camp to taxonomy terms (types, weeks, activities).
     *
     * @param int    $camp_id Camp ID.
     * @param string $types_csv Types (pipe-separated).
     * @param string $weeks_csv Weeks (pipe-separated).
     * @param string $acts_csv Activities (pipe-separated).
     */
    private static function link_terms( $camp_id, $types_csv = '', $weeks_csv = '', $acts_csv = '' ) {
        global $wpdb;

        // Normalize delimiters
        $types_csv = str_replace( [ '|', ';', ',' ], '|', $types_csv );
        $weeks_csv = str_replace( [ '|', ';', ',' ], '|', $weeks_csv );
        $acts_csv = str_replace( [ '|', ';', ',' ], '|', $acts_csv );

        // Link Types
        $type_names = array_filter( array_map( 'trim', explode( '|', $types_csv ) ) );
        foreach ( $type_names as $name ) {
            self::link_single_term( $camp_id, $name, 'type' );
        }

        // Link Weeks
        $week_names = array_filter( array_map( 'trim', explode( '|', $weeks_csv ) ) );
        foreach ( $week_names as $name ) {
            self::link_single_term( $camp_id, $name, 'week' );
        }

        // Link Activities (to pivot table)
        $act_names = array_filter( array_map( 'trim', explode( '|', $acts_csv ) ) );
        foreach ( $act_names as $name ) {
            self::link_single_term( $camp_id, $name, 'activity' );
        }
    }

    /**
     * Link a single term to a camp.
     *
     * @param int    $camp_id Camp ID.
     * @param string $name Term name.
     * @param string $type Type of term (type, week, activity).
     */
    private static function link_single_term( $camp_id, $name, $type ) {
        global $wpdb;

        // Determine tables
        if ( 'type' === $type ) {
            $term_table = DB::table_type_terms();
            $pivot_table = DB::table_camp_type_pivot();
            $fk_field = 'type_id';
        } elseif ( 'week' === $type ) {
            $term_table = DB::table_week_terms();
            $pivot_table = DB::table_camp_week_pivot();
            $fk_field = 'week_id';
        } else { // activity
            $term_table = DB::table_activity_terms();
            $pivot_table = DB::table_camp_activity_pivot();
            $fk_field = 'activity_id';
        }

        // Find or create term
        $term_id = DB::get_var(
            $wpdb->prepare(
                "SELECT id FROM {$term_table} WHERE name = %s OR slug = %s",
                $name,
                sanitize_title( $name )
            )
        );

        if ( ! $term_id ) {
            $wpdb->insert(
                $term_table,
                [
                    'name'       => $name,
                    'slug'       => sanitize_title( $name ),
                    'is_active'  => 1,
                    'created_at' => current_time( 'mysql' ),
                    'updated_at' => current_time( 'mysql' ),
                ]
            );
            $term_id = intval( $wpdb->insert_id );
        }

        // Link to camp
        if ( $term_id ) {
            $wpdb->insert(
                $pivot_table,
                [
                    'camp_id'      => $camp_id,
                    $fk_field      => $term_id,
                ]
            );
        }
    }

    /**
     * Create WordPress user from director information.
     *
     * @param string $directors_csv Directors (pipe-separated).
     * @param string $email Email address.
     * @param string $website Website URL.
     * @param string $camp_name Camp name (for fallback).
     * @return int|WP_Error User ID or error.
     */
    private static function create_user_from_directors( $directors_csv, $email, $website, $camp_name ) {
        // Extract first director
        $directors = array_filter( array_map( 'trim', explode( '|', $directors_csv ) ) );
        if ( empty( $directors ) ) {
            return new \WP_Error( 'no_director', 'No director name provided' );
        }

        $first_director = $directors[0];
        $name_parts = explode( ' ', $first_director, 2 );
        $first_name = isset( $name_parts[0] ) ? sanitize_text_field( $name_parts[0] ) : '';
        $last_name = isset( $name_parts[1] ) ? sanitize_text_field( $name_parts[1] ) : '';

        // Generate username
        $username = self::generate_username( $camp_name );

        // Check if username exists
        if ( username_exists( $username ) ) {
            return new \WP_Error( 'username_exists', "Username '$username' already exists" );
        }

        // Check if email exists
        if ( email_exists( $email ) ) {
            return new \WP_Error( 'email_exists', "Email '$email' already in use" );
        }

        // Generate password
        $password = wp_generate_password( 12 );

        // Create user
        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        // Set additional user data
        wp_update_user( [
            'ID'         => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'user_url'   => esc_url_raw( $website ),
        ] );

        // Set role to "camp"
        $user = new \WP_User( $user_id );
        $user->set_role( 'camp' );

        // Return array with user_id and password so caller can store both
        return [
            'user_id'  => $user_id,
            'username' => $username,
            'password' => $password,
        ];
    }

    /**
     * Generate username from camp name.
     *
     * @param string $camp_name Camp name.
     * @return string Generated username.
     */
    private static function generate_username( $camp_name ) {
        // Lowercase and remove spaces/special chars
        $username = strtolower( $camp_name );
        $username = preg_replace( '/[^a-z0-9_-]/', '', $username );
        $username = substr( $username, 0, 60 ); // Max 60 chars

        // Check for duplicates and append number if needed
        $base_username = $username;
        $counter = 1;
        while ( username_exists( $username ) ) {
            $username = $base_username . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Generate CSV file with user passwords.
     *
     * @param array $passwords Array of password data.
     * @return string URL to download the CSV.
     */
    private static function generate_password_csv( $passwords ) {
        // Create temporary CSV file
        $upload_dir = wp_upload_dir();
        $csv_filename = 'camp-user-passwords-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';
        $csv_path = $upload_dir['path'] . '/' . $csv_filename;
        $csv_url = $upload_dir['url'] . '/' . $csv_filename;

        $fh = fopen( $csv_path, 'w' );
        if ( ! $fh ) {
            return '';
        }

        // Write headers
        fputcsv( $fh, [ 'Camp Name', 'Username', 'Email', 'Password' ] );

        // Write data
        foreach ( $passwords as $pwd_data ) {
            fputcsv( $fh, [
                $pwd_data['camp_name'],
                $pwd_data['username'],
                $pwd_data['email'],
                $pwd_data['password'],
            ] );
        }

        fclose( $fh );

        return $csv_url;
    }
}
