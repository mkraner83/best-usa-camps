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
		add_shortcode( 'camp_password_reset', [ $this, 'render_password_reset_request' ] );
		add_shortcode( 'camp_set_password', [ $this, 'render_password_reset_form' ] );
		add_action( 'init', [ $this, 'handle_submission' ] );
		add_action( 'init', [ $this, 'handle_password_reset_request' ] );
		add_action( 'init', [ $this, 'handle_password_reset' ] );
		add_action( 'init', [ $this, 'register_camp_role' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'login_init', [ $this, 'redirect_login_password_reset' ] );
		// Redirect to login after password reset for camp users
		add_filter( 'password_reset_redirect', [ $this, 'redirect_to_login_after_reset' ], 10, 2 );
		add_action( 'template_redirect', [ $this, 'redirect_password_reset_success' ] );
	}

	/**
	 * Enqueue signup form styles and scripts
	 */
	public function enqueue_styles() {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && ( 
			has_shortcode( $post->post_content, 'camp_signup_form' ) ||
			has_shortcode( $post->post_content, 'camp_password_reset' ) ||
			has_shortcode( $post->post_content, 'camp_set_password' )
		) ) {
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
			
			// Pass success flag to JavaScript
			if ( ! session_id() ) {
				session_start();
			}
			$show_popup = isset( $_SESSION['camp_signup_success'] ) && $_SESSION['camp_signup_success'];
			$reset_url = isset( $_SESSION['camp_password_reset_url'] ) ? $_SESSION['camp_password_reset_url'] : '';
			if ( $show_popup ) {
				unset( $_SESSION['camp_signup_success'] );
				unset( $_SESSION['camp_password_reset_url'] );
			}
			wp_localize_script( 'camp-signup-form-logic', 'campSignupData', [
				'showSuccessPopup' => $show_popup,
				'passwordResetUrl' => $reset_url,
			] );
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
			<div class="full-width"><label>Camp Name</label><input type="text" name="camp_name" placeholder="e.g., Pine Valley Summer Camp" required></div>
			<div><label>Camp Opening Day</label><input type="text" id="opening_day" name="opening_day" placeholder="First day of camp" required readonly></div>
			<div><label>Camp Closing Day</label><input type="text" id="closing_day" name="closing_day" placeholder="Last day of camp" required readonly></div>
			<div><label>Lowest Rate</label><input type="text" id="minprice_2026" name="minprice_2026" placeholder="$1,500" required></div>
			<div><label>Highest Rate</label><input type="text" id="maxprice_2026" name="maxprice_2026" placeholder="$3,500" required></div>
			<div><label>Email</label><input type="email" id="email" name="email" placeholder="director@yourcamp.com" required></div>
			<div><label>Phone</label><input type="text" name="phone" placeholder="(555) 123-4567" required></div>
			<div><label>Website URL</label><input type="url" id="website" name="website" placeholder="https://yourcamp.com" required></div>
			<div><label>Camp Director</label><input type="text" name="camp_directors" placeholder="Director's Full Name" required></div>
			
			<!-- Social Media Links -->
			<div class="full-width">
				<label>Social Media Links</label>
				<div id="social-media-container">
					<div class="social-media-field">
						<input type="url" name="social_media[]" placeholder="https://facebook.com/yourcamp" class="social-media-input">
						<button type="button" class="remove-social-btn" style="display:none;">×</button>
					</div>
				</div>
				<button type="button" id="add-social-btn" class="add-social-btn">+ Add Another Social Link</button>
				<p class="description" style="margin-top:8px;color:#666;font-size:13px;">Add your camp's social media profiles (Facebook, Instagram, Twitter, etc.)</p>
			</div>
			
			<!-- Video URL -->
			<div class="full-width">
				<label>Camp Video URL</label>
				<input type="url" id="video_url" name="video_url" placeholder="https://youtube.com/watch?v=... or https://vimeo.com/...">
				<p class="description" style="margin-top:8px;color:#666;font-size:13px;">Showcase your camp with a video tour! (YouTube, Vimeo, or other video platform)</p>
			</div>
			<div><label>Address</label><input type="text" name="address" placeholder="123 Camp Road" required></div>
			<div><label>City</label><input type="text" name="city" placeholder="Lake Placid" required></div>
			<div><label>Zip</label><input type="text" name="zip" placeholder="12345" required></div>
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
			<div class="full-width"><label>About Camp</label><textarea name="about_camp" id="about_camp_signup" placeholder="Share what makes your camp special! Describe your mission, unique programs, facilities, and what campers can expect from a summer at your camp..." required></textarea>
				<p class="description" style="margin-top:5px;color:#666;font-size:12px;">
					<span id="word-count-signup">0</span> words (180 minimum, 300 maximum) <span id="word-limit-warning-signup" style="color: #dc3545; display: none;">● Limit not met</span>
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
				<input type="text" id="activities_field" name="activities" required placeholder="Comma-separated (e.g. Dance, Swimming)" list="activity-list">
				<datalist id="activity-list">
					<?php if ( $activities ) foreach ( $activities as $act ) : ?>
						<option value="<?php echo esc_attr($act->name); ?>">
					<?php endforeach; ?>
				</datalist>
			</div>
			<div class="full-width"><label class="required-label">How did you hear about us?</label>
				<select name="referral_source" required>
					<option value="">Select an option</option>
					<option value="Google (or similar) Search">Google (or similar) Search</option>
					<option value="Social Media (Facebook / Instagram...)">Social Media (Facebook / Instagram...)</option>
					<option value="Blog">Blog</option>
					<option value="Partner Camp">Partner Camp</option>
					<option value="Camp Directory / Listing Site">Camp Directory / Listing Site</option>
					<option value="Other">Other</option>
				</select>
			</div>
			<div class="full-width"><label class="required-label">Camp Logo</label><input type="file" name="logo" accept="image/jpeg,image/jpg,image/png" required>
				<p class="description" style="margin-top:8px;color:#666;font-size:13px;">JPG, JPEG or PNG format, max 5MB</p>
			</div>
			<button type="submit" name="camp_signup_submit" id="camp-submit-btn">Create Camp Profile</button>
		</form>
		
		<!-- Loading Overlay -->
		<div id="camp-loading-overlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(73,124,94,0.95);z-index:999999;align-items:center;justify-content:center;">
			<div style="text-align:center;color:white;">
				<div style="border:4px solid rgba(255,255,255,0.3);border-top:4px solid white;border-radius:50%;width:50px;height:50px;animation:spin 1s linear infinite;margin:0 auto;"></div>
				<p style="margin:20px 0 0 0;font-size:18px;font-weight:500;">Uploading your logo and creating your profile...</p>
				<p style="font-size:14px;margin-top:10px;">Please don't close this page</p>
			</div>
		</div>
		<style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>
		
		<!-- Success Popup -->
		<div id="camp-success-popup" class="camp-popup-overlay" style="display:none;">
			<div class="camp-popup-content">
				<div class="camp-popup-header">
				<h2>🏕️ Ready to Create Your Camp Profile!</h2>
				<p style="margin:10px 0 0 0;font-size:16px;color:#2d5a3f;">Please read the important information below, then click the button to complete your submission.</p>
				</div>
				<div class="camp-popup-body">
					<h3>Step 1: Complete Your Camp Profile</h3>
					<p>Our Director Dashboard is designed to be user-friendly and intuitive. To ensure your camp is published and discoverable by families, you must provide a complete profile:</p>
					<ul>
						<li><strong>Full Program Details:</strong> Fill in all required information including session lengths, cabin details, and frequently asked questions.</li>
						<li><strong>The Power of Photos:</strong> Listings without images are not published. High-resolution photos (1280px or larger) are the #1 factor in a parent's decision to request expert advice about your camp.</li>
					</ul>
					
					<h3>Step 2: The "Best USA Summer Camps" Logo Requirement</h3>
					<p>To provide this platform free of charge, Best USA Summer Camps operates on a community-support model:</p>
					<ul>
						<li><strong>Requirement:</strong> Your camp must display the Best USA Summer Camps logo with a backlink on your camp's official website.</li>
						<li><strong>Get Your Logo:</strong> <a href="https://bestusacamps.com/the-camp-directors-guide/" target="_blank">Download logos here →</a></li>
					</ul>
					<p class="highlight-box">Without the logo and backlink, your camp will not be published in our directory.</p>
					
					<h3>Step 3: Managing Leads in Your Director Dashboard</h3>
					<p>Once your listing is live, you can log in anytime to:</p>
					<ul>
						<li>Track profile views and engagement</li>
						<li>Update your camp's information and photos</li>
						<li>See which parents have expressed interest in your program</li>
						<li>Manage inquiries and connect with prospective families</li>
					</ul>
					
					<div style="margin-top: 30px;">
					<p style="font-size: 16px; color: #2d5a3f; margin-bottom: 15px; font-weight: 600;">Click the button below to upload your logo and create your profile. This may take a few seconds.</p>
					<button type="button" id="close-popup-btn" class="popup-close-btn">Got It! Submit My Camp Profile</button>
					</div>
				</div>
			</div>
		</div>
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

		// Remove slashes from POST data to prevent double-escaping
		$post_data = wp_unslash( $_POST );

		// Validate required fields
		$errors = [];
		$email = sanitize_email( $post_data['email'] ?? '' );
		$camp_name = sanitize_text_field( $post_data['camp_name'] ?? '' );
		$camp_director = sanitize_text_field( $post_data['camp_directors'] ?? '' );
		
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

		if ( empty( $post_data['type_ids'] ) ) {
			$errors[] = 'Please select at least one camp type.';
		}

		if ( empty( $post_data['week_ids'] ) ) {
			$errors[] = 'Please select at least one duration.';
		}

		if ( empty( $post_data['referral_source'] ) ) {
			$errors[] = 'Please tell us how you heard about us.';
		}

		// Validate About Camp word count (180 min - 300 max words)
		$about_camp = wp_kses_post( $post_data['about_camp'] ?? '' );
		$word_count = str_word_count( wp_strip_all_tags( $about_camp ) );
		if ( $word_count < 180 ) {
			$errors[] = 'Camp description must be at least 180 words. Current: ' . $word_count . ' words. Please add more detail about your camp.';
		}
		if ( $word_count > 300 ) {
			$errors[] = 'Camp description must be 300 words or less. Current: ' . $word_count . ' words. Please shorten your description.';
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

		// Update user meta - extract first and last name from camp director
		update_user_meta( $user_id, 'camp_name', $camp_name );
		
		// Split camp director name into first and last name
		$name_parts = explode( ' ', trim( $camp_director ), 2 );
		$first_name = $name_parts[0] ?? '';
		$last_name = $name_parts[1] ?? '';
		
		update_user_meta( $user_id, 'first_name', $first_name );
		update_user_meta( $user_id, 'last_name', $last_name );

		// Store camp data in database
		$camp_id = $this->create_camp_entry( $user_id, $post_data );

		if ( ! $camp_id ) {
			// Rollback user creation if camp creation fails
			wp_delete_user( $user_id );
			wp_die( 'Error creating camp profile.' );
		}

		// Link camp types, weeks, and activities
		$this->link_camp_types( $camp_id, $post_data['type_ids'] ?? [] );
		$this->link_camp_weeks( $camp_id, $post_data['week_ids'] ?? [] );
		$this->link_camp_activities( $camp_id, $post_data['activities'] ?? '' );

		// Send thank you email
		$this->send_welcome_email( $user_id, $username, $email, $camp_name );

		// Send notification to admin
		$this->send_admin_notification_new_camp( $camp_name, $email, $camp_director, $camp_id );

		// Generate password reset key for redirect after popup
		$reset_key = get_password_reset_key( $user );
		$reset_url = '';
		if ( ! is_wp_error( $reset_key ) ) {
			$reset_url = network_site_url( "wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode( $username ), 'login' );
		} else {
			// Fallback to password reset page
			$reset_url = home_url( '/camp-login/' );
		}

		// Redirect directly to password setup page
		wp_redirect( $reset_url );
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

		// Process social media links
		$social_media_links = [];
		if ( ! empty( $data['social_media'] ) && is_array( $data['social_media'] ) ) {
			foreach ( $data['social_media'] as $link ) {
				$link = esc_url_raw( trim( $link ) );
				if ( ! empty( $link ) ) {
					$social_media_links[] = $link;
				}
			}
		}
		$social_media_json = ! empty( $social_media_links ) ? wp_json_encode( $social_media_links ) : null;
		
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
			'social_media_links' => $social_media_json,
			'video_url'      => esc_url_raw( $data['video_url'] ?? '' ),
			'referral_source' => sanitize_text_field( $data['referral_source'] ?? '' ),
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
		$reset_url = home_url( '/camp-lost-password/' );
		
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
								<h1 style="margin: 0; font-size: 28px; font-weight: bold;">🏕️ Thank You for Creating Your Camp Profile!</h1>
							</div>
							<div style="padding: 30px 20px; text-align: left;">
								<p style="margin: 15px 0; font-size: 16px;">Hello!</p>
								<p style="margin: 15px 0; font-size: 16px;">Thank you for registering <strong><?php echo esc_html( $camp_name ); ?></strong> with Best USA Summer Camps. Your camp profile has been successfully created!</p>
								
								<div style="background: #f8f9fa; border-left: 4px solid #497C5E; padding: 15px; margin: 20px 0; border-radius: 4px;">
									<p style="margin: 0; font-size: 16px;"><strong style="color: #497C5E;">Email:</strong> <?php echo esc_html( $email ); ?></p>
								</div>
								
								<h3 style="color: #497C5E; font-size: 20px; margin: 25px 0 12px 0;">Step 1: Complete Your Camp Profile</h3>
								<p style="margin: 12px 0; font-size: 16px;">Our Director Dashboard is designed to be user-friendly and intuitive. To ensure your camp is published and discoverable by families, you must provide a complete profile:</p>
								<ul style="margin: 15px 0; padding-left: 25px;">
									<li style="margin: 10px 0; font-size: 15px; line-height: 1.6;"><strong>Full Program Details:</strong> Fill in all required information including session lengths, cabin details, and frequently asked questions.</li>
									<li style="margin: 10px 0; font-size: 15px; line-height: 1.6;"><strong>The Power of Photos:</strong> Listings without images are not published. High-resolution photos (1280px or larger) are the #1 factor in a parent's decision to request expert advice about your camp.</li>
								</ul>
								
								<h3 style="color: #497C5E; font-size: 20px; margin: 25px 0 12px 0;">Step 2: The "Best USA Summer Camps" Logo Requirement</h3>
								<p style="margin: 12px 0; font-size: 16px;">To provide this platform free of charge, Best USA Summer Camps operates on a community-support model:</p>
								<ul style="margin: 15px 0; padding-left: 25px;">
									<li style="margin: 10px 0; font-size: 15px; line-height: 1.6;"><strong>Requirement:</strong> Your camp must display the Best USA Summer Camps logo with a backlink on your camp's official website.</li>
									<li style="margin: 10px 0; font-size: 15px; line-height: 1.6;"><strong>Get Your Logo:</strong> <a href="https://bestusacamps.com/the-camp-directors-guide/" style="color: #497C5E; font-weight: 600; text-decoration: none;">Download logos here →</a></li>
								</ul>
								<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
									<p style="margin: 0; font-size: 15px; font-weight: 600; color: #856404;">Without the logo and backlink, your camp will not be published in our directory.</p>
								</div>
								
								<h3 style="color: #497C5E; font-size: 20px; margin: 25px 0 12px 0;">Step 3: Managing Leads in Your Director Dashboard</h3>
								<p style="margin: 12px 0; font-size: 16px;">Once your listing is live, you can log in anytime to:</p>
								<ul style="margin: 15px 0; padding-left: 25px;">
									<li style="margin: 10px 0; font-size: 15px; line-height: 1.6;">Track profile views and engagement</li>
									<li style="margin: 10px 0; font-size: 15px; line-height: 1.6;">Update your camp's information and photos</li>
									<li style="margin: 10px 0; font-size: 15px; line-height: 1.6;">See which parents have expressed interest in your program</li>
									<li style="margin: 10px 0; font-size: 15px; line-height: 1.6;">Manage inquiries and connect with prospective families</li>
								</ul>
								
								<div style="height: 1px; background: #e9ecef; margin: 30px 0;"></div>
								
							<h3 style="color: #497C5E; font-size: 20px; margin: 25px 0 12px 0;">Access Your Director Dashboard</h3>
							<p style="margin: 15px 0; font-size: 16px;">You can now log in to your camp dashboard to complete your profile and start managing your listing:</p>
							
							<div style="margin: 20px 0;">
								<a href="https://bestusacamps.com/user-dashboard/" style="display: inline-block; padding: 14px 30px; background: #497C5E !important; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; text-align: center;">Login to Director Dashboard</a>
							</div>
							
							<p style="font-size: 14px; color: #666; margin-top: 25px;">If you need to change your password in the future, you can visit:<br>
							<a href="<?php echo esc_url( $reset_url ); ?>" style="color: #497C5E;"><?php echo esc_url( $reset_url ); ?></a></p>
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
	
	/**
	 * Redirect to login page after password reset instead of showing success message
	 */
	public function redirect_to_login_after_reset( $redirect_url, $user ) {
		// Only redirect camp users to login
		if ( $user && in_array( 'camp', (array) $user->roles ) ) {
			return wp_login_url();
		}
		return $redirect_url;
	}

	public function redirect_password_reset_success() {
		// Check if we're on the password reset success page (after password has been changed)
		// This catches the white page that says "Your password has been reset. Log in"
		if ( isset( $_GET['password'] ) && $_GET['password'] === 'changed' ) {
			// Redirect to login page
			wp_safe_redirect( wp_login_url() );
			exit;
		}
	}

	/**
	 * Redirect wp-login.php password reset URLs to custom pages
	 */
	public function redirect_login_password_reset() {
		// Don't redirect the lost password page - WordPress's built-in one is fine
		// Only redirect the actual password reset form page (rp/resetpass)
		if ( isset( $_GET['action'] ) && in_array( $_GET['action'], [ 'rp', 'resetpass' ] ) ) {
			$key   = isset( $_GET['key'] )   ? sanitize_text_field( $_GET['key'] )   : '';
			$login = isset( $_GET['login'] ) ? sanitize_text_field( $_GET['login'] ) : '';
			
			if ( $key && $login ) {
				wp_safe_redirect( home_url( '/set-password/?key=' . $key . '&login=' . rawurlencode( $login ) ) );
				exit;
			}
		}
	}

	/**
	 * Render password reset request form
	 */
	public function render_password_reset_request() {
		$message = '';
		$error = '';
		
		if ( isset( $_GET['reset'] ) && $_GET['reset'] === 'success' ) {
			$message = 'Check your email for the password reset link.';
		}
		
		if ( isset( $_GET['error'] ) ) {
			$errors = [
				'invalid_email' => 'Please enter a valid email address.',
				'user_not_found' => 'No account found with that email address.',
			];
			$error = isset( $errors[ $_GET['error'] ] ) ? $errors[ $_GET['error'] ] : 'An error occurred. Please try again.';
		}
		
		ob_start();
		?>
		<div class="camp-login-form">
			<h2>Reset Your Password</h2>
			
			<?php if ( $message ) : ?>
				<div class="camp-message camp-success">
					<?php echo esc_html( $message ); ?>
				</div>
			<?php endif; ?>
			
			<?php if ( $error ) : ?>
				<div class="camp-message camp-error">
					<?php echo esc_html( $error ); ?>
				</div>
			<?php endif; ?>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'camp_password_reset_request', 'camp_reset_nonce' ); ?>
				<input type="hidden" name="camp_password_reset_request" value="1">
				
				<p class="login-username">
					<label for="user_email">Email Address</label>
					<input type="email" id="user_email" name="user_email" class="input" required placeholder="your@email.com" size="20">
				</p>
				
				<p class="login-submit">
					<input type="submit" class="button button-primary" value="Send Reset Link">
				</p>
			</form>
			
			<div class="camp-login-links">
				<a href="<?php echo esc_url( wp_login_url() ); ?>">← Back to Login</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle password reset request submission
	 */
	public function handle_password_reset_request() {
		if ( ! isset( $_POST['camp_password_reset_request'] ) ) {
			return;
		}
		
		if ( ! isset( $_POST['camp_reset_nonce'] ) || ! wp_verify_nonce( $_POST['camp_reset_nonce'], 'camp_password_reset_request' ) ) {
			return;
		}
		
		$email = sanitize_email( $_POST['user_email'] );
		
		if ( ! is_email( $email ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_email', home_url( '/reset-password/' ) ) );
			exit;
		}
		
		$user = get_user_by( 'email', $email );
		
		if ( ! $user ) {
			wp_safe_redirect( add_query_arg( 'error', 'user_not_found', home_url( '/reset-password/' ) ) );
			exit;
		}
		
		// Generate reset key
		$key = get_password_reset_key( $user );
		
		if ( is_wp_error( $key ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'general', home_url( '/reset-password/' ) ) );
			exit;
		}
		
		// Send reset email
		$reset_url = home_url( '/set-password/?key=' . $key . '&login=' . rawurlencode( $user->user_login ) );
		
		$subject = 'Password Reset Request - Best USA Summer Camps';
		$message = $this->get_password_reset_email_template( $user->display_name, $reset_url );
		
		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: Best USA Summer Camps <noreply@bestusacamps.com>',
		];
		
		wp_mail( $user->user_email, $subject, $message, $headers );
		
		wp_safe_redirect( add_query_arg( 'reset', 'success', home_url( '/reset-password/' ) ) );
		exit;
	}

	/**
	 * Get password reset email HTML template
	 */
	private function get_password_reset_email_template( $display_name, $reset_url ) {
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Password Reset Request</title>
		</head>
		<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f4f4;">
				<tr>
					<td align="center" style="padding: 20px 10px;">
						<div style="max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
							<div style="background: linear-gradient(135deg, #497C5E 0%, #679B7C 100%); color: #ffffff; padding: 30px 20px; text-align: center;">
								<h1 style="margin: 0; font-size: 28px; font-weight: bold;">🔐 Password Reset Request</h1>
							</div>
							<div style="padding: 30px 20px; text-align: left;">
								<p style="margin: 15px 0; font-size: 16px;">Hi <?php echo esc_html( $display_name ); ?>,</p>
								<p style="margin: 15px 0; font-size: 16px;">You requested a password reset for your account at Best USA Summer Camps.</p>
								
								<p style="margin: 15px 0; font-size: 16px;">Click the button below to set a new password:</p>
								
								<div style="margin: 30px 0; text-align: center;">
									<a href="<?php echo esc_url( $reset_url ); ?>" style="display: inline-block; padding: 14px 30px; background: #497C5E !important; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; text-align: center;">Set New Password</a>
								</div>
								
								<p style="font-size: 14px; color: #666; margin-top: 25px;">Or copy and paste this link into your browser:<br>
								<a href="<?php echo esc_url( $reset_url ); ?>" style="color: #497C5E; word-break: break-all;"><?php echo esc_url( $reset_url ); ?></a></p>
								
								<div style="height: 1px; background: #e9ecef; margin: 30px 0;"></div>
								
								<p style="font-size: 14px; color: #666;"><strong>⏰ This link will expire in 24 hours.</strong></p>
								<p style="font-size: 14px; color: #666;">If you didn't request this password reset, you can safely ignore this email. Your password will not be changed.</p>
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
	 * Render password reset form (actual password change)
	 */
	public function render_password_reset_form() {
		$key = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';
		$login = isset( $_GET['login'] ) ? sanitize_text_field( $_GET['login'] ) : '';
		$error = '';
		
		if ( isset( $_GET['error'] ) ) {
			$errors = [
				'invalid_key' => 'This password reset link is invalid or has expired.',
				'passwords_mismatch' => 'Passwords do not match.',
				'password_too_short' => 'Password must be at least 8 characters.',
			];
			$error = isset( $errors[ $_GET['error'] ] ) ? $errors[ $_GET['error'] ] : 'An error occurred. Please try again.';
		}
		
		// Validate key
		if ( $key && $login ) {
			$user = check_password_reset_key( $key, $login );
			if ( is_wp_error( $user ) ) {
				$error = 'This password reset link is invalid or has expired.';
				$key = '';
				$login = '';
			}
		}
		
		ob_start();
		?>
		<div class="camp-login-form">
			<h2>Set New Password</h2>
			
			<?php if ( $error ) : ?>
				<div class="camp-message camp-error">
					<?php echo esc_html( $error ); ?>
				</div>
			<?php endif; ?>
			
			<?php if ( $key && $login ) : ?>
				<form method="post" action="">
					<?php wp_nonce_field( 'camp_password_reset', 'camp_reset_nonce' ); ?>
					<input type="hidden" name="camp_password_reset" value="1">
					<input type="hidden" name="key" value="<?php echo esc_attr( $key ); ?>">
					<input type="hidden" name="login" value="<?php echo esc_attr( $login ); ?>">
					
					<p class="login-username">
						<label for="new_password">New Password</label>
						<input type="password" id="new_password" name="new_password" class="input" required minlength="8" placeholder="At least 8 characters" size="20" autocomplete="new-password">
					</p>
					
					<p class="login-password">
						<label for="confirm_password">Confirm Password</label>
						<input type="password" id="confirm_password" name="confirm_password" class="input" required minlength="8" placeholder="Re-enter your password" size="20" autocomplete="new-password">
					</p>
					
					<p class="login-submit">
						<input type="submit" class="button button-primary" value="Reset Password">
					</p>
				</form>
			<?php else : ?>
				<p>This password reset link is invalid or has expired. Please request a new one.</p>
				<div class="camp-login-links">
					<a href="<?php echo esc_url( home_url( '/reset-password/' ) ); ?>">Request Password Reset</a>
				</div>
			<?php endif; ?>
			
			<div class="camp-login-links">
				<a href="<?php echo esc_url( wp_login_url() ); ?>">← Back to Login</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle password reset form submission
	 */
	public function handle_password_reset() {
		if ( ! isset( $_POST['camp_password_reset'] ) ) {
			return;
		}
		
		if ( ! isset( $_POST['camp_reset_nonce'] ) || ! wp_verify_nonce( $_POST['camp_reset_nonce'], 'camp_password_reset' ) ) {
			return;
		}
		
		$key = sanitize_text_field( $_POST['key'] );
		$login = sanitize_text_field( $_POST['login'] );
		$new_password = $_POST['new_password'];
		$confirm_password = $_POST['confirm_password'];
		
		// Validate passwords match
		if ( $new_password !== $confirm_password ) {
			wp_safe_redirect( add_query_arg( [ 'error' => 'passwords_mismatch', 'key' => $key, 'login' => $login ], home_url( '/set-password/' ) ) );
			exit;
		}
		
		// Validate password length
		if ( strlen( $new_password ) < 8 ) {
			wp_safe_redirect( add_query_arg( [ 'error' => 'password_too_short', 'key' => $key, 'login' => $login ], home_url( '/set-password/' ) ) );
			exit;
		}
		
		// Verify reset key
		$user = check_password_reset_key( $key, $login );
		
		if ( is_wp_error( $user ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_key', home_url( '/set-password/' ) ) );
			exit;
		}
		
		// Reset password
		reset_password( $user, $new_password );
		
		// Redirect to login
		wp_safe_redirect( add_query_arg( 'password', 'changed', wp_login_url() ) );
		exit;
	}
}
