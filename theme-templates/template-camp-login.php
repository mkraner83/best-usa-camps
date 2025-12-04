<?php
/**
 * Template Name: Camp Login
 * Description: Custom login page template for camp directors
 */

get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<article class="camp-login-page">
			<div class="entry-content">
				
				<style>
					.camp-login-container {
						max-width: 500px;
						margin: 60px auto;
						padding: 20px;
					}
					
					.camp-login-form {
						background: #ffffff;
						border: none;
						box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
						padding: 50px 40px;
						border-radius: 8px;
					}
					
					.camp-login-form h2 {
						text-align: center;
						color: #497C5E;
						margin-top: 0;
						margin-bottom: 10px;
						font-size: 32px;
						font-family: "Annie Use Your Telescope", sans-serif !important;
					}
					
					.camp-login-form .login-username,
					.camp-login-form .login-password,
					.camp-login-form .login-remember {
						margin-bottom: 15px;
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
					
					.camp-login-form .login-remember label {
						font-weight: 400;
						color: #666666;
						font-size: 13px;
						display: inline;
					}
					
					.camp-login-form input[type="checkbox"] {
						margin-right: 8px;
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
							margin: 30px auto;
							padding: 10px;
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
					<?php
					// Check if user is already logged in
					if ( is_user_logged_in() ) {
						echo '<div class="camp-login-message">';
						echo '<p>You are already logged in. <a href="' . esc_url( home_url( '/user-dashboard/' ) ) . '">Go to your dashboard</a></p>';
						echo '</div>';
					} else {
						// Show login form with custom styling
						?>
						<div class="camp-login-form">
							<h2>Camp Login</h2>
							
							<?php
							// Display login errors
							if ( isset( $_GET['login'] ) && $_GET['login'] === 'failed' ) {
								echo '<div class="camp-login-error">';
								echo '<p><strong>Error:</strong> Invalid username or password.</p>';
								echo '</div>';
							}
							
							// Display logged out message
							if ( isset( $_GET['loggedout'] ) && $_GET['loggedout'] === 'true' ) {
								echo '<div class="camp-login-message">';
								echo '<p>You have been successfully logged out.</p>';
								echo '</div>';
							}
							
							// Display WordPress login form
							$args = array(
								'echo'           => true,
								'redirect'       => home_url( '/user-dashboard/' ),
								'form_id'        => 'camp-loginform',
								'label_username' => 'Username or Email Address',
								'label_password' => 'Password',
								'label_remember' => 'Remember Me',
								'label_log_in'   => 'LOG IN',
								'remember'       => true,
								'value_remember' => false,
							);
							wp_login_form( $args );
							?>
							
							<div class="camp-login-links">
								<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Lost your password?</a>
							</div>
						</div>
						<?php
					}
					?>
				</div>
				
			</div>
		</article>
	</main>
</div>

<?php get_footer(); ?>
