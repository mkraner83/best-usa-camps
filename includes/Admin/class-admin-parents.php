<?php
/**
 * Admin: Parent System
 *
 * Registers sub-menus under "WP Camp Management":
 *   - Parents           (list of parent accounts)
 *   - Parent Submissions (all form submissions, filterable)
 *   - Parent Favourites  (parent <-> camp relationships)
 *   - Messages          (all messages, read-only for admin)
 *   - Dynamic Options   (manage Session Length + Program option lists)
 *
 * Also handles AJAX for adding/deleting/reordering dynamic options.
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt\Admin;

defined( 'ABSPATH' ) || exit;

use CreativeDBS\CampMgmt\DB;

class Admin_Parents {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menus' ], 30 );
		add_action( 'admin_post_cdbs_submission_update', [ $this, 'handle_submission_update' ] );
		add_action( 'admin_post_cdbs_submission_delete', [ $this, 'handle_submission_delete' ] );
		add_action( 'admin_post_cdbs_message_delete',    [ $this, 'handle_message_delete' ] );
		add_action( 'admin_post_cdbs_message_read',      [ $this, 'handle_message_read' ] );
		add_action( 'admin_post_cdbs_favourite_delete',  [ $this, 'handle_favourite_delete' ] );
	}

	// =========================================================================
	// Menu registration
	// =========================================================================

	public function add_menus() {
		add_submenu_page(
			'creativedbs-camp-mgmt',
			'Parents',
			'Parents',
			'manage_options',
			'cdbs-parents',
			[ $this, 'page_parents' ]
		);
		add_submenu_page(
			'creativedbs-camp-mgmt',
			'Parent Submissions',
			'Parent Submissions',
			'manage_options',
			'cdbs-parent-submissions',
			[ $this, 'page_submissions' ]
		);
		add_submenu_page(
			'creativedbs-camp-mgmt',
			'Parent Favourites',
			'Parent Favourites',
			'manage_options',
			'cdbs-parent-favourites',
			[ $this, 'page_favourites' ]
		);
		add_submenu_page(
			'creativedbs-camp-mgmt',
			'Parent Messages',
			'Parent Messages',
			'manage_options',
			'cdbs-parent-messages',
			[ $this, 'page_messages' ]
		);
	}

	// =========================================================================
	// Page: Parents list
	// =========================================================================

	public function page_parents() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		global $wpdb;

		// Get all parent users
		$parent_users = get_users( [ 'role' => 'parent', 'orderby' => 'registered', 'order' => 'DESC' ] );

		$reg_table = $wpdb->prefix . 'cdbs_parent_registrations';
		$fav_table = $wpdb->prefix . 'cdbs_parent_favorites';

		// Build counts maps
		$submission_counts = [];
		$raw_counts = $wpdb->get_results( "SELECT user_id, COUNT(*) as cnt FROM {$reg_table} GROUP BY user_id" );
		foreach ( $raw_counts as $r ) $submission_counts[ $r->user_id ] = $r->cnt;

		$fav_counts = [];
		$raw_favs = $wpdb->get_results( "SELECT user_id, COUNT(*) as cnt FROM {$fav_table} GROUP BY user_id" );
		foreach ( $raw_favs as $r ) $fav_counts[ $r->user_id ] = $r->cnt;

		$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

		?>
		<div class="wrap">
			<h1>Parents <span class="title-count">(<?php echo count( $parent_users ); ?>)</span></h1>

			<form method="get" style="margin-bottom:16px;">
				<input type="hidden" name="page" value="cdbs-parents">
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search by name or email…" style="width:300px;">
				<button type="submit" class="button">Search</button>
				<?php if ( $search ) : ?><a href="<?php echo admin_url( 'admin.php?page=cdbs-parents' ); ?>" class="button" style="margin-left:4px;">Clear</a><?php endif; ?>
			</form>

			<?php if ( empty( $parent_users ) ) : ?>
				<p>No parent accounts yet.</p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Name</th>
							<th>Email</th>
							<th>Registered</th>
							<th style="text-align:center;">Submissions</th>
							<th style="text-align:center;">Favourites</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $parent_users as $parent ) :
							if ( $search && stripos( $parent->display_name . ' ' . $parent->user_email, $search ) === false ) continue;
							$sub_cnt = $submission_counts[ $parent->ID ] ?? 0;
							$fav_cnt = $fav_counts[ $parent->ID ] ?? 0;
							?>
							<tr>
								<td><strong><?php echo esc_html( $parent->display_name ); ?></strong></td>
								<td><a href="mailto:<?php echo esc_attr( $parent->user_email ); ?>"><?php echo esc_html( $parent->user_email ); ?></a></td>
								<td><?php echo esc_html( date( 'M j, Y', strtotime( $parent->user_registered ) ) ); ?></td>
								<td style="text-align:center;">
									<a href="<?php echo admin_url( 'admin.php?page=cdbs-parent-submissions&user_id=' . $parent->ID ); ?>"><?php echo $sub_cnt; ?></a>
								</td>
								<td style="text-align:center;">
									<a href="<?php echo admin_url( 'admin.php?page=cdbs-parent-favourites&user_id=' . $parent->ID ); ?>"><?php echo $fav_cnt; ?></a>
								</td>
								<td>
									<a href="<?php echo get_edit_user_link( $parent->ID ); ?>" class="button button-small">Edit User</a>
									<a href="<?php echo admin_url( 'admin.php?page=cdbs-parent-submissions&user_id=' . $parent->ID ); ?>" class="button button-small">View Submissions</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	// =========================================================================
	// Page: Submissions
	// =========================================================================

	public function page_submissions() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		global $wpdb;
		$table = $wpdb->prefix . 'cdbs_parent_registrations';

		// ── Edit single submission ──────────────────────────────────────────────
		if ( isset( $_GET['action'], $_GET['id'] ) && $_GET['action'] === 'edit' ) {
			$id  = intval( $_GET['id'] );
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
			if ( ! $row ) {
				echo '<div class="notice notice-error"><p>Submission not found.</p></div>';
				return;
			}

			// Decode JSON arrays
			$sessions  = json_decode( $row->session_lengths      ?: '[]', true );
			$locs      = json_decode( $row->preferred_locations   ?: '[]', true );
			$progs     = json_decode( $row->preferred_programs    ?: '[]', true );

			// Available options
			$week_terms = $wpdb->get_results( "SELECT name FROM {$wpdb->prefix}camp_week_terms WHERE is_active = 1 ORDER BY sort_order ASC, name ASC" );
			$type_terms = $wpdb->get_results( "SELECT name FROM {$wpdb->prefix}camp_type_terms WHERE is_active = 1 ORDER BY sort_order ASC, name ASC" );
			$camp_states_raw = $wpdb->get_col( "SELECT DISTINCT state FROM " . DB::table_camps() . " WHERE approved = 1 AND state != '' ORDER BY state ASC" );

			$state_map = [
				'AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas','CA'=>'California',
				'CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware','FL'=>'Florida','GA'=>'Georgia',
				'HI'=>'Hawaii','ID'=>'Idaho','IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa',
				'KS'=>'Kansas','KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland',
				'MA'=>'Massachusetts','MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi','MO'=>'Missouri',
				'MT'=>'Montana','NE'=>'Nebraska','NV'=>'Nevada','NH'=>'New Hampshire','NJ'=>'New Jersey',
				'NM'=>'New Mexico','NY'=>'New York','NC'=>'North Carolina','ND'=>'North Dakota','OH'=>'Ohio',
				'OK'=>'Oklahoma','OR'=>'Oregon','PA'=>'Pennsylvania','RI'=>'Rhode Island','SC'=>'South Carolina',
				'SD'=>'South Dakota','TN'=>'Tennessee','TX'=>'Texas','UT'=>'Utah','VT'=>'Vermont',
				'VA'=>'Virginia','WA'=>'Washington','WV'=>'West Virginia','WI'=>'Wisconsin','WY'=>'Wyoming',
			];
			$location_options = [];
			foreach ( $camp_states_raw as $abbr ) {
				$location_options[] = $state_map[ $abbr ] ?? $abbr;
			}
			$location_options[] = 'No Preference';

			$saved = isset( $_GET['saved'] ) && $_GET['saved'] === '1';
			?>
			<div class="wrap">
				<h1>Edit Submission #<?php echo $id; ?> <a href="<?php echo admin_url( 'admin.php?page=cdbs-parent-submissions' ); ?>" class="page-title-action">← Back to List</a></h1>

				<?php if ( $saved ) : ?>
					<div class="notice notice-success is-dismissible"><p>Submission updated successfully.</p></div>
				<?php endif; ?>

				<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
					<?php wp_nonce_field( 'cdbs_submission_update_' . $id ); ?>
					<input type="hidden" name="action" value="cdbs_submission_update">
					<input type="hidden" name="submission_id" value="<?php echo intval( $id ); ?>">

					<style>
						.cdbs-edit-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; max-width:900px; }
						.cdbs-edit-grid .full-width { grid-column:1/-1; }
						.cdbs-edit-grid label { display:block; font-weight:600; margin-bottom:4px; }
						.cdbs-edit-grid input[type=text], .cdbs-edit-grid input[type=email],
						.cdbs-edit-grid select, .cdbs-edit-grid textarea { width:100%; }
						.cdbs-edit-grid textarea { min-height:80px; }
						.cdbs-section-title { grid-column:1/-1; border-bottom:2px solid #2271b1; padding-bottom:6px; color:#2271b1; margin-top:10px; }
						.cdbs-checkbox-group { display:flex; flex-wrap:wrap; gap:8px 20px; margin-top:6px; }
						.cdbs-checkbox-group label { font-weight:normal; }
					</style>

					<div class="cdbs-edit-grid" style="margin-top:16px;">

						<h3 class="cdbs-section-title">Parent Information</h3>
						<div>
							<label>First Name</label>
							<input type="text" name="parent_first" value="<?php echo esc_attr( $row->parent_first ); ?>">
						</div>
						<div>
							<label>Last Name</label>
							<input type="text" name="parent_last" value="<?php echo esc_attr( $row->parent_last ); ?>">
						</div>
						<div>
							<label>Email</label>
							<input type="email" name="email" value="<?php echo esc_attr( $row->email ); ?>">
						</div>
						<div>
							<label>Phone</label>
							<input type="text" name="phone" value="<?php echo esc_attr( $row->phone ); ?>">
						</div>

						<h3 class="cdbs-section-title">Camper Information</h3>
						<div>
							<label>Child First Name</label>
							<input type="text" name="child_first" value="<?php echo esc_attr( $row->child_first ); ?>">
						</div>
						<div>
							<label>Child Last Name</label>
							<input type="text" name="child_last" value="<?php echo esc_attr( $row->child_last ); ?>">
						</div>
						<div>
							<label>Gender</label>
							<select name="gender">
								<option value="">— Select —</option>
								<?php foreach ( [ 'Male', 'Female', 'Non-binary', 'Prefer not to say' ] as $g ) : ?>
									<option value="<?php echo esc_attr( $g ); ?>" <?php selected( $g, $row->gender ); ?>><?php echo esc_html( $g ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<label>Birthday</label>
							<input type="text" name="birthday" value="<?php echo esc_attr( $row->birthday ); ?>" placeholder="MM/DD/YYYY">
						</div>
						<div>
							<label>Address</label>
							<input type="text" name="address" value="<?php echo esc_attr( $row->address ); ?>">
						</div>
						<div>
							<label>City</label>
							<input type="text" name="city" value="<?php echo esc_attr( $row->city ); ?>">
						</div>
						<div>
							<label>State</label>
							<input type="text" name="camper_state" value="<?php echo esc_attr( $row->camper_state ); ?>">
						</div>
						<div>
							<label>Country</label>
							<input type="text" name="country" value="<?php echo esc_attr( $row->country ); ?>">
						</div>

						<h3 class="cdbs-section-title">Camp Preferences</h3>
						<div>
							<label>Year of Interest</label>
							<select name="year_of_interest">
								<?php foreach ( range( date('Y'), date('Y') + 3 ) as $yr ) : ?>
									<option value="<?php echo $yr; ?>" <?php selected( (string)$yr, $row->year_of_interest ); ?>><?php echo $yr; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<label>Preferred Dates</label>
							<input type="text" name="preferred_dates" value="<?php echo esc_attr( $row->preferred_dates ); ?>">
						</div>
						<div>
							<label>First-Time Camper?</label>
							<select name="first_time_camper">
								<option value="Yes" <?php selected( 'Yes', $row->first_time_camper ); ?>>Yes</option>
								<option value="No" <?php selected( 'No', $row->first_time_camper ); ?>>No</option>
							</select>
						</div>
						<div>
							<label>Referral Source</label>
							<select name="referral_source">
								<option value="">— Select —</option>
								<?php foreach ( [ 'Google Search', 'Social Media', 'Friend / Family', 'Camp Fair', 'Other' ] as $src ) : ?>
									<option value="<?php echo esc_attr( $src ); ?>" <?php selected( $src, $row->referral_source ); ?>><?php echo esc_html( $src ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="full-width">
							<label>Preferred Session Length</label>
							<div class="cdbs-checkbox-group">
								<?php foreach ( $week_terms as $term ) : ?>
									<label>
										<input type="checkbox" name="session_lengths[]" value="<?php echo esc_attr( $term->name ); ?>" <?php checked( in_array( $term->name, $sessions ) ); ?>>
										<?php echo esc_html( $term->name ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="full-width">
							<label>Preferred Location</label>
							<div class="cdbs-checkbox-group">
								<?php foreach ( $location_options as $loc ) : ?>
									<label>
										<input type="checkbox" name="preferred_locations[]" value="<?php echo esc_attr( $loc ); ?>" <?php checked( in_array( $loc, $locs ) ); ?>>
										<?php echo esc_html( $loc ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="full-width">
							<label>Preferred Program (Camp Type)</label>
							<div class="cdbs-checkbox-group">
								<?php foreach ( $type_terms as $term ) : ?>
									<label>
										<input type="checkbox" name="preferred_programs[]" value="<?php echo esc_attr( $term->name ); ?>" <?php checked( in_array( $term->name, $progs ) ); ?>>
										<?php echo esc_html( $term->name ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="full-width">
							<label>Tell Us More</label>
							<textarea name="tell_us_more"><?php echo esc_textarea( $row->tell_us_more ); ?></textarea>
						</div>

					</div>

					<p class="submit" style="max-width:900px;">
						<button type="submit" class="button button-primary button-large">Save Changes</button>
						<a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=cdbs_submission_delete&id=' . $id ), 'cdbs_submission_delete_' . $id ); ?>"
						   class="button button-link-delete"
						   style="margin-left:20px;"
						   onclick="return confirm('Are you sure you want to permanently delete this submission?');">
							Delete Submission
						</a>
					</p>
				</form>
			</div>
			<?php
			return;
		}

		// ── List view ────────────────────────────────────────────────────────────
		$filter_user      = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;
		$filter_year      = isset( $_GET['year'] ) ? sanitize_text_field( $_GET['year'] ) : '';
		$filter_referral  = isset( $_GET['referral'] ) ? sanitize_text_field( $_GET['referral'] ) : '';

		$where_parts = [];
		$where_vals  = [];
		if ( $filter_user )     { $where_parts[] = 'user_id = %d';          $where_vals[] = $filter_user; }
		if ( $filter_year )     { $where_parts[] = 'year_of_interest = %s'; $where_vals[] = $filter_year; }
		if ( $filter_referral ) { $where_parts[] = 'referral_source = %s';  $where_vals[] = $filter_referral; }

		$where_sql = $where_parts ? 'WHERE ' . implode( ' AND ', $where_parts ) : '';

		$per_page = 20;
		$page     = max( 1, intval( $_GET['paged'] ?? 1 ) );
		$offset   = ( $page - 1 ) * $per_page;

		$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
		$total     = $where_vals ? $wpdb->get_var( $wpdb->prepare( $count_sql, $where_vals ) ) : $wpdb->get_var( $count_sql );
		$total_pages = ceil( $total / $per_page );

		$rows_sql  = "SELECT * FROM {$table} {$where_sql} ORDER BY submitted_at DESC LIMIT %d OFFSET %d";
		$rows_vals = array_merge( $where_vals, [ $per_page, $offset ] );
		$rows      = $wpdb->get_results( $wpdb->prepare( $rows_sql, $rows_vals ) );

		$years = $wpdb->get_col( "SELECT DISTINCT year_of_interest FROM {$table} ORDER BY year_of_interest DESC" );

		$deleted = isset( $_GET['deleted'] ) && $_GET['deleted'] === '1';
		?>
		<div class="wrap">
			<h1>Parent Submissions <span class="title-count">(<?php echo intval( $total ); ?>)</span></h1>

			<?php if ( $deleted ) : ?>
				<div class="notice notice-success is-dismissible"><p>Submission deleted.</p></div>
			<?php endif; ?>

			<form method="get" style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
				<input type="hidden" name="page" value="cdbs-parent-submissions">
				<?php if ( $filter_user ) : ?>
					<input type="hidden" name="user_id" value="<?php echo esc_attr( $filter_user ); ?>">
					<strong>Filtered by parent #<?php echo $filter_user; ?></strong>
					<a href="<?php echo admin_url( 'admin.php?page=cdbs-parent-submissions' ); ?>" class="button button-small">Clear Filter</a>
				<?php endif; ?>
				<select name="year" class="postform">
					<option value="">— All Years —</option>
					<?php foreach ( $years as $y ) : ?>
						<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $y, $filter_year ); ?>><?php echo esc_html( $y ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="referral" class="postform">
					<option value="">— All Sources —</option>
					<option value="Google Search" <?php selected( 'Google Search', $filter_referral ); ?>>Google Search</option>
					<option value="Social Media" <?php selected( 'Social Media', $filter_referral ); ?>>Social Media</option>
					<option value="Friend / Family" <?php selected( 'Friend / Family', $filter_referral ); ?>>Friend / Family</option>
					<option value="Camp Fair" <?php selected( 'Camp Fair', $filter_referral ); ?>>Camp Fair</option>
					<option value="Other" <?php selected( 'Other', $filter_referral ); ?>>Other</option>
				</select>
				<button type="submit" class="button">Filter</button>
			</form>

			<?php if ( empty( $rows ) ) : ?>
				<p>No submissions found.</p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped cdbs-submissions-table">
					<thead>
						<tr>
							<th style="width:110px;">Date</th>
							<th>Parent</th>
							<th>Child</th>
							<th>Year</th>
							<th>Preferred Dates</th>
							<th>Session Length</th>
							<th>Locations</th>
							<th>Programs</th>
							<th>Source</th>
							<th style="width:120px;">Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $row ) :
							$sessions  = implode( ', ', json_decode( $row->session_lengths      ?: '[]', true ) );
							$locs      = implode( ', ', json_decode( $row->preferred_locations   ?: '[]', true ) );
							$progs     = implode( ', ', json_decode( $row->preferred_programs    ?: '[]', true ) );
							$edit_url  = admin_url( 'admin.php?page=cdbs-parent-submissions&action=edit&id=' . $row->id );
							$del_url   = wp_nonce_url( admin_url( 'admin-post.php?action=cdbs_submission_delete&id=' . $row->id ), 'cdbs_submission_delete_' . $row->id );
							?>
							<tr>
								<td><?php echo esc_html( date( 'M j, Y', strtotime( $row->submitted_at ) ) ); ?></td>
								<td>
									<?php echo esc_html( $row->parent_first . ' ' . $row->parent_last ); ?><br>
									<a href="mailto:<?php echo esc_attr( $row->email ); ?>" style="font-size:12px;color:#999;"><?php echo esc_html( $row->email ); ?></a>
								</td>
								<td><?php echo esc_html( $row->child_first . ' ' . $row->child_last ); ?></td>
								<td><?php echo esc_html( $row->year_of_interest ); ?></td>
								<td><?php echo esc_html( $row->preferred_dates ?: '—' ); ?></td>
								<td><?php echo esc_html( $sessions ?: '—' ); ?></td>
								<td><?php echo esc_html( $locs ?: '—' ); ?></td>
								<td><?php echo esc_html( $progs ?: '—' ); ?></td>
								<td><?php echo esc_html( $row->referral_source ?: '—' ); ?></td>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small">Edit</a>
									<a href="<?php echo esc_url( $del_url ); ?>"
									   class="button button-small"
									   style="color:#cc1818;margin-top:4px;"
									   onclick="return confirm('Delete this submission?');">Delete</a>
								</td>
							</tr>
							<?php if ( $row->tell_us_more ) : ?>
							<tr>
								<td colspan="10" style="font-size:12px;color:#666;background:#f9f9f9;padding:6px 12px;">
									<strong>Notes:</strong> <?php echo esc_html( $row->tell_us_more ); ?>
								</td>
							</tr>
							<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $total_pages > 1 ) :
					$base_url = admin_url( 'admin.php?page=cdbs-parent-submissions' );
					echo '<div class="tablenav bottom"><div class="tablenav-pages">';
					echo paginate_links( [
						'base'    => add_query_arg( 'paged', '%#%', $base_url ),
						'format'  => '',
						'current' => $page,
						'total'   => $total_pages,
						'type'    => 'plain',
					] );
					echo '</div></div>';
				endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	// =========================================================================
	// Handler: update submission
	// =========================================================================

	public function handle_submission_update() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		$id = intval( $_POST['submission_id'] ?? 0 );
		check_admin_referer( 'cdbs_submission_update_' . $id );

		if ( ! $id ) wp_die( 'Invalid submission.' );

		global $wpdb;
		$table = $wpdb->prefix . 'cdbs_parent_registrations';

		$wpdb->update(
			$table,
			[
				'parent_first'         => sanitize_text_field( $_POST['parent_first'] ?? '' ),
				'parent_last'          => sanitize_text_field( $_POST['parent_last'] ?? '' ),
				'email'                => sanitize_email( $_POST['email'] ?? '' ),
				'phone'                => sanitize_text_field( $_POST['phone'] ?? '' ),
				'child_first'          => sanitize_text_field( $_POST['child_first'] ?? '' ),
				'child_last'           => sanitize_text_field( $_POST['child_last'] ?? '' ),
				'gender'               => sanitize_text_field( $_POST['gender'] ?? '' ),
				'birthday'             => sanitize_text_field( $_POST['birthday'] ?? '' ),
				'address'              => sanitize_text_field( $_POST['address'] ?? '' ),
				'city'                 => sanitize_text_field( $_POST['city'] ?? '' ),
				'camper_state'         => sanitize_text_field( $_POST['camper_state'] ?? '' ),
				'country'              => sanitize_text_field( $_POST['country'] ?? '' ),
				'year_of_interest'     => sanitize_text_field( $_POST['year_of_interest'] ?? '' ),
				'preferred_dates'      => sanitize_text_field( $_POST['preferred_dates'] ?? '' ),
				'first_time_camper'    => sanitize_text_field( $_POST['first_time_camper'] ?? '' ),
				'referral_source'      => sanitize_text_field( $_POST['referral_source'] ?? '' ),
				'session_lengths'      => wp_json_encode( array_map( 'sanitize_text_field', (array) ( $_POST['session_lengths'] ?? [] ) ) ),
				'preferred_locations'  => wp_json_encode( array_map( 'sanitize_text_field', (array) ( $_POST['preferred_locations'] ?? [] ) ) ),
				'preferred_programs'   => wp_json_encode( array_map( 'sanitize_text_field', (array) ( $_POST['preferred_programs'] ?? [] ) ) ),
				'tell_us_more'         => sanitize_textarea_field( $_POST['tell_us_more'] ?? '' ),
			],
			[ 'id' => $id ],
			null,
			[ '%d' ]
		);

		wp_redirect( admin_url( 'admin.php?page=cdbs-parent-submissions&action=edit&id=' . $id . '&saved=1' ) );
		exit;
	}

	// =========================================================================
	// Handler: delete submission
	// =========================================================================

	public function handle_submission_delete() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		$id = intval( $_GET['id'] ?? 0 );
		check_admin_referer( 'cdbs_submission_delete_' . $id );

		if ( ! $id ) wp_die( 'Invalid submission.' );

		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'cdbs_parent_registrations', [ 'id' => $id ], [ '%d' ] );

		wp_redirect( admin_url( 'admin.php?page=cdbs-parent-submissions&deleted=1' ) );
		exit;
	}

	// =========================================================================
	// Page: Favourites
	// =========================================================================

	public function page_favourites() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		global $wpdb;
		$fav_table  = $wpdb->prefix . 'cdbs_parent_favorites';
		$filter_user = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;

		$where = $filter_user ? $wpdb->prepare( 'WHERE f.user_id = %d', $filter_user ) : '';

		$rows = $wpdb->get_results(
			"SELECT f.id, f.user_id, f.camp_id, f.created_at,
			        u.display_name, u.user_email,
			        c.camp_name, c.state
			 FROM {$fav_table} f
			 LEFT JOIN {$wpdb->users} u ON u.ID = f.user_id
			 LEFT JOIN " . DB::table_camps() . " c ON c.id = f.camp_id
			 {$where}
			 ORDER BY f.created_at DESC
			 LIMIT 200"
		);
		?>
		<div class="wrap">
			<h1>Parent Favourites <?php if ( $filter_user ) echo '— User #' . $filter_user; ?></h1>
			<?php if ( isset( $_GET['deleted'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Favourite removed.</p></div>
			<?php endif; ?>
			<?php if ( $filter_user ) : ?>
				<a href="<?php echo admin_url( 'admin.php?page=cdbs-parent-favourites' ); ?>" class="button button-small" style="margin-bottom:12px;">Show All</a>
			<?php endif; ?>
			<?php if ( empty( $rows ) ) : ?>
				<p>No favourites saved yet.</p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Parent</th>
							<th>Camp</th>
							<th>State</th>
							<th>Saved On</th>
							<th style="width:80px;">Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td>
									<?php echo esc_html( $row->display_name ); ?><br>
									<a href="mailto:<?php echo esc_attr( $row->user_email ); ?>" style="font-size:12px;color:#999;"><?php echo esc_html( $row->user_email ); ?></a>
								</td>
								<td><?php echo esc_html( $row->camp_name ?: '(deleted)' ); ?></td>
								<td><?php echo esc_html( $row->state ?: '—' ); ?></td>
								<td><?php echo esc_html( date( 'M j, Y', strtotime( $row->created_at ) ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cdbs_favourite_delete&id=' . $row->id ), 'cdbs_favourite_delete_' . $row->id ) ); ?>" class="button button-small" style="color:#cc1818;" onclick="return confirm('Remove this favourite?');">Delete</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	// =========================================================================
	// Page: Messages
	// =========================================================================

	public function page_messages() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		global $wpdb;
		$msg_table = $wpdb->prefix . 'cdbs_messages';

		$rows = $wpdb->get_results(
			"SELECT m.*, c.camp_name,
			        u.display_name as sender_name, u.user_email as sender_email
			 FROM {$msg_table} m
			 LEFT JOIN " . DB::table_camps() . " c ON c.id = m.camp_id
			 LEFT JOIN {$wpdb->users} u ON u.ID = m.sender_id
			 ORDER BY m.created_at DESC
			 LIMIT 200"
		);
		?>
		<div class="wrap">
			<h1>Parent Messages</h1>
			<?php if ( isset( $_GET['deleted'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Message deleted.</p></div>
			<?php endif; ?>
			<?php if ( empty( $rows ) ) : ?>
				<p>No messages yet.</p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width:140px;">Date</th>
							<th>From</th>
							<th>Camp</th>
							<th>Message</th>
							<th style="width:60px;">Read</th>
							<th style="width:130px;">Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $msg ) : ?>
							<tr <?php echo ! $msg->is_read ? 'style="font-weight:600;"' : ''; ?>>
								<td><?php echo esc_html( date( 'M j, Y g:i a', strtotime( $msg->created_at ) ) ); ?></td>
								<td>
									<?php echo esc_html( $msg->sender_name ); ?><br>
									<span style="font-size:12px;color:#999;font-weight:400;"><?php echo esc_html( $msg->sender_email ); ?></span>
								</td>
								<td><?php echo esc_html( $msg->camp_name ?: '—' ); ?></td>
								<td>
									<?php
									$body = esc_html( $msg->body );
									if ( strlen( $body ) > 120 ) {
										echo '<details><summary>' . substr( $body, 0, 120 ) . '…</summary><div style="margin-top:6px;padding:8px;background:#f0f0f1;border-radius:3px;">' . $body . '</div></details>';
									} else {
										echo $body;
									}
									?>
								</td>
								<td><?php echo $msg->is_read ? '✓' : '—'; ?></td>
								<td style="white-space:nowrap;">
									<?php if ( ! $msg->is_read ) : ?>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cdbs_message_read&id=' . $msg->id ), 'cdbs_message_read_' . $msg->id ) ); ?>" class="button button-small" style="margin-bottom:4px;">Mark Read</a><br>
									<?php endif; ?>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cdbs_message_delete&id=' . $msg->id ), 'cdbs_message_delete_' . $msg->id ) ); ?>" class="button button-small" style="color:#cc1818;" onclick="return confirm('Delete this message?');">Delete</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	// =========================================================================
	// (Dynamic Form Options removed — Session Lengths and Camp Types are now
	//  managed via Camp Management → Weeks and Camp Management → Types)
	// =========================================================================

	// =========================================================================
	// Handlers: Messages
	// =========================================================================

	public function handle_message_delete() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		$id = intval( $_GET['id'] ?? 0 );
		check_admin_referer( 'cdbs_message_delete_' . $id );
		if ( $id ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'cdbs_messages', [ 'id' => $id ], [ '%d' ] );
		}
		wp_redirect( admin_url( 'admin.php?page=cdbs-parent-messages&deleted=1' ) );
		exit;
	}

	public function handle_message_read() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		$id = intval( $_GET['id'] ?? 0 );
		check_admin_referer( 'cdbs_message_read_' . $id );
		if ( $id ) {
			global $wpdb;
			$wpdb->update( $wpdb->prefix . 'cdbs_messages', [ 'is_read' => 1 ], [ 'id' => $id ], [ '%d' ], [ '%d' ] );
		}
		wp_redirect( admin_url( 'admin.php?page=cdbs-parent-messages' ) );
		exit;
	}

	// =========================================================================
	// Handlers: Favourites
	// =========================================================================

	public function handle_favourite_delete() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		$id = intval( $_GET['id'] ?? 0 );
		check_admin_referer( 'cdbs_favourite_delete_' . $id );
		if ( $id ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'cdbs_parent_favorites', [ 'id' => $id ], [ '%d' ] );
		}
		wp_redirect( admin_url( 'admin.php?page=cdbs-parent-favourites&deleted=1' ) );
		exit;
	}

	// @deprecated — kept as stub to avoid fatal errors from any cached hooks
	public function page_dynamic_options() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		global $wpdb;
		$table = $wpdb->prefix . 'cdbs_parent_dynamic_options';

		$active_type = isset( $_GET['opt_type'] ) ? sanitize_key( $_GET['opt_type'] ) : 'session_length';
		if ( ! in_array( $active_type, [ 'session_length', 'program' ], true ) ) {
			$active_type = 'session_length';
		}

		$saved = isset( $_GET['saved'] ) && $_GET['saved'] === '1';
		$added = isset( $_GET['added'] ) && $_GET['added'] === '1';

		$options = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE type = %s ORDER BY sort_order ASC, label ASC",
			$active_type
		) );

		$type_labels = [
			'session_length' => 'Session Lengths',
			'program'        => 'Preferred Programs',
		];
		?>
		<div class="wrap">
			<h1>Dynamic Form Options</h1>
			<p style="color:#666;">These options appear as checkboxes on the parent registration form. <strong>Preferred Locations</strong> are auto-pulled from approved camp states and cannot be edited here.</p>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p>Options saved.</p></div>
			<?php endif; ?>
			<?php if ( $added ) : ?>
				<div class="notice notice-success is-dismissible"><p>Option added.</p></div>
			<?php endif; ?>

			<!-- Type tabs -->
			<ul class="subsubsub" style="margin-bottom:16px;">
				<?php foreach ( $type_labels as $type => $label ) : ?>
					<li>
						<a href="<?php echo admin_url( 'admin.php?page=cdbs-parent-options&opt_type=' . $type ); ?>"
						   class="<?php echo $active_type === $type ? 'current' : ''; ?>">
							<?php echo esc_html( $label ); ?>
						</a>
						<?php end( $type_labels ) !== $label ? print( ' | ' ) : ''; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<br class="clear">

			<div style="display:flex;gap:40px;align-items:flex-start;flex-wrap:wrap;">

				<!-- Current options list -->
				<div style="flex:1;min-width:300px;">
					<h2><?php echo esc_html( $type_labels[ $active_type ] ); ?></h2>

					<?php if ( empty( $options ) ) : ?>
						<p>No options yet. Add one using the form on the right.</p>
					<?php else : ?>
						<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
							<?php wp_nonce_field( 'cdbs_parent_options_save' ); ?>
							<input type="hidden" name="action" value="cdbs_parent_option_save">
							<input type="hidden" name="opt_type" value="<?php echo esc_attr( $active_type ); ?>">

							<table class="wp-list-table widefat fixed striped" id="cdbs-options-table">
								<thead>
									<tr>
										<th style="width:36px;">#</th>
										<th>Label</th>
										<th style="width:80px;text-align:center;">Active</th>
										<th style="width:80px;">Delete</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $options as $i => $opt ) : ?>
										<tr data-id="<?php echo esc_attr( $opt->id ); ?>">
											<td>
												<input type="hidden" name="option_ids[]" value="<?php echo esc_attr( $opt->id ); ?>">
												<input type="hidden" name="option_orders[]" value="<?php echo esc_attr( $i + 1 ); ?>" class="sort-order-input">
												<?php echo intval( $i + 1 ); ?>
											</td>
											<td>
												<input
													type="text"
													name="option_labels[<?php echo esc_attr( $opt->id ); ?>]"
													value="<?php echo esc_attr( $opt->label ); ?>"
													style="width:100%;"
												>
											</td>
											<td style="text-align:center;">
												<input type="checkbox" name="option_active[<?php echo esc_attr( $opt->id ); ?>]" value="1" <?php checked( $opt->is_active, 1 ); ?>>
											</td>
											<td>
												<a
													href="<?php echo wp_nonce_url(
														admin_url( 'admin-post.php?action=cdbs_parent_option_delete&id=' . $opt->id . '&opt_type=' . $active_type ),
														'cdbs_parent_option_delete'
													); ?>"
													onclick="return confirm('Delete this option?');"
													class="button button-small"
													style="color:#cc1818;"
												>Delete</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>

							<p class="submit">
								<button type="submit" class="button button-primary">Save Changes</button>
							</p>
						</form>
					<?php endif; ?>
				</div>

				<!-- Add new option -->
				<div style="min-width:260px;">
					<h2>Add New Option</h2>
					<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
						<?php wp_nonce_field( 'cdbs_parent_option_add' ); ?>
						<input type="hidden" name="action" value="cdbs_parent_option_add">
						<input type="hidden" name="opt_type" value="<?php echo esc_attr( $active_type ); ?>">
						<table class="form-table">
							<tr>
								<th><label for="new_option_label">Label</label></th>
								<td><input type="text" id="new_option_label" name="new_label" required style="width:220px;"></td>
							</tr>
						</table>
						<p class="submit">
							<button type="submit" class="button button-primary">Add Option</button>
						</p>
					</form>
				</div>

			</div><!-- flex wrapper -->
		</div>
		<?php
	}

	// =========================================================================
	// Form handlers
	// =========================================================================

	public function handle_option_add() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		check_admin_referer( 'cdbs_parent_option_add' );

		$type  = in_array( $_POST['opt_type'] ?? '', [ 'session_length', 'program' ], true ) ? $_POST['opt_type'] : '';
		$label = sanitize_text_field( $_POST['new_label'] ?? '' );

		if ( $type && $label ) {
			global $wpdb;
			$table = $wpdb->prefix . 'cdbs_parent_dynamic_options';
			$max_order = (int) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(sort_order) FROM {$table} WHERE type = %s", $type ) );
			$wpdb->insert( $table, [ 'type' => $type, 'label' => $label, 'sort_order' => $max_order + 1 ], [ '%s', '%s', '%d' ] );
		}

		wp_redirect( admin_url( 'admin.php?page=cdbs-parent-options&opt_type=' . urlencode( $type ) . '&added=1' ) );
		exit;
	}

	public function handle_option_delete() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		check_admin_referer( 'cdbs_parent_option_delete' );

		$id   = intval( $_GET['id'] ?? 0 );
		$type = sanitize_key( $_GET['opt_type'] ?? 'session_length' );

		if ( $id ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'cdbs_parent_dynamic_options', [ 'id' => $id ], [ '%d' ] );
		}

		wp_redirect( admin_url( 'admin.php?page=cdbs-parent-options&opt_type=' . urlencode( $type ) . '&saved=1' ) );
		exit;
	}

	public function handle_options_save_order() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		check_admin_referer( 'cdbs_parent_options_save' );

		$type   = in_array( $_POST['opt_type'] ?? '', [ 'session_length', 'program' ], true ) ? $_POST['opt_type'] : '';
		$ids    = array_map( 'intval', $_POST['option_ids'] ?? [] );
		$labels = $_POST['option_labels'] ?? [];
		$active = $_POST['option_active'] ?? [];

		if ( $type && $ids ) {
			global $wpdb;
			$table = $wpdb->prefix . 'cdbs_parent_dynamic_options';
			foreach ( $ids as $i => $id ) {
				$label = sanitize_text_field( $labels[ $id ] ?? '' );
				$is_active = isset( $active[ $id ] ) ? 1 : 0;
				$wpdb->update(
					$table,
					[ 'label' => $label, 'is_active' => $is_active, 'sort_order' => $i + 1 ],
					[ 'id' => $id ],
					[ '%s', '%d', '%d' ],
					[ '%d' ]
				);
			}
		}

		wp_redirect( admin_url( 'admin.php?page=cdbs-parent-options&opt_type=' . urlencode( $type ) . '&saved=1' ) );
		exit;
	}

	public function ajax_toggle_option() {
		check_ajax_referer( 'cdbs_parent_options_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		$id        = intval( $_POST['id'] ?? 0 );
		$is_active = intval( $_POST['is_active'] ?? 0 );

		if ( $id ) {
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'cdbs_parent_dynamic_options',
				[ 'is_active' => $is_active ],
				[ 'id' => $id ],
				[ '%d' ],
				[ '%d' ]
			);
		}
		wp_send_json_success();
	}
}
