<?php
/**
 * Camp Sign-Up Form (Custom, replaces Ninja Forms)
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt\PublicArea;

defined( 'ABSPATH' ) || exit;


use CreativeDBS\CampMgmt\DB;

class Camp_Signup_Form {
	public function __construct() {
		add_shortcode( 'camp_signup_form', [ $this, 'render_form' ] );
		add_action( 'init', [ $this, 'handle_submission' ] );
		add_action( 'init', [ $this, 'register_camp_role' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}

	/**
	 * Enqueue signup form styles and scripts
	 */
	public function enqueue_styles() {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'camp_signup_form' ) ) {
			// Styles
			wp_enqueue_style(
				'camp-signup-form',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/camp-signup-form.css',
				[],
				CDBS_CAMP_VERSION
			);
			
			// Flatpickr for better date picker
			wp_enqueue_style(
				'flatpickr',
				'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
				[],
				'4.6.13'
			);
			
			wp_enqueue_script(
				'flatpickr',
				'https://cdn.jsdelivr.net/npm/flatpickr',
				[],
				'4.6.13',
				true
			);
			
			// Form logic script
			wp_enqueue_script(
				'camp-signup-form-logic',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/camp-signup-form.js',
				['flatpickr'],
				CDBS_CAMP_VERSION,
				true
			);
		}
	}

	/**
	 * Register the "Camp" user role
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

	public function render_form() {
		global $wpdb;
		// Fetch active camp types
		$types = $wpdb->get_results( "SELECT id, name FROM " . DB::table_type_terms() . " WHERE is_active = 1 ORDER BY sort_order ASC, name ASC" );
		// Fetch active durations/weeks
		$weeks = $wpdb->get_results( "SELECT id, name FROM " . DB::table_week_terms() . " WHERE is_active = 1 ORDER BY sort_order ASC, name ASC" );
		// Fetch active activities (for suggestions)
		$activities = $wpdb->get_results( "SELECT id, name FROM " . DB::table_activity_terms() . " WHERE is_active = 1 ORDER BY sort_order ASC, name ASC" );

		ob_start();
		?>
		<form method="post" enctype="multipart/form-data" class="camp-signup-form">
			<?php wp_nonce_field( 'camp_signup_form_action', 'camp_signup_nonce' ); ?>
			<div class="full-width"><label>Camp Name</label><input type="text" name="camp_name" required></div>
			<div><label>Camp Opening Day</label><input type="text" id="opening_day" name="opening_day" placeholder="Select date" required readonly></div>
			<div><label>Camp Closing Day</label><input type="text" id="closing_day" name="closing_day" placeholder="Select date" required readonly></div>
			<div><label>Lowest Rate</label><input type="text" id="minprice_2026" name="minprice_2026" placeholder="$0.00" required></div>
			<div><label>Highest Rate</label><input type="text" id="maxprice_2026" name="maxprice_2026" placeholder="$0.00" required></div>
			<div><label>Email</label><input type="email" id="email" name="email" required></div>
			<div><label>Phone</label><input type="text" name="phone" required></div>
			<div><label>Website URL</label><input type="url" id="website" name="website" placeholder="https://" required></div>
			<div><label>Camp Director</label><input type="text" name="camp_directors" required></div>
			<div><label>Address</label><input type="text" name="address" required></div>
			<div><label>City</label><input type="text" name="city" required></div>
			<div><label>Zip</label><input type="text" name="zip" required></div>
			<div><label>State</label><select name="state" required>
				<option value="">Select State</option>
				<option value="AL">Alabama</option>
				<option value="AK">Alaska</option>
				<option value="AZ">Arizona</option>
				<option value="AR">Arkansas</option>
				<option value="CA">California</option>
				<option value="CO">Colorado</option>
				<option value="CT">Connecticut</option>
				<option value="DE">Delaware</option>
				<option value="FL">Florida</option>
				<option value="GA">Georgia</option>
				<option value="HI">Hawaii</option>
				<option value="ID">Idaho</option>
				<option value="IL">Illinois</option>
				<option value="IN">Indiana</option>
				<option value="IA">Iowa</option>
				<option value="KS">Kansas</option>
				<option value="KY">Kentucky</option>
				<option value="LA">Louisiana</option>
				<option value="ME">Maine</option>
				<option value="MD">Maryland</option>
				<option value="MA">Massachusetts</option>
				<option value="MI">Michigan</option>
				<option value="MN">Minnesota</option>
				<option value="MS">Mississippi</option>
				<option value="MO">Missouri</option>
				<option value="MT">Montana</option>
				<option value="NE">Nebraska</option>
				<option value="NV">Nevada</option>
				<option value="NH">New Hampshire</option>
				<option value="NJ">New Jersey</option>
				<option value="NM">New Mexico</option>
				<option value="NY">New York</option>
				<option value="NC">North Carolina</option>
				<option value="ND">North Dakota</option>
				<option value="OH">Ohio</option>
				<option value="OK">Oklahoma</option>
				<option value="OR">Oregon</option>
				<option value="PA">Pennsylvania</option>
				<option value="RI">Rhode Island</option>
				<option value="SC">South Carolina</option>
				<option value="SD">South Dakota</option>
				<option value="TN">Tennessee</option>
				<option value="TX">Texas</option>
				<option value="UT">Utah</option>
				<option value="VT">Vermont</option>
				<option value="VA">Virginia</option>
				<option value="WA">Washington</option>
				<option value="WV">West Virginia</option>
				<option value="WI">Wisconsin</option>
				<option value="WY">Wyoming</option>
			</select></div>
			<div class="full-width"><label>About Camp</label><textarea name="about_camp" id="about_camp_signup" required></textarea>
				<p class="description" style="margin-top:5px;color:#666;font-size:12px;">
					<span id="word-count-signup">0</span>/300 words
				</p>
			</div>
			<div class="full-width"><label class="required-label">Camp Type</label></div>
			<div class="full-width checkbox-group">
				<?php if ( $types ) foreach ( $types as $type ) : ?>
					<label><input type="checkbox" name="type_ids[]" value="<?php echo esc_attr($type->id); ?>"> <?php echo esc_html($type->name); ?></label>
				<?php endforeach; ?>
			</div>
			<div class="full-width"><label class="required-label">Duration</label></div>
			<div class="full-width checkbox-group">
				<?php if ( $weeks ) foreach ( $weeks as $week ) : ?>
					<label><input type="checkbox" name="week_ids[]" value="<?php echo esc_attr($week->id); ?>"> <?php echo esc_html($week->name); ?></label>
				<?php endforeach; ?>
			</div>
			<div class="full-width"><label>Activities</label>
				<input type="text" name="activities" required placeholder="Comma-separated (e.g. Dance, Swimming)" list="activity-list">
				<datalist id="activity-list">
					<?php if ( $activities ) foreach ( $activities as $act ) : ?>
						<option value="<?php echo esc_attr($act->name); ?>">
					<?php endforeach; ?>
				</datalist>
			</div>
			<div class="full-width"><label>Logo</label><input type="file" name="logo"></div>
			<button type="submit" name="camp_signup_submit">Submit</button>
		</form>
		<?php
		return ob_get_clean();
	}

	public function handle_submission() {
		if ( ! isset( $_POST['camp_signup_submit'] ) || ! isset( $_POST['camp_signup_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['camp_signup_nonce'], 'camp_signup_form_action' ) ) {
			wp_die( 'Security check failed.' );
		}

		// Validate required fields
		$errors = [];
		$email = sanitize_email( $_POST['email'] ?? '' );
		$camp_name = sanitize_text_field( $_POST['camp_name'] ?? '' );
		$camp_director = sanitize_text_field( $_POST['camp_directors'] ?? '' );
		
		if ( empty( $email ) ) {
			$errors[] = 'Email is required.';
		} elseif ( ! is_email( $email ) ) {
			$errors[] = 'Invalid email address.';
		} elseif ( email_exists( $email ) ) {
			$errors[] = 'This email address is already registered.';
		}

		if ( empty( $camp_name ) ) {
			$errors[] = 'Camp name is required.';
		}

		if ( empty( $camp_director ) ) {
			$errors[] = 'Camp director name is required.';
		}

		if ( empty( $_POST['type_ids'] ) ) {
			$errors[] = 'Please select at least one camp type.';
		}

		if ( empty( $_POST['week_ids'] ) ) {
			$errors[] = 'Please select at least one duration.';
		}

		if ( ! empty( $errors ) ) {
			wp_die( implode( '<br>', $errors ) );
		}

		// Create WordPress user
		$username = $this->generate_username( $camp_director );
		$password = wp_generate_password( 12, true );
		$user_id = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			wp_die( 'Error creating user: ' . $user_id->get_error_message() );
		}

		// Set user role to "camp"
		$user = new \WP_User( $user_id );
		$user->set_role( 'camp' );

		// Update user meta
		update_user_meta( $user_id, 'camp_name', $camp_name );

		// Store camp data in database
		$camp_id = $this->create_camp_entry( $user_id, $_POST );

		if ( ! $camp_id ) {
			// Rollback user creation if camp creation fails
			wp_delete_user( $user_id );
			wp_die( 'Error creating camp profile.' );
		}

		// Link camp types, weeks, and activities
		$this->link_camp_types( $camp_id, $_POST['type_ids'] ?? [] );
		$this->link_camp_weeks( $camp_id, $_POST['week_ids'] ?? [] );
		$this->link_camp_activities( $camp_id, $_POST['activities'] ?? '' );

		// Send thank you email
		$this->send_welcome_email( $user_id, $username, $email, $camp_name );

		// Send notification to admin
		$this->send_admin_notification_new_camp( $camp_name, $email, $camp_director, $camp_id );

		// Generate password reset key and redirect to WordPress set password page
		$reset_key = get_password_reset_key( $user );
		if ( ! is_wp_error( $reset_key ) ) {
			$reset_url = network_site_url( "wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode( $username ), 'login' );
			wp_redirect( $reset_url );
		} else {
			// Fallback: redirect to login with message
			wp_redirect( wp_login_url() . '?checkemail=confirm' );
		}
		exit;
	}

	/**
	 * Generate a unique username from camp director name
	 */
	private function generate_username( $camp_director ) {
		$username = sanitize_user( strtolower( str_replace( ' ', '_', $camp_director ) ), true );

		// Ensure username is unique
		$original = $username;
		$counter = 1;
		while ( username_exists( $username ) ) {
			$username = $original . '_' . $counter;
			$counter++;
		}

		return $username;
	}

	/**
	 * Create camp entry in database
	 */
	private function create_camp_entry( $user_id, $data ) {
		global $wpdb;
		$table = DB::table_camps();

		// Generate unique key
		$unique_key = md5( uniqid( 'camp_', true ) );

		// Handle file upload (logo)
		$logo_url = '';
		if ( ! empty( $_FILES['logo']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$upload = wp_handle_upload( $_FILES['logo'], [ 'test_form' => false ] );
			if ( ! empty( $upload['url'] ) ) {
				$logo_url = $upload['url'];
			}
		}

		// Parse currency values
		$min_price = $this->parse_currency( $data['minprice_2026'] ?? '' );
		$max_price = $this->parse_currency( $data['maxprice_2026'] ?? '' );

		// Prepare camp data
		$camp_data = [
			'wordpress_user_id' => $user_id,
			'unique_key'     => $unique_key,
			'camp_name'      => sanitize_text_field( $data['camp_name'] ?? '' ),
			'opening_day'    => sanitize_text_field( $data['opening_day'] ?? '' ),
			'closing_day'    => sanitize_text_field( $data['closing_day'] ?? '' ),
			'minprice_2026'  => $min_price,
			'maxprice_2026'  => $max_price,
			'activities'     => sanitize_textarea_field( $data['activities'] ?? '' ),
			'email'          => sanitize_email( $data['email'] ?? '' ),
			'phone'          => sanitize_text_field( $data['phone'] ?? '' ),
			'website'        => esc_url_raw( $data['website'] ?? '' ),
			'camp_directors' => sanitize_text_field( $data['camp_directors'] ?? '' ),
			'address'        => sanitize_text_field( $data['address'] ?? '' ),
			'city'           => sanitize_text_field( $data['city'] ?? '' ),
			'state'          => sanitize_text_field( $data['state'] ?? '' ),
			'zip'            => sanitize_text_field( $data['zip'] ?? '' ),
			'about_camp'     => sanitize_textarea_field( $data['about_camp'] ?? '' ),
			'logo'           => esc_url_raw( $logo_url ),
			'approved'       => 0,
			'created_at'     => current_time( 'mysql' ),
			'updated_at'     => current_time( 'mysql' ),
		];

		$result = $wpdb->insert( $table, $camp_data );

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Parse currency value (remove $ and commas)
	 */
	private function parse_currency( $value ) {
		$value = trim( $value );
		$value = str_replace( ['$', ','], '', $value );
		return floatval( $value );
	}

	/**
	 * Link camp types to camp
	 */
	private function link_camp_types( $camp_id, $type_ids ) {
		global $wpdb;
		$table = DB::table_camp_type_pivot();

		foreach ( $type_ids as $type_id ) {
			$wpdb->insert( $table, [
				'camp_id' => $camp_id,
				'type_id' => intval( $type_id ),
			] );
		}
	}

	/**
	 * Link camp weeks/durations to camp
	 */
	private function link_camp_weeks( $camp_id, $week_ids ) {
		global $wpdb;
		$table = DB::table_camp_week_pivot();

		foreach ( $week_ids as $week_id ) {
			$wpdb->insert( $table, [
				'camp_id' => $camp_id,
				'week_id' => intval( $week_id ),
			] );
		}
	}

	/**
	 * Link or create activities and link to camp
	 */
	private function link_camp_activities( $camp_id, $activities_string ) {
		global $wpdb;
		$activities_table = DB::table_activity_terms();
		$pivot_table = DB::table_camp_activity_pivot();

		// Parse comma-separated activities
		$activities = array_map( 'trim', explode( ',', $activities_string ) );
		$activities = array_filter( $activities );

		// Get existing activities
		$existing = $wpdb->get_results( "SELECT id, name, slug FROM {$activities_table}" );
		$by_name = [];
		foreach ( $existing as $act ) {
			$by_name[ strtolower( $act->name ) ] = intval( $act->id );
		}

		foreach ( $activities as $activity_name ) {
			$activity_name_lower = strtolower( $activity_name );
			
			// Check if activity exists
			if ( isset( $by_name[ $activity_name_lower ] ) ) {
				$activity_id = $by_name[ $activity_name_lower ];
			} else {
				// Create new activity
				$slug = sanitize_title( $activity_name );
				$wpdb->insert( $activities_table, [
					'name'       => $activity_name,
					'slug'       => $slug,
					'is_active'  => 1,
					'created_at' => current_time( 'mysql' ),
					'updated_at' => current_time( 'mysql' ),
				] );
				$activity_id = $wpdb->insert_id;
				$by_name[ $activity_name_lower ] = $activity_id;
			}

			// Link to camp
			$wpdb->insert( $pivot_table, [
				'camp_id'     => $camp_id,
				'activity_id' => $activity_id,
			] );
		}
	}

	/**
	 * Send welcome email to new camp user
	 */
	private function send_welcome_email( $user_id, $username, $email, $camp_name ) {
		$reset_url = wp_lostpassword_url();
		
		$subject = 'Welcome to Best USA Summer Camps!';
		
		$message = $this->get_email_template( $camp_name, $email, $reset_url );

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: Best USA Summer Camps <noreply@bestusacamps.com>',
		];

		wp_mail( $email, $subject, $message, $headers );
	}

	/**
	 * Get email HTML template
	 */
	private function get_email_template( $camp_name, $email, $reset_url ) {
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Welcome to Best USA Summer Camps</title>
		</head>
		<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f4f4;">
				<tr>
					<td align="center" style="padding: 20px 10px;">
						<div style="max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
							<div style="background: linear-gradient(135deg, #497C5E 0%, #679B7C 100%); color: #ffffff; padding: 30px 20px; text-align: center;">
								<h1 style="margin: 0; font-size: 28px; font-weight: bold;">🏕️ Welcome to Best USA Summer Camps!</h1>
							</div>
							<div style="padding: 30px 20px;">
								<h2 style="color: #497C5E; margin-top: 0; font-size: 22px;">Your Camp Profile Has Been Created</h2>
								<p style="margin: 15px 0; font-size: 16px;">Hello!</p>
								<p style="margin: 15px 0; font-size: 16px;">Thank you for registering <strong><?php echo esc_html( $camp_name ); ?></strong> with Best USA Summer Camps. Your camp profile has been successfully created!</p>
								
								<div style="background: #f8f9fa; border-left: 4px solid #497C5E; padding: 15px; margin: 20px 0; border-radius: 4px;">
									<p style="margin: 0; font-size: 16px;"><strong style="color: #497C5E;">Email:</strong> <?php echo esc_html( $email ); ?></p>
								</div>
								
								<p style="margin: 15px 0; font-size: 16px;"><strong>Next Step: Set Your Password</strong></p>
								<p style="margin: 15px 0; font-size: 16px;">To secure your account and access your camp dashboard, please click the button below to create your password:</p>
								
								<div style="text-align: center;">
									<a href="<?php echo esc_url( $reset_url ); ?>" style="display: inline-block; padding: 14px 30px; background: #497C5E !important; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; text-align: center;">Set My Password</a>
								</div>
								
								<p style="font-size: 14px; color: #666; margin-top: 25px;">If the button doesn't work, copy and paste this link into your browser:<br>
								<a href="<?php echo esc_url( $reset_url ); ?>" style="color: #497C5E; word-break: break-all;"><?php echo esc_url( $reset_url ); ?></a></p>
								
								<div style="height: 1px; background: #e9ecef; margin: 25px 0;"></div>
								
								<p style="font-size: 14px; color: #666; margin: 15px 0;"><strong>Need Help?</strong><br>
								If you did not create this account or have any questions, please contact our support team. We're here to help!</p>
							</div>
							<div style="background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #666; border-top: 1px solid #e9ecef;">
								<p style="margin: 0;"><strong>Best USA Summer Camps</strong></p>
							</div>
						</div>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Send notification email to admin when new camp registers
	 */
	private function send_admin_notification_new_camp( $camp_name, $camp_email, $camp_director, $camp_id ) {
		$admin_email = get_option( 'admin_email' );
		
		$subject = 'New Camp Registration: ' . $camp_name;
		
		$message = $this->get_admin_email_template( $camp_name, $camp_email, $camp_director, $camp_id );

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: Best USA Summer Camps <noreply@bestusacamps.com>',
		];

		wp_mail( $admin_email, $subject, $message, $headers );
	}

	/**
	 * Get admin notification email HTML template
	 */
	private function get_admin_email_template( $camp_name, $camp_email, $camp_director, $camp_id ) {
		$edit_url = admin_url( 'admin.php?page=creativedbs-camp-mgmt&action=edit&camp=' . $camp_id );
		$edit_url = wp_nonce_url( $edit_url, 'edit_camp' );
		
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>New Camp Registration</title>
		</head>
		<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f4f4;">
				<tr>
					<td align="center" style="padding: 20px 10px;">
						<div style="max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
							<div style="background: linear-gradient(135deg, #497C5E 0%, #679B7C 100%); color: #ffffff; padding: 30px 20px; text-align: center;">
								<h1 style="margin: 0; font-size: 28px; font-weight: bold;">🏕️ New Camp Registration!</h1>
							</div>
							<div style="padding: 30px 20px;">
								<h2 style="color: #497C5E; margin-top: 0; font-size: 22px;">A New Camp Profile Has Been Created</h2>
								<p style="margin: 15px 0; font-size: 16px;">Hello Administrator,</p>
								<p style="margin: 15px 0; font-size: 16px;">A new camp has just registered on Best USA Summer Camps:</p>
								
								<div style="background: #f8f9fa; border-left: 4px solid #497C5E; padding: 15px; margin: 20px 0; border-radius: 4px;">
									<p style="margin: 5px 0; font-size: 16px;"><strong style="color: #497C5E;">Camp Name:</strong> <?php echo esc_html( $camp_name ); ?></p>
									<p style="margin: 5px 0; font-size: 16px;"><strong style="color: #497C5E;">Director:</strong> <?php echo esc_html( $camp_director ); ?></p>
									<p style="margin: 5px 0; font-size: 16px;"><strong style="color: #497C5E;">Email:</strong> <?php echo esc_html( $camp_email ); ?></p>
									<p style="margin: 5px 0; font-size: 16px;"><strong style="color: #497C5E;">Camp ID:</strong> #<?php echo esc_html( $camp_id ); ?></p>
								</div>
								
								<p style="margin: 15px 0; font-size: 16px;">You can review and edit this camp profile in the WordPress admin:</p>
								
								<div style="text-align: center;">
									<a href="<?php echo esc_url( $edit_url ); ?>" style="display: inline-block; padding: 14px 30px; background: #497C5E !important; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; text-align: center;">View Camp Profile</a>
								</div>
								
								<p style="font-size: 14px; color: #666; margin-top: 25px;">If the button doesn't work, copy and paste this link into your browser:<br>
								<a href="<?php echo esc_url( $edit_url ); ?>" style="color: #497C5E; word-break: break-all;"><?php echo esc_url( $edit_url ); ?></a></p>
							</div>
							<div style="background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #666; border-top: 1px solid #e9ecef;">
								<p style="margin: 0;"><strong>Best USA Summer Camps</strong> - Admin Notification</p>
							</div>
						</div>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}
