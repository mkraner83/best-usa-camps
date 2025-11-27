<?php
/**
 * Ninja Forms Integration - Auto-create Camp users on form submission.
 *
 * @package CreativeDBS\CampMgmt
 */
namespace CreativeDBS\CampMgmt;

defined( 'ABSPATH' ) || exit;

class Ninja_Forms_Integration {

	/**
	 * The Ninja Forms form ID to monitor
	 * 
	 * Expected field keys in Form ID 4:
	 * - 'email' (required): User email address
	 * - 'camp_name' (optional): Camp name for username generation
	 * - 'first_name' (optional): User first name
	 * - 'last_name' (optional): User last name
	 */
	const CAMP_FORM_ID = 4;

	public function __construct() {
		// Add comprehensive hook logging to detect which hooks exist
		add_action( 'init', [ $this, 'log_available_hooks' ], 999 );
		
		// Try ALL possible Ninja Forms hooks
		add_action( 'ninja_forms_after_submission', [ $this, 'handle_camp_submission' ], 999 );
		add_action( 'nf_after_submission', [ $this, 'handle_camp_submission_alt' ], 999 );
		add_action( 'ninja_forms_process_form', [ $this, 'handle_camp_submission_v3' ], 999 );
		add_action( 'nf_process_form', [ $this, 'handle_camp_submission_alt' ], 999 );
		
		// Additional v3 hooks
		add_action( 'ninja_forms_after_processing', [ $this, 'handle_camp_submission_alt' ], 999 );
		add_action( 'ninja_forms_submission_actions', [ $this, 'handle_camp_submission_alt' ], 999 );
		
		add_action( 'init', [ $this, 'register_camp_role' ] );
		add_filter( 'ninja_forms_submit_data', [ $this, 'validate_unique_email' ] );
		
		// Log on every single action to see if ANYTHING from Ninja Forms fires
		add_action( 'all', [ $this, 'log_all_actions' ], 1 );
	}
	
	/**
	 * Log ALL actions to see which Ninja Forms hooks are firing.
	 */
	public function log_all_actions( $hook ) {
		// Only log Ninja Forms related hooks
		if ( strpos( $hook, 'ninja' ) !== false || strpos( $hook, 'nf_' ) === 0 ) {
			error_log( 'CDBS Camp: Detected Ninja Forms action: ' . $hook );
		}
	}
	
