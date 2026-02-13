<?php
/**
 * Contact Form
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt\PublicArea;

defined( 'ABSPATH' ) || exit;

class Contact_Form {
	public function __construct() {
		add_shortcode( 'contact_form', [ $this, 'render_form' ] );
		add_action( 'init', [ $this, 'handle_submission' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}

	/**
	 * Enqueue contact form styles
	 */
	public function enqueue_styles() {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'contact_form' ) ) {
			wp_enqueue_style(
				'camp-contact-form',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/camp-contact-form.css',
				[],
				CDBS_CAMP_VERSION
			);
			
			wp_enqueue_script(
				'camp-contact-form-logic',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/camp-contact-form.js',
				['jquery'],
				CDBS_CAMP_VERSION,
				true
			);
		}
	}

	/**
	 * Handle form submission
	 */
	public function handle_submission() {
		if ( ! isset( $_POST['contact_form_nonce'] ) || ! wp_verify_nonce( $_POST['contact_form_nonce'], 'submit_contact_form' ) ) {
			return;
		}

		// Remove slashes from POST data to prevent double-escaping
		$post_data = wp_unslash( $_POST );

		// Simple math captcha verification
		$captcha_answer = isset( $post_data['captcha_answer'] ) ? intval( $post_data['captcha_answer'] ) : 0;
		$captcha_expected = isset( $post_data['captcha_expected'] ) ? intval( $post_data['captcha_expected'] ) : 0;
		
		if ( $captcha_answer !== $captcha_expected ) {
			wp_redirect( add_query_arg( 'contact_error', 'captcha', wp_get_referer() ) );
			exit;
		}

		// Sanitize and validate
		$first_name = sanitize_text_field( $post_data['first_name'] ?? '' );
		$last_name = sanitize_text_field( $post_data['last_name'] ?? '' );
		$email = sanitize_email( $post_data['email'] ?? '' );
		$email_confirm = sanitize_email( $post_data['email_confirm'] ?? '' );
		$phone = sanitize_text_field( $post_data['phone'] ?? '' );
		$message = sanitize_textarea_field( $post_data['message'] ?? '' );

		// Validation
		$errors = [];
		
		if ( empty( $first_name ) ) {
			$errors[] = 'first_name';
		}
		if ( empty( $last_name ) ) {
			$errors[] = 'last_name';
		}
		if ( empty( $email ) || ! is_email( $email ) ) {
			$errors[] = 'email';
		}
		if ( $email !== $email_confirm ) {
			$errors[] = 'email_mismatch';
		}
		if ( empty( $message ) ) {
			$errors[] = 'message';
		}
		
		// Check word count (max 200 words)
		$word_count = str_word_count( $message );
		if ( $word_count > 200 ) {
			$errors[] = 'message_too_long';
		}

		if ( ! empty( $errors ) ) {
			// Save failed submission
			\CreativeDBS\CampMgmt\Admin\Contact_Submissions::save_submission( 
				$first_name, 
				$last_name, 
				$email, 
				$phone, 
				$message, 
				'validation_failed',
				'Validation errors: ' . implode( ', ', $errors )
			);
			
			wp_redirect( add_query_arg( 'contact_error', implode( ',', $errors ), wp_get_referer() ) );
			exit;
		}

		// Try to send emails and track success/failure
		$email_status = 'success';
		$error_message = null;
		
		try {
			$admin_result = $this->send_admin_notification( $first_name, $last_name, $email, $phone, $message );
			$user_result = $this->send_user_confirmation( $first_name, $last_name, $email );
			
			if ( ! $admin_result && ! $user_result ) {
				$email_status = 'email_failed';
				$error_message = 'Failed to send both admin and user confirmation emails';
			} elseif ( ! $admin_result ) {
				$email_status = 'partial_success';
				$error_message = 'Failed to send admin notification email';
			} elseif ( ! $user_result ) {
				$email_status = 'partial_success';
				$error_message = 'Failed to send user confirmation email';
			}
		} catch ( Exception $e ) {
			$email_status = 'email_failed';
			$error_message = 'Email error: ' . $e->getMessage();
		}
		
		// Save to database with status
		\CreativeDBS\CampMgmt\Admin\Contact_Submissions::save_submission( 
			$first_name, 
			$last_name, 
			$email, 
			$phone, 
			$message,
			$email_status,
			$error_message
		);

		// Redirect with success
		wp_redirect( add_query_arg( 'contact_success', '1', wp_get_referer() ) );
		exit;
	}

	/**
	 * Send admin notification email
	 */
	private function send_admin_notification( $first_name, $last_name, $email, $phone, $message ) {
		$admin_emails = get_option( 'cdbs_contact_admin_emails', get_option( 'admin_email' ) );
		
		// Handle multiple emails (comma-separated)
		$admin_emails = array_map( 'trim', explode( ',', $admin_emails ) );
		$admin_emails = array_filter( $admin_emails, 'is_email' );
		
		if ( empty( $admin_emails ) ) {
			$admin_emails = [ get_option( 'admin_email' ) ];
		}

		$subject = 'New Contact Form Submission - Best USA Camps';
		
		ob_start();
		include plugin_dir_path( CREATIVE_DBS_CAMPMGMT_FILE ) . 'includes/email-contact-admin-template.php';
		$body = ob_get_clean();

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: Best USA Camps <noreply@bestusacamps.com>',
			'Reply-To: ' . $first_name . ' ' . $last_name . ' <' . $email . '>',
		];

		$success = true;
		foreach ( $admin_emails as $admin_email ) {
			$result = wp_mail( $admin_email, $subject, $body, $headers );
			if ( ! $result ) {
				$success = false;
			}
		}
		
		return $success;
	}

	/**
	 * Send user confirmation email
	 */
	private function send_user_confirmation( $first_name, $last_name, $email ) {
		$subject = 'Thanks for contacting Best USA Camps!';
		
		ob_start();
		include plugin_dir_path( CREATIVE_DBS_CAMPMGMT_FILE ) . 'includes/email-contact-user-template.php';
		$body = ob_get_clean();

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: Best USA Camps <noreply@bestusacamps.com>',
		];

		return wp_mail( $email, $subject, $body, $headers );
	}

	/**
	 * Render the contact form
	 */
	public function render_form() {
		// Generate simple math captcha
		$num1 = rand( 1, 10 );
		$num2 = rand( 1, 10 );
		$captcha_expected = $num1 + $num2;

		// Get error/success messages
		$has_success = isset( $_GET['contact_success'] );
		$errors = isset( $_GET['contact_error'] ) ? explode( ',', $_GET['contact_error'] ) : [];

		ob_start();
		?>
		<div class="camp-contact-form-wrapper">
			
			<?php if ( $has_success ) : ?>
				<div class="contact-success-message">
					<div class="success-icon">✓</div>
					<h3>Thank You!</h3>
					<p>Your message has been sent successfully. We'll get back to you soon.</p>
				</div>
			<?php else : ?>
				
				<form method="POST" action="" class="camp-contact-form" id="contactForm">
					<?php wp_nonce_field( 'submit_contact_form', 'contact_form_nonce' ); ?>
					
					<!-- Name Fields -->
					<div class="form-row">
						<div class="form-group half">
							<label for="first_name">First Name <span class="required">*</span></label>
							<input 
								type="text" 
								id="first_name" 
								name="first_name" 
								required
								value="<?php echo esc_attr( $_POST['first_name'] ?? '' ); ?>"
								class="<?php echo in_array( 'first_name', $errors ) ? 'error' : ''; ?>"
							>
							<?php if ( in_array( 'first_name', $errors ) ) : ?>
								<span class="error-message">First name is required</span>
							<?php endif; ?>
						</div>
						
						<div class="form-group half">
							<label for="last_name">Last Name <span class="required">*</span></label>
							<input 
								type="text" 
								id="last_name" 
								name="last_name" 
								required
								value="<?php echo esc_attr( $_POST['last_name'] ?? '' ); ?>"
								class="<?php echo in_array( 'last_name', $errors ) ? 'error' : ''; ?>"
							>
							<?php if ( in_array( 'last_name', $errors ) ) : ?>
								<span class="error-message">Last name is required</span>
							<?php endif; ?>
						</div>
					</div>

					<!-- Email Fields -->
					<div class="form-row">
						<div class="form-group half">
							<label for="email">Email Address <span class="required">*</span></label>
							<input 
								type="email" 
								id="email" 
								name="email" 
								required
								value="<?php echo esc_attr( $_POST['email'] ?? '' ); ?>"
								class="<?php echo in_array( 'email', $errors ) || in_array( 'email_mismatch', $errors ) ? 'error' : ''; ?>"
							>
							<?php if ( in_array( 'email', $errors ) ) : ?>
								<span class="error-message">Valid email is required</span>
							<?php endif; ?>
						</div>
						
						<div class="form-group half">
							<label for="email_confirm">Confirm Email <span class="required">*</span></label>
							<input 
								type="email" 
								id="email_confirm" 
								name="email_confirm" 
								required
								value="<?php echo esc_attr( $_POST['email_confirm'] ?? '' ); ?>"
								class="<?php echo in_array( 'email_mismatch', $errors ) ? 'error' : ''; ?>"
							>
							<?php if ( in_array( 'email_mismatch', $errors ) ) : ?>
								<span class="error-message">Email addresses must match</span>
							<?php endif; ?>
						</div>
					</div>

					<!-- Phone -->
					<div class="form-group">
						<label for="phone">Phone Number</label>
						<input 
							type="tel" 
							id="phone" 
							name="phone"
							value="<?php echo esc_attr( $_POST['phone'] ?? '' ); ?>"
						>
					</div>

					<!-- Message -->
					<div class="form-group">
						<label for="message">Your Message <span class="required">*</span></label>
						<textarea 
							id="message" 
							name="message" 
							rows="6" 
							required
						class="<?php echo in_array( 'message', $errors ) || in_array( 'message_too_long', $errors ) ? 'error' : ''; ?>"
					><?php echo esc_textarea( $_POST['message'] ?? '' ); ?></textarea>
					<small style="color: #666;">Maximum 200 words</small>
					<?php if ( in_array( 'message', $errors ) ) : ?>
						<span class="error-message">Message is required</span>
					<?php elseif ( in_array( 'message_too_long', $errors ) ) : ?>
						<span class="error-message">Message is too long. Please limit to 200 words.</span>
						<?php endif; ?>
					</div>

					<!-- Simple Math Captcha -->
					<div class="form-group captcha-group">
						<label for="captcha_answer">
							Security Check: What is <?php echo $num1; ?> + <?php echo $num2; ?>? <span class="required">*</span>
						</label>
						<input 
							type="number" 
							id="captcha_answer" 
							name="captcha_answer" 
							required
							class="<?php echo in_array( 'captcha', $errors ) ? 'error' : ''; ?>"
						>
						<input type="hidden" name="captcha_expected" value="<?php echo $captcha_expected; ?>">
						<?php if ( in_array( 'captcha', $errors ) ) : ?>
							<span class="error-message">Incorrect answer. Please try again.</span>
						<?php endif; ?>
					</div>

					<!-- Submit Button -->
					<div class="form-actions">
						<button type="submit" class="contact-submit-btn">
							Send Message
						</button>
					</div>
				</form>

			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}

