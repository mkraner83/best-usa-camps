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
		add_action( 'ninja_forms_after_submission', [ $this, 'handle_camp_submission' ] );
		add_action( 'init', [ $this, 'register_camp_role' ] );
		add_filter( 'ninja_forms_submit_data', [ $this, 'validate_unique_email' ] );
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
		// Only process our specific form
		if ( empty( $form_data['form_id'] ) || intval( $form_data['form_id'] ) !== self::CAMP_FORM_ID ) {
			return;
		}

		// Extract field values
		$fields = isset( $form_data['fields'] ) ? $form_data['fields'] : [];
		
		$email      = $this->get_field_value( $fields, 'email' );
		$camp_name  = $this->get_field_value( $fields, 'camp_name' );
		$first_name = $this->get_field_value( $fields, 'first_name' );
		$last_name  = $this->get_field_value( $fields, 'last_name' );

		// Validate required fields
		if ( empty( $email ) || ! is_email( $email ) ) {
			error_log( 'Camp user creation failed: Invalid or missing email' );
			return;
		}

		// Check if user already exists
		if ( email_exists( $email ) ) {
			error_log( "Camp user creation skipped: Email {$email} already exists" );
			$this->send_already_registered_email( $email, $username );
			return;
		}

		// Generate username from email or camp name
		$username = $this->generate_username( $email, $camp_name );

		// Create the user
		$user_id = wp_create_user( $username, wp_generate_password(), $email );

		if ( is_wp_error( $user_id ) ) {
			error_log( 'Camp user creation failed: ' . $user_id->get_error_message() );
			return;
		}

		// Set user role
		$user = new \WP_User( $user_id );
		$user->set_role( 'camp' );

		// Update user meta
		if ( $first_name ) {
			update_user_meta( $user_id, 'first_name', sanitize_text_field( $first_name ) );
		}
		if ( $last_name ) {
			update_user_meta( $user_id, 'last_name', sanitize_text_field( $last_name ) );
		}
		if ( $camp_name ) {
			update_user_meta( $user_id, 'camp_name', sanitize_text_field( $camp_name ) );
		}

		// Store Ninja Forms entry ID for reference
		if ( ! empty( $form_data['entry_id'] ) ) {
			update_user_meta( $user_id, 'ninja_forms_entry_id', intval( $form_data['entry_id'] ) );
		}

		// Send password reset email
		$this->send_welcome_email( $user_id, $username, $email );

		// Log success
		error_log( "Camp user created successfully: {$username} (ID: {$user_id})" );
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
	 */
	private function send_welcome_email( $user_id, $username, $email ) {
		// Generate password reset key
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			error_log( 'Failed to generate password reset key: ' . $key->get_error_message() );
			return;
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
		$sent = wp_mail( $email, $subject, $message );

		if ( ! $sent ) {
			error_log( "Failed to send welcome email to {$email}" );
		}
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