	/**
	 * Log available hooks at init.
	 */
	public function log_available_hooks() {
		error_log( 'CDBS Camp: Integration class loaded and hooks registered' );
		
		// Check if Ninja Forms is active
		if ( class_exists( 'Ninja_Forms' ) ) {
			error_log( 'CDBS Camp: Ninja Forms plugin is ACTIVE' );
			if ( defined( 'NF_PLUGIN_VERSION' ) ) {
				error_log( 'CDBS Camp: Ninja Forms version: ' . NF_PLUGIN_VERSION );
			}
		} else {
			error_log( 'CDBS Camp: WARNING - Ninja Forms plugin NOT detected!' );
		}
		
		// Check if our form exists
		global $wpdb;
		$forms_table = $wpdb->prefix . 'nf3_forms';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$forms_table}'" ) ) {
			$form_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$forms_table} WHERE id = %d", self::CAMP_FORM_ID ) );
			if ( $form_exists ) {
				error_log( 'CDBS Camp: Form ID ' . self::CAMP_FORM_ID . ' exists in database' );
			} else {
				error_log( 'CDBS Camp: WARNING - Form ID ' . self::CAMP_FORM_ID . ' NOT found in database!' );
			}
		}
	}

	/**
	 * Validate that email doesn't already exist in WordPress users.
	 *
	 * @param array $form_data Form submission data.
	 * @return array Modified form data with errors if validation fails.
	 */
	public function validate_unique_email( $form_data ) {
		// Only validate our specific form
		if ( empty( $form_data['form_id'] ) || intval( $form_data['form_id'] ) !== self::CAMP_FORM_ID ) {
			return $form_data;
		}

		// Find the email field
		$email = '';
		$email_field_id = null;
		
		if ( isset( $form_data['fields'] ) && is_array( $form_data['fields'] ) ) {
			foreach ( $form_data['fields'] as $field_id => $field ) {
				if ( isset( $field['key'] ) && $field['key'] === 'email' && ! empty( $field['value'] ) ) {
					$email = sanitize_email( $field['value'] );
					$email_field_id = $field_id;
					break;
				}
			}
		}

		// If email found, check if it exists
		if ( $email && email_exists( $email ) ) {
			// Add error to the email field
			$form_data['errors']['fields'][ $email_field_id ] = __( 'This email address is already registered. Please use a different email or log in to your existing account.', 'creativedbs-camp-mgmt' );
		}

		return $form_data;
	}

	/**
	 * Register the "Camp" user role with basic capabilities.
	 */
	public function register_camp_role() {
		if ( ! get_role( 'camp' ) ) {
			add_role(
				'camp',
				__( 'Camp', 'creativedbs-camp-mgmt' ),
				[
					'read'         => true,
					'edit_posts'   => false,
					'delete_posts' => false,
				]
			);
		}
	}

	/**
	 * Handle Ninja Forms submission and create user.
	 *
	 * @param array $form_data Form submission data.
	 */
	public function handle_camp_submission( $form_data ) {
		// Debug: Log that hook was triggered
		error_log( 'CDBS Camp: ninja_forms_after_submission hook triggered' );
		error_log( 'CDBS Camp: Form data: ' . print_r( $form_data, true ) );

		// Only process our specific form
		if ( empty( $form_data['form_id'] ) || intval( $form_data['form_id'] ) !== self::CAMP_FORM_ID ) {
			error_log( 'CDBS Camp: Skipping - not form ID ' . self::CAMP_FORM_ID . ' (got: ' . ( $form_data['form_id'] ?? 'none' ) . ')' );
			return;
		}
		
		error_log( 'CDBS Camp: Processing form submission for Form ID ' . self::CAMP_FORM_ID );

		// Extract field values
		$fields = isset( $form_data['fields'] ) ? $form_data['fields'] : [];
		
		error_log( 'CDBS Camp: Available fields: ' . print_r( array_keys( $fields ), true ) );
		
		$email      = $this->get_field_value( $fields, 'email' );
		$camp_name  = $this->get_field_value( $fields, 'camp_name' );
		$first_name = $this->get_field_value( $fields, 'first_name' );
		$last_name  = $this->get_field_value( $fields, 'last_name' );
		
		error_log( 'CDBS Camp: Extracted - Email: ' . $email . ', Camp: ' . $camp_name . ', Name: ' . $first_name . ' ' . $last_name );

		// Use shared create function
		$this->create_user_from_data( $email, $camp_name, $first_name, $last_name, $form_data );
	}

	/**
	 * Handle Ninja Forms v3+ submission (alternative hook).
	 *
	 * @param array $form_data Form data array.
	 */
	public function handle_camp_submission_v3( $form_data ) {
		error_log( 'CDBS Camp: ninja_forms_process_form hook triggered (v3)' );
		
		if ( ! isset( $form_data['form_id'] ) || intval( $form_data['form_id'] ) !== self::CAMP_FORM_ID ) {
			error_log( 'CDBS Camp: Skipping v3 hook - not form ID ' . self::CAMP_FORM_ID );
			return;
		}

		error_log( 'CDBS Camp: Processing v3 form submission' );
		
		// Extract fields from v3 structure
		$fields_data = isset( $form_data['fields'] ) ? $form_data['fields'] : [];
		$email = $camp_name = $first_name = $last_name = '';
		
		foreach ( $fields_data as $field ) {
			if ( ! isset( $field['key'] ) || ! isset( $field['value'] ) ) {
				continue;
			}
			
			switch ( $field['key'] ) {
				case 'email':
					$email = sanitize_email( $field['value'] );
					break;
				case 'camp_name':
					$camp_name = sanitize_text_field( $field['value'] );
					break;
				case 'first_name':
					$first_name = sanitize_text_field( $field['value'] );
					break;
				case 'last_name':
					$last_name = sanitize_text_field( $field['value'] );
					break;
			}
		}
		
		$this->create_user_from_data( $email, $camp_name, $first_name, $last_name, $form_data );
	}

	/**
	 * Handle alternative Ninja Forms hook.
	 *
	 * @param array $form_data Form data.
	 */
	public function handle_camp_submission_alt( $form_data ) {
		error_log( 'CDBS Camp: nf_after_submission hook triggered (alt)' );
		$this->handle_camp_submission( $form_data );
	}

	/**
	 * Create user from extracted data.
	 *
	 * @param string $email      User email.
	 * @param string $camp_name  Camp name.
	 * @param string $first_name First name.
	 * @param string $last_name  Last name.
	 * @param array  $form_data  Original form data.
	 */
	private function create_user_from_data( $email, $camp_name, $first_name, $last_name, $form_data = [] ) {
		error_log( 'CDBS Camp: Creating user - Email: ' . $email . ', Camp: ' . $camp_name );
		
		// Validate email
		if ( empty( $email ) || ! is_email( $email ) ) {
			error_log( 'CDBS Camp: Invalid or missing email' );
			return;
		}

		// Check if user exists
		if ( email_exists( $email ) ) {
			error_log( "CDBS Camp: Email {$email} already exists" );
			$this->send_already_registered_email( $email, '' );
			return;
		}

		// Generate username
		$username = $this->generate_username( $email, $camp_name );

		// Create user
		$user_id = wp_create_user( $username, wp_generate_password(), $email );

		if ( is_wp_error( $user_id ) ) {
			error_log( 'CDBS Camp: User creation failed: ' . $user_id->get_error_message() );
			return;
		}

		// Set role
		$user = new \WP_User( $user_id );
		$user->set_role( 'camp' );

		// Update meta
		if ( $first_name ) {
			update_user_meta( $user_id, 'first_name', $first_name );
		}
		if ( $last_name ) {
			update_user_meta( $user_id, 'last_name', $last_name );
		}
		if ( $camp_name ) {
			update_user_meta( $user_id, 'camp_name', $camp_name );
		}

		// Store entry ID if available
		$entry_id = null;
		if ( ! empty( $form_data['entry_id'] ) ) {
			$entry_id = intval( $form_data['entry_id'] );
			update_user_meta( $user_id, 'ninja_forms_entry_id', $entry_id );
		}

		// Create camp entry in camp_management table
		$this->create_camp_entry( $email, $camp_name, $first_name, $last_name, $entry_id, $form_data );

		// Send welcome email
		error_log( "CDBS Camp: Attempting to send welcome email to {$email}" );
		$email_sent = $this->send_welcome_email( $user_id, $username, $email );

		error_log( "CDBS Camp: User created successfully: {$username} (ID: {$user_id}), Email sent: " . ( $email_sent ? 'YES' : 'NO' ) );
	}

	/**
	 * Create camp entry in camp_management table.
	 *
	 * @param string $email      User email.
	 * @param string $camp_name  Camp name.
	 * @param string $first_name First name.
	 * @param string $last_name  Last name.
	 * @param int    $entry_id   Ninja Forms entry ID.
	 * @param array  $form_data  Full form data for extracting additional fields.
	 */
	private function create_camp_entry( $email, $camp_name, $first_name, $last_name, $entry_id = null, $form_data = [] ) {
		global $wpdb;
		$table = $wpdb->prefix . 'camp_management';

		// Generate unique key
		$unique_key = md5( uniqid( 'camp_', true ) );

		// Extract fields from form data
		$fields = isset( $form_data['fields'] ) ? $form_data['fields'] : [];
		
		// Map Ninja Forms field labels/keys to values
		$opening_day    = $this->get_field_by_label( $fields, 'Camp Opening Day' );
		$closing_day    = $this->get_field_by_label( $fields, 'Camp Closing Day' );
		$camp_type      = $this->get_field_by_label( $fields, 'Camp Type' ); // checkbox list
		$duration       = $this->get_field_by_label( $fields, 'Duration' ); // checkbox list
		$lowest_rate    = $this->get_field_by_label( $fields, 'Lowest Rate' );
		$highest_rate   = $this->get_field_by_label( $fields, 'Highest Rate' );
		$activities     = $this->get_field_by_label( $fields, 'Activities' );
		$website        = $this->get_field_by_label( $fields, 'Website URL' );
		$phone          = $this->get_field_by_label( $fields, 'Phone' );
		$camp_director  = $this->get_field_by_label( $fields, 'Camp Director' );
		$address        = $this->get_field_by_label( $fields, 'Address' );
		$city           = $this->get_field_by_label( $fields, 'City' );
		$state          = $this->get_field_by_label( $fields, 'State' );
		$zip            = $this->get_field_by_label( $fields, 'Zip' );
		$about_camp     = $this->get_field_by_label( $fields, 'About Camp' );
		$photos         = $this->get_field_by_label( $fields, 'Photos Upload' ); // file upload

		// Prepare camp data for database insertion
		$camp_data = [
			'ninja_entry_id' => $entry_id,
			'unique_key'     => $unique_key,
			'camp_name'      => sanitize_text_field( $camp_name ),
			'opening_day'    => ! empty( $opening_day ) ? sanitize_text_field( $opening_day ) : null,
			'closing_day'    => ! empty( $closing_day ) ? sanitize_text_field( $closing_day ) : null,
			'minprice_2026'  => ! empty( $lowest_rate ) ? floatval( $lowest_rate ) : null,
			'maxprice_2026'  => ! empty( $highest_rate ) ? floatval( $highest_rate ) : null,
			'activities'     => sanitize_textarea_field( $activities ),
			'email'          => sanitize_email( $email ),
			'phone'          => sanitize_text_field( $phone ),
			'website'        => esc_url_raw( $website ),
			'camp_directors' => sanitize_text_field( $camp_director ?: ( $first_name . ' ' . $last_name ) ),
			'address'        => sanitize_text_field( $address ),
			'city'           => sanitize_text_field( $city ),
			'state'          => sanitize_text_field( $state ),
			'zip'            => sanitize_text_field( $zip ),
			'about_camp'     => sanitize_textarea_field( $about_camp ),
			'photos'         => sanitize_text_field( $photos ), // Store file path/URL
			'approved'       => 0, // Not approved by default
			'created_at'     => current_time( 'mysql' ),
			'updated_at'     => current_time( 'mysql' ),
		];

		// Log the data being inserted for debugging
		error_log( 'CDBS Camp: Preparing to insert camp data: ' . print_r( $camp_data, true ) );

		// Insert into database
		$inserted = $wpdb->insert( $table, $camp_data );

		if ( $inserted ) {
			$camp_id = $wpdb->insert_id;
			error_log( "CDBS Camp: Camp entry created in database (ID: {$camp_id}, Entry ID: {$entry_id})" );
		} else {
			error_log( "CDBS Camp: FAILED to create camp entry in database: " . $wpdb->last_error );
		}
	}

	/**
	 * Get field value from Ninja Forms submission by field key.
	 *
	 * @param array  $fields Ninja Forms fields array.
	 * @param string $key    Field key to search for.
	 * @return string Field value or empty string.
	 */
	private function get_field_value( $fields, $key ) {
		foreach ( $fields as $field ) {
			if ( isset( $field['key'] ) && $field['key'] === $key && isset( $field['value'] ) ) {
				return sanitize_text_field( $field['value'] );
			}
		}
		return '';
	}

	/**
	 * Get field value from Ninja Forms submission by field label.
	 *
	 * @param array  $fields Ninja Forms fields array.
	 * @param string $label  Field label to search for (case-insensitive).
	 * @return string Field value or empty string. For arrays (checkboxes), returns comma-separated values.
	 */
	private function get_field_by_label( $fields, $label ) {
		foreach ( $fields as $field ) {
			// Check if label matches (case-insensitive)
			if ( isset( $field['label'] ) && strcasecmp( trim( $field['label'] ), trim( $label ) ) === 0 ) {
				if ( isset( $field['value'] ) ) {
					// Handle array values (checkbox lists)
					if ( is_array( $field['value'] ) ) {
						return implode( ', ', array_map( 'sanitize_text_field', $field['value'] ) );
					}
					return $field['value'];
				}
			}
		}
		return '';
	}

	/**
	 * Generate a unique username from email or camp name.
	 *
	 * @param string $email     User email.
	 * @param string $camp_name Camp name.
	 * @return string Unique username.
	 */
	private function generate_username( $email, $camp_name = '' ) {
		// Try camp name first
		if ( ! empty( $camp_name ) ) {
			$username = sanitize_user( strtolower( str_replace( ' ', '_', $camp_name ) ), true );
		} else {
			// Fall back to email prefix
			$username = sanitize_user( strtolower( explode( '@', $email )[0] ), true );
		}

		// Ensure uniqueness
		$base_username = $username;
		$counter = 1;
		while ( username_exists( $username ) ) {
			$username = $base_username . '_' . $counter;
			$counter++;
		}

		return $username;
	}

	/**
	 * Send welcome email with password reset link.
	 *
	 * @param int    $user_id  User ID.
	 * @param string $username Username.
	 * @param string $email    User email.
	 * @return bool Whether email was sent successfully.
	 */
	private function send_welcome_email( $user_id, $username, $email ) {
		// Generate password reset key
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			error_log( 'CDBS Camp: Failed to get user data for ID ' . $user_id );
			return false;
		}

		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			error_log( 'CDBS Camp: Failed to generate password reset key: ' . $key->get_error_message() );
			return false;
		}

		// Build reset link
		$reset_url = network_site_url( "wp-login.php?action=rp&key={$key}&login=" . rawurlencode( $username ), 'login' );

		// Email subject
		$subject = sprintf(
			/* translators: %s: Site name */
			__( '[%s] Your Camp Account Has Been Created', 'creativedbs-camp-mgmt' ),
			wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES )
		);

		// Email message
		$message = sprintf(
			/* translators: 1: Username, 2: Site name, 3: Password reset URL */
			__( 'Hello,

Your camp account has been created on %2$s.

Username: %1$s

To set your password, please click the link below:
%3$s

If you did not request this account, please contact the site administrator.

Thank you!', 'creativedbs-camp-mgmt' ),
			$username,
			wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
			$reset_url
		);

		// Send email
		error_log( 'CDBS Camp: Sending email to: ' . $email );
		error_log( 'CDBS Camp: Email subject: ' . $subject );
		error_log( 'CDBS Camp: Reset URL: ' . $reset_url );
		
		$sent = wp_mail( $email, $subject, $message );

		if ( ! $sent ) {
			error_log( "CDBS Camp: FAILED to send welcome email to {$email}" );
		} else {
			error_log( "CDBS Camp: Successfully sent welcome email to {$email}" );
		}
		
		return $sent;
	}

	/**
	 * Send email to user who tried to register with existing email.
	 *
	 * @param string $email    User email.
	 * @param string $username Attempted username.
	 */
	private function send_already_registered_email( $email, $username ) {
		$login_url = wp_login_url();
		$reset_url = wp_lostpassword_url();

		// Email subject
		$subject = sprintf(
			/* translators: %s: Site name */
			__( '[%s] Account Already Exists', 'creativedbs-camp-mgmt' ),
			wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES )
		);

		// Email message
		$message = sprintf(
			/* translators: 1: Site name, 2: Login URL, 3: Reset password URL */
			__( 'Hello,

You attempted to create a camp account on %1$s, but an account with this email address already exists.

To log in to your existing account:
%2$s

If you forgot your password, you can reset it here:
%3$s

If you believe this is an error, please contact the site administrator.

Thank you!', 'creativedbs-camp-mgmt' ),
			wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
			$login_url,
			$reset_url
		);

		// Send email
		$sent = wp_mail( $email, $subject, $message );

		if ( ! $sent ) {
			error_log( "Failed to send 'already registered' email to {$email}" );
		}
	}
}
