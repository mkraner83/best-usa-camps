<?php
/**
 * Parent Dashboard (front-end)
 *
 * Shortcode: [parent_dashboard]
 *
 * Tabs:
 *   1. My Submissions  – read-only list of all submissions for this parent
 *   2. My Favourites   – camps this parent has saved
 *   3. Messages        – placeholder (full messaging UI added in next phase)
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt\PublicArea;

defined( 'ABSPATH' ) || exit;

use CreativeDBS\CampMgmt\DB;

class Parent_Dashboard {

	public function __construct() {
		add_shortcode( 'parent_dashboard', [ $this, 'render_dashboard' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// AJAX: toggle favourite
		add_action( 'wp_ajax_parent_toggle_favourite', [ $this, 'ajax_toggle_favourite' ] );
	}

	// =========================================================================
	// Assets
	// =========================================================================

	public function enqueue_assets() {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && ( has_shortcode( $post->post_content, 'parent_dashboard' ) ) ) {
			wp_enqueue_style(
				'parent-dashboard',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/parent-dashboard.css',
				[],
				CDBS_CAMP_VERSION
			);
			wp_enqueue_script(
				'parent-dashboard',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/parent-dashboard.js',
				[ 'jquery' ],
				CDBS_CAMP_VERSION,
				true
			);
			wp_localize_script( 'parent-dashboard', 'parentDashboardData', [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'parent_dashboard_nonce' ),
			] );
		}
	}

	// =========================================================================
	// Main dashboard render
	// =========================================================================

	public function render_dashboard() {
		if ( ! is_user_logged_in() ) {
			$login_url = wp_login_url( home_url( '/parent-dashboard/' ) );
			return '<div class="pd-login-notice">Please <a href="' . esc_url( $login_url ) . '">log in</a> to view your dashboard.</div>';
		}

		$user = wp_get_current_user();
		if ( ! in_array( 'parent', (array) $user->roles, true ) ) {
			return '<div class="pd-login-notice">This dashboard is for parents only.</div>';
		}

		// Success notice after registration
		$show_success = isset( $_GET['registered'] ) && get_transient( 'prf_success_' . $user->ID );
		if ( $show_success ) {
			delete_transient( 'prf_success_' . $user->ID );
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'submissions';

		// Tab counts
		global $wpdb;
		$count_submissions = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}cdbs_parent_registrations WHERE user_id = %d",
			$user->ID
		) );
		$count_favourites  = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}cdbs_parent_favorites WHERE user_id = %d",
			$user->ID
		) );
		$count_messages    = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT id) FROM {$wpdb->prefix}cdbs_messages
			 WHERE sender_id = %d OR camp_id IN (
			     SELECT camp_id FROM {$wpdb->prefix}cdbs_messages WHERE sender_id = %d
			 )",
			$user->ID, $user->ID
		) );

		ob_start();
		?>
		<div class="pd-wrapper">

			<!-- Header -->
			<div class="pd-header">
				<div class="pd-header-inner">
					<h1 class="pd-title">Welcome, <?php echo esc_html( $user->first_name ?: $user->display_name ); ?>!</h1>
					<a href="<?php echo esc_url( wp_logout_url( home_url( '/camp-login/' ) ) ); ?>" class="pd-logout-btn">Log Out</a>
				</div>
			</div>

			<?php if ( $show_success ) : ?>
				<div class="pd-notice pd-notice-success">
					<strong>Thank you!</strong> Your request has been submitted. We will be in touch soon.
				</div>
			<?php endif; ?>

			<!-- Tabs -->
			<div class="pd-tabs">
				<a href="?tab=submissions" class="pd-tab <?php echo $active_tab === 'submissions' ? 'pd-tab-active' : ''; ?>">My Submissions<?php if ( $count_submissions > 0 ) : ?><span class="pd-tab-count"><?php echo $count_submissions; ?></span><?php endif; ?></a>
				<a href="?tab=favourites"  class="pd-tab <?php echo $active_tab === 'favourites'  ? 'pd-tab-active' : ''; ?>">My Favourites<?php if ( $count_favourites > 0 ) : ?><span class="pd-tab-count"><?php echo $count_favourites; ?></span><?php endif; ?></a>
				<a href="?tab=messages"    class="pd-tab <?php echo $active_tab === 'messages'    ? 'pd-tab-active' : ''; ?>">Messages<?php if ( $count_messages > 0 ) : ?><span class="pd-tab-count"><?php echo $count_messages; ?></span><?php endif; ?></a>
			</div>

			<!-- Tab content -->
			<div class="pd-content">
				<?php
				switch ( $active_tab ) {
					case 'favourites':
						echo $this->render_favourites( $user->ID );
						break;
					case 'messages':
						echo $this->render_messages( $user->ID );
						break;
					default:
						echo $this->render_submissions( $user->ID );
				}
				?>
			</div>

			<!-- Add another submission link -->
			<div class="pd-add-submission">
				<a href="<?php echo esc_url( home_url( '/find-the-perfect-summer-camp/' ) ); ?>" class="pd-add-btn">+ Submit Another Camp Request</a>
			</div>

		</div><!-- .pd-wrapper -->
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// Tab: Submissions
	// =========================================================================

	private function render_submissions( $user_id ) {
		global $wpdb;

		$submissions = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}cdbs_parent_registrations WHERE user_id = %d ORDER BY submitted_at DESC",
			$user_id
		) );

		ob_start();
		if ( empty( $submissions ) ) {
			?>
			<div class="pd-empty">
				<p>You haven't submitted any camp requests yet.</p>
				<a href="<?php echo esc_url( home_url( '/find-the-perfect-summer-camp/' ) ); ?>" class="pd-add-btn">Submit Your First Request</a>
			</div>
			<?php
		} else {
			?>
			<div class="pd-submissions-grid">
				<?php foreach ( $submissions as $sub ) :
					$session_lengths = json_decode( $sub->session_lengths ?: '[]', true );
					$locations       = json_decode( $sub->preferred_locations ?: '[]', true );
					$programs        = json_decode( $sub->preferred_programs ?: '[]', true );
					?>
					<div class="pd-card">
						<div class="pd-card-header">
							<div class="pd-card-child"><?php echo esc_html( $sub->child_first . ( $sub->child_last ? ' ' . $sub->child_last : '' ) ); ?></div>
							<div class="pd-card-date"><?php echo esc_html( date( 'M j, Y', strtotime( $sub->submitted_at ) ) ); ?></div>
						</div>

						<div class="pd-card-body">
							<div class="pd-meta-grid">
								<div class="pd-meta-tile">
									<span class="pd-meta-label">Year</span>
									<span class="pd-meta-value"><?php echo esc_html( $sub->year_of_interest ); ?></span>
								</div>
								<?php if ( $sub->preferred_dates ) : ?>
								<div class="pd-meta-tile">
									<span class="pd-meta-label">Preferred Dates</span>
									<span class="pd-meta-value"><?php echo esc_html( $sub->preferred_dates ); ?></span>
								</div>
								<?php endif; ?>
								<div class="pd-meta-tile">
									<span class="pd-meta-label">First-time Camper</span>
									<span class="pd-meta-value"><?php echo esc_html( $sub->first_time_camper ); ?></span>
								</div>
								<?php if ( ! empty( $session_lengths ) ) : ?>
								<div class="pd-meta-tile">
									<span class="pd-meta-label">Session Length</span>
									<span class="pd-meta-value"><?php echo esc_html( implode( ', ', $session_lengths ) ); ?></span>
								</div>
								<?php endif; ?>
								<?php if ( ! empty( $locations ) ) : ?>
								<div class="pd-meta-tile">
									<span class="pd-meta-label">Preferred Locations</span>
									<span class="pd-meta-value"><?php echo esc_html( implode( ', ', $locations ) ); ?></span>
								</div>
								<?php endif; ?>
								<?php if ( ! empty( $programs ) ) : ?>
								<div class="pd-meta-tile">
									<span class="pd-meta-label">Programs</span>
									<span class="pd-meta-value"><?php echo esc_html( implode( ', ', $programs ) ); ?></span>
								</div>
								<?php endif; ?>
							</div>
							<?php if ( $sub->tell_us_more ) : ?>
							<div class="pd-card-notes-wrap">
								<div class="pd-card-notes-label">Additional Info</div>
								<p class="pd-card-notes"><?php echo esc_html( $sub->tell_us_more ); ?></p>
							</div>
							<?php endif; ?>
						</div>

						<div class="pd-card-footer">
							<span class="pd-card-status pd-status-submitted">Submitted</span>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
		}
		return ob_get_clean();
	}

	// =========================================================================
	// Tab: Favourites
	// =========================================================================

	private function render_favourites( $user_id ) {
		global $wpdb;

		$favourites = $wpdb->get_results( $wpdb->prepare(
			"SELECT f.id as fav_id, f.camp_id, c.camp_name, c.city, c.state, c.about_camp, c.logo, c.unique_key
			 FROM {$wpdb->prefix}cdbs_parent_favorites f
			 INNER JOIN " . DB::table_camps() . " c ON c.id = f.camp_id
			 WHERE f.user_id = %d
			 ORDER BY f.created_at DESC",
			$user_id
		) );

		ob_start();
		if ( empty( $favourites ) ) {
			?>
			<div class="pd-empty">
				<p>You haven't saved any favourite camps yet.</p>
				<a href="<?php echo esc_url( home_url( '/find-the-perfect-american-summer-camp/' ) ); ?>" class="pd-add-btn">Browse Camps</a>
			</div>
			<?php
		} else {
			?>
			<div class="pd-favourites-grid">
				<?php foreach ( $favourites as $fav ) :
					$snippet = wp_trim_words( $fav->about_camp, 20, '...' );
					?>
					<div class="pd-fav-card" data-fav-id="<?php echo esc_attr( $fav->fav_id ); ?>" data-camp-id="<?php echo esc_attr( $fav->camp_id ); ?>">
						<?php if ( $fav->logo ) : ?>
							<img src="<?php echo esc_url( $fav->logo ); ?>" alt="<?php echo esc_attr( $fav->camp_name ); ?>" class="pd-fav-logo">
						<?php endif; ?>
						<div class="pd-fav-info">
							<h3 class="pd-fav-name"><?php echo esc_html( $fav->camp_name ); ?></h3>
							<?php if ( $fav->city || $fav->state ) : ?>
								<p class="pd-fav-location"><?php echo esc_html( trim( $fav->city . ', ' . $fav->state, ', ' ) ); ?></p>
							<?php endif; ?>
							<?php if ( $snippet ) : ?>
								<p class="pd-fav-snippet"><?php echo esc_html( $snippet ); ?></p>
							<?php endif; ?>
						</div>
						<div class="pd-fav-actions">
							<button class="pd-remove-fav-btn" data-camp-id="<?php echo esc_attr( $fav->camp_id ); ?>">&#9825; Remove</button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
		}
		return ob_get_clean();
	}

	// =========================================================================
	// Tab: Messages (placeholder)
	// =========================================================================

	private function render_messages( $user_id ) {
		global $wpdb;

		$messages = $wpdb->get_results( $wpdb->prepare(
			"SELECT m.*, c.camp_name
			 FROM {$wpdb->prefix}cdbs_messages m
			 LEFT JOIN " . DB::table_camps() . " c ON c.id = m.camp_id
			 WHERE m.sender_id = %d OR (m.camp_id IN (
			     SELECT camp_id FROM {$wpdb->prefix}cdbs_messages WHERE sender_id = %d
			 ))
			 ORDER BY m.created_at DESC
			 LIMIT 50",
			$user_id,
			$user_id
		) );

		ob_start();
		?>
		<div class="pd-messages">
			<?php if ( empty( $messages ) ) : ?>
				<div class="pd-empty">
					<p>No messages yet. You can send a message to a camp directly from the camp's page.</p>
					<a href="<?php echo esc_url( home_url( '/find-the-perfect-american-summer-camp/' ) ); ?>" class="pd-add-btn">Browse Camps</a>
				</div>
			<?php else : ?>
				<div class="pd-submissions-grid">
					<?php foreach ( $messages as $msg ) : ?>
						<div class="pd-card <?php echo $msg->is_read ? '' : 'pd-card-unread'; ?>">
							<div class="pd-card-header">
								<div class="pd-card-child"><?php echo esc_html( $msg->camp_name ?: '—' ); ?></div>
								<div class="pd-card-date"><?php echo esc_html( date( 'M j, Y', strtotime( $msg->created_at ) ) ); ?></div>
							</div>
							<div class="pd-card-body">
								<div class="pd-meta-grid">
									<div class="pd-meta-tile pd-meta-tile-wide">
										<span class="pd-meta-label">Message</span>
										<span class="pd-meta-value"><?php echo esc_html( $msg->body ); ?></span>
									</div>
								</div>
							</div>
							<div class="pd-card-footer">
								<?php if ( ! $msg->is_read ) : ?>
									<span class="pd-card-status pd-status-unread">Unread</span>
								<?php else : ?>
									<span class="pd-card-status pd-status-submitted">Sent</span>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// AJAX: toggle favourite
	// =========================================================================

	public function ajax_toggle_favourite() {
		check_ajax_referer( 'parent_dashboard_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'Not logged in.' ] );
		}

		$user    = wp_get_current_user();
		$camp_id = isset( $_POST['camp_id'] ) ? intval( $_POST['camp_id'] ) : 0;

		if ( ! $camp_id ) {
			wp_send_json_error( [ 'message' => 'Invalid camp.' ] );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'cdbs_parent_favorites';

		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE user_id = %d AND camp_id = %d",
			$user->ID, $camp_id
		) );

		if ( $exists ) {
			$wpdb->delete( $table, [ 'user_id' => $user->ID, 'camp_id' => $camp_id ], [ '%d', '%d' ] );
			wp_send_json_success( [ 'action' => 'removed' ] );
		} else {
			if ( ! in_array( 'parent', (array) $user->roles, true ) ) {
				wp_send_json_error( [ 'message' => 'Only parent accounts can save favourites.' ] );
			}
			$wpdb->insert( $table, [ 'user_id' => $user->ID, 'camp_id' => $camp_id ], [ '%d', '%d' ] );
			wp_send_json_success( [ 'action' => 'added' ] );
		}
	}
}
