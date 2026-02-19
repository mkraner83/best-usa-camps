<?php
/**
 * Camp Page Shortcodes for Parents
 *
 * [camp_favourite_button camp_id="123"]
 *   Shows an Add/Remove Favourite button for logged-in parents.
 *   Non-logged-in visitors see a "Log in to save" message.
 *
 * [camp_contact_form camp_id="123"]
 *   Shows a short message form that saves to wp_cdbs_messages.
 *   Works for both logged-in parents and guests (guests provide name + email).
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt\PublicArea;

defined( 'ABSPATH' ) || exit;

use CreativeDBS\CampMgmt\DB;

class Parent_Camp_Shortcodes {

	public function __construct() {
		add_shortcode( 'camp_favourite_button', [ $this, 'render_favourite_button' ] );
		add_shortcode( 'camp_contact_form',     [ $this, 'render_contact_form' ] );
		add_shortcode( 'cdbs_login_bar',        [ $this, 'render_login_bar' ] );

		add_action( 'wp_ajax_parent_toggle_fav',        [ $this, 'ajax_toggle_fav' ] );
		add_action( 'wp_ajax_nopriv_parent_toggle_fav', [ $this, 'ajax_fav_not_logged_in' ] );

		add_action( 'init', [ $this, 'handle_contact_form_submission' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	// =========================================================================
	// Login status bar
	// =========================================================================

	public function render_login_bar() {
		if ( ! is_user_logged_in() ) {
			ob_start();
			?>
			<div class="cdbs-login-bar cdbs-login-bar--guest">
				<a href="<?php echo esc_url( home_url( '/camp-login/' ) ); ?>" class="cdbs-login-bar__link">Log In</a>
				<span class="cdbs-login-bar__sep"></span>
				<a href="<?php echo esc_url( home_url( '/get-listed-on-best-usa-summer-camps/' ) ); ?>" class="cdbs-login-bar__link">Register (Camp)</a>
				<span class="cdbs-login-bar__sep"></span>
				<a href="<?php echo esc_url( home_url( '/find-the-perfect-summer-camp/' ) ); ?>" class="cdbs-login-bar__link">Register (Parent)</a>
			</div>
			<?php
			return ob_get_clean();
		}

		$user = wp_get_current_user();
		$roles = (array) $user->roles;

		if ( in_array( 'camp', $roles, true ) ) {
			$role_label   = 'Camp Director';
			$dashboard_url = home_url( '/user-dashboard/' );
		} elseif ( in_array( 'parent', $roles, true ) ) {
			$role_label   = 'Parent';
			$dashboard_url = home_url( '/parent-dashboard/' );
		} else {
			return ''; // admins / other roles — don't show the bar
		}

		$name       = esc_html( $user->first_name ?: $user->display_name );
		$logout_url = esc_url( wp_logout_url( home_url( '/camp-login/' ) ) );

		ob_start();
		?>
		<div class="cdbs-login-bar">
			<span class="cdbs-login-bar__who">
				<span class="cdbs-login-bar__dot"></span>
				<strong><?php echo $name; ?></strong>
				<span class="cdbs-login-bar__role"><?php echo esc_html( $role_label ); ?></span>
			</span>
			<span class="cdbs-login-bar__sep"></span>
			<a href="<?php echo esc_url( $dashboard_url ); ?>" class="cdbs-login-bar__link">My Dashboard</a>
			<span class="cdbs-login-bar__sep"></span>
			<a href="<?php echo $logout_url; ?>" class="cdbs-login-bar__link cdbs-login-bar__logout">Log Out</a>
		</div>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// Assets
	// =========================================================================

	public function enqueue_assets() {
		// Only load on singular posts/pages that may contain these shortcodes
		if ( ! is_singular() ) return;

		wp_enqueue_style(
			'parent-camp-shortcodes',
			plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/parent-camp-shortcodes.css',
			[],
			CDBS_CAMP_VERSION
		);
		wp_enqueue_script(
			'parent-camp-shortcodes',
			plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/parent-camp-shortcodes.js',
			[ 'jquery' ],
			CDBS_CAMP_VERSION,
			true
		);
		wp_localize_script( 'parent-camp-shortcodes', 'parentCampData', [
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'parent_camp_nonce' ),
			'isLoggedIn'   => is_user_logged_in(),
			'loginUrl'     => wp_login_url( get_permalink() ),
			'dashboardUrl' => home_url( '/parent-dashboard/' ),
		] );
	}

	// =========================================================================
	// [camp_favourite_button]
	// =========================================================================

	public function render_favourite_button( $atts ) {
		$atts    = shortcode_atts( [ 'camp_id' => 0 ], $atts );
		$camp_id = intval( $atts['camp_id'] );

		// Fallback: read camp id from the page's custom field "camp_id"
		if ( ! $camp_id ) {
			$page_id = get_the_ID() ?: get_queried_object_id();
			$camp_id = intval( get_post_meta( $page_id, 'camp_id', true ) );
		}

		if ( ! $camp_id ) return '';

		if ( ! is_user_logged_in() ) {
			$login_url = wp_login_url( get_permalink() );
			return '<div class="pcs-fav-wrapper"><a href="' . esc_url( $login_url ) . '" class="pcs-fav-btn pcs-fav-guest">&#9825; Save to Favourites</a><span class="pcs-fav-hint">Log in to save this camp</span></div>';
		}

		$user = wp_get_current_user();
		if ( ! in_array( 'parent', (array) $user->roles, true ) ) {
			// Non-parent users (e.g. camp directors) don't get the button
			return '';
		}

		global $wpdb;
		$is_fav = (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}cdbs_parent_favorites WHERE user_id = %d AND camp_id = %d",
			$user->ID, $camp_id
		) );

		$label    = $is_fav ? '&#9829; Saved' : '&#9825; Save to Favourites';
		$class    = $is_fav ? 'pcs-fav-btn pcs-fav-active' : 'pcs-fav-btn';
		$data_fav = $is_fav ? '1' : '0';

		return '<div class="pcs-fav-wrapper">
			<button
				class="' . esc_attr( $class ) . '"
				data-camp-id="' . esc_attr( $camp_id ) . '"
				data-is-fav="' . esc_attr( $data_fav ) . '"
				id="fav-btn-' . esc_attr( $camp_id ) . '"
			>' . $label . '</button>
		</div>';
	}

	// =========================================================================
	// AJAX: toggle favourite
	// =========================================================================

	public function ajax_fav_not_logged_in() {
		wp_send_json_error( [ 'message' => 'Please log in to save favourites.' ] );
	}

	public function ajax_toggle_fav() {
		check_ajax_referer( 'parent_camp_nonce', 'nonce' );

		$user    = wp_get_current_user();
		$camp_id = isset( $_POST['camp_id'] ) ? intval( $_POST['camp_id'] ) : 0;

		if ( ! $camp_id ) {
			wp_send_json_error( [ 'message' => 'Invalid camp.' ] );
		}

		if ( ! in_array( 'parent', (array) $user->roles, true ) ) {
			wp_send_json_error( [ 'message' => 'Only parent accounts can save favourites.' ] );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'cdbs_parent_favorites';

		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE user_id = %d AND camp_id = %d",
			$user->ID, $camp_id
		) );

		if ( $exists ) {
			$wpdb->delete( $table, [ 'user_id' => $user->ID, 'camp_id' => $camp_id ], [ '%d', '%d' ] );
			wp_send_json_success( [ 'action' => 'removed', 'label' => '&#9825; Save to Favourites' ] );
		} else {
			$wpdb->insert( $table, [ 'user_id' => $user->ID, 'camp_id' => $camp_id ], [ '%d', '%d' ] );
			wp_send_json_success( [ 'action' => 'added', 'label' => '&#9829; Saved' ] );
		}
	}

	// =========================================================================
	// [camp_contact_form]
	// =========================================================================

	public function render_contact_form( $atts ) {
		$atts    = shortcode_atts( [ 'camp_id' => 0 ], $atts );
		$camp_id = intval( $atts['camp_id'] );

		// Fallback: read camp id from the page's custom field "page_id"
		if ( ! $camp_id ) {
			$page_id = get_the_ID() ?: get_queried_object_id();
			$camp_id = intval( get_post_meta( $page_id, 'camp_id', true ) );
		}

		if ( ! $camp_id ) return '';

		// Success notice
		$success_key = 'camp_contact_success_' . $camp_id . '_' . ( is_user_logged_in() ? get_current_user_id() : md5( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$show_success = get_transient( $success_key );
		if ( $show_success ) delete_transient( $success_key );

		$user         = is_user_logged_in() ? wp_get_current_user() : null;

		ob_start();
		?>
		<div class="pcs-contact-wrapper">
			<?php if ( $show_success ) : ?>
				<div class="pcs-notice pcs-notice-success">Your message has been sent! The camp will be in touch with you.</div>
			<?php endif; ?>

			<?php if ( ! $user ) : ?>
				<!-- Guest: gray out form and show login/register CTA -->
				<div class="pcs-guest-lock">
					<div class="pcs-guest-form-blur">
						<div class="pcs-form-row pcs-two-col">
							<div>
								<label class="pcs-label">Your Name</label>
								<input type="text" class="pcs-input" disabled placeholder="Your name">
							</div>
							<div>
								<label class="pcs-label">Your Email</label>
								<input type="email" class="pcs-input" disabled placeholder="Your email">
							</div>
						</div>
						<div class="pcs-form-row">
							<label class="pcs-label">Message</label>
							<textarea class="pcs-textarea" rows="5" disabled placeholder="Tell the camp about your child and what you're looking for..."></textarea>
						</div>
						<div class="pcs-form-row">
							<button type="button" class="pcs-submit-btn" disabled>Send Message</button>
						</div>
					</div>
					<div class="pcs-guest-overlay">
						<p>Log in or create a free account to send a message to this camp.</p>
						<div class="pcs-guest-overlay-btns">
							<a href="<?php echo esc_url( home_url( '/camp-login/' ) ); ?>" class="pcs-guest-btn pcs-guest-btn-login">Log In</a>
							<a href="<?php echo esc_url( home_url( '/find-the-perfect-summer-camp/' ) ); ?>" class="pcs-guest-btn pcs-guest-btn-register">Create Free Account</a>
						</div>
					</div>
				</div>
			<?php else : ?>
				<form method="post" class="pcs-contact-form" id="camp-contact-form-<?php echo esc_attr( $camp_id ); ?>">
					<?php wp_nonce_field( 'camp_contact_form_' . $camp_id, 'camp_contact_nonce' ); ?>
					<input type="hidden" name="camp_contact_submit" value="1">
					<input type="hidden" name="contact_camp_id" value="<?php echo esc_attr( $camp_id ); ?>">
					<input type="hidden" name="contact_redirect_url" value="<?php echo esc_url( get_permalink() ); ?>">
					<p class="pcs-logged-as">Sending as <strong><?php echo esc_html( $user->display_name ); ?></strong> &mdash; <a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>">Not you?</a></p>
					<div class="pcs-form-row">
						<label class="pcs-label">Message <span class="pcs-required">*</span></label>
						<textarea name="contact_message" class="pcs-textarea" rows="5" placeholder="Tell the camp about your child and what you're looking for..." required></textarea>
					</div>
					<div class="pcs-form-row">
						<button type="submit" class="pcs-submit-btn">Send Message</button>
					</div>
				</form>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// Handle contact form submission
	// =========================================================================

	public function handle_contact_form_submission() {
		if ( ! isset( $_POST['camp_contact_submit'] ) ) return;

		$camp_id = intval( $_POST['contact_camp_id'] ?? 0 );
		if ( ! $camp_id ) return;

		if ( ! wp_verify_nonce(
			wp_unslash( $_POST['camp_contact_nonce'] ?? '' ),
			'camp_contact_form_' . $camp_id
		) ) {
			wp_die( 'Security check failed.' );
		}

		$data    = wp_unslash( $_POST );
		$message = sanitize_textarea_field( $data['contact_message'] ?? '' );

		$redirect_url = esc_url_raw( wp_unslash( $_POST['contact_redirect_url'] ?? '' ) );
		if ( empty( $redirect_url ) || ! wp_http_validate_url( $redirect_url ) ) {
			$redirect_url = wp_get_referer() ?: home_url();
		}

		if ( empty( $message ) ) {
			wp_redirect( $redirect_url );
			exit;
		}

		// Determine sender
		if ( is_user_logged_in() ) {
			$user      = wp_get_current_user();
			$sender_id = $user->ID;
			$sender_role = in_array( 'parent', (array) $user->roles, true ) ? 'parent' : 'user';
		} else {
			// Guest: create or fetch user by email
			$guest_email = sanitize_email( $data['contact_email'] ?? '' );
			$guest_name  = sanitize_text_field( $data['contact_name'] ?? 'Guest' );

			if ( empty( $guest_email ) || ! is_email( $guest_email ) ) {
				wp_redirect( $redirect_url );
				exit;
			}

			$existing = get_user_by( 'email', $guest_email );
			if ( $existing ) {
				$sender_id = $existing->ID;
			} else {
				$sender_id = 0; // Store as 0 for truly anonymous
			}
			$sender_role = 'guest';
		}

		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'cdbs_messages',
			[
				'camp_id'     => $camp_id,
				'sender_id'   => $sender_id,
				'sender_role' => $sender_role,
				'body'        => $message,
				'is_read'     => 0,
			],
			[ '%d', '%d', '%s', '%s', '%d' ]
		);

		// Notify admin
		$sender_display = '';
		if ( is_user_logged_in() ) {
			$su = wp_get_current_user();
			$sender_display = trim( $su->first_name . ' ' . $su->last_name ) ?: $su->display_name;
			$sender_email_addr = $su->user_email;
		} elseif ( isset( $guest_name ) ) {
			$sender_display    = $guest_name;
			$sender_email_addr = $guest_email ?? '';
		}
		$this->notify_admin_new_message( $camp_id, $sender_display, $sender_email_addr ?? '', $message );

		// Success transient
		$success_key = 'camp_contact_success_' . $camp_id . '_' . ( is_user_logged_in() ? get_current_user_id() : md5( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		set_transient( $success_key, 1, 120 );

		wp_redirect( $redirect_url );
		exit;
	}

	// =========================================================================
	// Notify admin
	// =========================================================================

	private function notify_admin_new_message( $camp_id, $sender_name, $sender_email, $message ) {
		global $wpdb;
		$camp = $wpdb->get_row( $wpdb->prepare(
			"SELECT camp_name FROM " . DB::table_camps() . " WHERE id = %d",
			$camp_id
		) );

		$admin_email = get_option( 'admin_email' );
		$site_name   = get_bloginfo( 'name' );
		$camp_name   = $camp->camp_name ?? 'Unknown Camp';
		$admin_url   = admin_url( 'admin.php?page=cdbs-parent-messages' );

		$subject = "[{$site_name}] New message for camp: {$camp_name}";

		$body = '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family:Arial,sans-serif;line-height:1.6;color:#333;background-color:#f4f4f4;margin:0;padding:0;">
  <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f4f4;">
    <tr><td align="center" style="padding:20px 10px;">
      <div style="max-width:600px;margin:20px auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,.1);">
        <div style="background:linear-gradient(135deg,#497C5E 0%,#679B7C 100%);color:#fff;padding:30px 20px;text-align:center;">
          <h1 style="margin:0;font-size:26px;font-weight:bold;">&#128140; New Camp Message</h1>
        </div>
        <div style="padding:30px 20px;">
          <h2 style="color:#497C5E;margin-top:0;font-size:20px;">A parent sent a message to ' . esc_html( $camp_name ) . '</h2>
          <div style="background:#f8f9fa;border-left:4px solid #497C5E;padding:15px;margin:20px 0;border-radius:4px;">
            <p style="margin:5px 0;font-size:16px;"><strong style="color:#497C5E;">From:</strong> ' . esc_html( $sender_name ) . '</p>
            ' . ( $sender_email ? '<p style="margin:5px 0;font-size:16px;"><strong style="color:#497C5E;">Email:</strong> <a href="mailto:' . esc_attr( $sender_email ) . '" style="color:#497C5E;">' . esc_html( $sender_email ) . '</a></p>' : '' ) . '
            <p style="margin:15px 0 5px;font-size:16px;"><strong style="color:#497C5E;">Message:</strong></p>
            <div style="color:#333;line-height:1.6;white-space:pre-wrap;background:#fff;padding:10px;border-radius:3px;">' . esc_html( $message ) . '</div>
          </div>
          <div style="text-align:center;">
            <a href="' . esc_url( $admin_url ) . '" style="display:inline-block;padding:14px 30px;background:#497C5E;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold;margin:20px 0;">View in Admin</a>
          </div>
        </div>
        <div style="background:#f8f9fa;padding:20px;text-align:center;font-size:14px;color:#666;border-top:1px solid #e9ecef;">
          <p style="margin:0;"><strong>Best USA Summer Camps</strong> — Admin Notification</p>
        </div>
      </div>
    </td></tr>
  </table>
</body>
</html>';

		wp_mail( $admin_email, $subject, $body, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}
}
