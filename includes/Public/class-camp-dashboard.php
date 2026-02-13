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
		add_shortcode( 'camp_dashboard_navi', [ $this, 'render_dashboard_navi' ] );
		add_shortcode( 'camp_dashboard_header', [ $this, 'render_dashboard_header' ] );
		add_shortcode( 'camp_dashboard_title', [ $this, 'render_dashboard_title' ] );
		
		// Handle form submissions
		add_action( 'init', [ $this, 'handle_form_submission' ] );
		
		// AJAX handlers for accommodations
		add_action( 'wp_ajax_camp_save_accommodation', [ $this, 'ajax_save_accommodation' ] );
		add_action( 'wp_ajax_camp_get_accommodation', [ $this, 'ajax_get_accommodation' ] );
		add_action( 'wp_ajax_camp_delete_accommodation', [ $this, 'ajax_delete_accommodation' ] );
		
		// AJAX handlers for FAQs
		add_action( 'wp_ajax_camp_save_faq', [ $this, 'ajax_save_faq' ] );
		add_action( 'wp_ajax_camp_get_faq', [ $this, 'ajax_get_faq' ] );
		add_action( 'wp_ajax_camp_delete_faq', [ $this, 'ajax_delete_faq' ] );
		
		// AJAX handlers for sessions
		add_action( 'wp_ajax_camp_save_session', [ $this, 'ajax_save_session' ] );
		add_action( 'wp_ajax_camp_get_session', [ $this, 'ajax_get_session' ] );
		add_action( 'wp_ajax_camp_delete_session', [ $this, 'ajax_delete_session' ] );
		
		// Enqueue front-end styles
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		
		// Register custom media category taxonomy
		add_action( 'init', [ $this, 'register_media_category' ] );
		
		// Customize WordPress login/password pages styling
		add_action( 'login_enqueue_scripts', [ $this, 'customize_login_page' ] );
		
		// Redirect camp users after login
		add_filter( 'login_redirect', [ $this, 'camp_login_redirect' ], 10, 3 );
		
		// Block camp users from accessing wp-admin
		add_action( 'admin_init', [ $this, 'block_camp_users_from_admin' ] );
		
		// Hide admin bar for camp users
		add_action( 'after_setup_theme', [ $this, 'hide_admin_bar_for_camps' ] );
		
		// Override lost password URL to use custom page (high priority to override other plugins)
		add_filter( 'lostpassword_url', [ $this, 'custom_lostpassword_url' ], 99, 2 );
		add_filter( 'login_url', [ $this, 'custom_login_url' ], 99, 3 );
		
		// Handle lost password form submission
		add_action( 'template_redirect', [ $this, 'handle_lostpassword_redirect' ] );
		
		// Fix lost password form action URL
		add_action( 'wp_footer', [ $this, 'fix_lostpassword_form_action' ] );
		
		// Allow HTML in login errors
		add_filter( 'login_errors', [ $this, 'allow_html_in_login_errors' ], 10, 1 );
		
		// Customize password reset email
		add_filter( 'retrieve_password_message', [ $this, 'custom_password_reset_email' ], 10, 4 );
		add_filter( 'retrieve_password_title', [ $this, 'custom_password_reset_subject' ], 10, 3 );
		
		// Register daily notification cron job
		add_action( 'init', [ $this, 'schedule_daily_notification_cron' ] );
		add_action( 'camp_send_daily_notifications', [ $this, 'send_daily_notification_batch' ] );
	}
	
	/**
	 * Hide admin bar for camp users
	 */
	public function hide_admin_bar_for_camps() {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( in_array( 'camp', $user->roles ) ) {
				show_admin_bar( false );
			}
		}
	}
	
	/**
	 * Schedule daily notification cron job at 8 PM CET
	 */
	public function schedule_daily_notification_cron() {
		if ( ! wp_next_scheduled( 'camp_send_daily_notifications' ) ) {
			// 8 PM CET = 19:00 UTC in winter, 18:00 UTC in summer
			// Using 19:00 UTC (covers most of the year)
			$timezone = new \DateTimeZone( 'Europe/Paris' ); // CET timezone
			$now = new \DateTime( 'now', $timezone );
			$target = new \DateTime( 'today 20:00', $timezone ); // 8 PM CET
			
			if ( $now > $target ) {
				$target->modify( '+1 day' );
			}
			
			$timestamp = $target->getTimestamp();
			
			wp_schedule_event( $timestamp, 'daily', 'camp_send_daily_notifications' );
		}
	}
	
	/**
	 * Send batched daily notifications grouped by camp
	 */
	public function send_daily_notification_batch() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'camp_notification_queue';
		$admin_email = get_option( 'admin_email' );
		
		// Get all notifications from today grouped by camp
		$notifications = $wpdb->get_results(
			"SELECT * FROM {$table_name} 
			WHERE DATE(update_time) = CURDATE()
			ORDER BY camp_id, update_time ASC"
		);
		
		if ( empty( $notifications ) ) {
			return; // No updates today
		}
		
		// Group notifications by camp
		$grouped_by_camp = [];
		foreach ( $notifications as $notification ) {
			$grouped_by_camp[ $notification->camp_id ][] = $notification;
		}
		
		// Send one email per camp
		foreach ( $grouped_by_camp as $camp_id => $camp_notifications ) {
			$camp_name = $camp_notifications[0]->camp_name;
			$update_count = count( $camp_notifications );
			
			$subject = "Daily Update Summary: {$camp_name} ({$update_count} update" . ( $update_count > 1 ? 's' : '' ) . ")";
			$message = $this->get_daily_batch_email_template( $camp_id, $camp_name, $camp_notifications );
			
			$headers = [
				'Content-Type: text/html; charset=UTF-8',
				'From: Best USA Summer Camps <noreply@bestusacamps.com>',
			];
			
			wp_mail( $admin_email, $subject, $message, $headers );
		}
		
		// Clear sent notifications
		$wpdb->query(
			"DELETE FROM {$table_name} WHERE DATE(update_time) = CURDATE()"
		);
	}
	
	/**
	 * Get daily batch notification email template
	 */
	private function get_daily_batch_email_template( $camp_id, $camp_name, $notifications ) {
		$edit_url = admin_url( 'admin.php?page=creativedbs-camp-mgmt&action=edit&camp=' . $camp_id );
		$edit_url = wp_nonce_url( $edit_url, 'edit_camp' );
		
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Daily Camp Update Summary</title>
		</head>
		<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f4f4;">
				<tr>
					<td align="center" style="padding: 20px 10px;">
						<div style="max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
							<div style="background: linear-gradient(135deg, #497C5E 0%, #679B7C 100%); color: #ffffff; padding: 30px 20px; text-align: center;">
								<h1 style="margin: 0; font-size: 28px; font-weight: bold;">📊 Daily Update Summary</h1>
							</div>
							<div style="padding: 30px 20px;">
								<h2 style="color: #497C5E; margin-top: 0; font-size: 22px;"><?php echo esc_html( $camp_name ); ?></h2>
								<p style="margin: 15px 0; font-size: 16px;">This camp made <strong><?php echo count( $notifications ); ?> update<?php echo count( $notifications ) > 1 ? 's' : ''; ?></strong> today:</p>
								
								<div style="background: #f8f9fa; border-left: 4px solid #497C5E; padding: 15px; margin: 20px 0; border-radius: 4px;">
									<?php foreach ( $notifications as $index => $notif ) : ?>
										<div style="margin: <?php echo $index > 0 ? '15px' : '0'; ?> 0; padding-top: <?php echo $index > 0 ? '15px' : '0'; ?>; border-top: <?php echo $index > 0 ? '1px solid #dee2e6' : 'none'; ?>;">
											<p style="margin: 5px 0; font-size: 14px; color: #666;"><?php echo esc_html( date( 'g:i A', strtotime( $notif->update_time ) ) ); ?></p>
											<p style="margin: 5px 0; font-size: 15px; text-align: left;">
												<?php if ( $notif->photos_uploaded ) : ?>
													• New photos uploaded<br>
												<?php endif; ?>
												<?php if ( $notif->logo_uploaded ) : ?>
													• Logo updated<br>
												<?php endif; ?>
												• Profile information updated
											</p>
										</div>
									<?php endforeach; ?>
								</div>
								
								<div style="text-align: center; margin-top: 30px;">
									<a href="<?php echo esc_url( $edit_url ); ?>" style="display: inline-block; padding: 14px 30px; background: #497C5E !important; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold;">View Camp Profile</a>
								</div>
							</div>
							<div style="background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #666; border-top: 1px solid #e9ecef;">
								<p style="margin: 0;"><strong>Best USA Summer Camps</strong> - Daily Notification</p>
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
	 * Override lost password URL to use custom page
	 */
	public function custom_lostpassword_url( $lostpassword_url, $redirect ) {
		return home_url( '/camp-lost-password/' );
	}
	
	/**
	 * Override login URL to always use custom camp-login page
	 */
	public function custom_login_url( $login_url, $redirect, $force_reauth ) {
		// Always redirect to custom camp login page
		return home_url( '/camp-login/' );
	}
	
	/**
	 * Redirect lost password attempts to custom page
	 */
	public function handle_lostpassword_redirect() {
		// Check if this is the administrator login page with lostpassword action
		// But don't redirect if it's a POST request (actual form submission)
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'lostpassword' && $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			// Redirect to custom page
			wp_redirect( home_url( '/camp-lost-password/' ) );
			exit;
		}
	}
	
	/**
	 * Fix lost password form action URL with JavaScript
	 */
	public function fix_lostpassword_form_action() {
		// Only run on the camp lost password page
		if ( is_page( 'camp-lost-password' ) ) {
			?>
			<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function() {
				// Find the lost password form
				var form = document.getElementById('lostpasswordform');
				if (form) {
					// Get the proper WordPress login URL (not filtered)
					var wpLoginUrl = '<?php echo esc_js( site_url( 'wp-login.php' ) ); ?>';
					
					// Always set the action to WordPress login with lostpassword action
					form.setAttribute('action', wpLoginUrl + '?action=lostpassword');
					
					console.log('Lost password form action set to:', form.getAttribute('action'));
				} else {
					console.log('Lost password form not found');
				}
			});
			</script>
			<?php
		}
		
		// Fix HTML rendering in login errors on camp-login page
		if ( is_page( 'camp-login' ) || is_page( 'camp-lost-password' ) || is_page( 'camp-reset-password' ) ) {
			?>
			<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function() {
				// Fix HTML rendering in error and message boxes
				setTimeout(function() {
					// Find all possible error/message containers
					var selectors = [
						'.login-error p',
						'.login-message p', 
						'#login_error',
						'.message',
						'.error',
						'[class*="login"][class*="error"]',
						'[class*="login"][class*="message"]'
					];
					
					selectors.forEach(function(selector) {
						var elements = document.querySelectorAll(selector);
						elements.forEach(function(element) {
							var html = element.innerHTML;
							// Check if HTML tags are being displayed as text
							if (html.indexOf('&lt;') !== -1 || html.indexOf('&gt;') !== -1) {
								// Decode HTML entities
								var textarea = document.createElement('textarea');
								textarea.innerHTML = html;
								element.innerHTML = textarea.value;
							}
							// Also check for literal tags in text content
							var text = element.textContent || element.innerText || '';
							if (text.indexOf('<strong>') !== -1 || text.indexOf('<a href=') !== -1) {
								element.innerHTML = text;
							}
						});
					});
				}, 100);
			});
			</script>
			<?php
		}
	}
	
	/**
	 * Allow HTML in login errors (WordPress sanitizes too aggressively by default)
	 */
	public function allow_html_in_login_errors( $error ) {
		// Remove duplicate "Error:" prefix
		$error = preg_replace( '/^Error:\s*<strong>Error:<\/strong>/i', '<strong>Error:</strong>', $error );
		
		// Return the error as-is since WordPress already sanitizes it with wp_kses
		return $error;
	}
	
	/**
	 * Customize password reset email subject
	 */
	public function custom_password_reset_subject( $title, $user_login, $user_data ) {
		return '[Best USA Summer Camps - 2026 Camp Search Engine] Password Reset';
	}
	
	/**
	 * Customize password reset email with HTML template
	 */
	public function custom_password_reset_email( $message, $key, $user_login, $user_data ) {
		// Build the reset URL
		$reset_url = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' );
		
		// Get the HTML template
		$html_message = $this->get_password_reset_email_template( $user_login, $reset_url );
		
		// Send the email directly using wp_mail to ensure HTML content type
		$to = $user_data->user_email;
		$subject = '[Best USA Summer Camps - 2026 Camp Search Engine] Password Reset';
		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: Best American Summer Camps <info@bestusacamps.com>',
		];
		
		// Send the email
		wp_mail( $to, $subject, $html_message, $headers );
		
		// Return empty string to prevent WordPress from sending the default email
		return '';
	}
	
	/**
	 * Get password reset email HTML template
	 */
	private function get_password_reset_email_template( $user_login, $reset_url ) {
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
							<div style="padding: 30px 20px;">
								<h2 style="color: #497C5E; margin-top: 0; font-size: 22px;">Reset Your Password</h2>
								<p style="margin: 15px 0; font-size: 16px;">Hello!</p>
								<p style="margin: 15px 0; font-size: 16px;">Someone has requested a password reset for the following account on <strong>Best USA Summer Camps</strong>:</p>
								
								<div style="background: #f8f9fa; border-left: 4px solid #497C5E; padding: 15px; margin: 20px 0; border-radius: 4px;">
									<p style="margin: 0; font-size: 16px;"><strong style="color: #497C5E;">Site Name:</strong> Best USA Summer Camps - 2026 Camp Search Engine</p>
									<p style="margin: 5px 0 0 0; font-size: 16px;"><strong style="color: #497C5E;">Username:</strong> <?php echo esc_html( $user_login ); ?></p>
								</div>
								
								<p style="margin: 15px 0; font-size: 16px;"><strong>If this was a mistake, just ignore this email and nothing will happen.</strong></p>
								
								<p style="margin: 15px 0; font-size: 16px;">To reset your password, click the button below:</p>
								
								<div style="text-align: center;">
									<a href="<?php echo esc_url( $reset_url ); ?>" style="display: inline-block; padding: 14px 30px; background: #497C5E !important; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; text-align: center;">Reset My Password</a>
								</div>
								
								<p style="font-size: 14px; color: #666; margin-top: 25px;">If the button doesn't work, copy and paste this link into your browser:<br>
								<a href="<?php echo esc_url( $reset_url ); ?>" style="color: #497C5E; word-break: break-all;"><?php echo esc_url( $reset_url ); ?></a></p>
								
								<div style="height: 1px; background: #e9ecef; margin: 25px 0;"></div>
								
								<p style="font-size: 14px; color: #666; margin: 15px 0;"><strong>Security Note:</strong><br>
								This password reset request originated from the IP address: <?php echo esc_html( $_SERVER['REMOTE_ADDR'] ?? 'Unknown' ); ?></p>
								
								<p style="font-size: 14px; color: #666; margin: 15px 0;">If you did not request a password reset, please ignore this email or contact our support team if you have concerns.</p>
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
	 * Redirect users after login based on their role
	 */
	public function camp_login_redirect( $redirect_to, $request, $user ) {
		if ( isset( $user->roles ) && is_array( $user->roles ) ) {
			// Camp directors go to their dashboard
			if ( in_array( 'camp', $user->roles ) ) {
				return home_url( '/user-dashboard/' );
			}
			// Administrators go to WordPress admin
			if ( in_array( 'administrator', $user->roles ) ) {
				return admin_url();
			}
		}
		return $redirect_to;
	}
	
	/**
	 * Block camp users from accessing wp-admin
	 */
	public function block_camp_users_from_admin() {
		if ( ! defined( 'DOING_AJAX' ) && is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( in_array( 'camp', $user->roles ) ) {
				wp_redirect( home_url( '/user-dashboard/' ) );
				exit;
			}
		}
	}
	
	/**
	 * Register custom media category for camp files
	 */
	public function register_media_category() {
		register_taxonomy(
			'media_category',
			'attachment',
			[
				'labels' => [
					'name'          => 'Media Folders',
					'singular_name' => 'Media Folder',
					'search_items'  => 'Search Folders',
					'all_items'     => 'All Folders',
					'edit_item'     => 'Edit Folder',
					'add_new_item'  => 'Add New Folder',
				],
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_admin_column' => true,
				'hierarchical'      => true,
				'show_in_nav_menus' => false,
			]
		);
		
		// Create "Camps" term if it doesn't exist
		// term_exists returns term_id if exists, null if not - so we need to check for null
		$term_exists = term_exists( 'camps', 'media_category' );
		if ( ! $term_exists ) {
			$result = wp_insert_term( 'Camps', 'media_category', [
				'description' => 'All camp photos and logos',
				'slug'        => 'camps',
			] );
			error_log( 'Camp category term creation result: ' . print_r( $result, true ) );
		}
	}
	
	/**
	 * Customize WordPress login/password pages styling
	 */
	public function customize_login_page() {
		?>
		<style type="text/css">
			/* Import Google Fonts */
			@import url('https://fonts.googleapis.com/css2?family=Annie+Use+Your+Telescope&display=swap');
			
			/* Overall page styling */
			body.login {
				background: #ffffff;
				font-family: Arial, sans-serif;
			}
			
			/* Hide default WordPress logo */
			.login h1 {
				display: none;
			}
			
			/* Form container */
			#login {
				padding: 0;
			}
			
			.login form {
				background: #ffffff;
				border: none;
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
				padding: 50px 40px;
				border-radius: 8px;
			}
			
			/* Form title */
			.login form::before {
				content: 'Camp Login';
				display: block;
				text-align: center;
				color: #497C5E;
				margin-top: 0;
				margin-bottom: 10px;
				font-size: 32px;
				font-family: "Annie Use Your Telescope", sans-serif !important;
			}
			
			/* Lost password form title */
			body.login.login-action-lostpassword form::before {
				content: 'Reset Password';
			}
			
			/* Reset password form title */
			body.login.login-action-rp form::before,
			body.login.login-action-resetpass form::before {
				content: 'Set Password';
			}
			
			/* Form description */
			.login form p.message,
			.login #login_error {
				font-size: 14px;
				color: #666666;
				text-align: center;
				line-height: 1.6;
				margin: 0 0 20px 0;
				padding: 12px;
				background: transparent;
				border: none;
			}
			
			/* Labels */
			.login form label {
				font-size: 14px;
				font-weight: 700;
				color: #333333;
				display: block;
				margin-bottom: 8px;
			}
			
			/* Input fields */
			.login form input[type="text"],
			.login form input[type="password"],
			.login form input[type="email"] {
				width: 100%;
				padding: 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
				font-size: 16px;
				margin-bottom: 15px;
				box-sizing: border-box;
				font-family: Arial, sans-serif;
				color: #333333;
			}
			
			.login form input[type="text"]:focus,
			.login form input[type="password"]:focus,
			.login form input[type="email"]:focus {
				border: 1px solid #497C5E;
				outline: none;
				box-shadow: 0 0 0 2px rgba(73, 124, 94, 0.2);
			}
			
			/* Remember Me checkbox */
			.login .forgetmenot {
				margin-bottom: 20px;
			}
			
			.login .forgetmenot label {
				font-weight: 400;
				color: #666666;
				font-size: 13px;
			}
			
			.login form input[type="checkbox"] {
				margin-right: 8px;
			}
			
			/* Submit button */
			.login form .button-primary {
				width: 100%;
				padding: 14px;
				background: #497C5E;
				color: #ffffff;
				border: none;
				border-radius: 5px;
				font-family: "Annie Use Your Telescope", sans-serif !important;
				font-weight: 800 !important;
				font-size: 20px;
				text-transform: uppercase;
				letter-spacing: 1.5px;
				cursor: pointer;
				transition: background 0.3s;
				box-shadow: none;
				text-shadow: none;
			}
			
			.login form .button-primary:hover {
				background: #3d6a4f;
			}
			
			/* Links below form */
			.login #nav,
			.login #backtoblog {
				text-align: center;
				margin-top: 20px;
			}
			
			.login #nav a,
			.login #backtoblog a {
				color: #999999;
				font-size: 13px;
				text-decoration: none;
				transition: color 0.2s ease;
			}
			
			.login #nav a:hover,
			.login #backtoblog a:hover {
				color: #497C5E;
			}
			
			/* Password strength meter */
			.login form .pw-weak {
				display: none;
			}
			
			#pass-strength-result {
				background: #f5f5f5;
				border: none;
				border-radius: 4px;
				margin-top: 10px;
				padding: 8px 12px;
				font-size: 13px;
			}
			
			/* Success messages */
			.login .message,
			.login .notice,
			.login .success {
				border-left: 4px solid #497C5E;
				background: #ffffff;
				color: #333333;
				padding: 12px;
				margin-left: 0;
				margin-bottom: 20px;
				box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);
				word-wrap: break-word;
			}
			
			/* Error messages */
			.login #login_error {
				border-left: 4px solid #dc3545;
				background: #f8d7da;
				color: #721c24;
				padding: 12px 15px;
				margin-bottom: 20px;
			}
			
			/* Responsive design */
			@media screen and (max-width: 768px) {
				.login form {
					padding: 35px 25px;
				}
				
				.login form::before {
					font-size: 26px;
				}
			}
		</style>
		<?php
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
			"SELECT camp_name FROM {$wpdb->prefix}camp_management WHERE wordpress_user_id = %d",
			$user->ID
		), ARRAY_A );

		if ( ! $camp || empty( $camp['camp_name'] ) ) {
			return 'CAMP DASHBOARD';
		}

		return 'Admin: ' . esc_html( $camp['camp_name'] );
	}

	/**
	 * Render dashboard navigation shortcode
	 */
	public function render_dashboard_navi( $atts ) {
		ob_start();
		?>
		<nav class="dashboard-sidenav-container" id="camp-sidenav">
			<div class="sidenav-header">Camp Sections</div>
			<div class="sidenav-links">
				<a href="#basic-info" class="sidenav-link">Basic Information</a>
				<a href="#contact-info" class="sidenav-link">Contact Information</a>
				<a href="#camp-details" class="sidenav-link">Camp Details</a>
				<a href="#camp-types" class="sidenav-link">Camp Types</a>
				<a href="#available-weeks" class="sidenav-link">Available Weeks</a>
				<a href="#activities" class="sidenav-link">Activities Offered</a>
				<a href="#photos" class="sidenav-link">Camp Photos</a>
				<a href="#logo" class="sidenav-link">Camp Logo</a>
				<a href="#accommodations" class="sidenav-link">Accommodation Facilities</a>
				<a href="#faqs" class="sidenav-link">FAQs</a>
				<a href="#sessions" class="sidenav-link">Sessions (Rates & Dates)</a>
				<button type="submit" form="camp-dashboard-form" class="sidenav-link sidenav-save-btn">Save All Changes</button>
			</div>
		</nav>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			const nav = document.getElementById('camp-sidenav');
			const navLinks = document.querySelectorAll('.sidenav-link');
			const offset = 80;
			
			// Make navigation sticky on scroll (desktop only)
			function handleStickyNav() {
				if (window.innerWidth <= 768) {
					nav.classList.remove('is-sticky');
					return;
				}
				
				const navContainer = nav.parentElement;
				const navTop = navContainer.getBoundingClientRect().top + window.pageYOffset;
				const scrollTop = window.pageYOffset;
				
				if (scrollTop > navTop - 100) {
					nav.classList.add('is-sticky');
				} else {
					nav.classList.remove('is-sticky');
				}
			}
			
			// Initial check
			handleStickyNav();
			
			// Update on scroll and resize
			window.addEventListener('scroll', handleStickyNav);
			window.addEventListener('resize', handleStickyNav);
			
			// Smooth scroll with offset
			navLinks.forEach(link => {
				link.addEventListener('click', function(e) {
				// Skip if this is a button (like save button) or has no href
				if (this.tagName === 'BUTTON' || !this.getAttribute('href')) {
					return;
				}
				
					
					if (targetElement) {
						const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - offset;
						
						window.scrollTo({
							top: targetPosition,
							behavior: 'smooth'
						});
						
						// Update active state
						navLinks.forEach(l => l.classList.remove('active'));
						this.classList.add('active');
					}
				});
			});
			
			// Track scroll position and update active state
			window.addEventListener('scroll', function() {
				let current = '';
				const sections = document.querySelectorAll('.form-section[id]');
				
				sections.forEach(section => {
					const sectionTop = section.offsetTop - offset - 100;
					if (window.pageYOffset >= sectionTop) {
						current = section.getAttribute('id');
					}
				});
				
				navLinks.forEach(link => {
					link.classList.remove('active');
					if (link.getAttribute('href') === '#' + current) {
						link.classList.add('active');
					}
				});
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render dashboard header shortcode
	 */
	public function render_dashboard_header( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$user = wp_get_current_user();
		if ( ! in_array( 'camp', $user->roles ) ) {
			return '';
		}

		global $wpdb;
		$camp = $wpdb->get_row( $wpdb->prepare(
			"SELECT camp_directors FROM {$wpdb->prefix}camp_management WHERE wordpress_user_id = %d",
			$user->ID
		), ARRAY_A );

		if ( ! $camp ) {
			return '';
		}

		ob_start();
		?>
		<div class="dashboard-header">
			<h1>Welcome, <?php echo esc_html( $camp['camp_directors'] ); ?>!</h1>
			<p class="dashboard-subtitle">Manage your camp profile and information</p>
			<div class="dashboard-actions">
				<a href="<?php echo wp_logout_url( wp_login_url() ); ?>" class="btn-logout">Logout</a>
			</div>
		</div>
		
	<?php if ( get_option( 'cdbs_show_beta_notice', '1' ) === '1' ) : ?>
	<div class="beta-notice-box">
		<div class="beta-notice-content">
			<strong>Beta Version Notice</strong>
			<p>This Camp Management Dashboard is currently in beta but is fully functional. If you encounter any issues or unexpected behavior, we'd really appreciate you <a href="https://bestusacamps.com/contact/" target="_blank">letting us know</a> so we can improve it.</p>
		</div>
	</div>
	<?php endif; ?>
	<?php
	return ob_get_clean();
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
			"SELECT id FROM {$wpdb->prefix}camp_management WHERE wordpress_user_id = %d",
			$user->ID
		) );

		if ( ! $camp_id ) {
			wp_die( 'No camp found for this user.' );
		}

		// Get current camp data for handlers
		$camp = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}camp_management WHERE id = %d",
			$camp_id
		), ARRAY_A );

		// Remove slashes from POST data to prevent double-escaping
		$post_data = wp_unslash( $_POST );

		// Update camp data
		$about_camp = wp_kses_post( $post_data['about_camp'] ?? '' );
		
		// Validate word count (180 min - 300 max words)
		$word_count = str_word_count( wp_strip_all_tags( $about_camp ) );
		if ( $word_count < 180 ) {
			wp_redirect( add_query_arg( 'error', 'word_limit_min', wp_get_referer() ) );
			exit;
		}
		if ( $word_count > 300 ) {
			wp_redirect( add_query_arg( 'error', 'word_limit_max', wp_get_referer() ) );
			exit;
		}
		
		// Process social media links
		$social_media_links = [];
		if ( ! empty( $post_data['social_media'] ) && is_array( $post_data['social_media'] ) ) {
			foreach ( $post_data['social_media'] as $link ) {
				$link = trim( $link );
				if ( ! empty( $link ) ) {
					// Add https:// if no protocol is specified
					if ( ! preg_match( '/^https?:\/\//i', $link ) ) {
						$link = 'https://' . $link;
					}
					$social_media_links[] = esc_url_raw( $link );
				}
			}
		}
		$social_media_json = ! empty( $social_media_links ) ? wp_json_encode( $social_media_links ) : null;
		
		$camp_data = [
			'camp_name'        => sanitize_text_field( $post_data['camp_name'] ?? '' ),
			'camp_directors'   => sanitize_text_field( $post_data['camp_directors'] ?? '' ),
			'address'          => sanitize_text_field( $post_data['address'] ?? '' ),
			'city'             => sanitize_text_field( $post_data['city'] ?? '' ),
			'state'            => sanitize_text_field( $post_data['state'] ?? '' ),
			'zip'              => sanitize_text_field( $post_data['zip'] ?? '' ),
			'phone'            => sanitize_text_field( $post_data['phone'] ?? '' ),
			'email'            => sanitize_email( $post_data['email'] ?? '' ),
			'website'          => esc_url_raw( $post_data['website'] ?? '' ),
			'social_media_links' => $social_media_json,
			'video_url'        => $this->ensure_url_protocol( $post_data['video_url'] ?? '' ),
			'about_camp'       => $about_camp,
			'opening_day'      => sanitize_text_field( $post_data['opening_day'] ?? '' ),
			'closing_day'      => sanitize_text_field( $post_data['closing_day'] ?? '' ),
			'minprice_2026'    => floatval( $post_data['minprice_2026'] ?? 0 ),
			'maxprice_2026'    => floatval( $post_data['maxprice_2026'] ?? 0 ),
			'last_edited'      => current_time( 'mysql' ),
		];

		$wpdb->update(
			"{$wpdb->prefix}camp_management",
			$camp_data,
			[ 'id' => $camp_id ],
			[
				'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s'
			],
			[ '%d' ]
		);

		// Update pivot tables
		$this->update_pivot_data( $camp_id, 'camp_management_types_map', 'type_id', $_POST['camp_types'] ?? [] );
		$this->update_pivot_data( $camp_id, 'camp_management_weeks_map', 'week_id', $_POST['camp_weeks'] ?? [] );
		
		// Handle activities - create new ones if needed and link to camp
		$this->update_activities( $camp_id, $_POST['activity_names'] ?? '' );

		// Handle photo uploads
		$this->handle_photo_uploads( $camp_id, $camp );

		// Handle logo upload
		$this->handle_logo_upload( $camp_id, $camp );

		// Check if photos or logo were uploaded
		$photos_uploaded = ! empty( $_FILES['camp_photos']['name'][0] );
		$logo_uploaded = ! empty( $_FILES['camp_logo']['name'] );
		
		// Always send admin notification when camp saves changes
		$this->send_admin_notification_camp_updated( $camp_id, $camp_data['camp_name'], $photos_uploaded, $logo_uploaded );

		// Debug: Check if section marker fields are present
		error_log('CDBS: Section markers - Accommodations: ' . (isset($_POST['accommodations_section_present']) ? 'YES' : 'NO'));
		error_log('CDBS: Section markers - FAQs: ' . (isset($_POST['faqs_section_present']) ? 'YES' : 'NO'));
		error_log('CDBS: Section markers - Sessions: ' . (isset($_POST['sessions_section_present']) ? 'YES' : 'NO'));
		error_log('CDBS: All POST keys: ' . implode(', ', array_keys($_POST)));

		// NOTE: Accommodations, FAQs, and Sessions are now handled via AJAX
		// The old POST-based handlers below are commented out to prevent data deletion
		
		// Handle accommodations
		// error_log('CDBS: About to handle accommodations for camp ' . $camp_id);
		// error_log('CDBS: Accommodations POST data: ' . print_r($_POST['accommodations'] ?? 'NONE', true));
		// $this->handle_accommodations_submission( $camp_id );

		// Handle FAQs
		// error_log('CDBS: About to handle FAQs for camp ' . $camp_id);
		// error_log('CDBS: FAQs POST data: ' . print_r($_POST['faqs'] ?? 'NONE', true));
		// $this->handle_faqs_submission( $camp_id );

		// Handle sessions
		// error_log('CDBS: About to handle sessions for camp ' . $camp_id);
		// error_log('CDBS: Sessions POST data: ' . print_r($_POST['sessions'] ?? 'NONE', true));
		// $this->handle_sessions_submission( $camp_id );

		// Redirect to avoid resubmission
		wp_redirect( add_query_arg( 'updated', 'true', wp_get_referer() ) );
		exit;
	}

	/**
	 * Update activities - create new ones if needed and link to camp
	 */
	private function update_activities( $camp_id, $activity_names_string ) {
		global $wpdb;
		$activities_table = "{$wpdb->prefix}camp_activity_terms";
		$pivot_table = "{$wpdb->prefix}camp_management_activities_map";
		
		// Delete existing activity relationships
		$wpdb->delete( $pivot_table, [ 'camp_id' => $camp_id ], [ '%d' ] );
		
		// Parse activity names from comma-separated string
		if ( empty( $activity_names_string ) ) {
			return;
		}
		
		$activity_names = array_map( 'trim', explode( ',', $activity_names_string ) );
		$activity_names = array_filter( $activity_names ); // Remove empty values
		
		foreach ( $activity_names as $activity_name ) {
			// Check if activity exists
			$activity_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$activities_table} WHERE name = %s",
				$activity_name
			) );
			
			// Create activity if it doesn't exist
			if ( ! $activity_id ) {
				$slug = sanitize_title( $activity_name );
				$wpdb->insert(
					$activities_table,
					[
						'name' => $activity_name,
						'slug' => $slug,
						'is_active' => 1
					],
					[ '%s', '%s', '%d' ]
				);
				$activity_id = $wpdb->insert_id;
			}
			
			// Link activity to camp
			if ( $activity_id ) {
				$wpdb->insert(
					$pivot_table,
					[
						'camp_id' => $camp_id,
						'activity_id' => $activity_id
					],
					[ '%d', '%d' ]
				);
			}
		}
	}

	/**
	 * Handle photo uploads
	 */
	private function handle_photo_uploads( $camp_id, $camp ) {
		global $wpdb;
		
		// Get existing photos
		$existing_photos = ! empty( $camp['photos'] ) ? explode( ',', $camp['photos'] ) : [];
		$existing_photos = array_map( 'trim', $existing_photos );
		$existing_photos = array_filter( $existing_photos ); // Remove empty values
		$existing_photos = array_values( $existing_photos ); // Re-index array
		
		$photos_changed = false;
		
		// Handle photo removal
		if ( ! empty( $_POST['photos_to_remove'] ) ) {
			$photos_to_remove = explode( ',', $_POST['photos_to_remove'] );
			$photos_to_remove = array_map( 'trim', $photos_to_remove );
			$photos_to_remove = array_filter( $photos_to_remove );
			
			$existing_photos = array_diff( $existing_photos, $photos_to_remove );
			$existing_photos = array_values( $existing_photos ); // Re-index
			
			// Delete physical files
			foreach ( $photos_to_remove as $photo_url ) {
				$this->delete_uploaded_file( $photo_url );
			}
			
			$photos_changed = true;
		}
		
		// Handle new photo uploads
		if ( ! empty( $_FILES['photos_upload']['name'][0] ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			
			// Set custom upload directory for camp photos
			add_filter( 'upload_dir', [ $this, 'custom_upload_dir_for_camps' ] );
			
			$files = $_FILES['photos_upload'];
			$uploaded_count = 0;
			
			// Calculate total size of all files
			$total_size = 0;
			for ( $i = 0; $i < count( $files['name'] ); $i++ ) {
				if ( ! empty( $files['name'][$i] ) ) {
					$total_size += $files['size'][$i];
				}
			}
			
			// Validate total size (25MB)
			if ( $total_size > 25 * 1024 * 1024 ) {
				return; // Skip upload if total size exceeds limit
			}
			
			for ( $i = 0; $i < count( $files['name'] ); $i++ ) {
				// Check if we've reached the limit
				if ( count( $existing_photos ) + $uploaded_count >= 10 ) {
					break;
				}
				
				// Skip empty files
				if ( empty( $files['name'][$i] ) ) {
					continue;
				}
				
				// Prepare file array for wp_handle_upload
				$file = [
					'name'     => $files['name'][$i],
					'type'     => $files['type'][$i],
					'tmp_name' => $files['tmp_name'][$i],
					'error'    => $files['error'][$i],
					'size'     => $files['size'][$i],
				];
				
				// Validate file type
				$file_type = wp_check_filetype( $file['name'] );
				if ( ! in_array( $file_type['ext'], [ 'jpg', 'jpeg' ] ) ) {
					continue;
				}
				
				// Upload file
				$upload_overrides = [ 'test_form' => false ];
				$uploaded_file = wp_handle_upload( $file, $upload_overrides );
				
				if ( ! isset( $uploaded_file['error'] ) && isset( $uploaded_file['url'] ) ) {
					// Add to media library
					$attachment = [
						'post_mime_type' => $uploaded_file['type'],
						'post_title'     => sanitize_file_name( pathinfo( $uploaded_file['file'], PATHINFO_FILENAME ) ),
						'post_content'   => '',
						'post_status'    => 'inherit'
					];
					
					$attach_id = wp_insert_attachment( $attachment, $uploaded_file['file'] );
					
					// Assign to "Camps" category
					$term = get_term_by( 'slug', 'camps', 'media_category' );
					if ( $term ) {
						$result = wp_set_object_terms( $attach_id, (int) $term->term_id, 'media_category', false );
						error_log( 'Camp photo upload - Attachment ID: ' . $attach_id . ', Term ID: ' . $term->term_id . ', Result: ' . print_r( $result, true ) );
					} else {
						error_log( 'Camp photo upload - Term "camps" not found in media_category taxonomy' );
					}
					
					// Generate attachment metadata
					$attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded_file['file'] );
					wp_update_attachment_metadata( $attach_id, $attach_data );
					
					$existing_photos[] = $uploaded_file['url'];
					$uploaded_count++;
					$photos_changed = true;
				}
			}
			
			// Remove custom upload directory filter
			remove_filter( 'upload_dir', [ $this, 'custom_upload_dir_for_camps' ] );
		}
		
		// Update database with new photo list (only if there were changes)
		if ( $photos_changed ) {
			$photos_csv = implode( ', ', $existing_photos ); // Add space after comma for readability
			
			$wpdb->update(
				"{$wpdb->prefix}camp_management",
				[ 'photos' => $photos_csv ],
				[ 'id' => $camp_id ],
				[ '%s' ],
				[ '%d' ]
			);
		}
	}

	/**
	 * Handle logo upload
	 */
	private function handle_logo_upload( $camp_id, $camp ) {
		global $wpdb;
		
		// Handle new logo upload
		if ( ! empty( $_FILES['logo_upload']['name'] ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			
			// Set custom upload directory for camp logos
			add_filter( 'upload_dir', [ $this, 'custom_upload_dir_for_camps' ] );
			
			$file = $_FILES['logo_upload'];
			
			// Validate file type
			$file_type = wp_check_filetype( $file['name'] );
			if ( ! in_array( $file_type['ext'], [ 'jpg', 'jpeg', 'png', 'pdf' ] ) ) {
				remove_filter( 'upload_dir', [ $this, 'custom_upload_dir_for_camps' ] );
				return;
			}
			
			// Validate file size (5MB)
			if ( $file['size'] > 5 * 1024 * 1024 ) {
				remove_filter( 'upload_dir', [ $this, 'custom_upload_dir_for_camps' ] );
				return;
			}
			
			// Delete old logo if exists
			if ( ! empty( $camp['logo'] ) ) {
				$this->delete_uploaded_file( $camp['logo'] );
			}
			
			// Upload file
			$upload_overrides = [ 'test_form' => false ];
			$uploaded_file = wp_handle_upload( $file, $upload_overrides );
			
			// Remove custom upload directory filter
			remove_filter( 'upload_dir', [ $this, 'custom_upload_dir_for_camps' ] );
			
			if ( ! isset( $uploaded_file['error'] ) && isset( $uploaded_file['url'] ) ) {
				// Add to media library
				$attachment = [
					'post_mime_type' => $uploaded_file['type'],
					'post_title'     => sanitize_file_name( pathinfo( $uploaded_file['file'], PATHINFO_FILENAME ) ),
					'post_content'   => '',
					'post_status'    => 'inherit'
				];
				
				$attach_id = wp_insert_attachment( $attachment, $uploaded_file['file'] );
				
				// Assign to "Camps" category - create term if it doesn't exist
				$term = get_term_by( 'slug', 'camps', 'media_category' );
				if ( ! $term ) {
					// Create the term if it doesn't exist
					$new_term = wp_insert_term( 'Camps', 'media_category', [
						'description' => 'All camp photos and logos',
						'slug'        => 'camps',
					] );
					if ( ! is_wp_error( $new_term ) ) {
						$term = get_term_by( 'id', $new_term['term_id'], 'media_category' );
					}
				}
				if ( $term ) {
					wp_set_object_terms( $attach_id, (int) $term->term_id, 'media_category', false );
				}
				
				// Generate attachment metadata
				$attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded_file['file'] );
				wp_update_attachment_metadata( $attach_id, $attach_data );
				
				// Update logo URL in database
				$wpdb->update(
					"{$wpdb->prefix}camp_management",
					[ 'logo' => $uploaded_file['url'] ],
					[ 'id' => $camp_id ],
					[ '%s' ],
					[ '%d' ]
				);
			}
		}
	}

	/**
	 * Delete uploaded file from server
	 */
	private function delete_uploaded_file( $file_url ) {
		if ( empty( $file_url ) ) {
			return;
		}
		
		// Convert URL to file path
		$upload_dir = wp_upload_dir();
		$file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_url );
		
		// Delete file if it exists
		if ( file_exists( $file_path ) ) {
			@unlink( $file_path );
		}
	}

	/**
	 * Handle accommodations form submission
	 */
	private function handle_accommodations_submission( $camp_id ) {
		global $wpdb;
		$table = \CreativeDBS\CampMgmt\DB::table_accommodations();
		error_log('CDBS: Accommodations table: ' . $table);
		
		// Get existing accommodation IDs
		$existing_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE camp_id = %d",
			$camp_id
		) );
		error_log('CDBS: Existing accommodation IDs: ' . print_r($existing_ids, true));
		
		$submitted_ids = [];
		
		if ( ! empty( $_POST['accommodations'] ) && is_array( $_POST['accommodations'] ) ) {
			error_log('CDBS: Processing ' . count($_POST['accommodations']) . ' accommodations');
			foreach ( $_POST['accommodations'] as $index => $acc ) {
				$id = intval( $acc['id'] ?? 0 );
				$name = sanitize_text_field( $acc['name'] ?? '' );
				$description = sanitize_textarea_field( $acc['description'] ?? '' );
				$capacity = intval( $acc['capacity'] ?? 0 );
				
				if ( empty( $name ) ) {
					continue; // Skip if name is empty
				}
				
				$data = [
					'camp_id' => $camp_id,
					'name' => $name,
					'description' => $description,
					'capacity' => $capacity,
					'sort_order' => $index,
				];
				
				if ( $id > 0 && in_array( $id, $existing_ids ) ) {
					// Update existing
					$result = $wpdb->update(
						$table,
						$data,
						[ 'id' => $id ],
						[ '%d', '%s', '%s', '%d', '%d' ],
						[ '%d' ]
					);
					error_log('CDBS: Updated accommodation ID ' . $id . ', Result: ' . $result . ', Error: ' . $wpdb->last_error);
					$submitted_ids[] = $id;
				} else {
					// Insert new
					$result = $wpdb->insert(
						$table,
						$data,
						[ '%d', '%s', '%s', '%d', '%d' ]
					);
					error_log('CDBS: Inserted new accommodation, Insert ID: ' . $wpdb->insert_id . ', Error: ' . $wpdb->last_error);
					$submitted_ids[] = $wpdb->insert_id;
				}
			}
		}
		
		// Delete removed accommodations
		foreach ( $existing_ids as $existing_id ) {
			if ( ! in_array( $existing_id, $submitted_ids ) ) {
				$wpdb->delete( $table, [ 'id' => $existing_id ], [ '%d' ] );
			}
		}
	}

	/**
	 * Handle FAQs form submission
	 */
	private function handle_faqs_submission( $camp_id ) {
		global $wpdb;
		$table = \CreativeDBS\CampMgmt\DB::table_faqs();
		
		// Get existing FAQ IDs
		$existing_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE camp_id = %d",
			$camp_id
		) );
		
		$submitted_ids = [];
		
		if ( ! empty( $_POST['faqs'] ) && is_array( $_POST['faqs'] ) ) {
			$count = 0;
			foreach ( $_POST['faqs'] as $index => $faq ) {
				if ( $count >= 12 ) {
					break; // Limit to 12 FAQs
				}
				
				$id = intval( $faq['id'] ?? 0 );
				$question = sanitize_text_field( $faq['question'] ?? '' );
				$answer = sanitize_textarea_field( $faq['answer'] ?? '' );
				
				if ( empty( $question ) || empty( $answer ) ) {
					continue; // Skip if either is empty
				}
				
				$data = [
					'camp_id' => $camp_id,
					'question' => $question,
					'answer' => $answer,
					'sort_order' => $count,
				];
				
				if ( $id > 0 && in_array( $id, $existing_ids ) ) {
					// Update existing
					$wpdb->update(
						$table,
						$data,
						[ 'id' => $id ],
						[ '%d', '%s', '%s', '%d' ],
						[ '%d' ]
					);
					$submitted_ids[] = $id;
				} else {
					// Insert new
					$wpdb->insert(
						$table,
						$data,
						[ '%d', '%s', '%s', '%d' ]
					);
					$submitted_ids[] = $wpdb->insert_id;
				}
				
				$count++;
			}
		}
		
		// Delete removed FAQs
		foreach ( $existing_ids as $existing_id ) {
			if ( ! in_array( $existing_id, $submitted_ids ) ) {
				$wpdb->delete( $table, [ 'id' => $existing_id ], [ '%d' ] );
			}
		}
	}

	/**
	 * Handle sessions form submission
	 */
	private function handle_sessions_submission( $camp_id ) {
		global $wpdb;
		$table = \CreativeDBS\CampMgmt\DB::table_sessions();
		
		// Get existing session IDs
		$existing_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE camp_id = %d",
			$camp_id
		) );
		
		$submitted_ids = [];
		
		if ( ! empty( $_POST['sessions'] ) && is_array( $_POST['sessions'] ) ) {
			foreach ( $_POST['sessions'] as $index => $session ) {
				$id = intval( $session['id'] ?? 0 );
				$session_name = sanitize_text_field( $session['session_name'] ?? '' );
				$start_date = sanitize_text_field( $session['start_date'] ?? '' );
				$end_date = sanitize_text_field( $session['end_date'] ?? '' );
				$price = floatval( $session['price'] ?? 0 );
				$notes = sanitize_text_field( $session['notes'] ?? '' );
				$description = sanitize_textarea_field( $session['description'] ?? '' );
				
				if ( empty( $session_name ) ) {
					continue; // Skip if name is empty
				}
				
				$data = [
					'camp_id' => $camp_id,
					'session_name' => $session_name,
					'start_date' => ! empty( $start_date ) ? $start_date : null,
					'end_date' => ! empty( $end_date ) ? $end_date : null,
					'price' => $price,
					'notes' => $notes,
					'description' => $description,
					'sort_order' => $index,
				];
				
				if ( $id > 0 && in_array( $id, $existing_ids ) ) {
					// Update existing
					$wpdb->update(
						$table,
						$data,
						[ 'id' => $id ],
						[ '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%d' ],
						[ '%d' ]
					);
					$submitted_ids[] = $id;
				} else {
					// Insert new
					$wpdb->insert(
						$table,
						$data,
						[ '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%d' ]
					);
					$submitted_ids[] = $wpdb->insert_id;
				}
			}
		}
		
		// Delete removed sessions
		foreach ( $existing_ids as $existing_id ) {
			if ( ! in_array( $existing_id, $submitted_ids ) ) {
				$wpdb->delete( $table, [ 'id' => $existing_id ], [ '%d' ] );
			}
		}
	}

	/**
	 * Custom upload directory for camp files
	 */
	public function custom_upload_dir_for_camps( $dirs ) {
		$dirs['path']   = $dirs['basedir'] . '/camps';
		$dirs['url']    = $dirs['baseurl'] . '/camps';
		$dirs['subdir'] = '/camps';
		
		// Create directory if it doesn't exist
		if ( ! file_exists( $dirs['path'] ) ) {
			wp_mkdir_p( $dirs['path'] );
		}
		
		return $dirs;
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
			// Redirect to WordPress login page
			wp_redirect( wp_login_url( get_permalink() ) );
			exit;
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
			"SELECT * FROM {$wpdb->prefix}camp_management WHERE wordpress_user_id = %d",
			$user->ID
		), ARRAY_A );

		error_log( 'CDBS Camp Dashboard: Camp data query result: ' . ( $camp ? 'Found' : 'Not found' ) );
		if ( $camp ) {
			error_log( 'CDBS Camp Dashboard: Camp ID: ' . $camp['id'] . ', Camp Name: ' . $camp['camp_name'] );
		}

		if ( ! $camp ) {
			error_log( 'CDBS Camp Dashboard: No camp profile found for user ID ' . $user->ID );
			
			ob_start();
			?>
			<div class="camp-dashboard-error">
				<h2>Camp Profile Not Found</h2>
				<p>Your account has been created, but your camp profile is still being set up.</p>
				<p>This usually happens when:</p>
				<ul style="text-align: left; margin: 20px auto; max-width: 500px;">
					<li>Your registration form submission is still being processed</li>
					<li>An administrator needs to approve your camp listing</li>
					<li>There was an issue syncing your camp data</li>
				</ul>
				<p><strong>What to do:</strong></p>
				<p>Please contact the site administrator or try again in a few minutes.</p>
				<details style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
					<summary style="cursor: pointer; font-weight: 600;">Technical Details (for support)</summary>
					<ul style="text-align: left; margin: 10px 0; font-size: 0.9em;">
						<li>User ID: <?php echo $user->ID; ?></li>
						<li>Username: <?php echo esc_html( $user->user_login ); ?></li>
						<li>Email: <?php echo esc_html( $user->user_email ); ?></li>
						<li>Roles: <?php echo esc_html( implode( ', ', $user->roles ) ); ?></li>
						<li>Camp profile in database: Not found</li>
					</ul>
				</details>
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

		error_log( "CDBS Camp Dashboard: get_pivot_data - Table: {$table}, Camp ID: {$camp_id}, Column: {$value_column}, Count: " . count( $results ) );
		if ( $wpdb->last_error ) {
			error_log( "CDBS Camp Dashboard: Database error: " . $wpdb->last_error );
		}
		
		return $results;
	}

	/**
	 * Get all available options from lookup tables
	 */
	private function get_all_options( $table_name ) {
		global $wpdb;
		// Map plural to singular table names
		$table_map = [
			'types'      => 'type',
			'weeks'      => 'week',
			'activities' => 'activity'
		];
		
		// Use the mapped name if it exists, otherwise use the input
		$singular_name = isset( $table_map[$table_name] ) ? $table_map[$table_name] : $table_name;
		$table = "{$wpdb->prefix}camp_{$singular_name}_terms";
		
		$results = $wpdb->get_results( "SELECT id, name FROM {$table} WHERE is_active = 1 ORDER BY sort_order ASC, name ASC", ARRAY_A );
		
		error_log( "CDBS Camp Dashboard: get_all_options for {$table_name} - Singular: {$singular_name}, Table: {$table}, Count: " . count( $results ) );
		if ( $wpdb->last_error ) {
			error_log( "CDBS Camp Dashboard: Database error: " . $wpdb->last_error );
		}
		
		return $results;
	}

	/**
	 * Render login form
	 * 
	 * @param string $message Optional message to display above login form
	 */
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
			
