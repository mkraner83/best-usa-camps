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
		
		// Enqueue front-end styles
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
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
			'description'      => wp_kses_post( $_POST['description'] ?? '' ),
			'opening_day'      => sanitize_text_field( $_POST['opening_day'] ?? '' ),
			'closing_day'      => sanitize_text_field( $_POST['closing_day'] ?? '' ),
			'minprice_2026'    => floatval( $_POST['minprice_2026'] ?? 0 ),
			'maxprice_2026'    => floatval( $_POST['maxprice_2026'] ?? 0 ),
			'capacity'         => absint( $_POST['capacity'] ?? 0 ),
			'age_range'        => sanitize_text_field( $_POST['age_range'] ?? '' ),
		];

		$wpdb->update(
			"{$wpdb->prefix}camp_management",
			$camp_data,
			[ 'id' => $camp_id ],
			[
				'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%s'
			],
			[ '%d' ]
		);

		// Update pivot tables
		$this->update_pivot_data( $camp_id, 'camp_management_types_map', 'type_id', $_POST['camp_types'] ?? [] );
		$this->update_pivot_data( $camp_id, 'camp_management_weeks_map', 'week_id', $_POST['camp_weeks'] ?? [] );
		$this->update_pivot_data( $camp_id, 'camp_management_activities_map', 'activity_id', $_POST['camp_activities'] ?? [] );

		// Redirect to avoid resubmission
		wp_redirect( add_query_arg( 'updated', 'true', wp_get_referer() ) );
		exit;
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
	 * Render the dashboard shortcode
	 */
	public function render_dashboard( $atts ) {
		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			error_log( 'CDBS Camp Dashboard: User not logged in' );
			return $this->render_login_form();
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
			
			// Show diagnostic info
			ob_start();
			?>
			<div class="camp-dashboard-error">
				<p><strong>Debug Information:</strong></p>
				<ul style="text-align: left; margin: 10px 0;">
					<li>User ID: <?php echo $user->ID; ?></li>
					<li>Username: <?php echo esc_html( $user->user_login ); ?></li>
					<li>Email: <?php echo esc_html( $user->user_email ); ?></li>
					<li>Roles: <?php echo esc_html( implode( ', ', $user->roles ) ); ?></li>
					<li>Camp profile in database: Not found</li>
				</ul>
				<p>Please contact support with this information.</p>
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

		return $results;
	}

	/**
	 * Get all available options from lookup tables
	 */
	private function get_all_options( $table_name ) {
		global $wpdb;
		$table = "{$wpdb->prefix}camp_{$table_name}_terms";
		
		$results = $wpdb->get_results( "SELECT id, name FROM {$table} WHERE is_active = 1 ORDER BY name ASC", ARRAY_A );
		
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
					<div class="login-message">
						<p><?php echo esc_html( $message ); ?></p>
					</div>
				<?php endif; ?>
				<h2>Camp Login</h2>
				<p>Please log in to access your camp dashboard.</p>
				<?php
				$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				wp_login_form( [
					'redirect' => $current_url,
					'label_username' => 'Username or Email',
					'label_password' => 'Password',
					'label_remember' => 'Remember Me',
					'label_log_in' => 'Log In',
				] );
				?>
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

			<form method="post" action="" class="camp-edit-form">
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
						<label for="description">Camp Description <span class="required">*</span></label>
						<textarea id="description" name="description" rows="6" required><?php echo esc_textarea( $camp['description'] ); ?></textarea>
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
							<label for="age_range">Age Range <span class="required">*</span></label>
							<input type="text" id="age_range" name="age_range" value="<?php echo esc_attr( $camp['age_range'] ); ?>" placeholder="e.g., 8-16" required>
						</div>
						<div class="form-group half">
							<label for="capacity">Capacity <span class="required">*</span></label>
							<input type="number" id="capacity" name="capacity" value="<?php echo esc_attr( $camp['capacity'] ); ?>" min="0" required>
						</div>
					</div>

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
				<p class="field-note">Select at least one activity</p>
				<div class="checkbox-inline-list">
					<?php foreach ( $all_activities as $activity ) : ?>
						<label style="display:inline-block;margin:0 12px 6px 0;">
							<input type="checkbox" name="camp_activities[]" value="<?php echo esc_attr( $activity['id'] ); ?>" 
								<?php checked( in_array( $activity['id'], $camp_activities ) ); ?>
								class="required-checkbox-activities">
							<?php echo esc_html( $activity['name'] ); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>				<div class="form-actions">
					<button type="submit" class="btn-primary">Save Changes</button>
					<a href="<?php echo get_permalink(); ?>" class="btn-secondary">Cancel</a>
				</div>
			</form>

			<script>
			document.addEventListener('DOMContentLoaded', function() {
				const form = document.querySelector('.camp-edit-form');
				
				form.addEventListener('submit', function(e) {
					let valid = true;
					let errorMessage = '';

					// Validate Camp Types
					const campTypes = document.querySelectorAll('.required-checkbox:checked');
					if (campTypes.length === 0) {
						valid = false;
						errorMessage += 'Please select at least one Camp Type.\n';
					}

					// Validate Available Weeks
					const campWeeks = document.querySelectorAll('.required-checkbox-weeks:checked');
					if (campWeeks.length === 0) {
						valid = false;
						errorMessage += 'Please select at least one Available Week/Session.\n';
					}

					// Validate Activities
					const activities = document.querySelectorAll('.required-checkbox-activities:checked');
					if (activities.length === 0) {
						valid = false;
						errorMessage += 'Please select at least one Activity.\n';
					}

					if (!valid) {
						e.preventDefault();
						alert(errorMessage);
						return false;
					}
				});
			});
			</script>
		</div>
		<?php
	}
}
