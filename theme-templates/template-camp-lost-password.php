<?php
/**
 * Template Name: Camp Lost Password
 * Description: Custom lost password page template for camp directors
 */

get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<article class="camp-lostpassword-page">
			<div class="entry-content">
				
				<style>
				.camp-login-container {
					max-width: 500px;
					margin: 0 auto;
					padding: 50px 20px;
				}					.camp-login-form {
						background: #ffffff;
						border: none;
						box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
						padding: 50px 40px;
						border-radius: 8px;
						margin-top: 110px;
					}
					
					.camp-login-form h2 {
						text-align: center;
						color: #497C5E;
						margin-top: 0;
						margin-bottom: 10px;
						font-size: 32px;
						font-family: "Annie Use Your Telescope", sans-serif !important;
					}
					
					.camp-login-form p {
						text-align: center;
						color: #666666;
						margin-bottom: 20px;
					}
					
					.camp-login-form label {
						font-size: 14px;
						font-weight: 700;
						color: #333333;
						display: block;
						margin-bottom: 8px;
					}
					
					.camp-login-form input[type="text"],
					.camp-login-form input[type="email"] {
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
					
					.camp-login-form input[type="text"]:focus,
					.camp-login-form input[type="email"]:focus {
						border: 1px solid #497C5E;
						outline: none;
						box-shadow: 0 0 0 2px rgba(73, 124, 94, 0.2);
					}
					
					.camp-login-form input[type="submit"] {
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
					}
					
					.camp-login-form input[type="submit"]:hover {
						background: #3d6a4f;
					}
					
					.camp-login-links {
						text-align: center;
						margin-top: 25px;
					}
					
					.camp-login-links a {
						color: #999999;
						font-size: 13px;
						text-decoration: none;
						transition: color 0.2s ease;
					}
					
					.camp-login-links a:hover {
						color: #497C5E;
					}
					
					.camp-login-message {
						border-left: 4px solid #497C5E;
						background: #ffffff;
						color: #333333;
						padding: 12px;
						margin-bottom: 20px;
						box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);
					}
					
					.camp-login-error {
						border-left: 4px solid #dc3545;
						background: #f8d7da;
						color: #721c24;
						padding: 12px;
						margin-bottom: 20px;
					}
					
				@media screen and (max-width: 768px) {
					.camp-login-container {
						margin: 0 auto;
						padding: 50px 10px;
					}						.camp-login-form {
							padding: 35px 25px;
						}
						
						.camp-login-form h2 {
							font-size: 26px;
						}
					}
				</style>
				
				<div class="camp-login-container">
					<div class="camp-login-form">
						<h2>Reset Password</h2>
						
						<?php
						// Handle password reset request
						if ( isset( $_POST['user_login'] ) && isset( $_POST['reset-password-nonce'] ) ) {
							if ( wp_verify_nonce( $_POST['reset-password-nonce'], 'reset-password' ) ) {
								$user_login = sanitize_text_field( $_POST['user_login'] );
								$errors = retrieve_password( $user_login );
								
								if ( is_wp_error( $errors ) ) {
									echo '<div class="camp-login-error">';
									echo '<p>' . esc_html( $errors->get_error_message() ) . '</p>';
									echo '</div>';
								} else {
									echo '<div class="camp-login-message">';
									echo '<p>Check your email for the confirmation link, then visit the <a href="' . esc_url( home_url( '/camp-login/' ) ) . '">login page</a>.</p>';
									echo '</div>';
								}
							}
						}
						
						// Show success message if coming from email
						if ( isset( $_GET['checkemail'] ) && $_GET['checkemail'] === 'confirm' ) {
							echo '<div class="camp-login-message">';
							echo '<p>Check your email for the confirmation link.</p>';
							echo '</div>';
						}
						?>
						
						<p>Please enter your username or email address. You will receive an email message with instructions on how to reset your password.</p>
						
						<form name="lostpasswordform" id="lostpasswordform" action="<?php echo esc_url( site_url( 'administrator/?action=lostpassword', 'login_post' ) ); ?>" method="post">
							<p class="login-username">
								<label for="user_login">Username or Email Address</label>
								<input type="text" name="user_login" id="user_login" class="input" value="" size="20" />
							</p>
							
							<input type="hidden" name="redirect_to" value="<?php echo esc_attr( home_url( '/camp-lost-password/?checkemail=confirm' ) ); ?>" />
							
							<p class="submit">
								<input type="submit" name="wp-submit" id="wp-submit" value="GET NEW PASSWORD" />
							</p>
						</form>
						
						<div class="camp-login-links">
							<a href="<?php echo esc_url( home_url( '/camp-login/' ) ); ?>">← Back to Login</a>
						</div>
					</div>
				</div>
				
			</div>
		</article>
	</main>
</div>

<?php get_footer(); ?>