<?php if ( isset( $_GET['error'] ) && $_GET['error'] === 'word_limit_min' ) : ?>
			<div class="camp-dashboard-error">
				<p>✗ Camp description must be at least 180 words. Please add more detail to your description.</p>
			</div>
		<?php endif; ?>
		
		<?php if ( isset( $_GET['error'] ) && $_GET['error'] === 'word_limit_max' ) : ?>
				<div class="camp-dashboard-error">
					<p>✗ Camp description must be 300 words or less. Please shorten your description and try again.</p>
				</div>
			<?php endif; ?>

<form method="post" action="" class="camp-edit-form" id="camp-dashboard-form" enctype="multipart/form-data">
				<?php wp_nonce_field( 'update_camp_data', 'camp_dashboard_nonce' ); ?>

				<div class="form-section" id="basic-info">
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
							<label for="about_camp">Camp Description <span class="required">*</span></label>
							<textarea id="about_camp" name="about_camp" rows="6" maxlength="5000" required><?php echo esc_textarea( $camp['about_camp'] ); ?></textarea>
							<div class="word-counter">
								<span id="word-count">0</span> words (180 minimum, 300 maximum) <span id="word-limit-warning" style="color: #dc3545; display: none;">● Limit not met</span>
							</div>
						</div>
					</div>

					<div class="form-section" id="contact-info">
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

					<!-- Social Media Links -->
					<div class="form-row">
						<div class="form-group">
							<label>Social Media Links</label>
							<div id="dashboard-social-media-container">
								<?php
								$social_links = [];
								if ( ! empty( $camp['social_media_links'] ) ) {
									$social_links = json_decode( $camp['social_media_links'], true );
									if ( ! is_array( $social_links ) ) {
										$social_links = [];
									}
								}
								if ( empty( $social_links ) ) {
									$social_links = [ '' ]; // At least one field
								}
								foreach ( $social_links as $index => $link ) :
								?>
								<div class="social-media-field" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
									<input type="url" name="social_media[]" value="<?php echo esc_attr( $link ); ?>" placeholder="https://facebook.com/yourcamp" style="flex: 1;">
									<button type="button" class="dashboard-remove-social-btn" style="<?php echo count( $social_links ) <= 1 ? 'display:none;' : ''; ?> background-color: #dc3545; color: white; border: none; border-radius: 4px; width: 35px; height: 35px; font-size: 20px; cursor: pointer; flex-shrink: 0; padding: 7px 24px 5px 15px;">&times;</button>
								</div>
								<?php endforeach; ?>
							</div>
							<button type="button" id="dashboard-add-social-btn" style="background-color: #497C5E; color: white; border: none; border-radius: 5px; padding: 10px 16px; font-size: 14px; font-weight: 600; cursor: pointer; margin-top: 10px;">+ Add Another Social Link</button>
							<p class="description" style="margin-top:8px;color:#666;font-size:13px;">Add your camp's social media profiles (Facebook, Instagram, Twitter, etc.)</p>
						</div>
					</div>

					<!-- Video URL -->
					<div class="form-row">
						<div class="form-group">
							<label for="video_url">Camp Video URL</label>
							<input type="url" id="video_url" name="video_url" value="<?php echo esc_attr( $camp['video_url'] ?? '' ); ?>" placeholder="https://youtube.com/watch?v=... or https://vimeo.com/...">
							<p class="description" style="margin-top:8px;color:#666;font-size:13px;">Showcase your camp with a video tour! (YouTube, Vimeo, or other video platform)</p>
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

				<div class="form-section" id="camp-details">
					<h2 class="section-title">Camp Details</h2>
					


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

			<div class="form-section" id="camp-types">
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
			</div>			<div class="form-section" id="available-weeks">
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
			</div>			<div class="form-section" id="activities">
				<h2 class="section-title">Activities Offered <span class="required">*</span></h2>
				<p class="field-note">Type an activity and press Enter or comma to add it</p>
				<ul id="activities-list" class="chip-list">
					<?php 
					// Get activity names for existing activities
					foreach ( $camp_activities as $activity_id ) {
						$activity = array_filter( $all_activities, function($a) use ($activity_id) {
							return $a['id'] == $activity_id;
						});
						if ( ! empty( $activity ) ) {
							$activity = reset( $activity );
							echo '<li class="chip" data-val="' . esc_attr( $activity['name'] ) . '"><span>' . esc_html( $activity['name'] ) . '</span><button type="button" aria-label="Remove">×</button></li>';
						}
					}
					?>
				</ul>
				<input type="hidden" id="activities-hidden" name="activity_names" value="" />
				<input type="text" id="activities-field" class="regular-text" placeholder="Type an activity and press Enter or comma" />
			</div>

				<!-- Activities tag/chip functionality script -->
				<script>
				document.addEventListener('DOMContentLoaded', function() {
					const field = document.getElementById('activities-field');
					const list = document.getElementById('activities-list');
					const hidden = document.getElementById('activities-hidden');

				function normalizeLabel(s) {
					return s.replace(/\s+/g,' ').trim();
				}

				function syncHidden() {
					const vals = [];
					list.querySelectorAll('li[data-val]').forEach(function(li) {
						vals.push(li.getAttribute('data-val'));
					});
					hidden.value = vals.join(',');
				}

				function addToken(label) {
					label = normalizeLabel(label);
					if (!label) return;
					const exists = Array.from(list.querySelectorAll('li[data-val]')).some(function(li) {
						return li.getAttribute('data-val').toLowerCase() === label.toLowerCase();
					});
					if (exists) return;
					const li = document.createElement('li');
					li.setAttribute('data-val', label);
					li.className = 'chip';
					li.innerHTML = '<span>' + label + '</span><button type="button" aria-label="Remove">&times;</button>';
					li.querySelector('button').addEventListener('click', function() {
						li.remove();
						syncHidden();
					});
					list.appendChild(li);
					syncHidden();
				}

				field.addEventListener('keydown', function(e) {
					if (e.key === 'Enter' || e.key === ',') {
						e.preventDefault();
						const val = field.value;
						field.value = '';
						addToken(val);
					}
				});

				// Initialize hidden field with existing values
				syncHidden();

				// Setup remove buttons for existing chips
				list.querySelectorAll('button').forEach(function(btn) {
					btn.addEventListener('click', function() {
						btn.closest('li').remove();
						syncHidden();
					});
				});
			});
			</script>
			
			<!-- Word counter for camp description -->
			<script>
			document.addEventListener('DOMContentLoaded', function() {
				const textarea = document.getElementById('about_camp');
			if (!textarea) return;
			
			const wordCountSpan = document.getElementById('word-count');
			const warningSpan = document.getElementById('word-limit-warning');
			const submitButton = document.querySelector('.camp-edit-form button[type="submit"]');
			const sidebarSaveButton = document.querySelector('.sidenav-save-btn');
			const minWords = 180;
			const maxWords = 300;
			
			function countWords(text) {
				text = text.trim();
				if (text === '') return 0;
				return text.split(/\s+/).length;
			}
			
			function updateWordCount() {
				const text = textarea.value;
				const wordCount = countWords(text);
				wordCountSpan.textContent = wordCount;
				
				if (wordCount < minWords) {
					wordCountSpan.style.color = '#dc3545';
					wordCountSpan.style.fontWeight = '700';
					warningSpan.textContent = '● Too few words (minimum ' + minWords + ')';
					warningSpan.style.display = 'inline';
					if (submitButton) {
						submitButton.disabled = true;
						submitButton.style.opacity = '0.6';
						submitButton.style.cursor = 'not-allowed';
					}
					if (sidebarSaveButton) {
						sidebarSaveButton.disabled = true;
						sidebarSaveButton.style.opacity = '0.6';
						sidebarSaveButton.style.cursor = 'not-allowed';
					}
				} else if (wordCount > maxWords) {
					wordCountSpan.style.color = '#dc3545';
					wordCountSpan.style.fontWeight = '700';
					warningSpan.textContent = '● Too many words (maximum ' + maxWords + ')';
					warningSpan.style.display = 'inline';
					if (submitButton) {
						submitButton.disabled = true;
						submitButton.style.opacity = '0.6';
						submitButton.style.cursor = 'not-allowed';
					}
					if (sidebarSaveButton) {
						sidebarSaveButton.disabled = true;
						sidebarSaveButton.style.opacity = '0.6';
						sidebarSaveButton.style.cursor = 'not-allowed';
					}
				} else {
					wordCountSpan.style.color = '#2d5a3f';
					wordCountSpan.style.fontWeight = '700';
					warningSpan.style.display = 'none';
					if (submitButton) {
						submitButton.disabled = false;
						submitButton.style.opacity = '1';
						submitButton.style.cursor = 'pointer';
					}
					if (sidebarSaveButton) {
						sidebarSaveButton.disabled = false;
						sidebarSaveButton.style.opacity = '1';
						sidebarSaveButton.style.cursor = 'pointer';
					}
				}
			}
				// Update on typing
				textarea.addEventListener('input', updateWordCount);
			});
			</script>
			
			<!-- Social Media Links Management -->
			<script>
			document.addEventListener('DOMContentLoaded', function() {
				// Auto-add https:// to video URL field
				const videoUrlField = document.getElementById('video_url');
				if (videoUrlField) {
					videoUrlField.addEventListener('blur', function() {
						const value = this.value.trim();
						if (value && !/^https?:\/\//i.test(value)) {
							this.value = 'https://' + value;
						}
					});
				}

				// Function to add https:// to social media fields
				function addHttpsToSocialField(field) {
					field.addEventListener('blur', function() {
						const value = this.value.trim();
						if (value && !/^https?:\/\//i.test(value)) {
							this.value = 'https://' + value;
						}
					});
				}

				const socialContainer = document.getElementById('dashboard-social-media-container');
				const addSocialBtn = document.getElementById('dashboard-add-social-btn');
				const maxSocialFields = 5;
				
				if (addSocialBtn && socialContainer) {
					// Count initial fields
					let socialFieldCount = socialContainer.querySelectorAll('.social-media-field').length;

					// Apply auto-https to existing social media fields
					socialContainer.querySelectorAll('input[name="social_media[]"]').forEach(function(field) {
						addHttpsToSocialField(field);
					});
					
					// Add new social media field
					addSocialBtn.addEventListener('click', function() {
						if (socialFieldCount >= maxSocialFields) {
							return;
						}
						
						socialFieldCount++;
						
						const newField = document.createElement('div');
						newField.className = 'social-media-field';
						newField.style.cssText = 'display: flex; gap: 10px; margin-bottom: 10px; align-items: center;';
						newField.innerHTML = `
							<input type="url" name="social_media[]" placeholder="https://instagram.com/yourcamp" style="flex: 1;">
							<button type="button" class="dashboard-remove-social-btn" style="background-color: #dc3545; color: white; border: none; border-radius: 4px; width: 35px; height: 35px; font-size: 20px; cursor: pointer; flex-shrink: 0; padding: 7px 24px 5px 15px;">&times;</button>
						`;
						
						socialContainer.appendChild(newField);

						// Add auto-https handler to the new field
						const newInput = newField.querySelector('input[name="social_media[]"]');
						if (newInput) {
							addHttpsToSocialField(newInput);
						}
						
						// Update remove button visibility
						updateRemoveButtons();
						
						// Disable add button if max reached
						if (socialFieldCount >= maxSocialFields) {
							addSocialBtn.disabled = true;
							addSocialBtn.textContent = 'Maximum 5 Links';
							addSocialBtn.style.opacity = '0.6';
							addSocialBtn.style.cursor = 'not-allowed';
						}
						
						// Attach remove handler to new button
						const removeBtn = newField.querySelector('.dashboard-remove-social-btn');
						removeBtn.addEventListener('click', function() {
							newField.remove();
							socialFieldCount--;
							addSocialBtn.disabled = false;
							addSocialBtn.textContent = '+ Add Another Social Link';
							addSocialBtn.style.opacity = '1';
							addSocialBtn.style.cursor = 'pointer';
							updateRemoveButtons();
						});
					});
					
					// Function to update remove button visibility
					function updateRemoveButtons() {
						const fields = socialContainer.querySelectorAll('.social-media-field');
						fields.forEach((field) => {
							const removeBtn = field.querySelector('.dashboard-remove-social-btn');
							if (removeBtn) {
								removeBtn.style.display = fields.length > 1 ? 'block' : 'none';
							}
						});
					}
					
					// Attach remove handlers to existing fields
					socialContainer.querySelectorAll('.dashboard-remove-social-btn').forEach(function(btn) {
						btn.addEventListener('click', function() {
							btn.closest('.social-media-field').remove();
							socialFieldCount--;
							addSocialBtn.disabled = false;
							addSocialBtn.textContent = '+ Add Another Social Link';
							addSocialBtn.style.opacity = '1';
							addSocialBtn.style.cursor = 'pointer';
							updateRemoveButtons();
						});
					});
					
					// Initial setup
					updateRemoveButtons();
					if (socialFieldCount >= maxSocialFields) {
						addSocialBtn.disabled = true;
						addSocialBtn.textContent = 'Maximum 5 Links';
						addSocialBtn.style.opacity = '0.6';
						addSocialBtn.style.cursor = 'not-allowed';
					}
				}
			});
			</script>
		</div>

		<!-- Photos Upload Section -->
		<?php
		// Calculate total size of uploaded photos
		$photos = ! empty( $camp['photos'] ) ? explode( ',', $camp['photos'] ) : [];
		$total_size = 0;
		foreach ( $photos as $photo_url ) {
			if ( ! empty( trim( $photo_url ) ) ) {
				$file_path = str_replace( home_url(), ABSPATH, trim( $photo_url ) );
				if ( file_exists( $file_path ) ) {
					$total_size += filesize( $file_path );
				}
			}
		}
		$max_size = 25 * 1024 * 1024; // 25MB in bytes
		$remaining_size = $max_size - $total_size;
		$used_mb = round( $total_size / 1024 / 1024, 2 );
		$remaining_mb = round( $remaining_size / 1024 / 1024, 2 );
		$percent_used = round( ( $total_size / $max_size ) * 100, 1 );
		?>

	<div class="form-section" id="photos">
		<h2 class="section-title">Camp Photos</h2>
		<p class="section-description">Upload up to 10 camp photos (JPG/JPEG only, 25MB total for all photos)</p>
			<p class="storage-info" style="color: #666; font-size: 14px; margin: 10px 0;">
				<strong>Storage:</strong> <?php echo $used_mb; ?>MB used / <?php echo $remaining_mb; ?>MB remaining (<?php echo $percent_used; ?>%)
			</p>
			
			<div class="photos-container">
				<?php
				$photos = ! empty( $camp['photos'] ) ? explode( ',', $camp['photos'] ) : [];
				foreach ( $photos as $index => $photo_url ) :
					if ( ! empty( trim( $photo_url ) ) ) :
				?>
					<div class="photo-item">
						<img src="<?php echo esc_url( trim( $photo_url ) ); ?>" alt="Camp photo <?php echo $index + 1; ?>">
						<button type="button" class="remove-photo" data-photo="<?php echo esc_attr( trim( $photo_url ) ); ?>">&times;</button>
					</div>
				<?php 
					endif;
				endforeach; 
				?>
			</div>
			
			<div class="form-group">
				<label for="photos_upload">Add Photos (up to <?php echo 10 - count( $photos ); ?> more)</label>
				<input type="file" id="photos_upload" name="photos_upload[]" accept="image/jpeg,image/jpg" multiple>
				<p class="field-note">JPG/JPEG only, 25MB total for all photos, up to 10 photos</p>
			</div>
			
			<input type="hidden" id="photos_to_remove" name="photos_to_remove" value="">
		</div>

		<!-- Logo Upload Section -->
		<div class="form-section" id="logo">
			<h2 class="section-title">Camp Logo</h2>
			<?php
			// Calculate logo file size
			$logo_size = 0;
			if ( ! empty( $camp['logo'] ) ) {
				$logo_path = str_replace( home_url(), ABSPATH, $camp['logo'] );
				if ( file_exists( $logo_path ) ) {
					$logo_size = filesize( $logo_path );
				}
			}
			$max_logo_size = 5 * 1024 * 1024; // 5MB in bytes
			$remaining_logo_size = $max_logo_size - $logo_size;
			$used_logo_mb = round( $logo_size / 1024 / 1024, 2 );
			$remaining_logo_mb = round( $remaining_logo_size / 1024 / 1024, 2 );
			$percent_logo_used = $logo_size > 0 ? round( ( $logo_size / $max_logo_size ) * 100, 1 ) : 0;
			?>
			<p class="section-description">Upload your camp logo (JPG/JPEG/PNG/PDF, max 5MB)</p>
			<p class="storage-info" style="color: #666; font-size: 14px; margin: 10px 0;">
				<strong>Storage:</strong> <?php echo $used_logo_mb; ?>MB used / <?php echo $remaining_logo_mb; ?>MB remaining (<?php echo $percent_logo_used; ?>%)
			</p>
			
			<?php if ( ! empty( $camp['logo'] ) ) : ?>
				<div class="logo-preview">
					<?php if ( pathinfo( $camp['logo'], PATHINFO_EXTENSION ) === 'pdf' ) : ?>
						<div class="pdf-placeholder">
							<span>📄 PDF Logo</span>
							<a href="<?php echo esc_url( $camp['logo'] ); ?>" target="_blank">View PDF</a>
						</div>
					<?php else : ?>
						<img src="<?php echo esc_url( $camp['logo'] ); ?>" alt="Camp logo">
					<?php endif; ?>
				</div>
			<?php endif; ?>
			
			<div class="form-group">
				<label for="logo_upload"><?php echo ! empty( $camp['logo'] ) ? 'Replace Logo' : 'Upload Logo'; ?></label>
				<input type="file" id="logo_upload" name="logo_upload" accept="image/jpeg,image/jpg,image/png">
				<p class="field-note">JPG, JPEG or PNG format, max 5MB</p>
			</div>
			
			<input type="hidden" id="logo_to_remove" name="logo_to_remove" value="">
		</div>

		<!-- Accommodations Section - AJAX Based -->
		<div class="form-section" id="accommodations">
			<h2 class="section-title">Accommodation Facilities</h2>
			<p class="section-description">Click "Add New Facility" to add cabins and other facilities</p>
			
			<div id="accommodations-list" class="ajax-list">
				<?php
				global $wpdb;
				$accommodations = $wpdb->get_results( $wpdb->prepare(
					"SELECT * FROM " . \CreativeDBS\CampMgmt\DB::table_accommodations() . " WHERE camp_id = %d ORDER BY sort_order ASC",
					$camp['id']
				), ARRAY_A );
				
				if ( empty( $accommodations ) ) {
					echo '<p class="no-items">No accommodations added yet.</p>';
				} else {
					foreach ( $accommodations as $acc ) :
				?>
					<div class="list-item" data-id="<?php echo esc_attr( $acc['id'] ); ?>">
						<div class="item-content">
							<strong><?php echo esc_html( $acc['name'] ); ?></strong>
							<span class="item-meta">Capacity: <?php echo esc_html( $acc['capacity'] ); ?></span>
							<p><?php echo esc_html( $acc['description'] ); ?></p>
						</div>
						<div class="item-actions">
							<button type="button" class="btn-edit-sm" onclick="editAccommodation(<?php echo $acc['id']; ?>)">Edit</button>
							<button type="button" class="btn-delete-sm" onclick="deleteAccommodation(<?php echo $acc['id']; ?>)">Delete</button>
						</div>
					</div>
				<?php
					endforeach;
				}
				?>
			</div>
			
			<button type="button" class="btn-add-item" onclick="showAccommodationForm()">+ Add New Facility</button>
			
			<!-- Add/Edit Form (hidden by default) -->
			<div id="accommodation-form" style="display:none; margin-top:20px; padding:20px; background:#f9f9f9; border-radius:8px;">
				<h3 id="accommodation-form-title">Add New Facility</h3>
				<input type="hidden" id="accommodation-id" value="0">
				<div class="form-group">
					<label>Facility Name *</label>
					<input type="text" id="accommodation-name" placeholder="e.g., Cabin A, Main Lodge">
				</div>
				<div class="form-group">
					<label>Capacity *</label>
					<input type="number" id="accommodation-capacity" placeholder="Number of guests" min="0">
				</div>
				<div class="form-group">
					<label>Description</label>
					<textarea id="accommodation-description" rows="3" placeholder="Describe the facilities and amenities" maxlength="1000"></textarea>
					<div class="word-counter">
						<span id="accommodation-word-count">0</span> / 90 words <span id="accommodation-word-limit-warning" style="color: #dc3545; display: none;">● Limit exceeded</span>
					</div>
				</div>
				<div style="margin-top:15px;">
					<button type="button" class="btn-save" onclick="saveAccommodation()">Save</button>
					<button type="button" class="btn-cancel" onclick="cancelAccommodationForm()">Cancel</button>
				</div>
			</div>
		</div>

		<!-- FAQs Section - AJAX Based -->
		<div class="form-section" id="faqs">
			<h2 class="section-title">Frequently Asked Questions (FAQs)</h2>
			<p class="section-description">Add up to 12 frequently asked questions</p>
			
			<div id="faqs-list" class="ajax-list">
				<?php
				$faqs = $wpdb->get_results( $wpdb->prepare(
					"SELECT * FROM " . \CreativeDBS\CampMgmt\DB::table_faqs() . " WHERE camp_id = %d ORDER BY sort_order ASC LIMIT 12",
					$camp['id']
				), ARRAY_A );
				
				if ( empty( $faqs ) ) {
					echo '<p class="no-items">No FAQs added yet.</p>';
				} else {
					foreach ( $faqs as $faq ) :
				?>
					<div class="list-item" data-id="<?php echo esc_attr( $faq['id'] ); ?>">
						<div class="item-content">
							<strong><?php echo esc_html( $faq['question'] ); ?></strong>
							<p><?php echo esc_html( $faq['answer'] ); ?></p>
						</div>
						<div class="item-actions">
							<button type="button" class="btn-edit-sm" onclick="editFaq(<?php echo $faq['id']; ?>)">Edit</button>
							<button type="button" class="btn-delete-sm" onclick="deleteFaq(<?php echo $faq['id']; ?>)">Delete</button>
						</div>
					</div>
				<?php
					endforeach;
				}
				?>
			</div>
			
			<button type="button" class="btn-add-item" onclick="showFaqForm()" <?php echo count( $faqs ) >= 12 ? 'disabled title="Maximum 12 FAQs"' : ''; ?>>+ Add New FAQ</button>
			
			<!-- Add/Edit Form -->
			<div id="faq-form" style="display:none; margin-top:20px; padding:20px; background:#f9f9f9; border-radius:8px;">
				<h3 id="faq-form-title">Add New FAQ</h3>
				<input type="hidden" id="faq-id" value="0">
				<div class="form-group">
					<label>Question *</label>
					<input type="text" id="faq-question" placeholder="Enter your question">
				</div>
				<div class="form-group">
					<label>Answer *</label>
					<textarea id="faq-answer" rows="4" placeholder="Enter the answer"></textarea>
				</div>
				<div style="margin-top:15px;">
					<button type="button" class="btn-save" onclick="saveFaq()">Save</button>
					<button type="button" class="btn-cancel" onclick="cancelFaqForm()">Cancel</button>
				</div>
			</div>
		</div>

		<!-- Sessions Section - AJAX Based -->
		<div class="form-section" id="sessions">
			<h2 class="section-title">Sessions (Rates & Dates)</h2>
			<p class="section-description">Add session cards with dates, prices, and details</p>
			
			<div id="sessions-list" class="ajax-list">
				<?php
				$sessions = $wpdb->get_results( $wpdb->prepare(
					"SELECT * FROM " . \CreativeDBS\CampMgmt\DB::table_sessions() . " WHERE camp_id = %d ORDER BY sort_order ASC",
					$camp['id']
				), ARRAY_A );
				
				if ( empty( $sessions ) ) {
					echo '<p class="no-items">No sessions added yet.</p>';
				} else {
					foreach ( $sessions as $session ) :
				?>
					<div class="list-item" data-id="<?php echo esc_attr( $session['id'] ); ?>">
						<div class="item-content">
							<strong><?php echo esc_html( $session['session_name'] ); ?></strong>
							<span class="item-meta">
								<?php echo esc_html( $session['start_date'] ); ?> - <?php echo esc_html( $session['end_date'] ); ?> | 
								$<?php echo esc_html( number_format( $session['price'], 2 ) ); ?>
							</span>
							<?php if ( ! empty( $session['notes'] ) ) : ?>
								<p><em><?php echo esc_html( $session['notes'] ); ?></em></p>
							<?php endif; ?>
							<?php if ( ! empty( $session['description'] ) ) : ?>
								<p><?php echo esc_html( $session['description'] ); ?></p>
							<?php endif; ?>
						</div>
						<div class="item-actions">
							<button type="button" class="btn-edit-sm" onclick="editSession(<?php echo $session['id']; ?>)">Edit</button>
							<button type="button" class="btn-delete-sm" onclick="deleteSession(<?php echo $session['id']; ?>)">Delete</button>
						</div>
					</div>
				<?php
					endforeach;
				}
				?>
			</div>
			
			<button type="button" class="btn-add-item" onclick="showSessionForm()">+ Add New Session</button>
			
			<!-- Add/Edit Form -->
			<div id="session-form" style="display:none; margin-top:20px; padding:20px; background:#f9f9f9; border-radius:8px;">
				<h3 id="session-form-title">Add New Session</h3>
				<input type="hidden" id="session-id" value="0">
				<div class="form-row">
					<div class="form-group half">
						<label>Session Name *</label>
						<input type="text" id="session-name" placeholder="e.g., Week 1 - Adventure">
					</div>
					<div class="form-group half">
						<label>Price *</label>
						<input type="number" id="session-price" step="0.01" min="0" placeholder="0.00">
					</div>
				</div>
				<div class="form-row">
					<div class="form-group half">
						<label>Start Date *</label>
						<input type="date" id="session-start-date">
					</div>
					<div class="form-group half">
						<label>End Date *</label>
						<input type="date" id="session-end-date">
					</div>
				</div>
				<div class="form-group">
					<label>Notes</label>
					<input type="text" id="session-notes" placeholder="Brief note">
				</div>
				<div class="form-group">
					<label>Description</label>
					<textarea id="session-description" rows="3" placeholder="Session details" maxlength="1000"></textarea>
					<div class="word-counter">
						<span id="session-word-count">0</span> / 90 words <span id="session-word-limit-warning" style="color: #dc3545; display: none;">● Limit exceeded</span>
					</div>
				</div>
				<div style="margin-top:15px;">
					<button type="button" class="btn-save" onclick="saveSession()">Save</button>
					<button type="button" class="btn-cancel" onclick="cancelSessionForm()">Cancel</button>
				</div>
			</div>
		</div>

		<script type="text/javascript">
		/* <![CDATA[ */
		document.addEventListener('DOMContentLoaded', function() {
			// File validation for photos
			document.getElementById('photos_upload').addEventListener('change', function(e) {
				const files = e.target.files;
				if (files.length > 10) {
					alert('You can only upload up to 10 photos total.');
					this.value = '';
					return;
				}
				
				let totalSize = 0;
				for (let i = 0; i < files.length; i++) {
					const file = files[i];
					// Check file type
					if (!file.type.match('image/jpeg')) {
						if (!file.type.match('image/jpg')) {
							alert('Photos must be in JPG or JPEG format only.');
							this.value = '';
							return;
						}
					}
					totalSize += file.size;
				}
				
				// Check total size (25MB = 25 * 1024 * 1024 bytes)
				if (totalSize > 25 * 1024 * 1024) {
					const totalMB = (totalSize / (1024 * 1024)).toFixed(2);
					alert('Total size of all photos must be under 25MB. Current total: ' + totalMB + 'MB.');
					this.value = '';
					return;
				}
			});

			// File validation for logo
			document.getElementById('logo_upload').addEventListener('change', function(e) {
				const file = e.target.files[0];
				if (!file) return;
				
				// Check file type
				const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
				if (!allowedTypes.includes(file.type)) {
					alert('Logo must be JPG, JPEG, PNG, or PDF format only.');
					this.value = '';
					return;
				}
				
				// Check file size (5MB = 5 * 1024 * 1024 bytes)
				if (file.size > 5 * 1024 * 1024) {
					alert('Logo must be under 5MB. Current file is ' + (file.size / (1024 * 1024)).toFixed(2) + 'MB.');
					this.value = '';
					return;
				}
			});
		});
		/* ]]> */
		</script>

			<script type="text/javascript">
			/* <![CDATA[ */
			document.addEventListener('DOMContentLoaded', function() {
				const form = document.querySelector('.camp-edit-form');
				
				form.addEventListener('submit', function(e) {
					// Check if files are being uploaded
					const photosUpload = document.getElementById('photos_upload');
					const logoUpload = document.getElementById('logo_upload');
					let hasPhotos = false;
					let hasLogo = false;
					if (photosUpload) {
						if (photosUpload.files.length > 0) hasPhotos = true;
					}
					if (logoUpload) {
						if (logoUpload.files.length > 0) hasLogo = true;
					}
					const hasFiles = hasPhotos || hasLogo;
					
					// Check required text fields
					const missingFields = [];
					const requiredFields = form.querySelectorAll('[required]');
						
						requiredFields.forEach(function(field) {
							if (!field.value.trim()) {
								missingFields.push(field.previousElementSibling.textContent.replace('*', '').trim());
							}
						});

						// Check camp types (at least one)
						const campTypes = form.querySelectorAll('input[name="camp_types[]"]:checked');
					if (campTypes.length === 0) {
						missingFields.push('Camp Types (select at least one)');
					}

					// Check available weeks (at least one)
					const campWeeks = form.querySelectorAll('input[name="camp_weeks[]"]:checked');
					if (campWeeks.length === 0) {
						missingFields.push('Available Weeks (select at least one)');
					}
					const activitiesHidden = document.getElementById('activities-hidden');
					if (!activitiesHidden.value.trim()) {
						missingFields.push('Activities Offered (add at least one)');
					}

					if (missingFields.length > 0) {
						e.preventDefault();
						alert('Please complete the following required fields:\n\n- ' + missingFields.join('\n- '));
						return false;
					}
					
					// Show loading overlay if files are being uploaded
					if (hasFiles) {
						const overlay = document.createElement('div');
						overlay.id = 'upload-overlay';
						overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:9999;display:flex;align-items:center;justify-content:center;';
						
						const box = document.createElement('div');
						box.style.cssText = 'background:white;padding:50px 70px;border-radius:12px;text-align:center;box-shadow:0 8px 30px rgba(0,0,0,0.4);max-width:600px;';
						
						const title = document.createElement('div');
						title.style.cssText = 'font-size:24px;font-weight:700;color:#497C5E;margin-bottom:20px;';
						title.textContent = 'Uploading Files...';
						
						const barContainer = document.createElement('div');
						barContainer.style.cssText = 'width:100%;height:8px;background:#e0e0e0;border-radius:4px;overflow:hidden;margin-bottom:20px;';
						
						const progressBar = document.createElement('div');
						progressBar.id = 'upload-progress-bar';
						progressBar.style.cssText = 'width:0%;height:100%;background:#497C5E;transition:width 0.3s ease;';
						barContainer.appendChild(progressBar);
						
						const progressText = document.createElement('div');
						progressText.id = 'upload-percentage';
						progressText.style.cssText = 'font-size:32px;font-weight:700;color:#497C5E;margin-bottom:15px;';
						progressText.textContent = '0%';
						
						const warning = document.createElement('div');
						warning.style.cssText = 'font-size:16px;color:#333;font-weight:600;margin-bottom:10px;';
						warning.textContent = 'Please Keep This Window Open';
						
						const details = document.createElement('div');
						details.style.cssText = 'font-size:14px;color:#666;line-height:1.6;';
						details.textContent = 'Your photos are being uploaded. This might take a few minutes depending on file sizes. Do not close or refresh this page.';
						
						box.appendChild(title);
						box.appendChild(barContainer);
						box.appendChild(progressText);
						box.appendChild(warning);
						box.appendChild(details);
						overlay.appendChild(box);
						document.body.appendChild(overlay);
						
						// Simulate upload progress
						let progress = 0;
						const progressInterval = setInterval(function() {
							if (progress < 90) {
								progress += Math.random() * 15;
								if (progress > 90) progress = 90;
								progressBar.style.width = progress + '%';
								progressText.textContent = Math.round(progress) + '%';
							}
						}, 500);
					}
				});
			});
			/* ]]> */
			</script>

			<!-- Submit Button -->
			<div class="form-section" style="text-align: center; padding: 40px 0 20px; border: none; background: transparent; box-shadow: none;">
				<button type="submit" class="btn-submit">Save All Changes</button>
			</div>
		</form>
	</div>
		
		<script>
		// Photo removal functionality
		document.addEventListener('DOMContentLoaded', function() {
			const removeButtons = document.querySelectorAll('.remove-photo');
			const photosToRemoveField = document.getElementById('photos_to_remove');
			let photosToRemove = [];
			
			removeButtons.forEach(function(btn) {
				btn.addEventListener('click', function(e) {
					e.preventDefault();
					e.stopPropagation();
					const photoUrl = btn.getAttribute('data-photo');
					photosToRemove.push(photoUrl);
					photosToRemoveField.value = photosToRemove.join(',');
					btn.closest('.photo-item').remove();
				});
			});
		});

		//  ===========================================
		// AJAX FUNCTIONS FOR ACCOMMODATIONS
		// ===========================================
		function showAccommodationForm() {
			document.getElementById('accommodation-form').style.display = 'block';
			document.getElementById('accommodation-form-title').textContent = 'Add New Facility';
			document.getElementById('accommodation-id').value = '0';
			document.getElementById('accommodation-name').value = '';
			document.getElementById('accommodation-capacity').value = '';
			document.getElementById('accommodation-description').value = '';
		}

		function cancelAccommodationForm() {
			document.getElementById('accommodation-form').style.display = 'none';
		}

		// Word counter for accommodation description
		(function() {
			const textarea = document.getElementById('accommodation-description');
			const wordCountSpan = document.getElementById('accommodation-word-count');
			const warningSpan = document.getElementById('accommodation-word-limit-warning');
			const maxWords = 90;
			
			if (textarea && wordCountSpan) {
				function countWords(text) {
					text = text.trim();
					if (text === '') return 0;
					return text.split(/\s+/).length;
				}
				
				function updateWordCount() {
					const text = textarea.value;
					const wordCount = countWords(text);
					wordCountSpan.textContent = wordCount;
					
					if (wordCount > maxWords) {
						wordCountSpan.style.color = '#dc3545';
						wordCountSpan.style.fontWeight = '700';
						warningSpan.style.display = 'inline';
					} else {
						wordCountSpan.style.color = '#333';
						wordCountSpan.style.fontWeight = '600';
						warningSpan.style.display = 'none';
					}
				}
				
				textarea.addEventListener('input', updateWordCount);
				updateWordCount();
			}
		})();

		function saveAccommodation() {
			const id = document.getElementById('accommodation-id').value;
			const name = document.getElementById('accommodation-name').value.trim();
			const capacity = document.getElementById('accommodation-capacity').value;
			const description = document.getElementById('accommodation-description').value.trim();

			if (!name || !capacity) {
				alert('Please fill in all required fields');
				return;
			}

			const formData = new FormData();
			formData.append('action', 'camp_save_accommodation');
			formData.append('nonce', '<?php echo wp_create_nonce( 'camp_ajax_nonce' ); ?>');
			formData.append('id', id);
			formData.append('name', name);
			formData.append('capacity', capacity);
			formData.append('description', description);

			fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					location.reload(); // Reload to show the new item
				} else {
					alert('Error: ' + (data.message || 'Failed to save'));
				}
			})
			.catch(error => {
				alert('Error: ' + error.message);
			});
		}

		function editAccommodation(id) {
			// Fetch the accommodation data and populate the form
			const formData = new FormData();
			formData.append('action', 'camp_get_accommodation');
			formData.append('nonce', '<?php echo wp_create_nonce( 'camp_ajax_nonce' ); ?>');
			formData.append('id', id);

			fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					document.getElementById('accommodation-form').style.display = 'block';
					document.getElementById('accommodation-form-title').textContent = 'Edit Facility';
					document.getElementById('accommodation-id').value = data.data.id;
					document.getElementById('accommodation-name').value = data.data.name;
					document.getElementById('accommodation-capacity').value = data.data.capacity;
					document.getElementById('accommodation-description').value = data.data.description || '';
				}
			});
		}

		function deleteAccommodation(id) {
			if (!confirm('Are you sure you want to delete this facility?')) return;

			const formData = new FormData();
			formData.append('action', 'camp_delete_accommodation');
			formData.append('nonce', '<?php echo wp_create_nonce( 'camp_ajax_nonce' ); ?>');
			formData.append('id', id);

			fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					location.reload();
				} else {
					alert('Error: ' + (data.message || 'Failed to delete'));
				}
			});
		}

		// ===========================================
		// AJAX FUNCTIONS FOR FAQs
		// ===========================================
		function showFaqForm() {
			document.getElementById('faq-form').style.display = 'block';
			document.getElementById('faq-form-title').textContent = 'Add New FAQ';
			document.getElementById('faq-id').value = '0';
			document.getElementById('faq-question').value = '';
			document.getElementById('faq-answer').value = '';
		}

		function cancelFaqForm() {
			document.getElementById('faq-form').style.display = 'none';
		}

		function saveFaq() {
			const id = document.getElementById('faq-id').value;
			const question = document.getElementById('faq-question').value.trim();
			const answer = document.getElementById('faq-answer').value.trim();

			if (!question || !answer) {
				alert('Please fill in all required fields');
				return;
			}

			const formData = new FormData();
			formData.append('action', 'camp_save_faq');
			formData.append('nonce', '<?php echo wp_create_nonce( 'camp_ajax_nonce' ); ?>');
			formData.append('id', id);
			formData.append('question', question);
			formData.append('answer', answer);

			fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					location.reload();
				} else {
					alert('Error: ' + (data.message || 'Failed to save'));
				}
			});
		}

		function editFaq(id) {
			const formData = new FormData();
			formData.append('action', 'camp_get_faq');
			formData.append('nonce', '<?php echo wp_create_nonce( 'camp_ajax_nonce' ); ?>');
			formData.append('id', id);

			fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					document.getElementById('faq-form').style.display = 'block';
					document.getElementById('faq-form-title').textContent = 'Edit FAQ';
					document.getElementById('faq-id').value = data.data.id;
					document.getElementById('faq-question').value = data.data.question;
					document.getElementById('faq-answer').value = data.data.answer || '';
				}
			});
		}

		function deleteFaq(id) {
			if (!confirm('Are you sure you want to delete this FAQ?')) return;

			const formData = new FormData();
			formData.append('action', 'camp_delete_faq');
			formData.append('nonce', '<?php echo wp_create_nonce( 'camp_ajax_nonce' ); ?>');
			formData.append('id', id);

			fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					location.reload();
				} else {
					alert('Error: ' + (data.message || 'Failed to delete'));
				}
			});
		}

		// ===========================================
		// AJAX FUNCTIONS FOR SESSIONS
		// ===========================================
		function showSessionForm() {
			document.getElementById('session-form').style.display = 'block';
			document.getElementById('session-form-title').textContent = 'Add New Session';
			document.getElementById('session-id').value = '0';
			document.getElementById('session-name').value = '';
			document.getElementById('session-price').value = '';
			document.getElementById('session-start-date').value = '';
			document.getElementById('session-end-date').value = '';
			document.getElementById('session-notes').value = '';
			document.getElementById('session-description').value = '';
		}

		function cancelSessionForm() {
			document.getElementById('session-form').style.display = 'none';
		}

		// Word counter for session description
		(function() {
			const textarea = document.getElementById('session-description');
			const wordCountSpan = document.getElementById('session-word-count');
			const warningSpan = document.getElementById('session-word-limit-warning');
			const maxWords = 90;
			
			if (textarea && wordCountSpan) {
				function countWords(text) {
					text = text.trim();
					if (text === '') return 0;
					return text.split(/\s+/).length;
				}
				
				function updateWordCount() {
					const text = textarea.value;
					const wordCount = countWords(text);
					wordCountSpan.textContent = wordCount;
					
					if (wordCount > maxWords) {
						wordCountSpan.style.color = '#dc3545';
						wordCountSpan.style.fontWeight = '700';
						warningSpan.style.display = 'inline';
					} else {
						wordCountSpan.style.color = '#333';
						wordCountSpan.style.fontWeight = '600';
						warningSpan.style.display = 'none';
					}
				}
				
				textarea.addEventListener('input', updateWordCount);
				updateWordCount();
			}
		})();

		function saveSession() {
			const id = document.getElementById('session-id').value;
			const name = document.getElementById('session-name').value.trim();
			const price = document.getElementById('session-price').value;
			const startDate = document.getElementById('session-start-date').value;
			const endDate = document.getElementById('session-end-date').value;
			const notes = document.getElementById('session-notes').value.trim();
			const description = document.getElementById('session-description').value.trim();

			if (!name || !price || !startDate || !endDate) {
				alert('Please fill in all required fields');
				return;
			}

			const formData = new FormData();
			formData.append('action', 'camp_save_session');
			formData.append('nonce', '<?php echo wp_create_nonce( 'camp_ajax_nonce' ); ?>');
			formData.append('id', id);
			formData.append('session_name', name);
			formData.append('price', price);
			formData.append('start_date', startDate);
			formData.append('end_date', endDate);
			formData.append('notes', notes);
			formData.append('description', description);

			fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					location.reload();
				} else {
					alert('Error: ' + (data.message || 'Failed to save'));
				}
			});
		}

		function editSession(id) {
			const formData = new FormData();
			formData.append('action', 'camp_get_session');
			formData.append('nonce', '<?php echo wp_create_nonce( 'camp_ajax_nonce' ); ?>');
			formData.append('id', id);

			fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					document.getElementById('session-form').style.display = 'block';
					document.getElementById('session-form-title').textContent = 'Edit Session';
					document.getElementById('session-id').value = data.data.id;
					document.getElementById('session-name').value = data.data.session_name;
					document.getElementById('session-price').value = data.data.price;
					document.getElementById('session-start-date').value = data.data.start_date;
					document.getElementById('session-end-date').value = data.data.end_date;
					document.getElementById('session-notes').value = data.data.notes || '';
					document.getElementById('session-description').value = data.data.description || '';
				}
			});
		}

		function deleteSession(id) {
			if (!confirm('Are you sure you want to delete this session?')) return;

			const formData = new FormData();
			formData.append('action', 'camp_delete_session');
			formData.append('nonce', '<?php echo wp_create_nonce( 'camp_ajax_nonce' ); ?>');
			formData.append('id', id);

			fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					location.reload();
				} else {
					alert('Error: ' + (data.message || 'Failed to delete'));
				}
			});
		}
		/* ]]> */
		</script>

		<style>
		.ajax-list {
			margin: 20px 0;
		}
		.list-item {
			background: #fff;
			border: 1px solid #ddd;
			border-radius: 8px;
			padding: 15px;
			margin-bottom: 15px;
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
		}
		.item-content {
			flex: 1;
		}
		.item-content strong {
			display: block;
			font-size: 16px;
			margin-bottom: 5px;
			color: #333;
		}
		.item-meta {
			display: block;
			font-size: 14px;
			color: #666;
			margin-bottom: 8px;
		}
		.item-content p {
			margin: 5px 0;
			color: #555;
			font-size: 14px;
		}
		.item-actions {
			display: flex;
			gap: 10px;
		}
		.btn-edit-sm, .btn-delete-sm {
			padding: 6px 12px;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			font-size: 13px;
			font-weight: 500;
		}
		.btn-edit-sm {
			background: #497C5E;
			color: white;
		}
		.btn-edit-sm:hover {
			background: #3d6a4f;
		}
		.btn-delete-sm {
			background: #dc3545;
			color: white;
		}
		.btn-delete-sm:hover {
			background: #c82333;
		}
		.btn-save, .btn-cancel {
			padding: 10px 20px;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			font-size: 14px;
			font-weight: 600;
			margin-right: 10px;
		}
		.btn-save {
			background: #497C5E;
			color: white;
		}
		.btn-save:hover {
			background: #3d6a4f;
		}
		.btn-cancel {
			background: #6c757d;
			color: white;
		}
		.btn-cancel:hover {
			background: #5a6268;
		}
		.no-items {
			color: #999;
			font-style: italic;
			padding: 20px;
			text-align: center;
		}
		.btn-add-item {
			background: #497C5E;
			color: white;
			border: none;
			padding: 12px 24px;
			border-radius: 4px;
			cursor: pointer;
			font-size: 15px;
			font-weight: 600;
		}
		.btn-add-item:hover {
			background: #3d6a4f;
		}
		.btn-add-item:disabled {
			background: #ccc;
			cursor: not-allowed;
		}
		</style>

		<?php
	}

	// ===========================================
	// AJAX HANDLERS FOR ACCOMMODATIONS
	// ===========================================
	
	public function ajax_save_accommodation() {
		check_ajax_referer( 'camp_ajax_nonce', 'nonce' );
		
		$user = wp_get_current_user();
		if ( ! in_array( 'camp', $user->roles ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		// Get camp_id for current user
		global $wpdb;
		$camp_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}camp_management WHERE wordpress_user_id = %d",
			$user->ID
		) );

		if ( ! $camp_id ) {
			wp_send_json_error( array( 'message' => 'Camp not found' ) );
		}

		$id = intval( $_POST['id'] );
		$name = sanitize_text_field( $_POST['name'] );
		$capacity = intval( $_POST['capacity'] );
		$description = sanitize_textarea_field( $_POST['description'] );

		$table = \CreativeDBS\CampMgmt\DB::table_accommodations();
		
		if ( $id > 0 ) {
			// Update
			$wpdb->update(
				$table,
				array(
					'name' => $name,
					'capacity' => $capacity,
					'description' => $description,
				),
				array( 'id' => $id, 'camp_id' => $camp_id )
			);
		} else {
			// Insert
			$wpdb->insert(
				$table,
				array(
					'camp_id' => $camp_id,
					'name' => $name,
					'capacity' => $capacity,
					'description' => $description,
					'sort_order' => 0,
				)
			);
		}

		wp_send_json_success();
	}

	public function ajax_get_accommodation() {
		check_ajax_referer( 'camp_ajax_nonce', 'nonce' );
		
		global $wpdb;
		$id = intval( $_POST['id'] );
		
		$accommodation = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM " . \CreativeDBS\CampMgmt\DB::table_accommodations() . " WHERE id = %d",
			$id
		), ARRAY_A );

		if ( $accommodation ) {
			wp_send_json_success( $accommodation );
		} else {
			wp_send_json_error( array( 'message' => 'Not found' ) );
		}
	}

	public function ajax_delete_accommodation() {
		check_ajax_referer( 'camp_ajax_nonce', 'nonce' );
		
		global $wpdb;
		$id = intval( $_POST['id'] );
		
		$wpdb->delete( \CreativeDBS\CampMgmt\DB::table_accommodations(), array( 'id' => $id ) );
		
		wp_send_json_success();
	}

	// ===========================================
	// AJAX HANDLERS FOR FAQs
	// ===========================================
	
	public function ajax_save_faq() {
		check_ajax_referer( 'camp_ajax_nonce', 'nonce' );
		
		$user = wp_get_current_user();
		if ( ! in_array( 'camp', $user->roles ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		global $wpdb;
		$camp_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}camp_management WHERE wordpress_user_id = %d",
			$user->ID
		) );

		if ( ! $camp_id ) {
			wp_send_json_error( array( 'message' => 'Camp not found' ) );
		}

		$id = intval( $_POST['id'] );
		$question = sanitize_text_field( $_POST['question'] );
		$answer = sanitize_textarea_field( $_POST['answer'] );

		$table = \CreativeDBS\CampMgmt\DB::table_faqs();
		
		if ( $id > 0 ) {
			$wpdb->update(
				$table,
				array(
					'question' => $question,
					'answer' => $answer,
				),
				array( 'id' => $id, 'camp_id' => $camp_id )
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'camp_id' => $camp_id,
					'question' => $question,
					'answer' => $answer,
					'sort_order' => 0,
				)
			);
		}

		wp_send_json_success();
	}

	public function ajax_get_faq() {
		check_ajax_referer( 'camp_ajax_nonce', 'nonce' );
		
		global $wpdb;
		$id = intval( $_POST['id'] );
		
		$faq = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM " . \CreativeDBS\CampMgmt\DB::table_faqs() . " WHERE id = %d",
			$id
		), ARRAY_A );

		if ( $faq ) {
			wp_send_json_success( $faq );
		} else {
			wp_send_json_error( array( 'message' => 'Not found' ) );
		}
	}

	public function ajax_delete_faq() {
		check_ajax_referer( 'camp_ajax_nonce', 'nonce' );
		
		global $wpdb;
		$id = intval( $_POST['id'] );
		
		$wpdb->delete( \CreativeDBS\CampMgmt\DB::table_faqs(), array( 'id' => $id ) );
		
		wp_send_json_success();
	}

	// ===========================================
	// AJAX HANDLERS FOR SESSIONS
	// ===========================================
	
	public function ajax_save_session() {
		check_ajax_referer( 'camp_ajax_nonce', 'nonce' );
		
		$user = wp_get_current_user();
		if ( ! in_array( 'camp', $user->roles ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		global $wpdb;
		$camp_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}camp_management WHERE wordpress_user_id = %d",
			$user->ID
		) );

		if ( ! $camp_id ) {
			wp_send_json_error( array( 'message' => 'Camp not found' ) );
		}

		$id = intval( $_POST['id'] );
		$session_name = sanitize_text_field( $_POST['session_name'] );
		$price = floatval( $_POST['price'] );
		$start_date = sanitize_text_field( $_POST['start_date'] );
		$end_date = sanitize_text_field( $_POST['end_date'] );
		$notes = sanitize_text_field( $_POST['notes'] );
		$description = sanitize_textarea_field( $_POST['description'] );

		$table = \CreativeDBS\CampMgmt\DB::table_sessions();
		
		if ( $id > 0 ) {
			$wpdb->update(
				$table,
				array(
					'session_name' => $session_name,
					'price' => $price,
					'start_date' => $start_date,
					'end_date' => $end_date,
					'notes' => $notes,
					'description' => $description,
				),
				array( 'id' => $id, 'camp_id' => $camp_id )
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'camp_id' => $camp_id,
					'session_name' => $session_name,
					'price' => $price,
					'start_date' => $start_date,
					'end_date' => $end_date,
					'notes' => $notes,
					'description' => $description,
					'sort_order' => 0,
				)
			);
		}

		wp_send_json_success();
	}

	public function ajax_get_session() {
		check_ajax_referer( 'camp_ajax_nonce', 'nonce' );
		
		global $wpdb;
		$id = intval( $_POST['id'] );
		
		$session = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM " . \CreativeDBS\CampMgmt\DB::table_sessions() . " WHERE id = %d",
			$id
		), ARRAY_A );

		if ( $session ) {
			wp_send_json_success( $session );
		} else {
			wp_send_json_error( array( 'message' => 'Not found' ) );
		}
	}

	public function ajax_delete_session() {
		check_ajax_referer( 'camp_ajax_nonce', 'nonce' );
		
		global $wpdb;
		$id = intval( $_POST['id'] );
		
		$wpdb->delete( \CreativeDBS\CampMgmt\DB::table_sessions(), array( 'id' => $id ) );
		
		wp_send_json_success();
	}

	/**
	 * Queue notification for daily batch email instead of sending immediately
	 */
	private function send_admin_notification_camp_updated( $camp_id, $camp_name, $photos_uploaded, $logo_uploaded ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'camp_notification_queue';
		
		// Log notification attempt
		error_log( 'CDBS Notification: Queuing notification for camp ' . $camp_id . ' - ' . $camp_name );
		
		// Add notification to queue
		$result = $wpdb->insert(
			$table_name,
			[
				'camp_id' => $camp_id,
				'camp_name' => $camp_name,
				'update_type' => 'profile_update',
				'update_time' => current_time( 'mysql' ),
				'photos_uploaded' => $photos_uploaded ? 1 : 0,
				'logo_uploaded' => $logo_uploaded ? 1 : 0,
			],
			[
				'%d', '%s', '%s', '%s', '%d', '%d'
			]
		);
		
		if ( $result === false ) {
			error_log( 'CDBS Notification ERROR: Failed to insert notification. ' . $wpdb->last_error );
		} else {
			error_log( 'CDBS Notification SUCCESS: Notification queued with ID ' . $wpdb->insert_id );
		}
	}

	/**
	 * Get admin update notification email HTML template
	 */
	private function get_admin_update_email_template( $camp_id, $camp_name, $photos_uploaded, $logo_uploaded ) {
		$edit_url = admin_url( 'admin.php?page=creativedbs-camp-mgmt&action=edit&camp=' . $camp_id );
		$edit_url = wp_nonce_url( $edit_url, 'edit_camp' );
		
		$changes = [];
		if ( $photos_uploaded ) {
			$changes[] = 'New photos uploaded';
		}
		if ( $logo_uploaded ) {
			$changes[] = 'Logo updated';
		}
		$changes[] = 'Profile information updated';
		
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Camp Profile Updated</title>
		</head>
		<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f4f4;">
				<tr>
					<td align="center" style="padding: 20px 10px;">
						<div style="max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
							<div style="background: linear-gradient(135deg, #497C5E 0%, #679B7C 100%); color: #ffffff; padding: 30px 20px; text-align: center;">
								<h1 style="margin: 0; font-size: 28px; font-weight: bold;">📝 Camp Profile Updated</h1>
							</div>
							<div style="padding: 30px 20px;">
								<h2 style="color: #497C5E; margin-top: 0; font-size: 22px;">Camp Information Has Been Updated</h2>
								<p style="margin: 15px 0; font-size: 16px;">Hello Administrator,</p>
								<p style="margin: 15px 0; font-size: 16px;"><strong><?php echo esc_html( $camp_name ); ?></strong> has updated their camp profile.</p>
								
								<div style="background: #f8f9fa; border-left: 4px solid #497C5E; padding: 15px; margin: 20px 0; border-radius: 4px;">
									<p style="margin: 5px 0; font-size: 16px;"><strong style="color: #497C5E;">Camp Name:</strong> <?php echo esc_html( $camp_name ); ?></p>
									<p style="margin: 5px 0; font-size: 16px;"><strong style="color: #497C5E;">Camp ID:</strong> #<?php echo esc_html( $camp_id ); ?></p>
									<p style="margin: 5px 0; font-size: 16px;"><strong style="color: #497C5E;">Updated:</strong> <?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ); ?></p>
								</div>
								
								<p style="margin: 15px 0; font-size: 16px;"><strong>Changes made:</strong></p>
								<ul style="margin: 10px 0; padding-left: 25px;">
									<?php foreach ( $changes as $change ) : ?>
										<li style="margin: 5px 0; font-size: 15px;"><?php echo esc_html( $change ); ?></li>
									<?php endforeach; ?>
								</ul>
								
								<p style="margin: 15px 0; font-size: 16px;">You can review the updated camp profile in the WordPress admin:</p>
								
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
 * Ensure URL has a protocol (add https:// if missing)
 */
private function ensure_url_protocol( $url ) {
	$url = trim( $url );
	if ( empty( $url ) ) {
		return '';
	}
	// Add https:// if no protocol is specified
	if ( ! preg_match( '/^https?:\/\//i', $url ) ) {
		$url = 'https://' . $url;
	}
	return esc_url_raw( $url );
}

}
