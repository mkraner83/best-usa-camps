<?php
/**
 * Parent Registration Form
 *
 * Shortcode: [parent_registration_form]
 *
 * - Renders the full parent + camper info form
 * - Dynamic checkboxes for Session Length (DB), Preferred Program (DB)
 *   and Preferred Location (auto-pulled from camp states in DB)
 * - On submit: creates WP user with 'parent' role (or reuses existing),
 *   saves row to wp_cdbs_parent_registrations, sends welcome e-mail
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt\PublicArea;

defined( 'ABSPATH' ) || exit;

use CreativeDBS\CampMgmt\DB;

class Parent_Registration_Form {

	public function __construct() {
		add_shortcode( 'parent_registration_form', [ $this, 'render_form' ] );
		add_action( 'init', [ $this, 'handle_submission' ] );
		add_action( 'init', [ $this, 'register_parent_role' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Redirect parents away from wp-admin
		add_action( 'admin_init', [ $this, 'block_parent_from_admin' ] );
		// Hide admin bar for parents
		add_action( 'after_setup_theme', [ $this, 'hide_admin_bar_for_parents' ] );
		// Redirect parents after login
		add_filter( 'login_redirect', [ $this, 'parent_login_redirect' ], 10, 3 );
	}

	// =========================================================================
	// Role helpers
	// =========================================================================

	public function register_parent_role() {
		if ( ! get_role( 'parent' ) ) {
			add_role(
				'parent',
				__( 'Parent', 'creativedbs-camp-mgmt' ),
				[ 'read' => true ]
			);
		}
	}

	public function hide_admin_bar_for_parents() {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( in_array( 'parent', (array) $user->roles, true ) ) {
				show_admin_bar( false );
			}
		}
	}

	public function block_parent_from_admin() {
		if ( ! defined( 'DOING_AJAX' ) ) {
			$user = wp_get_current_user();
			if ( in_array( 'parent', (array) $user->roles, true ) ) {
				wp_redirect( home_url( '/parent-dashboard/' ) );
				exit;
			}
		}
	}

	public function parent_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
		if ( is_wp_error( $user ) ) {
			return $redirect_to;
		}
		if ( in_array( 'parent', (array) $user->roles, true ) ) {
			return home_url( '/parent-dashboard/' );
		}
		return $redirect_to;
	}

	// =========================================================================
	// Assets
	// =========================================================================

	public function enqueue_assets() {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'parent_registration_form' ) ) {
			wp_enqueue_style(
				'parent-registration-form',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/parent-registration-form.css',
				[],
				CDBS_CAMP_VERSION
			);
			wp_enqueue_script(
				'parent-registration-form',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/parent-registration-form.js',
				[],
				CDBS_CAMP_VERSION,
				true
			);
		}
	}

	// =========================================================================
	// Dynamic option helpers
	// =========================================================================

	private function get_week_terms() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT id, name FROM {$wpdb->prefix}camp_week_terms WHERE is_active = 1 ORDER BY sort_order ASC, name ASC"
		);
	}

	private function get_type_terms() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT id, name FROM {$wpdb->prefix}camp_type_terms WHERE is_active = 1 ORDER BY sort_order ASC, name ASC"
		);
	}

	/**
	 * Pull distinct, non-empty states from the camps table.
	 * Only approved camps are used (approved = 1).
	 */
	private function get_camp_states() {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT DISTINCT state FROM " . DB::table_camps() . " WHERE approved = 1 AND state != '' ORDER BY state ASC"
		);

		// Full US state name map keyed by abbreviation
		$state_names = [
			'AL' => 'Alabama',    'AK' => 'Alaska',       'AZ' => 'Arizona',
			'AR' => 'Arkansas',   'CA' => 'California',   'CO' => 'Colorado',
			'CT' => 'Connecticut','DE' => 'Delaware',     'FL' => 'Florida',
			'GA' => 'Georgia',    'HI' => 'Hawaii',       'ID' => 'Idaho',
			'IL' => 'Illinois',   'IN' => 'Indiana',      'IA' => 'Iowa',
			'KS' => 'Kansas',     'KY' => 'Kentucky',     'LA' => 'Louisiana',
			'ME' => 'Maine',      'MD' => 'Maryland',     'MA' => 'Massachusetts',
			'MI' => 'Michigan',   'MN' => 'Minnesota',    'MS' => 'Mississippi',
			'MO' => 'Missouri',   'MT' => 'Montana',      'NE' => 'Nebraska',
			'NV' => 'Nevada',     'NH' => 'New Hampshire','NJ' => 'New Jersey',
			'NM' => 'New Mexico', 'NY' => 'New York',     'NC' => 'North Carolina',
			'ND' => 'North Dakota','OH' => 'Ohio',        'OK' => 'Oklahoma',
			'OR' => 'Oregon',     'PA' => 'Pennsylvania', 'RI' => 'Rhode Island',
			'SC' => 'South Carolina','SD' => 'South Dakota','TN' => 'Tennessee',
			'TX' => 'Texas',      'UT' => 'Utah',         'VT' => 'Vermont',
			'VA' => 'Virginia',   'WA' => 'Washington',   'WV' => 'West Virginia',
			'WI' => 'Wisconsin',  'WY' => 'Wyoming',
		];

		$locations = [];
		foreach ( $rows as $row ) {
			$abbr = strtoupper( trim( $row->state ) );
			$locations[] = isset( $state_names[ $abbr ] ) ? $state_names[ $abbr ] : $abbr;
		}
		// Deduplicate (in case some camps store full names already)
		$locations = array_unique( $locations );
		sort( $locations );

		// Always append "No Preference" at the end
		$locations[] = 'No Preference';

		return $locations;
	}

	// =========================================================================
	// Render form
	// =========================================================================

	public function render_form() {
		// If a parent is already logged in, offer to submit another form or go to dashboard.
		$prefill_first = $prefill_last = $prefill_email = $prefill_phone = '';
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( in_array( 'parent', (array) $user->roles, true ) ) {
				// Allow re-submission (for a second child etc.)
				// Show a small notice but still render the form.
				$logged_in_notice = '<div class="prf-notice prf-notice-info">You are submitting as <strong>' . esc_html( $user->display_name ) . '</strong>. This will be added as a new submission to your account. <a href="' . esc_url( home_url( '/parent-dashboard/' ) ) . '">Go to Dashboard</a></div>';
				// Pre-fill parent fields from user account
				$prefill_first = $user->first_name;
				$prefill_last  = $user->last_name;
				$prefill_email = $user->user_email;
				global $wpdb;
				$prefill_phone = (string) $wpdb->get_var( $wpdb->prepare(
					"SELECT phone FROM {$wpdb->prefix}cdbs_parent_registrations WHERE user_id = %d AND phone != '' ORDER BY submitted_at DESC LIMIT 1",
					$user->ID
				) );
			} else {
				$logged_in_notice = '';
			}
		} else {
			$logged_in_notice = '';
		}

		$session_lengths = $this->get_week_terms();
		$programs        = $this->get_type_terms();
		$locations       = $this->get_camp_states();

		// Error/success passback via transient (avoids session dependency)
		$transient_key  = 'prf_errors_' . ( is_user_logged_in() ? get_current_user_id() : md5( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$stored_errors  = get_transient( $transient_key );
		if ( $stored_errors ) {
			delete_transient( $transient_key );
		}

		$current_year = (int) date( 'Y' );
		$years        = [ $current_year, $current_year + 1, $current_year + 2 ];

		ob_start();
		?>
		<div class="prf-wrapper">
			<?php echo $logged_in_notice; // Already escaped above ?>

			<?php if ( ! empty( $stored_errors ) && is_array( $stored_errors ) ) : ?>
				<div class="prf-notice prf-notice-error">
					<strong>Please fix the following errors:</strong>
					<ul>
						<?php foreach ( $stored_errors as $err ) : ?>
							<li><?php echo esc_html( $err ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<form method="post" class="prf-form" id="parent-registration-form" novalidate>
				<?php wp_nonce_field( 'parent_reg_form_action', 'parent_reg_nonce' ); ?>

				<!-- ============================================================
				     PARENT INFORMATION
				     ============================================================ -->
				<div class="prf-section-title">Parent Information</div>
				<div class="prf-divider"></div>

				<div class="prf-row">
					<div class="prf-col">
						<label class="prf-label prf-required">Parent First Name</label>
						<input type="text" name="parent_first" class="prf-input" required value="<?php echo esc_attr( $prefill_first ); ?>">
					</div>
					<div class="prf-col">
						<label class="prf-label prf-required">Parent Last Name</label>
						<input type="text" name="parent_last" class="prf-input" required value="<?php echo esc_attr( $prefill_last ); ?>">
					</div>
				</div>

				<div class="prf-row">
					<div class="prf-col">
						<label class="prf-label prf-required">Email</label>
						<input type="email" name="email" class="prf-input" required value="<?php echo esc_attr( $prefill_email ); ?>">
					</div>
					<div class="prf-col">
						<label class="prf-label">Phone</label>
						<input type="tel" name="phone" class="prf-input" placeholder="(555) 123-4567" value="<?php echo esc_attr( $prefill_phone ); ?>">
					</div>
				</div>

				<!-- ============================================================
				     CAMPER INFORMATION
				     ============================================================ -->
				<div class="prf-section-title" style="margin-top:32px;">Camper Information</div>
				<div class="prf-divider"></div>

				<div class="prf-row">
					<div class="prf-col">
						<label class="prf-label prf-required">Child First Name</label>
						<input type="text" name="child_first" class="prf-input" required>
					</div>
					<div class="prf-col">
						<label class="prf-label prf-required">Child Last Name</label>
						<input type="text" name="child_last" class="prf-input" required>
					</div>
				</div>

				<div class="prf-row">
					<div class="prf-col">
						<label class="prf-label prf-required">Gender</label>
						<select name="gender" class="prf-select" required>
							<option value="">— Select —</option>
							<option value="Male">Male</option>
							<option value="Female">Female</option>
							<option value="Non-binary">Non-binary</option>
							<option value="Prefer not to say">Prefer not to say</option>
						</select>
					</div>
					<div class="prf-col">
						<label class="prf-label prf-required">Birthday</label>
						<input type="text" name="birthday" class="prf-input" placeholder="MM/DD/YYYY" required>
					</div>
				</div>

				<div class="prf-row">
					<div class="prf-col">
						<label class="prf-label">Address</label>
						<input type="text" name="address" class="prf-input">
					</div>
					<div class="prf-col">
						<label class="prf-label">City</label>
						<input type="text" name="city" class="prf-input">
					</div>
				</div>

				<div class="prf-row">
					<div class="prf-col">
						<label class="prf-label">State</label>
						<select name="camper_state" class="prf-select">
							<option value="">- Select State -</option>
							<option value="Alabama">Alabama</option>
							<option value="Alaska">Alaska</option>
							<option value="Arizona">Arizona</option>
							<option value="Arkansas">Arkansas</option>
							<option value="California">California</option>
							<option value="Colorado">Colorado</option>
							<option value="Connecticut">Connecticut</option>
							<option value="Delaware">Delaware</option>
							<option value="Florida">Florida</option>
							<option value="Georgia">Georgia</option>
							<option value="Hawaii">Hawaii</option>
							<option value="Idaho">Idaho</option>
							<option value="Illinois">Illinois</option>
							<option value="Indiana">Indiana</option>
							<option value="Iowa">Iowa</option>
							<option value="Kansas">Kansas</option>
							<option value="Kentucky">Kentucky</option>
							<option value="Louisiana">Louisiana</option>
							<option value="Maine">Maine</option>
							<option value="Maryland">Maryland</option>
							<option value="Massachusetts">Massachusetts</option>
							<option value="Michigan">Michigan</option>
							<option value="Minnesota">Minnesota</option>
							<option value="Mississippi">Mississippi</option>
							<option value="Missouri">Missouri</option>
							<option value="Montana">Montana</option>
							<option value="Nebraska">Nebraska</option>
							<option value="Nevada">Nevada</option>
							<option value="New Hampshire">New Hampshire</option>
							<option value="New Jersey">New Jersey</option>
							<option value="New Mexico">New Mexico</option>
							<option value="New York">New York</option>
							<option value="North Carolina">North Carolina</option>
							<option value="North Dakota">North Dakota</option>
							<option value="Ohio">Ohio</option>
							<option value="Oklahoma">Oklahoma</option>
							<option value="Oregon">Oregon</option>
							<option value="Pennsylvania">Pennsylvania</option>
							<option value="Rhode Island">Rhode Island</option>
							<option value="South Carolina">South Carolina</option>
							<option value="South Dakota">South Dakota</option>
							<option value="Tennessee">Tennessee</option>
							<option value="Texas">Texas</option>
							<option value="Utah">Utah</option>
							<option value="Vermont">Vermont</option>
							<option value="Virginia">Virginia</option>
							<option value="Washington">Washington</option>
							<option value="West Virginia">West Virginia</option>
							<option value="Wisconsin">Wisconsin</option>
							<option value="Wyoming">Wyoming</option>
						</select>
					</div>
					<div class="prf-col">
						<label class="prf-label prf-required">Country</label>
						<select name="country" class="prf-select" required>
							<option value="">— Select Country —</option>
							<option value="United States">United States</option>
							<option value="Canada">Canada</option>
							<option value="United Kingdom">United Kingdom</option>
							<option value="Australia">Australia</option>
							<option value="Afghanistan">Afghanistan</option>
							<option value="Albania">Albania</option>
							<option value="Algeria">Algeria</option>
							<option value="Andorra">Andorra</option>
							<option value="Angola">Angola</option>
							<option value="Antigua and Barbuda">Antigua and Barbuda</option>
							<option value="Argentina">Argentina</option>
							<option value="Armenia">Armenia</option>
							<option value="Austria">Austria</option>
							<option value="Azerbaijan">Azerbaijan</option>
							<option value="Bahamas">Bahamas</option>
							<option value="Bahrain">Bahrain</option>
							<option value="Bangladesh">Bangladesh</option>
							<option value="Barbados">Barbados</option>
							<option value="Belarus">Belarus</option>
							<option value="Belgium">Belgium</option>
							<option value="Belize">Belize</option>
							<option value="Benin">Benin</option>
							<option value="Bhutan">Bhutan</option>
							<option value="Bolivia">Bolivia</option>
							<option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
							<option value="Botswana">Botswana</option>
							<option value="Brazil">Brazil</option>
							<option value="Brunei">Brunei</option>
							<option value="Bulgaria">Bulgaria</option>
							<option value="Burkina Faso">Burkina Faso</option>
							<option value="Burundi">Burundi</option>
							<option value="Cabo Verde">Cabo Verde</option>
							<option value="Cambodia">Cambodia</option>
							<option value="Cameroon">Cameroon</option>
							<option value="Central African Republic">Central African Republic</option>
							<option value="Chad">Chad</option>
							<option value="Chile">Chile</option>
							<option value="China">China</option>
							<option value="Colombia">Colombia</option>
							<option value="Comoros">Comoros</option>
							<option value="Congo (DRC)">Congo (DRC)</option>
							<option value="Congo (Republic)">Congo (Republic)</option>
							<option value="Costa Rica">Costa Rica</option>
							<option value="Croatia">Croatia</option>
							<option value="Cuba">Cuba</option>
							<option value="Cyprus">Cyprus</option>
							<option value="Czech Republic">Czech Republic</option>
							<option value="Denmark">Denmark</option>
							<option value="Djibouti">Djibouti</option>
							<option value="Dominica">Dominica</option>
							<option value="Dominican Republic">Dominican Republic</option>
							<option value="Ecuador">Ecuador</option>
							<option value="Egypt">Egypt</option>
							<option value="El Salvador">El Salvador</option>
							<option value="Equatorial Guinea">Equatorial Guinea</option>
							<option value="Eritrea">Eritrea</option>
							<option value="Estonia">Estonia</option>
							<option value="Eswatini">Eswatini</option>
							<option value="Ethiopia">Ethiopia</option>
							<option value="Fiji">Fiji</option>
							<option value="Finland">Finland</option>
							<option value="France">France</option>
							<option value="Gabon">Gabon</option>
							<option value="Gambia">Gambia</option>
							<option value="Georgia">Georgia</option>
							<option value="Germany">Germany</option>
							<option value="Ghana">Ghana</option>
							<option value="Greece">Greece</option>
							<option value="Grenada">Grenada</option>
							<option value="Guatemala">Guatemala</option>
							<option value="Guinea">Guinea</option>
							<option value="Guinea-Bissau">Guinea-Bissau</option>
							<option value="Guyana">Guyana</option>
							<option value="Haiti">Haiti</option>
							<option value="Honduras">Honduras</option>
							<option value="Hungary">Hungary</option>
							<option value="Iceland">Iceland</option>
							<option value="India">India</option>
							<option value="Indonesia">Indonesia</option>
							<option value="Iran">Iran</option>
							<option value="Iraq">Iraq</option>
							<option value="Ireland">Ireland</option>
							<option value="Israel">Israel</option>
							<option value="Italy">Italy</option>
							<option value="Jamaica">Jamaica</option>
							<option value="Japan">Japan</option>
							<option value="Jordan">Jordan</option>
							<option value="Kazakhstan">Kazakhstan</option>
							<option value="Kenya">Kenya</option>
							<option value="Kiribati">Kiribati</option>
							<option value="Kosovo">Kosovo</option>
							<option value="Kuwait">Kuwait</option>
							<option value="Kyrgyzstan">Kyrgyzstan</option>
							<option value="Laos">Laos</option>
							<option value="Latvia">Latvia</option>
							<option value="Lebanon">Lebanon</option>
							<option value="Lesotho">Lesotho</option>
							<option value="Liberia">Liberia</option>
							<option value="Libya">Libya</option>
							<option value="Liechtenstein">Liechtenstein</option>
							<option value="Lithuania">Lithuania</option>
							<option value="Luxembourg">Luxembourg</option>
							<option value="Madagascar">Madagascar</option>
							<option value="Malawi">Malawi</option>
							<option value="Malaysia">Malaysia</option>
							<option value="Maldives">Maldives</option>
							<option value="Mali">Mali</option>
							<option value="Malta">Malta</option>
							<option value="Marshall Islands">Marshall Islands</option>
							<option value="Mauritania">Mauritania</option>
							<option value="Mauritius">Mauritius</option>
							<option value="Mexico">Mexico</option>
							<option value="Micronesia">Micronesia</option>
							<option value="Moldova">Moldova</option>
							<option value="Monaco">Monaco</option>
							<option value="Mongolia">Mongolia</option>
							<option value="Montenegro">Montenegro</option>
							<option value="Morocco">Morocco</option>
							<option value="Mozambique">Mozambique</option>
							<option value="Myanmar">Myanmar</option>
							<option value="Namibia">Namibia</option>
							<option value="Nauru">Nauru</option>
							<option value="Nepal">Nepal</option>
							<option value="Netherlands">Netherlands</option>
							<option value="New Zealand">New Zealand</option>
							<option value="Nicaragua">Nicaragua</option>
							<option value="Niger">Niger</option>
							<option value="Nigeria">Nigeria</option>
							<option value="North Korea">North Korea</option>
							<option value="North Macedonia">North Macedonia</option>
							<option value="Norway">Norway</option>
							<option value="Oman">Oman</option>
							<option value="Pakistan">Pakistan</option>
							<option value="Palau">Palau</option>
							<option value="Palestine">Palestine</option>
							<option value="Panama">Panama</option>
							<option value="Papua New Guinea">Papua New Guinea</option>
							<option value="Paraguay">Paraguay</option>
							<option value="Peru">Peru</option>
							<option value="Philippines">Philippines</option>
							<option value="Poland">Poland</option>
							<option value="Portugal">Portugal</option>
							<option value="Qatar">Qatar</option>
							<option value="Romania">Romania</option>
							<option value="Russia">Russia</option>
							<option value="Rwanda">Rwanda</option>
							<option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
							<option value="Saint Lucia">Saint Lucia</option>
							<option value="Saint Vincent and the Grenadines">Saint Vincent and the Grenadines</option>
							<option value="Samoa">Samoa</option>
							<option value="San Marino">San Marino</option>
							<option value="Sao Tome and Principe">Sao Tome and Principe</option>
							<option value="Saudi Arabia">Saudi Arabia</option>
							<option value="Senegal">Senegal</option>
							<option value="Serbia">Serbia</option>
							<option value="Seychelles">Seychelles</option>
							<option value="Sierra Leone">Sierra Leone</option>
							<option value="Singapore">Singapore</option>
							<option value="Slovakia">Slovakia</option>
							<option value="Slovenia">Slovenia</option>
							<option value="Solomon Islands">Solomon Islands</option>
							<option value="Somalia">Somalia</option>
							<option value="South Africa">South Africa</option>
							<option value="South Korea">South Korea</option>
							<option value="South Sudan">South Sudan</option>
							<option value="Spain">Spain</option>
							<option value="Sri Lanka">Sri Lanka</option>
							<option value="Sudan">Sudan</option>
							<option value="Suriname">Suriname</option>
							<option value="Sweden">Sweden</option>
							<option value="Switzerland">Switzerland</option>
							<option value="Syria">Syria</option>
							<option value="Taiwan">Taiwan</option>
							<option value="Tajikistan">Tajikistan</option>
							<option value="Tanzania">Tanzania</option>
							<option value="Thailand">Thailand</option>
							<option value="Timor-Leste">Timor-Leste</option>
							<option value="Togo">Togo</option>
							<option value="Tonga">Tonga</option>
							<option value="Trinidad and Tobago">Trinidad and Tobago</option>
							<option value="Tunisia">Tunisia</option>
							<option value="Turkey">Turkey</option>
							<option value="Turkmenistan">Turkmenistan</option>
							<option value="Tuvalu">Tuvalu</option>
							<option value="Uganda">Uganda</option>
							<option value="Ukraine">Ukraine</option>
							<option value="United Arab Emirates">United Arab Emirates</option>
							<option value="Uruguay">Uruguay</option>
							<option value="Uzbekistan">Uzbekistan</option>
							<option value="Vanuatu">Vanuatu</option>
							<option value="Vatican City">Vatican City</option>
							<option value="Venezuela">Venezuela</option>
							<option value="Vietnam">Vietnam</option>
							<option value="Yemen">Yemen</option>
							<option value="Zambia">Zambia</option>
							<option value="Zimbabwe">Zimbabwe</option>
						</select>
					</div>
				</div>

				<!-- ============================================================
				     TELL US MORE
				     ============================================================ -->
				<div class="prf-section-title" style="margin-top:32px;">Tell Us More</div>
				<div class="prf-divider"></div>

				<div class="prf-row">
					<div class="prf-col prf-col-full">
						<label class="prf-label prf-required">Year of Interest</label>
						<select name="year_of_interest" class="prf-select" required>
							<?php foreach ( $years as $year ) : ?>
								<option value="<?php echo esc_attr( $year ); ?>" <?php selected( $year, $current_year ); ?>>
									<?php echo esc_html( $year ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="prf-row">
					<div class="prf-col prf-col-full">
						<label class="prf-label prf-required">Preferred Dates</label>
						<input type="text" name="preferred_dates" class="prf-input" placeholder="e.g. Late July to mid-August" required>
						<p class="prf-hint">Describe your preferred camp dates in any format you like.</p>
					</div>
				</div>

				<div class="prf-row">
					<div class="prf-col prf-col-full">
						<label class="prf-label prf-required">Is your child a first-time camper?</label>
						<select name="first_time_camper" class="prf-select" required>
							<option value="Yes">Yes</option>
							<option value="No">No</option>
						</select>
					</div>
				</div>

				<!-- Session Length (dynamic) -->
				<div class="prf-row">
					<div class="prf-col prf-col-full">
						<label class="prf-label prf-required">Preferred Session Length <span class="prf-multi-hint">(you can select multiple options)</span></label>
						<div class="prf-checkbox-group" id="session-length-group">
							<?php if ( $session_lengths ) : ?>
								<?php foreach ( $session_lengths as $opt ) : ?>
									<label class="prf-checkbox-label">
									<input type="checkbox" name="session_lengths[]" value="<?php echo esc_attr( $opt->name ); ?>">
									<?php echo esc_html( $opt->name ); ?>
									</label>
								<?php endforeach; ?>
							<?php else : ?>
								<p class="prf-hint">No options configured yet.</p>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- Preferred Location (auto from camp states) -->
				<div class="prf-row">
					<div class="prf-col prf-col-full">
						<label class="prf-label prf-required">Preferred Location <span class="prf-multi-hint">(you can select multiple options)</span></label>
						<div class="prf-checkbox-group" id="preferred-location-group">
							<?php if ( $locations ) : ?>
								<?php foreach ( $locations as $loc ) : ?>
									<label class="prf-checkbox-label">
										<input type="checkbox" name="preferred_locations[]" value="<?php echo esc_attr( $loc ); ?>">
										<?php echo esc_html( $loc ); ?>
									</label>
								<?php endforeach; ?>
							<?php else : ?>
								<p class="prf-hint">No camp locations available yet.</p>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- Preferred Program (dynamic) -->
				<div class="prf-row">
					<div class="prf-col prf-col-full">
						<label class="prf-label prf-required">Preferred Program <span class="prf-multi-hint">(you can select multiple options)</span></label>
						<div class="prf-checkbox-group" id="preferred-program-group">
							<?php if ( $programs ) : ?>
								<?php foreach ( $programs as $opt ) : ?>
									<label class="prf-checkbox-label">
									<input type="checkbox" name="preferred_programs[]" value="<?php echo esc_attr( $opt->name ); ?>">
									<?php echo esc_html( $opt->name ); ?>
									</label>
								<?php endforeach; ?>
							<?php else : ?>
								<p class="prf-hint">No options configured yet.</p>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="prf-row">
					<div class="prf-col prf-col-full">
						<label class="prf-label">Please tell us more:</label>
						<textarea name="tell_us_more" class="prf-textarea" rows="6" placeholder="Any additional information about your child or what you're looking for in a camp..."></textarea>
					</div>
				</div>

				<div class="prf-row">
					<div class="prf-col prf-col-full">
						<label class="prf-label prf-required">How did you hear about us?</label>
						<select name="referral_source" class="prf-select" required>
							<option value="">Select an option</option>
							<option value="Google Search">Google Search</option>
							<option value="Social Media">Social Media</option>
							<option value="Friend / Family">Friend / Family</option>
							<option value="Camp Fair">Camp Fair</option>
							<option value="Other">Other</option>
						</select>
					</div>
				</div>

				<div class="prf-row">
					<div class="prf-col prf-col-full">
						<button type="submit" name="parent_reg_submit" class="prf-submit-btn">Submit</button>
					</div>
				</div>

			</form>
		</div><!-- .prf-wrapper -->
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// Handle submission
	// =========================================================================

	public function handle_submission() {
		if ( ! isset( $_POST['parent_reg_submit'] ) || ! isset( $_POST['parent_reg_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( wp_unslash( $_POST['parent_reg_nonce'] ), 'parent_reg_form_action' ) ) {
			wp_die( 'Security check failed.' );
		}

		$data = wp_unslash( $_POST );

		// ------------------------------------------------------------------
		// Validation
		// ------------------------------------------------------------------
		$errors = [];

		$parent_first = sanitize_text_field( $data['parent_first'] ?? '' );
		$parent_last  = sanitize_text_field( $data['parent_last'] ?? '' );
		$email        = sanitize_email( $data['email'] ?? '' );
		$child_first  = sanitize_text_field( $data['child_first'] ?? '' );

		if ( empty( $parent_first ) ) {
			$errors[] = 'Parent first name is required.';
		}
		if ( empty( $parent_last ) ) {
			$errors[] = 'Parent last name is required.';
		}
		if ( empty( $email ) || ! is_email( $email ) ) {
			$errors[] = 'A valid email address is required.';
		}
		if ( empty( $child_first ) ) {
			$errors[] = 'Child first name is required.';
		}
		$child_last = sanitize_text_field( $data['child_last'] ?? '' );
		if ( empty( $child_last ) ) {
			$errors[] = 'Child last name is required.';
		}
		if ( empty( $data['gender'] ) ) {
			$errors[] = 'Gender is required.';
		}
		if ( empty( $data['birthday'] ) ) {
			$errors[] = 'Birthday is required.';
		}
		if ( empty( $data['country'] ) ) {
			$errors[] = 'Country is required.';
		}
		if ( empty( $data['preferred_dates'] ) ) {
			$errors[] = 'Preferred dates are required.';
		}
		if ( empty( $data['year_of_interest'] ) ) {
			$errors[] = 'Year of interest is required.';
		}
		if ( empty( $data['first_time_camper'] ) ) {
			$errors[] = 'Please indicate if your child is a first-time camper.';
		}
		if ( empty( $data['session_lengths'] ) ) {
			$errors[] = 'Please select at least one preferred session length.';
		}
		if ( empty( $data['preferred_locations'] ) ) {
			$errors[] = 'Please select at least one preferred location.';
		}
		if ( empty( $data['preferred_programs'] ) ) {
			$errors[] = 'Please select at least one preferred program.';
		}
		if ( empty( $data['referral_source'] ) ) {
			$errors[] = 'Please tell us how you heard about us.';
		}

		if ( ! empty( $errors ) ) {
			// Store errors in transient and redirect back
			$transient_key = 'prf_errors_' . ( is_user_logged_in() ? get_current_user_id() : md5( $_SERVER['REMOTE_ADDR'] ?? '' ) );
			set_transient( $transient_key, $errors, 120 );
			wp_redirect( wp_get_referer() ?: home_url( '/find-the-perfect-summer-camp/' ) );
			exit;
		}

		// ------------------------------------------------------------------
		// User creation or lookup
		// ------------------------------------------------------------------
		$user_id     = null;
		$is_new_user = false;

		if ( is_user_logged_in() ) {
			$current = wp_get_current_user();
			if ( in_array( 'parent', (array) $current->roles, true ) ) {
				$user_id = $current->ID;
			}
		}

		if ( ! $user_id ) {
			$existing = get_user_by( 'email', $email );
			if ( $existing ) {
				// Existing WP user — attach submission but do NOT change their role/password
				$user_id = $existing->ID;
				// If they're not yet a parent, add the role
				if ( ! in_array( 'parent', (array) $existing->roles, true ) ) {
					$existing->add_role( 'parent' );
				}
			} else {
				// Create new parent user
				$username = $this->generate_username( $parent_first, $parent_last, $email );
				$password = wp_generate_password( 14, true );
				$user_id  = wp_create_user( $username, $password, $email );

				if ( is_wp_error( $user_id ) ) {
					wp_die( 'Error creating account: ' . $user_id->get_error_message() );
				}

				$user = new \WP_User( $user_id );
				$user->set_role( 'parent' );
				update_user_meta( $user_id, 'first_name', $parent_first );
				update_user_meta( $user_id, 'last_name', $parent_last );

				$is_new_user = true;
			}
		}

		// ------------------------------------------------------------------
		// Save submission
		// ------------------------------------------------------------------
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'cdbs_parent_registrations',
			[
				'user_id'             => $user_id,
				'parent_first'        => $parent_first,
				'parent_last'         => $parent_last,
				'email'               => $email,
				'phone'               => sanitize_text_field( $data['phone'] ?? '' ),
				'child_first'         => $child_first,
				'child_last'          => sanitize_text_field( $data['child_last'] ?? '' ),
				'gender'              => sanitize_text_field( $data['gender'] ?? '' ),
				'birthday'            => sanitize_text_field( $data['birthday'] ?? '' ),
				'address'             => sanitize_text_field( $data['address'] ?? '' ),
				'city'                => sanitize_text_field( $data['city'] ?? '' ),
				'camper_state'        => sanitize_text_field( $data['camper_state'] ?? '' ),
				'country'             => sanitize_text_field( $data['country'] ?? 'United States' ),
				'year_of_interest'    => sanitize_text_field( $data['year_of_interest'] ?? '' ),
				'preferred_dates'     => sanitize_text_field( $data['preferred_dates'] ?? '' ),
				'first_time_camper'   => sanitize_text_field( $data['first_time_camper'] ?? 'No' ),
				'session_lengths'     => wp_json_encode( array_map( 'sanitize_text_field', (array) ( $data['session_lengths'] ?? [] ) ) ),
				'preferred_locations' => wp_json_encode( array_map( 'sanitize_text_field', (array) ( $data['preferred_locations'] ?? [] ) ) ),
				'preferred_programs'  => wp_json_encode( array_map( 'sanitize_text_field', (array) ( $data['preferred_programs'] ?? [] ) ) ),
				'tell_us_more'        => sanitize_textarea_field( $data['tell_us_more'] ?? '' ),
				'referral_source'     => sanitize_text_field( $data['referral_source'] ?? '' ),
			],
			[
				'%d','%s','%s','%s','%s','%s','%s','%s','%s',
				'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',
			]
		);

		// ------------------------------------------------------------------
		// Welcome e-mail for new accounts
		// ------------------------------------------------------------------
		if ( $is_new_user ) {
			$this->send_welcome_email( $user_id, $parent_first, $email );
		}

		// Admin notification
		$this->send_admin_notification( $parent_first, $parent_last, $email, $child_first );

		// ------------------------------------------------------------------
		// Redirect: new users → password-reset flow; returning users → dashboard
		// ------------------------------------------------------------------
		set_transient( 'prf_success_' . $user_id, 1, 120 );
		if ( $is_new_user ) {
			$user      = get_user_by( 'id', $user_id );
			$reset_key = get_password_reset_key( $user );
			if ( ! is_wp_error( $reset_key ) ) {
				$redirect = home_url( '/set-password/?key=' . $reset_key . '&login=' . rawurlencode( $user->user_login ) );
			} else {
				$redirect = home_url( '/parent-dashboard/?registered=1' );
			}
		} else {
			$redirect = home_url( '/parent-dashboard/?registered=1' );
		}
		wp_redirect( $redirect );
		exit;
	}

	// =========================================================================
	// Username generator
	// =========================================================================

	private function generate_username( $first, $last, $email ) {
		// Try firstname.lastname
		$base = sanitize_user( strtolower( $first . '.' . $last ), true );
		if ( empty( $base ) ) {
			$base = sanitize_user( strtolower( explode( '@', $email )[0] ), true );
		}

		$username = $base;
		$counter  = 1;
		while ( username_exists( $username ) ) {
			$username = $base . $counter;
			$counter++;
		}
		return $username;
	}

	// =========================================================================
	// Emails
	// =========================================================================

	private function send_welcome_email( $user_id, $first_name, $email ) {
		$user      = get_user_by( 'id', $user_id );
		$site_name = get_bloginfo( 'name' );
		$login_url = wp_login_url( home_url( '/parent-dashboard/' ) );

		// Generate a password reset link so the parent can set their own password
		$reset_key = get_password_reset_key( $user );
		if ( ! is_wp_error( $reset_key ) ) {
			$reset_url = home_url( '/set-password/?key=' . $reset_key . '&login=' . rawurlencode( $user->user_login ) );
		} else {
			$reset_url = $login_url;
		}

		$subject = 'Welcome to ' . $site_name . ' — Set Your Password';

		$html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>';
		$html .= '<body style="font-family:Arial,sans-serif;line-height:1.6;color:#333;background-color:#f4f4f4;margin:0;padding:0;">';
		$html .= '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f4f4;">';
		$html .= '<tr><td align="center" style="padding:20px 10px;">';
		$html .= '<div style="max-width:600px;margin:20px auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.1);">';
		$html .= '<div style="background:linear-gradient(135deg,#497C5E 0%,#679B7C 100%);color:#ffffff;padding:30px 20px;text-align:center;">';
		$html .= '<h1 style="margin:0;font-size:28px;font-weight:bold;">Welcome to ' . esc_html( $site_name ) . '!</h1>';
		$html .= '</div>';
		$html .= '<div style="padding:30px 20px;">';
		$html .= '<h2 style="color:#497C5E;margin-top:0;font-size:22px;">Set Your Password &amp; Log In</h2>';
		$html .= '<p style="margin:15px 0;font-size:16px;">Hi ' . esc_html( $first_name ) . ',</p>';
		$html .= '<p style="margin:15px 0;font-size:16px;">Thank you for submitting your camp request through <strong style="color:#497C5E;">' . esc_html( $site_name ) . '</strong>!</p>';
		$html .= '<p style="margin:15px 0;font-size:16px;">An account has been created for you so you can track your submissions and save favourite camps.</p>';
		$html .= '<p style="margin:15px 0;font-size:16px;">Click the button below to set your password and access your parent dashboard:</p>';
		$html .= '<div style="text-align:center;">';
		$html .= '<a href="' . esc_url( $reset_url ) . '" style="display:inline-block;padding:14px 30px;background:#497C5E;color:#ffffff;text-decoration:none;border-radius:5px;font-weight:bold;margin:20px 0;">Set Your Password</a>';
		$html .= '</div>';
		$html .= '<p style="margin:15px 0;font-size:14px;color:#666;">This link will expire in <strong>24 hours</strong>. If it expires, visit <a href="' . esc_url( $login_url ) . '" style="color:#497C5E;">' . esc_url( $login_url ) . '</a> and click &ldquo;Lost your password?&rdquo;</p>';
		$html .= '</div>';
		$html .= '<div style="background:#f8f9fa;padding:20px;text-align:center;font-size:14px;color:#666;border-top:1px solid #e9ecef;">';
		$html .= '<p style="margin:0;"><strong>' . esc_html( $site_name ) . '</strong></p>';
		$html .= '<p style="margin:5px 0 0;">Helping families find the perfect summer camp experience</p>';
		$html .= '</div></div></td></tr></table></body></html>';

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		wp_mail( $email, $subject, $html, $headers );
	}

	private function send_admin_notification( $parent_first, $parent_last, $email, $child_first ) {
		$admin_email = get_option( 'admin_email' );
		$site_name   = get_bloginfo( 'name' );
		$admin_url   = admin_url( 'admin.php?page=cdbs-parent-submissions' );

		$subject = "[{$site_name}] New parent registration: {$parent_first} {$parent_last}";

		$html  = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">';
		$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;"><tr><td align="center">';
		$html .= '<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">';
		// Header
		$html .= '<tr><td style="background:linear-gradient(135deg,#3b7a57 0%,#497C5E 100%);padding:28px 36px;">';
		$html .= '<h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;letter-spacing:0.5px;">New Parent Registration</h1>';
		$html .= '<p style="color:rgba(255,255,255,0.85);margin:6px 0 0;font-size:14px;">' . esc_html( $site_name ) . '</p>';
		$html .= '</td></tr>';
		// Body
		$html .= '<tr><td style="padding:32px 36px;">';
		$html .= '<p style="margin:0 0 24px;font-size:15px;color:#374151;">A new parent has submitted a camp search request.</p>';
		$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f7f4;border-radius:6px;padding:20px;border:1px solid #d1e7da;">';
		$html .= '<tr><td style="padding:8px 16px;font-size:13px;color:#497C5E;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;width:140px;">Parent</td>';
		$html .= '<td style="padding:8px 16px;font-size:15px;color:#1e293b;font-weight:600;">' . esc_html( $parent_first . ' ' . $parent_last ) . '</td></tr>';
		$html .= '<tr><td style="padding:8px 16px;font-size:13px;color:#497C5E;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Email</td>';
		$html .= '<td style="padding:8px 16px;font-size:15px;color:#1e293b;"><a href="mailto:' . esc_attr( $email ) . '" style="color:#3b7a57;">' . esc_html( $email ) . '</a></td></tr>';
		$html .= '<tr><td style="padding:8px 16px;font-size:13px;color:#497C5E;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Child</td>';
		$html .= '<td style="padding:8px 16px;font-size:15px;color:#1e293b;font-weight:600;">' . esc_html( $child_first ) . '</td></tr>';
		$html .= '</table>';
		$html .= '<div style="text-align:center;margin-top:32px;">';
		$html .= '<a href="' . esc_url( $admin_url ) . '" style="display:inline-block;padding:14px 32px;background:#3b7a57;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:700;font-size:15px;">View in Admin</a>';
		$html .= '</div>';
		$html .= '</td></tr>';
		// Footer
		$html .= '<tr><td style="background:#f8fafc;padding:18px 36px;border-top:1px solid #e2e8f0;font-size:12px;color:#94a3b8;text-align:center;">' . esc_html( $site_name ) . ' &mdash; Admin Notification</td></tr>';
		$html .= '</table></td></tr></table></body></html>';

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		wp_mail( $admin_email, $subject, $html, $headers );
	}
}
