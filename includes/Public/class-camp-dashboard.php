<?php
/**
 * Front-end Camp Dashboard for Camp users.
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt\PublicArea;

defined( 'ABSPATH' ) || exit;

class Camp_Dashboard {

	public function __construct() {
		// Register shortcodes for front-end dashboard
		add_shortcode( 'camp_dashboard', [ $this, 'render_dashboard' ] );
		add_shortcode( 'camp_dashboard_title', [ $this, 'render_dashboard_title' ] );
		
		// Handle form submissions
		add_action( 'init', [ $this, 'handle_form_submission' ] );
		add_action( 'init', [ $this, 'handle_custom_login' ] );
		
		// Enqueue front-end styles
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		
		// Register custom media category taxonomy
		add_action( 'init', [ $this, 'register_media_category' ] );
		
		// Customize WordPress login page
		add_action( 'login_enqueue_scripts', [ $this, 'customize_login_page' ] );
		add_filter( 'login_headerurl', [ $this, 'login_logo_url' ] );
		add_filter( 'login_headertext', [ $this, 'login_logo_url_title' ] );
	}
	
	/**
	 * Register custom media category for camp files
	 */
	public function register_media_category() {
		register_taxonomy(
			'media_category',
			'attachment',
			[
				'labels' => [
					'name'          => 'Media Folders',
					'singular_name' => 'Media Folder',
					'search_items'  => 'Search Folders',
					'all_items'     => 'All Folders',
					'edit_item'     => 'Edit Folder',
					'add_new_item'  => 'Add New Folder',
				],
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_admin_column' => true,
				'hierarchical'      => true,
				'show_in_nav_menus' => false,
			]
		);
		
		// Create "Camps" term if it doesn't exist
		// term_exists returns term_id if exists, null if not - so we need to check for null
		$term_exists = term_exists( 'camps', 'media_category' );
		if ( ! $term_exists ) {
			$result = wp_insert_term( 'Camps', 'media_category', [
				'description' => 'All camp photos and logos',
				'slug'        => 'camps',
			] );
			error_log( 'Camp category term creation result: ' . print_r( $result, true ) );
		}
	}
	
	/**
	 * Customize WordPress password reset page backgrounds
	 */
	public function customize_login_page() {
		?>
		<style type="text/css">
			/* Import Google Fonts */
			@import url('https://fonts.googleapis.com/css2?family=Annie+Use+Your+Telescope&display=swap');
			
			/* Overall page styling */
			body.login {
				background: #ffffff;
				font-family: Arial, sans-serif;
			}
			
			/* Hide default WordPress logo */
			.login h1 {
				display: none;
			}
			
			/* Form container */
			#login {
				padding: 0;
			}
			
			.login form {
				background: #ffffff;
				border: none;
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
				padding: 50px 40px;
				border-radius: 8px;
			}
			
			/* Form title */
			.login form::before {
				content: 'Camp Login';
				display: block;
				text-align: center;
				color: #497C5E;
				margin-top: 0;
				margin-bottom: 10px;
				font-size: 32px;
				font-family: "Annie Use Your Telescope", sans-serif !important;
			}
			
			/* Lost password form title */
			body.login.login-action-lostpassword form::before {
				content: 'Reset Password';
			}
			
			/* Reset password form title */
			body.login.login-action-rp form::before,
			body.login.login-action-resetpass form::before {
				content: 'Generate Password';
			}
			
			/* Form description */
			.login form p.message,
			.login #login_error {
				font-size: 14px;
				color: #666666;
				text-align: center;
				line-height: 1.6;
				margin: 0 0 20px 0;
				padding: 12px;
				background: transparent;
				border: none;
			}
			
			/* Labels */
			.login form label {
				font-size: 14px;
				font-weight: 700;
				color: #333333;
				display: block;
				margin-bottom: 8px;
			}
			
			/* Input fields */
			.login form input[type="text"],
			.login form input[type="password"],
			.login form input[type="email"] {
				width: 100%;
				padding: 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
				font-size: 16px;
				margin-bottom: 15px;
				box-sizing: border-box;
				font-family: Arial, sans-serif;
				color: #333333;
			}
			
			.login form input[type="text"]:focus,
			.login form input[type="password"]:focus,
			.login form input[type="email"]:focus {
				border: 1px solid #497C5E;
				outline: none;
				box-shadow: 0 0 0 2px rgba(73, 124, 94, 0.2);
			}
			
			/* Remember Me checkbox */
			.login .forgetmenot {
				margin-bottom: 20px;
			}
			
			.login .forgetmenot label {
				font-weight: 400;
				color: #666666;
				font-size: 13px;
			}
			
			.login form input[type="checkbox"] {
				margin-right: 8px;
			}
			
			/* Submit button */
			.login form .button-primary {
				width: 100%;
				padding: 14px;
				background: #497C5E;
				color: #ffffff;
				border: none;
				border-radius: 5px;
				font-family: "Annie Use Your Telescope", sans-serif !important;
				font-weight: 800 !important;
				font-size: 20px;
				text-transform: uppercase;
				letter-spacing: 1.5px;
				cursor: pointer;
				transition: background 0.3s;
				box-shadow: none;
				text-shadow: none;
			}
			
			.login form .button-primary:hover {
				background: #3d6a4f;
			}
			
			/* Links below form */
			.login #nav,
			.login #backtoblog {
				text-align: center;
				margin-top: 20px;
			}
			
			.login #nav a,
			.login #backtoblog a {
				color: #999999;
				font-size: 13px;
				text-decoration: none;
				transition: color 0.2s ease;
			}
			
			.login #nav a:hover,
			.login #backtoblog a:hover {
				color: #497C5E;
			}
			
			/* "Forgot your password?" link styling */
			.login #nav {
				margin-top: 25px;
			}
			
			/* Password strength meter */
			.login form .pw-weak {
				display: none;
			}
			
			#pass-strength-result {
				background: #f5f5f5;
				border: none;
				border-radius: 4px;
				margin-top: 10px;
				padding: 8px 12px;
				font-size: 13px;
			}
			
			/* Success messages */
			.login .message,
			.login .notice,
			.login .success {
				border-left: 4px solid #497C5E;
				background: #ffffff;
				color: #333333;
				padding: 12px;
				margin-left: 0;
				margin-bottom: 20px;
				box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);
				word-wrap: break-word;
			}
			
			/* Error messages */
			.login #login_error {
				border-left: 4px solid #dc3545;
				background: #f8d7da;
				color: #721c24;
				padding: 12px 15px;
				margin-bottom: 20px;
			}
			
			/* Responsive design */
			@media screen and (max-width: 768px) {
				.login form {
					padding: 35px 25px;
				}
				
				.login form::before {
					font-size: 26px;
				}
			}
		</style>
		<?php
	}
	
	/**
	 * Change login logo URL to homepage
	 */
	public function login_logo_url() {
		return home_url();
	}
	
	/**
	 * Change login logo title
	 */
	public function login_logo_url_title() {
		return 'Best USA Camps';
	}

	/**
	 * Render the dashboard title shortcode
	 * Returns "Camp [Camp Name]" if logged in, or "CAMP DASHBOARD" if not
	 */
	public function render_dashboard_title( $atts ) {
		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			return 'CAMP DASHBOARD';
		}

		$user = wp_get_current_user();
		
		// Check if user has camp role
		if ( ! in_array( 'camp', $user->roles ) ) {
			return 'CAMP DASHBOARD';
		}

		// Get camp data
		global $wpdb;
		$camp = $wpdb->get_row( $wpdb->prepare(
			"SELECT camp_name FROM {$wpdb->prefix}camp_management WHERE user_id = %d",
			$user->ID
		), ARRAY_A );

		if ( ! $camp || empty( $camp['camp_name'] ) ) {
			return 'CAMP DASHBOARD';
		}

		return 'Admin: ' . esc_html( $camp['camp_name'] );
	}

	/**
	 * Enqueue front-end dashboard styles
	 */
	public function enqueue_styles() {
		// Only enqueue on pages with the shortcode
		global $post;
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'camp_dashboard' ) ) {
			wp_enqueue_style( 
				'camp-dashboard', 
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/camp-dashboard.css',
				[],
				CDBS_CAMP_VERSION
			);
		}
	}

	/**
	 * Handle form submission for updating camp data
	 */
	public function handle_form_submission() {
		if ( ! isset( $_POST['camp_dashboard_nonce'] ) || ! wp_verify_nonce( $_POST['camp_dashboard_nonce'], 'update_camp_data' ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! in_array( 'camp', $user->roles ) ) {
			return;
		}

		// Get camp ID associated with this user
		global $wpdb;
		$camp_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}camp_management WHERE user_id = %d",
			$user->ID
		) );

		if ( ! $camp_id ) {
			wp_die( 'No camp found for this user.' );
		}

		// Get current camp data for handlers
		$camp = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}camp_management WHERE id = %d",
			$camp_id
		), ARRAY_A );

		// Update camp data
		$camp_data = [
			'camp_name'        => sanitize_text_field( $_POST['camp_name'] ?? '' ),
			'camp_directors'   => sanitize_text_field( $_POST['camp_directors'] ?? '' ),
			'address'          => sanitize_text_field( $_POST['address'] ?? '' ),
			'city'             => sanitize_text_field( $_POST['city'] ?? '' ),
			'state'            => sanitize_text_field( $_POST['state'] ?? '' ),
			'zip'              => sanitize_text_field( $_POST['zip'] ?? '' ),
			'phone'            => sanitize_text_field( $_POST['phone'] ?? '' ),
			'email'            => sanitize_email( $_POST['email'] ?? '' ),
			'website'          => esc_url_raw( $_POST['website'] ?? '' ),
			'about_camp'       => wp_kses_post( $_POST['about_camp'] ?? '' ),
			'opening_day'      => sanitize_text_field( $_POST['opening_day'] ?? '' ),
			'closing_day'      => sanitize_text_field( $_POST['closing_day'] ?? '' ),
			'minprice_2026'    => floatval( $_POST['minprice_2026'] ?? 0 ),
			'maxprice_2026'    => floatval( $_POST['maxprice_2026'] ?? 0 ),
		];

		$wpdb->update(
			"{$wpdb->prefix}camp_management",
			$camp_data,
			[ 'id' => $camp_id ],
			[
				'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f'
			],
			[ '%d' ]
		);

		// Update pivot tables
		$this->update_pivot_data( $camp_id, 'camp_management_types_map', 'type_id', $_POST['camp_types'] ?? [] );
		$this->update_pivot_data( $camp_id, 'camp_management_weeks_map', 'week_id', $_POST['camp_weeks'] ?? [] );
		
		// Handle activities - create new ones if needed and link to camp
		$this->update_activities( $camp_id, $_POST['activity_names'] ?? '' );

		// Handle photo uploads
		$this->handle_photo_uploads( $camp_id, $camp );

		// Handle logo upload
		$this->handle_logo_upload( $camp_id, $camp );

		// Redirect to avoid resubmission
		wp_redirect( add_query_arg( 'updated', 'true', wp_get_referer() ) );
		exit;
	}

	/**
	 * Update activities - create new ones if needed and link to camp
	 */
	private function update_activities( $camp_id, $activity_names_string ) {
		global $wpdb;
		$activities_table = "{$wpdb->prefix}camp_activity_terms";
		$pivot_table = "{$wpdb->prefix}camp_management_activities_map";
		
		// Delete existing activity relationships
		$wpdb->delete( $pivot_table, [ 'camp_id' => $camp_id ], [ '%d' ] );
		
		// Parse activity names from comma-separated string
		if ( empty( $activity_names_string ) ) {
			return;
		}
		
		$activity_names = array_map( 'trim', explode( ',', $activity_names_string ) );
		$activity_names = array_filter( $activity_names ); // Remove empty values
		
		foreach ( $activity_names as $activity_name ) {
			// Check if activity exists
			$activity_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$activities_table} WHERE name = %s",
				$activity_name
			) );
			
			// Create activity if it doesn't exist
			if ( ! $activity_id ) {
				$slug = sanitize_title( $activity_name );
				$wpdb->insert(
					$activities_table,
					[
						'name' => $activity_name,
						'slug' => $slug,
						'is_active' => 1
					],
					[ '%s', '%s', '%d' ]
				);
				$activity_id = $wpdb->insert_id;
			}
			
			// Link activity to camp
			if ( $activity_id ) {
				$wpdb->insert(
					$pivot_table,
					[
						'camp_id' => $camp_id,
						'activity_id' => $activity_id
					],
					[ '%d', '%d' ]
				);
			}
		}
	}

	/**
	 * Handle photo uploads
	 */
	private function handle_photo_uploads( $camp_id, $camp ) {
		global $wpdb;
		
		// Get existing photos
		$existing_photos = ! empty( $camp['photos'] ) ? explode( ',', $camp['photos'] ) : [];
		$existing_photos = array_map( 'trim', $existing_photos );
		$existing_photos = array_filter( $existing_photos ); // Remove empty values
		$existing_photos = array_values( $existing_photos ); // Re-index array
		
		$photos_changed = false;
		
		// Handle photo removal
		if ( ! empty( $_POST['photos_to_remove'] ) ) {
			$photos_to_remove = explode( ',', $_POST['photos_to_remove'] );
			$photos_to_remove = array_map( 'trim', $photos_to_remove );
			$photos_to_remove = array_filter( $photos_to_remove );
			
			$existing_photos = array_diff( $existing_photos, $photos_to_remove );
			$existing_photos = array_values( $existing_photos ); // Re-index
			
			// Delete physical files
			foreach ( $photos_to_remove as $photo_url ) {
				$this->delete_uploaded_file( $photo_url );
			}
			
			$photos_changed = true;
		}
		
		// Handle new photo uploads
		if ( ! empty( $_FILES['photos_upload']['name'][0] ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			
			// Set custom upload directory for camp photos
			add_filter( 'upload_dir', [ $this, 'custom_upload_dir_for_camps' ] );
			
			$files = $_FILES['photos_upload'];
			$uploaded_count = 0;
			
			// Calculate total size of all files
			$total_size = 0;
			for ( $i = 0; $i < count( $files['name'] ); $i++ ) {
				if ( ! empty( $files['name'][$i] ) ) {
					$total_size += $files['size'][$i];
				}
			}
			
			// Validate total size (25MB)
			if ( $total_size > 25 * 1024 * 1024 ) {
				return; // Skip upload if total size exceeds limit
			}
			
			for ( $i = 0; $i < count( $files['name'] ); $i++ ) {
				// Check if we've reached the limit
				if ( count( $existing_photos ) + $uploaded_count >= 10 ) {
					break;
				}
				
				// Skip empty files
				if ( empty( $files['name'][$i] ) ) {
					continue;
				}
				
				// Prepare file array for wp_handle_upload
				$file = [
					'name'     => $files['name'][$i],
					'type'     => $files['type'][$i],
					'tmp_name' => $files['tmp_name'][$i],
					'error'    => $files['error'][$i],
					'size'     => $files['size'][$i],
				];
				
				// Validate file type
				$file_type = wp_check_filetype( $file['name'] );
				if ( ! in_array( $file_type['ext'], [ 'jpg', 'jpeg' ] ) ) {
					continue;
				}
				
				// Upload file
				$upload_overrides = [ 'test_form' => false ];
				$uploaded_file = wp_handle_upload( $file, $upload_overrides );
				
				if ( ! isset( $uploaded_file['error'] ) && isset( $uploaded_file['url'] ) ) {
					// Add to media library
					$attachment = [
						'post_mime_type' => $uploaded_file['type'],
						'post_title'     => sanitize_file_name( pathinfo( $uploaded_file['file'], PATHINFO_FILENAME ) ),
						'post_content'   => '',
						'post_status'    => 'inherit'
					];
					
					$attach_id = wp_insert_attachment( $attachment, $uploaded_file['file'] );
					
					// Assign to "Camps" category
					$term = get_term_by( 'slug', 'camps', 'media_category' );
					if ( $term ) {
						$result = wp_set_object_terms( $attach_id, (int) $term->term_id, 'media_category', false );
						error_log( 'Camp photo upload - Attachment ID: ' . $attach_id . ', Term ID: ' . $term->term_id . ', Result: ' . print_r( $result, true ) );
					} else {
						error_log( 'Camp photo upload - Term "camps" not found in media_category taxonomy' );
					}
					
					// Generate attachment metadata
					$attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded_file['file'] );
					wp_update_attachment_metadata( $attach_id, $attach_data );
					
					$existing_photos[] = $uploaded_file['url'];
					$uploaded_count++;
					$photos_changed = true;
				}
			}
			
			// Remove custom upload directory filter
			remove_filter( 'upload_dir', [ $this, 'custom_upload_dir_for_camps' ] );
		}
		
		// Update database with new photo list (only if there were changes)
		if ( $photos_changed ) {
			$photos_csv = implode( ', ', $existing_photos ); // Add space after comma for readability
			
			$wpdb->update(
				"{$wpdb->prefix}camp_management",
				[ 'photos' => $photos_csv ],
				[ 'id' => $camp_id ],
				[ '%s' ],
				[ '%d' ]
			);
		}
	}

	/**
	 * Handle logo upload
	 */
	private function handle_logo_upload( $camp_id, $camp ) {
		global $wpdb;
		
		// Handle new logo upload
		if ( ! empty( $_FILES['logo_upload']['name'] ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			
			// Set custom upload directory for camp logos
			add_filter( 'upload_dir', [ $this, 'custom_upload_dir_for_camps' ] );
			
			$file = $_FILES['logo_upload'];
			
			// Validate file type
			$file_type = wp_check_filetype( $file['name'] );
			if ( ! in_array( $file_type['ext'], [ 'jpg', 'jpeg', 'png', 'pdf' ] ) ) {
				remove_filter( 'upload_dir', [ $this, 'custom_upload_dir_for_camps' ] );
				return;
			}
			
			// Validate file size (5MB)
			if ( $file['size'] > 5 * 1024 * 1024 ) {
				remove_filter( 'upload_dir', [ $this, 'custom_upload_dir_for_camps' ] );
				return;
			}
			
			// Delete old logo if exists
			if ( ! empty( $camp['logo'] ) ) {
				$this->delete_uploaded_file( $camp['logo'] );
			}
			
			// Upload file
			$upload_overrides = [ 'test_form' => false ];
			$uploaded_file = wp_handle_upload( $file, $upload_overrides );
			
			// Remove custom upload directory filter
			remove_filter( 'upload_dir', [ $this, 'custom_upload_dir_for_camps' ] );
			
			if ( ! isset( $uploaded_file['error'] ) && isset( $uploaded_file['url'] ) ) {
				// Add to media library
				$attachment = [
					'post_mime_type' => $uploaded_file['type'],
					'post_title'     => sanitize_file_name( pathinfo( $uploaded_file['file'], PATHINFO_FILENAME ) ),
					'post_content'   => '',
					'post_status'    => 'inherit'
				];
				
				$attach_id = wp_insert_attachment( $attachment, $uploaded_file['file'] );
				
				// Assign to "Camps" category
				$term = get_term_by( 'slug', 'camps', 'media_category' );
				if ( $term ) {
					$result = wp_set_object_terms( $attach_id, (int) $term->term_id, 'media_category', false );
					error_log( 'Camp logo upload - Attachment ID: ' . $attach_id . ', Term ID: ' . $term->term_id . ', Result: ' . print_r( $result, true ) );
				} else {
					error_log( 'Camp logo upload - Term "camps" not found in media_category taxonomy' );
				}
				
				// Generate attachment metadata
				$attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded_file['file'] );
				wp_update_attachment_metadata( $attach_id, $attach_data );
				
				$wpdb->update(
					"{$wpdb->prefix}camp_management",
					[ 'logo' => $uploaded_file['url'] ],
					[ 'id' => $camp_id ],
					[ '%s' ],
					[ '%d' ]
				);
			}
		}
	}

	/**
	 * Delete uploaded file from server
	 */
	private function delete_uploaded_file( $file_url ) {
		if ( empty( $file_url ) ) {
			return;
		}
		
		// Convert URL to file path
		$upload_dir = wp_upload_dir();
		$file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_url );
		
		// Delete file if it exists
		if ( file_exists( $file_path ) ) {
			@unlink( $file_path );
		}
	}

	/**
	 * Custom upload directory for camp files
	 */
	public function custom_upload_dir_for_camps( $dirs ) {
		$dirs['path']   = $dirs['basedir'] . '/camps';
		$dirs['url']    = $dirs['baseurl'] . '/camps';
		$dirs['subdir'] = '/camps';
		
		// Create directory if it doesn't exist
		if ( ! file_exists( $dirs['path'] ) ) {
			wp_mkdir_p( $dirs['path'] );
		}
		
		return $dirs;
	}

	/**
	 * Update pivot table data
	 */
	private function update_pivot_data( $camp_id, $table_name, $id_column, $values ) {
		global $wpdb;
		$table = "{$wpdb->prefix}{$table_name}";

		// Delete existing relationships
		$wpdb->delete( $table, [ 'camp_id' => $camp_id ], [ '%d' ] );

		// Insert new relationships
		if ( ! empty( $values ) && is_array( $values ) ) {
			foreach ( $values as $value_id ) {
				$wpdb->insert(
					$table,
					[
						'camp_id' => $camp_id,
						$id_column => absint( $value_id )
					],
					[ '%d', '%d' ]
				);
			}
		}
	}
	
	/**
	 * Handle custom login form submission
	 */
	public function handle_custom_login() {
		if ( isset( $_POST['camp_login_submit'] ) && isset( $_POST['camp_login_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['camp_login_nonce'], 'camp_login_action' ) ) {
				return;
			}
			
			$username = sanitize_text_field( $_POST['log'] );
			$password = $_POST['pwd'];
			$remember = isset( $_POST['rememberme'] );
			
			$creds = [
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => $remember,
			];
			
			$user = wp_signon( $creds, false );
			
			if ( is_wp_error( $user ) ) {
				// Store error in session/transient
				set_transient( 'camp_login_error_' . session_id(), $user->get_error_message(), 60 );
				// Redirect back to same page with error parameter
				$redirect_url = add_query_arg( 'login', 'failed', wp_get_referer() );
				wp_safe_redirect( $redirect_url );
				exit;
			} else {
				// Successful login - redirect to same page
				wp_safe_redirect( remove_query_arg( 'login', wp_get_referer() ) );
				exit;
			}
		}
	}

	/**
	 * Render the dashboard shortcode
	 */
	public function render_dashboard( $atts ) {
		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			error_log( 'CDBS Camp Dashboard: User not logged in' );
			
			// Check for login error
			$error_message = '';
			if ( isset( $_GET['login'] ) && $_GET['login'] === 'failed' ) {
				$error_message = get_transient( 'camp_login_error_' . session_id() );
				delete_transient( 'camp_login_error_' . session_id() );
				if ( empty( $error_message ) ) {
					$error_message = 'Invalid username or password.';
				}
			}
			
			return $this->render_login_form( $error_message );
		}

		$user = wp_get_current_user();
		error_log( 'CDBS Camp Dashboard: User logged in - ID: ' . $user->ID . ', Username: ' . $user->user_login );
		error_log( 'CDBS Camp Dashboard: User roles: ' . print_r( $user->roles, true ) );

		// Check if user has 'camp' role
		if ( ! in_array( 'camp', $user->roles ) ) {
			error_log( 'CDBS Camp Dashboard: User does not have camp role' );
			return '<div class="camp-dashboard-error"><p>Access denied. This dashboard is only available for camp users. Your current roles: ' . implode( ', ', $user->roles ) . '</p></div>';
		}

		// Get camp data
		global $wpdb;
		$camp = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}camp_management WHERE user_id = %d",
			$user->ID
		), ARRAY_A );

		error_log( 'CDBS Camp Dashboard: Camp data query result: ' . ( $camp ? 'Found' : 'Not found' ) );
		if ( $camp ) {
			error_log( 'CDBS Camp Dashboard: Camp ID: ' . $camp['id'] . ', Camp Name: ' . $camp['camp_name'] );
		}

		if ( ! $camp ) {
			error_log( 'CDBS Camp Dashboard: No camp profile found for user ID ' . $user->ID );
			
			ob_start();
			?>
			<div class="camp-dashboard-error">
				<h2>Camp Profile Not Found</h2>
				<p>Your account has been created, but your camp profile is still being set up.</p>
				<p>This usually happens when:</p>
				<ul style="text-align: left; margin: 20px auto; max-width: 500px;">
					<li>Your registration form submission is still being processed</li>
					<li>An administrator needs to approve your camp listing</li>
					<li>There was an issue syncing your camp data</li>
				</ul>
				<p><strong>What to do:</strong></p>
				<p>Please contact the site administrator or try again in a few minutes.</p>
				<details style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
					<summary style="cursor: pointer; font-weight: 600;">Technical Details (for support)</summary>
					<ul style="text-align: left; margin: 10px 0; font-size: 0.9em;">
						<li>User ID: <?php echo $user->ID; ?></li>
						<li>Username: <?php echo esc_html( $user->user_login ); ?></li>
						<li>Email: <?php echo esc_html( $user->user_email ); ?></li>
						<li>Roles: <?php echo esc_html( implode( ', ', $user->roles ) ); ?></li>
						<li>Camp profile in database: Not found</li>
					</ul>
				</details>
			</div>
			<?php
			return ob_get_clean();
		}

		// Get pivot data
		$camp_types = $this->get_pivot_data( $camp['id'], 'camp_management_types_map', 'type_id' );
		$camp_weeks = $this->get_pivot_data( $camp['id'], 'camp_management_weeks_map', 'week_id' );
		$camp_activities = $this->get_pivot_data( $camp['id'], 'camp_management_activities_map', 'activity_id' );

		// Get all available options
		$all_types = $this->get_all_options( 'types' );
		$all_weeks = $this->get_all_options( 'weeks' );
		$all_activities = $this->get_all_options( 'activities' );

		ob_start();
		$this->render_dashboard_form( $camp, $camp_types, $camp_weeks, $camp_activities, $all_types, $all_weeks, $all_activities );
		return ob_get_clean();
	}

	/**
	 * Get pivot data for a camp
	 */
	private function get_pivot_data( $camp_id, $table_name, $value_column ) {
		global $wpdb;
		$table = "{$wpdb->prefix}{$table_name}";
		
		$results = $wpdb->get_col( $wpdb->prepare(
			"SELECT {$value_column} FROM {$table} WHERE camp_id = %d",
			$camp_id
		) );

		error_log( "CDBS Camp Dashboard: get_pivot_data - Table: {$table}, Camp ID: {$camp_id}, Column: {$value_column}, Count: " . count( $results ) );
		if ( $wpdb->last_error ) {
			error_log( "CDBS Camp Dashboard: Database error: " . $wpdb->last_error );
		}
		
		return $results;
	}

	/**
	 * Get all available options from lookup tables
	 */
	private function get_all_options( $table_name ) {
		global $wpdb;
		// Map plural to singular table names
		$table_map = [
			'types'      => 'type',
			'weeks'      => 'week',
			'activities' => 'activity'
		];
		
		// Use the mapped name if it exists, otherwise use the input
		$singular_name = isset( $table_map[$table_name] ) ? $table_map[$table_name] : $table_name;
		$table = "{$wpdb->prefix}camp_{$singular_name}_terms";
		
		$results = $wpdb->get_results( "SELECT id, name FROM {$table} WHERE is_active = 1 ORDER BY name ASC", ARRAY_A );
		
		error_log( "CDBS Camp Dashboard: get_all_options for {$table_name} - Singular: {$singular_name}, Table: {$table}, Count: " . count( $results ) );
		if ( $wpdb->last_error ) {
			error_log( "CDBS Camp Dashboard: Database error: " . $wpdb->last_error );
		}
		
		return $results;
	}

	/**
	 * Render login form
	 * 
	 * @param string $message Optional message to display above login form
	 */
	private function render_login_form( $message = '' ) {
		ob_start();
		?>
		<div class="camp-dashboard-login">
			<div class="login-wrapper">
				<?php if ( ! empty( $message ) ) : ?>
					<div class="login-error">
						<p><?php echo wp_kses_post( $message ); ?></p>
					</div>
				<?php endif; ?>
				<h2>Camp Login</h2>
				<p>Please log in to access your camp dashboard.</p>
				<form name="camp_login_form" id="camp_login_form" action="" method="post">
					<?php wp_nonce_field( 'camp_login_action', 'camp_login_nonce' ); ?>
					<p class="login-username">
						<label for="user_login">Username or Email</label>
						<input type="text" name="log" id="user_login" class="input" value="" size="20" autocapitalize="off" required>
					</p>
					<p class="login-password">
						<label for="user_pass">Password</label>
						<input type="password" name="pwd" id="user_pass" class="input" value="" size="20" required>
					</p>
					<p class="login-remember">
						<label>
							<input name="rememberme" type="checkbox" id="rememberme" value="forever"> Remember Me
						</label>
					</p>
					<p class="login-submit">
						<input type="submit" name="camp_login_submit" id="wp-submit" class="button button-primary" value="Log In">
					</p>
				</form>
				<p class="login-links">
					<a href="<?php echo wp_lostpassword_url( get_permalink() ); ?>">Forgot your password?</a>
				</p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the dashboard edit form
	 */
	private function render_dashboard_form( $camp, $camp_types, $camp_weeks, $camp_activities, $all_types, $all_weeks, $all_activities ) {
		?>
		<div class="camp-dashboard">
			<?php if ( isset( $_GET['updated'] ) && $_GET['updated'] === 'true' ) : ?>
				<div class="camp-dashboard-success">
					<p>✓ Your camp information has been updated successfully!</p>
				</div>
			<?php endif; ?>

			<div class="dashboard-header">
				<h1>Welcome, <?php echo esc_html( $camp['camp_directors'] ); ?>!</h1>
				<p class="dashboard-subtitle">Manage your camp profile and information</p>
				<div class="dashboard-actions">
					<a href="<?php echo wp_logout_url( get_permalink() ); ?>" class="btn-logout">Logout</a>
				</div>
			</div>

			<form method="post" action="" class="camp-edit-form" enctype="multipart/form-data">
				<?php wp_nonce_field( 'update_camp_data', 'camp_dashboard_nonce' ); ?>

				<div class="form-section">
					<h2 class="section-title">Basic Information</h2>
					
					<div class="form-row">
						<div class="form-group">
							<label for="camp_name">Camp Name <span class="required">*</span></label>
							<input type="text" id="camp_name" name="camp_name" value="<?php echo esc_attr( $camp['camp_name'] ); ?>" required>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group">
							<label for="camp_directors">Camp Director(s) <span class="required">*</span></label>
							<input type="text" id="camp_directors" name="camp_directors" value="<?php echo esc_attr( $camp['camp_directors'] ); ?>" required>
						</div>
					</div>

					<div class="form-row">
					<div class="form-group">
						<label for="about_camp">Camp Description <span class="required">*</span></label>
						<textarea id="about_camp" name="about_camp" rows="6" required><?php echo esc_textarea( $camp['about_camp'] ); ?></textarea>
					</div>
					</div>
				</div>

				<div class="form-section">
					<h2 class="section-title">Contact Information</h2>
					
					<div class="form-row">
						<div class="form-group half">
							<label for="email">Email <span class="required">*</span></label>
							<input type="email" id="email" name="email" value="<?php echo esc_attr( $camp['email'] ); ?>" required>
						</div>
						<div class="form-group half">
							<label for="phone">Phone <span class="required">*</span></label>
							<input type="tel" id="phone" name="phone" value="<?php echo esc_attr( $camp['phone'] ); ?>" required>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group">
							<label for="website">Website <span class="required">*</span></label>
							<input type="url" id="website" name="website" value="<?php echo esc_attr( $camp['website'] ); ?>" placeholder="https://" required>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group">
							<label for="address">Street Address <span class="required">*</span></label>
							<input type="text" id="address" name="address" value="<?php echo esc_attr( $camp['address'] ); ?>" required>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group third">
							<label for="city">City <span class="required">*</span></label>
							<input type="text" id="city" name="city" value="<?php echo esc_attr( $camp['city'] ); ?>" required>
						</div>
					<div class="form-group third">
						<label for="state">State <span class="required">*</span></label>
						<select id="state" name="state" required>
							<option value="">Select State</option>
							<?php
							$us_states = [
								'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
								'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia',
								'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
								'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
								'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri',
								'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
								'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
								'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
								'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
								'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming'
							];
							foreach ( $us_states as $code => $name ) {
								printf(
									'<option value="%s" %s>%s</option>',
									esc_attr( $code ),
									selected( $camp['state'], $code, false ),
									esc_html( $name )
								);
							}
							?>
						</select>
					</div>
						<div class="form-group third">
							<label for="zip">ZIP Code <span class="required">*</span></label>
							<input type="text" id="zip" name="zip" value="<?php echo esc_attr( $camp['zip'] ); ?>" required>
						</div>
					</div>
				</div>

				<div class="form-section">
					<h2 class="section-title">Camp Details</h2>
					


				<div class="form-row">
					<div class="form-group half">
						<label for="opening_day">Starting Date <span class="required">*</span></label>
						<input type="date" id="opening_day" name="opening_day" value="<?php echo esc_attr( $camp['opening_day'] ); ?>" required>
					</div>
					<div class="form-group half">
						<label for="closing_day">Ending Date <span class="required">*</span></label>
						<input type="date" id="closing_day" name="closing_day" value="<?php echo esc_attr( $camp['closing_day'] ); ?>" required>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group half">
						<label for="minprice_2026">Lowest Rate <span class="required">*</span></label>
						<div class="input-with-prefix">
							<span class="prefix">$</span>
							<input type="number" id="minprice_2026" name="minprice_2026" value="<?php echo esc_attr( $camp['minprice_2026'] ); ?>" min="0" step="0.01" placeholder="0.00" required>
						</div>
					</div>
					<div class="form-group half">
						<label for="maxprice_2026">Highest Rate <span class="required">*</span></label>
						<div class="input-with-prefix">
							<span class="prefix">$</span>
							<input type="number" id="maxprice_2026" name="maxprice_2026" value="<?php echo esc_attr( $camp['maxprice_2026'] ); ?>" min="0" step="0.01" placeholder="0.00" required>
						</div>
					</div>
				</div>
				</div>

			<div class="form-section">
				<h2 class="section-title">Camp Types <span class="required">*</span></h2>
				<p class="field-note">Select at least one camp type</p>
				<div class="checkbox-inline-list">
					<?php foreach ( $all_types as $type ) : ?>
						<label style="display:inline-block;margin:0 12px 6px 0;">
							<input type="checkbox" name="camp_types[]" value="<?php echo esc_attr( $type['id'] ); ?>" 
								<?php checked( in_array( $type['id'], $camp_types ) ); ?>
								class="required-checkbox">
							<?php echo esc_html( $type['name'] ); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>			<div class="form-section">
				<h2 class="section-title">Available Weeks / Session Length <span class="required">*</span></h2>
				<p class="field-note">Select at least one week/session</p>
				<div class="checkbox-inline-list">
					<?php foreach ( $all_weeks as $week ) : ?>
						<label style="display:inline-block;margin:0 12px 6px 0;">
							<input type="checkbox" name="camp_weeks[]" value="<?php echo esc_attr( $week['id'] ); ?>" 
								<?php checked( in_array( $week['id'], $camp_weeks ) ); ?>
								class="required-checkbox-weeks">
							<?php echo esc_html( $week['name'] ); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>			<div class="form-section">
				<h2 class="section-title">Activities Offered <span class="required">*</span></h2>
				<p class="field-note">Type an activity and press Enter or comma to add it</p>
				<ul id="activities-list" class="chip-list">
					<?php 
					// Get activity names for existing activities
					foreach ( $camp_activities as $activity_id ) {
						$activity = array_filter( $all_activities, function($a) use ($activity_id) {
							return $a['id'] == $activity_id;
						});
						if ( ! empty( $activity ) ) {
							$activity = reset( $activity );
							echo '<li class="chip" data-val="' . esc_attr( $activity['name'] ) . '"><span>' . esc_html( $activity['name'] ) . '</span><button type="button" aria-label="Remove">×</button></li>';
						}
					}
					?>
				</ul>
				<input type="hidden" id="activities-hidden" name="activity_names" value="" />
				<input type="text" id="activities-field" class="regular-text" placeholder="Type an activity and press Enter or comma" />
			</div>

				<!-- Activities tag/chip functionality script -->
				<script>
				document.addEventListener('DOMContentLoaded', function() {
					const field = document.getElementById('activities-field');
					const list = document.getElementById('activities-list');
					const hidden = document.getElementById('activities-hidden');

				function normalizeLabel(s) {
					return s.replace(/\s+/g,' ').trim();
				}

				function syncHidden() {
					const vals = [];
					list.querySelectorAll('li[data-val]').forEach(function(li) {
						vals.push(li.getAttribute('data-val'));
					});
					hidden.value = vals.join(',');
				}

				function addToken(label) {
					label = normalizeLabel(label);
					if (!label) return;
					const exists = Array.from(list.querySelectorAll('li[data-val]')).some(function(li) {
						return li.getAttribute('data-val').toLowerCase() === label.toLowerCase();
					});
					if (exists) return;
					const li = document.createElement('li');
					li.setAttribute('data-val', label);
					li.className = 'chip';
					li.innerHTML = '<span>' + label + '</span><button type="button" aria-label="Remove">×</button>';
					li.querySelector('button').addEventListener('click', function() {
						li.remove();
						syncHidden();
					});
					list.appendChild(li);
					syncHidden();
				}

				field.addEventListener('keydown', function(e) {
					if (e.key === 'Enter' || e.key === ',') {
						e.preventDefault();
						const val = field.value;
						field.value = '';
						addToken(val);
					}
				});

				// Initialize hidden field with existing values
				syncHidden();

				// Setup remove buttons for existing chips
				list.querySelectorAll('button').forEach(function(btn) {
					btn.addEventListener('click', function() {
						btn.closest('li').remove();
						syncHidden();
					});
				});
			});
			</script>
		</div>

		<!-- Photos Upload Section -->
		<div class="form-section">
			<h2 class="section-title">Photos</h2>
			<p class="section-description">Upload up to 10 camp photos (JPG/JPEG only, 25MB total for all photos)</p>
			
			<div class="photos-container">
				<?php
				$photos = ! empty( $camp['photos'] ) ? explode( ',', $camp['photos'] ) : [];
				foreach ( $photos as $index => $photo_url ) :
					if ( ! empty( trim( $photo_url ) ) ) :
				?>
					<div class="photo-item">
						<img src="<?php echo esc_url( trim( $photo_url ) ); ?>" alt="Camp photo <?php echo $index + 1; ?>">
						<button type="button" class="remove-photo" data-photo="<?php echo esc_attr( trim( $photo_url ) ); ?>">&times;</button>
					</div>
				<?php 
					endif;
				endforeach; 
				?>
			</div>
			
			<div class="form-group">
				<label for="photos_upload">Add Photos (up to <?php echo 10 - count( $photos ); ?> more)</label>
				<input type="file" id="photos_upload" name="photos_upload[]" accept="image/jpeg,image/jpg" multiple>
				<p class="field-note">JPG/JPEG only, 25MB total for all photos, up to 10 photos</p>
			</div>
			
			<input type="hidden" id="photos_to_remove" name="photos_to_remove" value="">
		</div>

		<!-- Logo Upload Section -->
		<div class="form-section">
			<h2 class="section-title">Camp Logo</h2>
			<p class="section-description">Upload your camp logo (JPG/JPEG/PNG/PDF, max 5MB)</p>
			
			<?php if ( ! empty( $camp['logo'] ) ) : ?>
				<div class="logo-preview">
					<?php if ( pathinfo( $camp['logo'], PATHINFO_EXTENSION ) === 'pdf' ) : ?>
						<div class="pdf-placeholder">
							<span>📄 PDF Logo</span>
							<a href="<?php echo esc_url( $camp['logo'] ); ?>" target="_blank">View PDF</a>
						</div>
					<?php else : ?>
						<img src="<?php echo esc_url( $camp['logo'] ); ?>" alt="Camp logo">
					<?php endif; ?>
				</div>
			<?php endif; ?>
			
			<div class="form-group">
				<label for="logo_upload"><?php echo ! empty( $camp['logo'] ) ? 'Replace Logo' : 'Upload Logo'; ?></label>
				<input type="file" id="logo_upload" name="logo_upload" accept="image/jpeg,image/jpg,image/png,application/pdf">
				<p class="field-note">JPG, JPEG, PNG, or PDF format, max 5MB</p>
			</div>
			
			<input type="hidden" id="logo_to_remove" name="logo_to_remove" value="">
		</div>

		<div class="form-section">
			<button type="submit" class="btn-submit">Update Camp Information</button>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			// File validation for photos
			document.getElementById('photos_upload').addEventListener('change', function(e) {
				const files = e.target.files;
				if (files.length > 10) {
					alert('You can only upload up to 10 photos total.');
					this.value = '';
					return;
				}
				
				let totalSize = 0;
				for (let i = 0; i < files.length; i++) {
					const file = files[i];
					// Check file type
					if (!file.type.match('image/jpeg') && !file.type.match('image/jpg')) {
						alert('Photos must be in JPG or JPEG format only.');
						this.value = '';
						return;
					}
					totalSize += file.size;
				}
				
				// Check total size (25MB = 25 * 1024 * 1024 bytes)
				if (totalSize > 25 * 1024 * 1024) {
					const totalMB = (totalSize / (1024 * 1024)).toFixed(2);
					alert('Total size of all photos must be under 25MB. Current total: ' + totalMB + 'MB.');
					this.value = '';
					return;
				}
			});

			// File validation for logo
			document.getElementById('logo_upload').addEventListener('change', function(e) {
				const file = e.target.files[0];
				if (!file) return;
				
				// Check file type
				const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
				if (!allowedTypes.includes(file.type)) {
					alert('Logo must be JPG, JPEG, PNG, or PDF format only.');
					this.value = '';
					return;
				}
				
				// Check file size (5MB = 5 * 1024 * 1024 bytes)
				if (file.size > 5 * 1024 * 1024) {
					alert('Logo must be under 5MB. Current file is ' + (file.size / (1024 * 1024)).toFixed(2) + 'MB.');
					this.value = '';
					return;
				}
			});
		});
		</script>

			<script>
			document.addEventListener('DOMContentLoaded', function() {
				const form = document.querySelector('.camp-edit-form');
				
				form.addEventListener('submit', function(e) {
					// Check if files are being uploaded
					const photosUpload = document.getElementById('photos_upload');
					const logoUpload = document.getElementById('logo_upload');
					const hasFiles = (photosUpload && photosUpload.files.length > 0) || (logoUpload && logoUpload.files.length > 0);
					
					// Check required text fields
					const requiredFields = form.querySelectorAll('[required]');
					let missingFields = [];
					
					requiredFields.forEach(function(field) {
						if (!field.value.trim()) {
							missingFields.push(field.previousElementSibling.textContent.replace('*', '').trim());
						}
					});

					// Check camp types (at least one)
					const campTypes = form.querySelectorAll('input[name="camp_types[]"]:checked');
					if (campTypes.length === 0) {
						missingFields.push('Camp Types (select at least one)');
					}

					// Check available weeks (at least one)
					const campWeeks = form.querySelectorAll('input[name="camp_weeks[]"]:checked');
					if (campWeeks.length === 0) {
						missingFields.push('Available Weeks (select at least one)');
					}

					// Check activities (at least one)
					const activitiesHidden = document.getElementById('activities-hidden');
					if (!activitiesHidden.value.trim()) {
						missingFields.push('Activities Offered (add at least one)');
					}

					if (missingFields.length > 0) {
						e.preventDefault();
						alert('Please complete the following required fields:\n\n- ' + missingFields.join('\n- '));
						return false;
					}
					
					// Show loading overlay if files are being uploaded
					if (hasFiles) {
						const overlay = document.createElement('div');
						overlay.id = 'upload-overlay';
						overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;display:flex;align-items:center;justify-content:center;';
						overlay.innerHTML = '<div style="background:white;padding:40px 60px;border-radius:8px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.3);"><div style="font-size:18px;font-weight:600;color:#333;margin-bottom:15px;">Uploading Files...</div><div style="width:200px;height:4px;background:#e0e0e0;border-radius:2px;overflow:hidden;"><div style="width:100%;height:100%;background:#497C5E;animation:loading 1.5s ease-in-out infinite;"></div></div><style>@keyframes loading{0%{transform:translateX(-100%)}50%{transform:translateX(0)}100%{transform:translateX(100%)}}</style><div style="margin-top:15px;font-size:14px;color:#666;">Please wait while your files are being uploaded...</div></div>';
						document.body.appendChild(overlay);
					}
				});
			});
			</script>
		</form>
		
		<script>
		// Photo removal functionality
		document.addEventListener('DOMContentLoaded', function() {
			const removeButtons = document.querySelectorAll('.remove-photo');
			const photosToRemoveField = document.getElementById('photos_to_remove');
			let photosToRemove = [];
			
			removeButtons.forEach(function(btn) {
				btn.addEventListener('click', function(e) {
					e.preventDefault();
					e.stopPropagation();
					const photoUrl = btn.getAttribute('data-photo');
					photosToRemove.push(photoUrl);
					photosToRemoveField.value = photosToRemove.join(',');
					btn.closest('.photo-item').remove();
				});
			});
		});
		</script>

		<?php
	}
}
