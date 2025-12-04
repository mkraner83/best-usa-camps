<?php
/**
 * Template Name: Camp Reset Password
 * Description: Custom reset password page template for camp directors
 */

get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<article class="camp-resetpass-page">
			<div class="entry-content">
				
				<style>
					.camp-login-container {
						max-width: 500px;
						margin: 0 auto;
						padding: 50px 20px;
					}
					.camp-login-form {
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
					.camp-login-form input[type="password"] {
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
					.camp-login-form input[type="password"]:focus {
						border: 1px solid #497C5E;
						outline: none;
						box-shadow: 0 0 0 2px rgba(73, 124, 94, 0.2);
					}
					
					.camp-login-form input[type="submit"],
					.camp-login-form .button {
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
						text-align: center;
						display: inline-block;
						text-decoration: none;
					}
					
					.camp-login-form input[type="submit"]:hover,
					.camp-login-form .button:hover {
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
					
					.password-strength-meter {
						margin: 10px 0;
					}
					
					#pass-strength-result {
						background: #f5f5f5;
						border: none;
						border-radius: 4px;
						padding: 8px 12px;
						font-size: 13px;
						margin-bottom: 15px;
					}
					
					.pw-weak {
						display: none;
					}
					
					@media screen and (max-width: 768px) {
						.camp-login-container {
							margin: 0 auto;
							padding: 50px 10px;
						}
						
						.camp-login-form {
							padding: 35px 25px;
						}
						
						.camp-login-form h2 {
							font-size: 26px;
						}
					}
				</style>
				
				<div class="camp-login-container">
					<div class="camp-login-form">
						<h2>Generate Password</h2>
						
						<?php
						// Get the reset key and login from URL
						$rp_key   = isset( $_GET['key'] ) ? $_GET['key'] : '';
						$rp_login = isset( $_GET['login'] ) ? $_GET['login'] : '';
						
						// Handle form submission
						if ( isset( $_POST['pass1'] ) && isset( $_POST['rp_key'] ) ) {
							$user = check_password_reset_key( $_POST['rp_key'], $_POST['rp_login'] );
							
							if ( ! is_wp_error( $user ) && ! empty( $_POST['pass1'] ) ) {
								reset_password( $user, $_POST['pass1'] );
								echo '<div class="camp-login-message">';
								echo '<p><strong>Success!</strong> Your password has been changed.</p>';
								echo '</div>';
								echo '<div class="camp-login-links" style="text-align: center;">';
								echo '<a href="' . esc_url( home_url( '/camp-login/' ) ) . '" class="button">Go to Login</a>';
								echo '</div>';
							} else {
								echo '<div class="camp-login-error">';
								echo '<p>Error: Unable to reset password. Please request a new link.</p>';
								echo '</div>';
								echo '<div class="camp-login-links">';
								echo '<a href="' . esc_url( home_url( '/camp-lost-password/' ) ) . '">← Request New Reset Link</a>';
								echo '</div>';
							}
						} elseif ( empty( $rp_key ) || empty( $rp_login ) ) {
							// No valid reset link
							echo '<div class="camp-login-error">';
							echo '<p>Invalid password reset link. Please request a new one.</p>';
							echo '</div>';
							echo '<div class="camp-login-links">';
							echo '<a href="' . esc_url( home_url( '/camp-lost-password/' ) ) . '">← Request New Reset Link</a>';
							echo '</div>';
						} else {
							// Verify the key is valid before showing form
							$user = check_password_reset_key( $rp_key, $rp_login );
							
							if ( is_wp_error( $user ) ) {
								echo '<div class="camp-login-error">';
								echo '<p>Error: Your password reset link appears to be invalid or has expired. Please request a new link.</p>';
								echo '</div>';
								echo '<div class="camp-login-links">';
								echo '<a href="' . esc_url( home_url( '/camp-lost-password/' ) ) . '">← Request New Reset Link</a>';
								echo '</div>';
							} else {
								// Show the password reset form
								?>
								<p>Enter your new password below.</p>
								
								<form name="resetpassform" id="resetpassform" method="post" autocomplete="off">
									<input type="hidden" name="rp_login" value="<?php echo esc_attr( $rp_login ); ?>" autocomplete="off" />
									<input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>" />
									
									<p class="user-pass1-wrap">
										<label for="pass1">New password</label>
										<input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" />
									</p>
									
									<p class="user-pass2-wrap">
										<label for="pass2">Confirm new password</label>
										<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" />
									</p>
									
									<p class="description indicator-hint">Hint: The password should be at least twelve characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ &amp; ).</p>
									
									<p class="submit">
										<input type="submit" name="wp-submit" id="wp-submit" value="RESET PASSWORD" />
									</p>
								</form>
								
								<div class="camp-login-links">
									<a href="<?php echo esc_url( home_url( '/camp-login/' ) ); ?>">← Back to Login</a>
								</div>
								<?php
							}
						}
						?>
					</div>
				</div>
				
			</div>
		</article>
	</main>
</div>

<?php get_footer(); ?>
