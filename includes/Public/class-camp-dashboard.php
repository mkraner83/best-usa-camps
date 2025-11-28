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
		// Register shortcode for front-end dashboard
		add_shortcode( 'camp_dashboard', [ $this, 'render_dashboard' ] );
		
		// Handle form submissions
		add_action( 'init', [ $this, 'handle_form_submission' ] );
		
		// Enqueue front-end styles
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
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
			'tuition_fees'     => sanitize_text_field( $_POST['tuition_fees'] ?? '' ),
			'session_length'   => sanitize_text_field( $_POST['session_length'] ?? '' ),
			'capacity'         => absint( $_POST['capacity'] ?? 0 ),
			'age_range'        => sanitize_text_field( $_POST['age_range'] ?? '' ),
		];

		$wpdb->update(
			"{$wpdb->prefix}camp_management",
			$camp_data,
			[ 'id' => $camp_id ],
			[
				'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s'
			],
			[ '%d' ]
		);

		// Update pivot tables
		$this->update_pivot_data( $camp_id, 'camp_types', $_POST['camp_types'] ?? [] );
		$this->update_pivot_data( $camp_id, 'camp_weeks', $_POST['camp_weeks'] ?? [] );
		$this->update_pivot_data( $camp_id, 'camp_activities', $_POST['camp_activities'] ?? [] );

		// Redirect to avoid resubmission
		wp_redirect( add_query_arg( 'updated', 'true', wp_get_referer() ) );
		exit;
	}

	/**
	 * Update pivot table data
	 */
	private function update_pivot_data( $camp_id, $table_suffix, $values ) {
		global $wpdb;
		$table = "{$wpdb->prefix}{$table_suffix}";

		// Delete existing relationships
		$wpdb->delete( $table, [ 'camp_id' => $camp_id ], [ '%d' ] );

		// Insert new relationships
		if ( ! empty( $values ) && is_array( $values ) ) {
			foreach ( $values as $value_id ) {
				$wpdb->insert(
					$table,
					[
						'camp_id' => $camp_id,
						$table_suffix === 'camp_types' ? 'type_id' : 
						( $table_suffix === 'camp_weeks' ? 'week_id' : 'activity_id' ) => absint( $value_id )
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
			return $this->render_login_form();
		}

		$user = wp_get_current_user();

		// Check if user has 'camp' role
		if ( ! in_array( 'camp', $user->roles ) ) {
			return '<div class="camp-dashboard-error"><p>Access denied. This dashboard is only available for camp users.</p></div>';
		}

		// Get camp data
		global $wpdb;
		$camp = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}camp_management WHERE user_id = %d",
			$user->ID
		), ARRAY_A );

		if ( ! $camp ) {
			return '<div class="camp-dashboard-error"><p>No camp profile found for your account. Please contact support.</p></div>';
		}

		// Get pivot data
		$camp_types = $this->get_pivot_data( $camp['id'], 'camp_types', 'type_id' );
		$camp_weeks = $this->get_pivot_data( $camp['id'], 'camp_weeks', 'week_id' );
		$camp_activities = $this->get_pivot_data( $camp['id'], 'camp_activities', 'activity_id' );

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
	private function get_pivot_data( $camp_id, $table_suffix, $value_column ) {
		global $wpdb;
		$table = "{$wpdb->prefix}{$table_suffix}";
		
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
		$table = "{$wpdb->prefix}{$table_name}";
		
		$results = $wpdb->get_results( "SELECT id, name FROM {$table} ORDER BY name ASC", ARRAY_A );
		
		return $results;
	}

	/**
	 * Render login form
	 */
	private function render_login_form() {
		ob_start();
		?>
		<div class="camp-dashboard-login">
			<div class="login-wrapper">
				<h2>Camp Login</h2>
				<p>Please log in to access your camp dashboard.</p>
				<?php
				wp_login_form( [
					'redirect' => get_permalink(),
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
							<label for="description">Camp Description</label>
							<textarea id="description" name="description" rows="6"><?php echo esc_textarea( $camp['description'] ); ?></textarea>
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
							<label for="phone">Phone</label>
							<input type="tel" id="phone" name="phone" value="<?php echo esc_attr( $camp['phone'] ); ?>">
						</div>
					</div>

					<div class="form-row">
						<div class="form-group">
							<label for="website">Website</label>
							<input type="url" id="website" name="website" value="<?php echo esc_attr( $camp['website'] ); ?>" placeholder="https://">
						</div>
					</div>

					<div class="form-row">
						<div class="form-group">
							<label for="address">Street Address</label>
							<input type="text" id="address" name="address" value="<?php echo esc_attr( $camp['address'] ); ?>">
						</div>
					</div>

					<div class="form-row">
						<div class="form-group third">
							<label for="city">City</label>
							<input type="text" id="city" name="city" value="<?php echo esc_attr( $camp['city'] ); ?>">
						</div>
						<div class="form-group third">
							<label for="state">State</label>
							<input type="text" id="state" name="state" value="<?php echo esc_attr( $camp['state'] ); ?>">
						</div>
						<div class="form-group third">
							<label for="zip">ZIP Code</label>
							<input type="text" id="zip" name="zip" value="<?php echo esc_attr( $camp['zip'] ); ?>">
						</div>
					</div>
				</div>

				<div class="form-section">
					<h2 class="section-title">Camp Details</h2>
					
					<div class="form-row">
						<div class="form-group half">
							<label for="age_range">Age Range</label>
							<input type="text" id="age_range" name="age_range" value="<?php echo esc_attr( $camp['age_range'] ); ?>" placeholder="e.g., 8-16">
						</div>
						<div class="form-group half">
							<label for="capacity">Capacity</label>
							<input type="number" id="capacity" name="capacity" value="<?php echo esc_attr( $camp['capacity'] ); ?>" min="0">
						</div>
					</div>

					<div class="form-row">
						<div class="form-group half">
							<label for="session_length">Session Length</label>
							<input type="text" id="session_length" name="session_length" value="<?php echo esc_attr( $camp['session_length'] ); ?>" placeholder="e.g., 2 weeks">
						</div>
						<div class="form-group half">
							<label for="tuition_fees">Tuition & Fees</label>
							<input type="text" id="tuition_fees" name="tuition_fees" value="<?php echo esc_attr( $camp['tuition_fees'] ); ?>" placeholder="e.g., $2,500">
						</div>
					</div>
				</div>

				<div class="form-section">
					<h2 class="section-title">Camp Types</h2>
					<div class="checkbox-grid">
						<?php foreach ( $all_types as $type ) : ?>
							<label class="checkbox-label">
								<input type="checkbox" name="camp_types[]" value="<?php echo esc_attr( $type['id'] ); ?>" 
									<?php checked( in_array( $type['id'], $camp_types ) ); ?>>
								<span><?php echo esc_html( $type['name'] ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="form-section">
					<h2 class="section-title">Available Weeks</h2>
					<div class="checkbox-grid">
						<?php foreach ( $all_weeks as $week ) : ?>
							<label class="checkbox-label">
								<input type="checkbox" name="camp_weeks[]" value="<?php echo esc_attr( $week['id'] ); ?>" 
									<?php checked( in_array( $week['id'], $camp_weeks ) ); ?>>
								<span><?php echo esc_html( $week['name'] ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="form-section">
					<h2 class="section-title">Activities Offered</h2>
					<div class="checkbox-grid">
						<?php foreach ( $all_activities as $activity ) : ?>
							<label class="checkbox-label">
								<input type="checkbox" name="camp_activities[]" value="<?php echo esc_attr( $activity['id'] ); ?>" 
									<?php checked( in_array( $activity['id'], $camp_activities ) ); ?>>
								<span><?php echo esc_html( $activity['name'] ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="form-actions">
					<button type="submit" class="btn-primary">Save Changes</button>
					<a href="<?php echo get_permalink(); ?>" class="btn-secondary">Cancel</a>
				</div>
			</form>
		</div>
		<?php
	}
}
