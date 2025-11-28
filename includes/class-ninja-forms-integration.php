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

	// Store created user credentials for use in email merge tags
	private static $created_username = '';
	private static $created_password_reset_url = '';

	public function __construct() {
		// Use only one reliable hook that fires AFTER file uploads complete
		// Priority 999 ensures it runs after all file processing is done
		add_action( 'ninja_forms_after_submission', [ $this, 'handle_camp_submission' ], 999 );
		
		add_action( 'init', [ $this, 'register_camp_role' ] );
		add_filter( 'ninja_forms_submit_data', [ $this, 'validate_unique_email' ], 10 );
		
		// Add filter to replace custom shortcodes in Ninja Forms emails
		add_filter( 'ninja_forms_action_email_message', [ $this, 'replace_custom_shortcodes' ], 10, 3 );
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
	 * Replace custom shortcodes in Ninja Forms email message.
	 *
	 * @param string $message Email message.
	 * @param array  $data    Form data.
	 * @param array  $action_settings Action settings.
	 * @return string Modified message with shortcodes replaced.
	 */
	public function replace_custom_shortcodes( $message, $data, $action_settings ) {
		// Replace our custom shortcodes with stored values
		$message = str_replace( '{user:username}', self::$created_username, $message );
		$message = str_replace( '{user:password_reset_url}', self::$created_password_reset_url, $message );
		
		return $message;
	}

	/**
	 * Handle Ninja Forms submission and create user.
	 *
	 * @param array $form_data Form submission data.
	 */
	public function handle_camp_submission( $form_data ) {
		// Debug: Log that hook was triggered
		error_log( 'CDBS Camp: ninja_forms_after_submission hook triggered' );

		// Only process our specific form
		if ( empty( $form_data['form_id'] ) || intval( $form_data['form_id'] ) !== self::CAMP_FORM_ID ) {
			error_log( 'CDBS Camp: Skipping - not form ID ' . self::CAMP_FORM_ID . ' (got: ' . ( $form_data['form_id'] ?? 'none' ) . ')' );
			return;
		}
		
		error_log( 'CDBS Camp: Processing form submission for Form ID ' . self::CAMP_FORM_ID );

		// Extract field values
		$fields = isset( $form_data['fields'] ) ? $form_data['fields'] : [];
		
		error_log( 'CDBS Camp: Available fields: ' . print_r( array_keys( $fields ), true ) );
		
		$email         = $this->get_field_by_label( $fields, 'Email' );
		$camp_name     = $this->get_field_by_label( $fields, 'Camp Name' );
		$camp_director = $this->get_field_by_label( $fields, 'Camp Director' );
		
		// Split Camp Director into first and last name
		$first_name = '';
		$last_name  = '';
		if ( ! empty( $camp_director ) ) {
			$name_parts = explode( ' ', trim( $camp_director ), 2 );
			$first_name = $name_parts[0];
			$last_name  = isset( $name_parts[1] ) ? $name_parts[1] : '';
		}
		
		error_log( 'CDBS Camp: Extracted - Email: ' . $email . ', Camp: ' . $camp_name . ', Director: ' . $camp_director . ' (First: ' . $first_name . ', Last: ' . $last_name . ')' );

		// Use shared create function
		$this->create_user_from_data( $email, $camp_name, $first_name, $last_name, $form_data );
	}	/**
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

		// Generate password reset key for email
		$reset_key = get_password_reset_key( $user );
		if ( ! is_wp_error( $reset_key ) ) {
			self::$created_password_reset_url = network_site_url( "wp-login.php?action=rp&key={$reset_key}&login=" . rawurlencode( $username ), 'login' );
		}
		
		// Store username for email shortcode
		self::$created_username = $username;
		
		error_log( 'CDBS Camp: Stored credentials - Username: ' . self::$created_username );
		error_log( 'CDBS Camp: Stored credentials - Reset URL: ' . self::$created_password_reset_url );

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

		// Note: Credentials email sent via Ninja Forms Email Action using {user:username} and {user:password_reset_url} shortcodes
		error_log( "CDBS Camp: User created successfully: {$username} (ID: {$user_id})" );
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

		// Convert dates to MySQL format (Y-m-d)
		$opening_day_formatted = $this->convert_to_mysql_date( $opening_day );
		$closing_day_formatted = $this->convert_to_mysql_date( $closing_day );

		// Parse currency values (remove $ and commas)
		$min_price = $this->parse_currency( $lowest_rate );
		$max_price = $this->parse_currency( $highest_rate );

		// Prepare camp data for database insertion
		$camp_data = [
			'ninja_entry_id' => $entry_id,
			'unique_key'     => $unique_key,
			'camp_name'      => sanitize_text_field( $camp_name ),
			'opening_day'    => $opening_day_formatted,
			'closing_day'    => $closing_day_formatted,
			'minprice_2026'  => $min_price,
			'maxprice_2026'  => $max_price,
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

			// Link Camp Type terms to pivot table
			if ( ! empty( $camp_type ) ) {
				$this->link_camp_types( $camp_id, $camp_type );
			}

			// Link Duration (weeks) to pivot table
			if ( ! empty( $duration ) ) {
				$this->link_camp_weeks( $camp_id, $duration );
			}

			// Link Activities to pivot table
			if ( ! empty( $activities ) ) {
				$this->link_camp_activities( $camp_id, $activities );
			}
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
	 * @return string Field value or empty string. For arrays (checkboxes/files), returns comma-separated values.
	 */
	private function get_field_by_label( $fields, $label ) {
		foreach ( $fields as $field ) {
			// Check if label matches (case-insensitive)
			if ( isset( $field['label'] ) && strcasecmp( trim( $field['label'] ), trim( $label ) ) === 0 ) {
				// Handle file upload fields - check files array first
				if ( isset( $field['type'] ) && $field['type'] === 'file_upload' ) {
					$urls = [];
					
					// Method 1: Check 'files' array with data.file_url structure
					if ( isset( $field['files'] ) && is_array( $field['files'] ) ) {
						foreach ( $field['files'] as $file ) {
							if ( is_array( $file ) && isset( $file['data']['file_url'] ) ) {
								$urls[] = $file['data']['file_url'];
							}
						}
					}
					
					// Method 2: Check 'value' array with numeric keys containing URLs
					if ( empty( $urls ) && isset( $field['value'] ) && is_array( $field['value'] ) ) {
						foreach ( $field['value'] as $key => $file ) {
							if ( is_string( $file ) && filter_var( $file, FILTER_VALIDATE_URL ) ) {
								$urls[] = $file;
							} elseif ( is_array( $file ) && isset( $file['url'] ) ) {
								$urls[] = $file['url'];
							}
						}
					}
					
					if ( ! empty( $urls ) ) {
						error_log( 'CDBS Camp: File upload field "' . $label . '" URLs extracted: ' . implode( ', ', $urls ) );
						return implode( ', ', $urls );
					}
					return '';
				}
				
				// Handle regular fields
				if ( isset( $field['value'] ) ) {
					// Handle checkbox lists
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
	 * Send credentials email with password reset link.
	 * 
	 * NOTE: This is the second email sent (after Ninja Forms confirmation).
	 * Contains username and password reset URL that only plugin can generate.
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
		$site_url  = 'https://bestusacamps.com';
		$site_name = 'Best USA Summer Camps';

		// Email subject (different from Ninja Forms confirmation email)
		$subject = sprintf( '[%s] Your Account Credentials - Set Your Password', $site_name );

		// HTML Email message
		$message = '
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
		.container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
		.header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 30px 20px; text-align: center; }
		.header h1 { margin: 0; font-size: 28px; font-weight: bold; }
		.content { padding: 30px 20px; }
		.content h2 { color: #667eea; margin-top: 0; font-size: 22px; }
		.content p { margin: 15px 0; font-size: 16px; }
		.credentials-box { background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 4px; }
		.credentials-box strong { color: #667eea; }
		.button { display: inline-block; padding: 14px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; text-align: center; }
		.button:hover { opacity: 0.9; }
		.footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #666; border-top: 1px solid #e9ecef; }
		.footer a { color: #667eea; text-decoration: none; }
		.divider { height: 1px; background: #e9ecef; margin: 25px 0; }
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<h1>🏕️ Welcome to Best USA Summer Camps!</h1>
		</div>
		<div class="content">
			<h2>Your Camp Profile Has Been Created</h2>
			<p>Hello!</p>
			<p>We\'re excited to let you know that your camp profile has been successfully created on <strong>Best USA Summer Camps</strong>. You can now manage your camp information and connect with families searching for the perfect summer camp experience.</p>
			
			<div class="credentials-box">
				<p style="margin: 0;"><strong>Username:</strong> ' . esc_html( $username ) . '</p>
				<p style="margin: 10px 0 0 0;"><strong>Email:</strong> ' . esc_html( $email ) . '</p>
			</div>
			
			<p><strong>Next Step: Set Your Password</strong></p>
			<p>To secure your account and access your camp dashboard, please click the button below to create your password:</p>
			
			<div style="text-align: center;">
				<a href="' . esc_url( $reset_url ) . '" class="button">Set My Password</a>
			</div>
			
			<p style="font-size: 14px; color: #666; margin-top: 25px;">If the button doesn\'t work, copy and paste this link into your browser:<br>
			<a href="' . esc_url( $reset_url ) . '" style="color: #667eea; word-break: break-all;">' . esc_url( $reset_url ) . '</a></p>
			
			<div class="divider"></div>
			
			<p style="font-size: 14px; color: #666;"><strong>Need Help?</strong><br>
			If you did not create this account or have any questions, please contact our support team. We\'re here to help!</p>
		</div>
		<div class="footer">
			<p style="margin: 0 0 10px 0;"><strong>Best USA Summer Camps</strong></p>
			<p style="margin: 0 0 10px 0;"><a href="' . esc_url( $site_url ) . '">' . esc_html( $site_url ) . '</a></p>
			<p style="margin: 0; font-size: 12px; color: #999;">© ' . date( 'Y' ) . ' Best USA Summer Camps. All rights reserved.</p>
		</div>
	</div>
</body>
</html>';

		// Set email headers for HTML
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Best USA Summer Camps <noreply@bestusacamps.com>',
		);

		// Send email
		error_log( '========================================' );
		error_log( 'CDBS Camp: ATTEMPTING TO SEND EMAIL' );
		error_log( 'To: ' . $email );
		error_log( 'Subject: ' . $subject );
		error_log( 'Username: ' . $username );
		error_log( 'Reset URL: ' . $reset_url );
		error_log( 'Headers: ' . print_r( $headers, true ) );
		error_log( '========================================' );
		
		// Hook to capture wp_mail errors
		add_action( 'wp_mail_failed', function( $wp_error ) {
			error_log( 'CDBS Camp: wp_mail ERROR - ' . $wp_error->get_error_message() );
		} );
		
		$sent = wp_mail( $email, $subject, $message, $headers );

		if ( ! $sent ) {
			error_log( "CDBS Camp: ❌ FAILED to send welcome email to {$email}" );
			// Check if wp_mail is properly configured
			$phpmailer_object = null;
			add_action( 'phpmailer_init', function( $phpmailer ) use ( &$phpmailer_object ) {
				$phpmailer_object = $phpmailer;
			} );
			error_log( 'CDBS Camp: PHP mail() function available: ' . ( function_exists( 'mail' ) ? 'YES' : 'NO' ) );
		} else {
			error_log( "CDBS Camp: ✅ Successfully sent welcome email to {$email}" );
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

	/**
	 * Convert various date formats to MySQL DATE format (Y-m-d).
	 *
	 * @param string $date_string Date in various formats (m/d/Y, Y-m-d, etc.).
	 * @return string|null MySQL formatted date or null if invalid.
	 */
	private function convert_to_mysql_date( $date_string ) {
		if ( empty( $date_string ) ) {
			return null;
		}

		// Try to parse the date
		$timestamp = strtotime( $date_string );
		if ( $timestamp === false ) {
			error_log( "CDBS Camp: Invalid date format: {$date_string}" );
			return null;
		}

		return date( 'Y-m-d', $timestamp );
	}

	/**
	 * Parse currency value (remove $, commas, etc.).
	 *
	 * @param string $currency Currency string like "$1,234.56".
	 * @return float|null Numeric value or null if empty.
	 */
	private function parse_currency( $currency ) {
		if ( empty( $currency ) ) {
			return null;
		}

		// Remove currency symbols, commas, spaces
		$clean = preg_replace( '/[^0-9.]/', '', $currency );
		$value = floatval( $clean );

		return $value > 0 ? $value : null;
	}

	/**
	 * Link camp types to the pivot table.
	 *
	 * @param int    $camp_id   Camp ID.
	 * @param string $types_csv Comma-separated camp types.
	 */
	private function link_camp_types( $camp_id, $types_csv ) {
		global $wpdb;
		$type_terms_table = $wpdb->prefix . 'camp_type_terms';
		$pivot_table = $wpdb->prefix . 'camp_management_types_map';

		// Split by comma and clean
		$types = array_filter( array_map( 'trim', explode( ',', $types_csv ) ) );

		foreach ( $types as $type_name ) {
			// Find or create the type term
			$type_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$type_terms_table} WHERE name=%s OR slug=%s",
				$type_name,
				sanitize_title( $type_name )
			) );

			if ( ! $type_id ) {
				// Create new type term
				$wpdb->insert( $type_terms_table, [
					'name'       => $type_name,
					'slug'       => sanitize_title( $type_name ),
					'is_active'  => 1,
					'created_at' => current_time( 'mysql' ),
					'updated_at' => current_time( 'mysql' ),
				] );
				$type_id = $wpdb->insert_id;
				error_log( "CDBS Camp: Created new camp type: {$type_name} (ID: {$type_id})" );
			}

			// Link to camp
			$wpdb->insert( $pivot_table, [
				'camp_id' => $camp_id,
				'type_id' => intval( $type_id ),
			] );
			error_log( "CDBS Camp: Linked camp {$camp_id} to type {$type_name} (ID: {$type_id})" );
		}
	}

	/**
	 * Link camp weeks/duration to the pivot table.
	 *
	 * @param int    $camp_id   Camp ID.
	 * @param string $weeks_csv Comma-separated week durations.
	 */
	private function link_camp_weeks( $camp_id, $weeks_csv ) {
		global $wpdb;
		$week_terms_table = $wpdb->prefix . 'camp_week_terms';
		$pivot_table = $wpdb->prefix . 'camp_management_weeks_map';

		// Split by comma and clean
		$weeks = array_filter( array_map( 'trim', explode( ',', $weeks_csv ) ) );

		foreach ( $weeks as $week_name ) {
			// Find or create the week term
			$week_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$week_terms_table} WHERE name=%s OR slug=%s",
				$week_name,
				sanitize_title( $week_name )
			) );

			if ( ! $week_id ) {
				// Create new week term
				$wpdb->insert( $week_terms_table, [
					'name'       => $week_name,
					'slug'       => sanitize_title( $week_name ),
					'is_active'  => 1,
					'created_at' => current_time( 'mysql' ),
					'updated_at' => current_time( 'mysql' ),
				] );
				$week_id = $wpdb->insert_id;
				error_log( "CDBS Camp: Created new week duration: {$week_name} (ID: {$week_id})" );
			}

			// Link to camp
			$wpdb->insert( $pivot_table, [
				'camp_id' => $camp_id,
				'week_id' => intval( $week_id ),
			] );
			error_log( "CDBS Camp: Linked camp {$camp_id} to week {$week_name} (ID: {$week_id})" );
		}
	}

	/**
	 * Link camp activities to the pivot table.
	 *
	 * @param int    $camp_id       Camp ID.
	 * @param string $activities_csv Comma-separated activities.
	 */
	private function link_camp_activities( $camp_id, $activities_csv ) {
		global $wpdb;
		$activity_terms_table = $wpdb->prefix . 'camp_activity_terms';
		$pivot_table = $wpdb->prefix . 'camp_management_activities_map';

		// Split by comma and clean
		$activities = array_filter( array_map( 'trim', explode( ',', $activities_csv ) ) );

		foreach ( $activities as $activity_name ) {
			// Find or create the activity term
			$activity_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$activity_terms_table} WHERE name=%s OR slug=%s",
				$activity_name,
				sanitize_title( $activity_name )
			) );

			if ( ! $activity_id ) {
				// Create new activity term
				$wpdb->insert( $activity_terms_table, [
					'name'       => $activity_name,
					'slug'       => sanitize_title( $activity_name ),
					'is_active'  => 1,
					'created_at' => current_time( 'mysql' ),
					'updated_at' => current_time( 'mysql' ),
				] );
				$activity_id = $wpdb->insert_id;
				error_log( "CDBS Camp: Created new activity: {$activity_name} (ID: {$activity_id})" );
			}

			// Link to camp
			$wpdb->insert( $pivot_table, [
				'camp_id'     => $camp_id,
				'activity_id' => intval( $activity_id ),
			] );
			error_log( "CDBS Camp: Linked camp {$camp_id} to activity {$activity_name} (ID: {$activity_id})" );
		}
	}
}
