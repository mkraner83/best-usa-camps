<?php
/**
 * Shortcodes Guide Admin Page.
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt\Admin;

defined( 'ABSPATH' ) || exit;

class Shortcodes_Guide {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}

	/**
	 * Add submenu page
	 */
	public function add_menu_page() {
		add_submenu_page(
			'creativedbs-camp-mgmt',
			'Shortcodes Guide',
			'📋 Shortcodes',
			'manage_options',
			'creativedbs-shortcodes-guide',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Enqueue admin styles
	 */
	public function enqueue_styles( $hook ) {
		if ( 'camp-management_page_creativedbs-shortcodes-guide' !== $hook ) {
			return;
		}

		wp_add_inline_style( 'wp-admin', '
			.shortcodes-guide {
				max-width: 1200px;
				margin: 20px 0;
			}
			.shortcode-section {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
				padding: 20px;
				margin-bottom: 20px;
			}
			.shortcode-section h2 {
				margin-top: 0;
				color: #1d2327;
				font-size: 18px;
				border-bottom: 2px solid #2271b1;
				padding-bottom: 10px;
			}
			.shortcode-item {
				background: #f6f7f7;
				border-left: 4px solid #2271b1;
				padding: 15px;
				margin: 15px 0;
			}
			.shortcode-code {
				background: #1d2327;
				color: #50e3c2;
				padding: 8px 12px;
				border-radius: 3px;
				font-family: "Courier New", monospace;
				font-size: 14px;
				display: inline-block;
				margin: 5px 0;
				cursor: pointer;
			}
			.shortcode-code:hover {
				background: #2c3338;
			}
			.shortcode-desc {
				color: #50575e;
				margin: 8px 0;
			}
			.shortcode-params {
				background: #fff;
				padding: 10px;
				margin-top: 10px;
				border-radius: 3px;
				font-size: 13px;
			}
			.shortcode-params strong {
				color: #2271b1;
			}
			.copy-notice {
				color: #00a32a;
				font-size: 12px;
				margin-left: 10px;
				display: none;
			}
		' );
	}

	/**
	 * Render admin page
	 */
	public function render_page() {
		?>
		<div class="wrap">
			<h1>📋 Camp Management Shortcodes Guide</h1>
			<p class="description">Click on any shortcode to copy it to clipboard. Each section also lists the recommended page URL.</p>

			<div class="shortcodes-guide">

				<!-- ============================================================ -->
				<!-- LOGIN / PASSWORD PAGES -->
				<!-- ============================================================ -->
				<div class="shortcode-section" style="border-color:#2271b1;">
					<h2>🔐 Login &amp; Password Pages</h2>
					<p class="shortcode-desc" style="margin-top:0;">One login form for <strong>all roles</strong> (admin, camp director, parent). After login each role is redirected automatically to its own dashboard.</p>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_login_page]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Unified login form for all user roles. Redirects after login: <strong>administrator</strong> → <code>/wp-admin/</code> · <strong>camp director</strong> → <code>/user-dashboard/</code> · <strong>parent</strong> → <code>/parent-dashboard/</code>.</p>
						<div class="shortcode-params">
							<strong>Recommended page URL:</strong> <code>/camp-login/</code>
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_lost_password_page]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Forgot-password form. Sends WordPress's standard reset email with a link to the Set Password page.</p>
						<div class="shortcode-params">
							<strong>Recommended page URL:</strong> <code>/camp-lost-password/</code>
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_set_password_page]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Set / reset password form. Reached via the link in the reset email. After a successful password set the user is automatically logged in and redirected to their dashboard.</p>
						<div class="shortcode-params">
							<strong>Recommended page URL:</strong> <code>/set-password/</code>
						</div>
					</div>
				</div>

				<!-- ============================================================ -->
				<!-- CAMP DIRECTOR SECTION -->
				<!-- ============================================================ -->
				<div class="shortcode-section" style="border-color:#497C5E;">
					<h2>🏕️ Camp Director Pages</h2>
					<p class="shortcode-desc" style="margin-top:0;">Pages used exclusively by users with the <strong>camp</strong> role.</p>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_signup_form]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">New camp registration/signup form. Creates a WordPress account with the <code>camp</code> role and a camp profile record. Used by new camp directors.</p>
						<div class="shortcode-params">
							<strong>Recommended page URL:</strong> <code>/get-listed-on-best-usa-summer-camps/</code>
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_dashboard]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Full camp director dashboard. Requires a logged-in user with the <code>camp</code> role. Displays all editable sections: Basic Info, Contact, Description, Types, Weeks, Activities, Accommodations, FAQs, Sessions, Media, etc.</p>
						<div class="shortcode-params">
							<strong>Recommended page URL:</strong> <code>/user-dashboard/</code>
						</div>
					</div>
				</div>

				<!-- ============================================================ -->
				<!-- PARENT SECTION -->
				<!-- ============================================================ -->
				<div class="shortcode-section" style="border-color:#c0392b;">
					<h2>👨‍👩‍👧 Parent Pages</h2>
					<p class="shortcode-desc" style="margin-top:0;">Pages used exclusively by users with the <strong>parent</strong> role.</p>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[parent_registration_form]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Full parent + camper registration form. Creates a WordPress account with the <code>parent</code> role on first submission and sends a branded welcome email with a "Set Your Password" link. Logged-in parents can add additional camper submissions.</p>
						<div class="shortcode-params">
							<strong>Recommended page URL:</strong> <code>/find-the-perfect-summer-camp/</code>
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[parent_dashboard]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Frontend parent dashboard. Requires a logged-in user with the <code>parent</code> role. Three tabs: <strong>My Submissions</strong> (read-only camper request cards), <strong>My Favourites</strong> (saved camps with remove button), <strong>Messages</strong>. Non-parent users are redirected away automatically.</p>
						<div class="shortcode-params">
							<strong>Recommended page URL:</strong> <code>/parent-dashboard/</code>
						</div>
					</div>
				</div>

				<!-- ============================================================ -->
				<!-- FEATURED CAMPS -->
				<!-- ============================================================ -->
				<div class="shortcode-section">
					<h2>🌟 Featured Camps Shortcodes</h2>
					
					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[featured_camps]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Display featured camps you've selected in the admin area.</p>
						<div class="shortcode-params">
							<strong>Parameters:</strong> <code>limit</code> (default: 100)
							<br><strong>Example:</strong> <code>[featured_camps limit="6"]</code>
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[best_day_camps]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Display featured day camps.</p>
						<div class="shortcode-params">
							<strong>Parameters:</strong> <code>limit</code> (default: 100)
							<br><strong>Example:</strong> <code>[best_day_camps limit="8"]</code>
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[best_overnight_camps]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Display featured overnight camps.</p>
						<div class="shortcode-params">
							<strong>Parameters:</strong> <code>limit</code> (default: 100)
							<br><strong>Example:</strong> <code>[best_overnight_camps limit="10"]</code>
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[best_girls_camps]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Display featured all-girls camps.</p>
						<div class="shortcode-params">
							<strong>Parameters:</strong> <code>limit</code> (default: 100)
							<br><strong>Example:</strong> <code>[best_girls_camps limit="6"]</code>
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[best_boys_camps]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Display featured all-boys camps.</p>
						<div class="shortcode-params">
							<strong>Parameters:</strong> <code>limit</code> (default: 100)
							<br><strong>Example:</strong> <code>[best_boys_camps limit="6"]</code>
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[latest_camps]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Display the most recently added approved camps.</p>
						<div class="shortcode-params">
							<strong>Parameters:</strong> <code>limit</code> (default: 3)
							<br><strong>Example:</strong> <code>[latest_camps limit="5"]</code>
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[single_camp id="123"]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Display a single specific camp card by ID or slug.</p>
						<div class="shortcode-params">
							<strong>Parameters:</strong> <code>id</code> (camp ID) OR <code>slug</code> (camp slug)
							<br><strong>Example:</strong> <code>[single_camp id="45"]</code>
							<br><strong>Example:</strong> <code>[single_camp slug="camp-laney"]</code>
						</div>
					</div>
				</div>

				<!-- Camp Pages Section -->
				<div class="shortcode-section">
					<h2>📄 Individual Camp Page Shortcodes</h2>
					<p class="shortcode-desc" style="margin-top: 0;">Use these on individual camp pages to display camp information.</p>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_logo]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Display camp logo with customizable size.</p>
						<div class="shortcode-params">
							<strong>Parameters:</strong> <code>size</code> (small, medium, large, full - default: medium), <code>class</code>
							<br><strong>Example:</strong> <code>[camp_logo size="large" class="my-logo"]</code>
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_name]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Display camp name as SEO-optimized H1 heading.</p>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_name_text]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Plain text camp name (for SEO titles/meta).</p>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_subtitle]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Auto-generated subtitle from camp types and location.</p>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_rating]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Display star rating (0-5 stars).</p>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_contact_bar]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Horizontal contact bar with Google Maps links.</p>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_contact_info]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Vertical sidebar contact display.</p>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_description]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Camp description (220-word limit).</p>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_activities]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Activity tags in a grid.</p>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_types_weeks]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Two columns: Camp Types | Available Weeks.</p>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_accommodations]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Accommodations with dynamic columns (90-word limit per item).</p>
						<div class="shortcode-params">
							<strong>Parameters:</strong> <code>layout</code> (list, cards, grid), <code>columns</code> (1-3)
							<br><strong>Example:</strong> <code>[camp_accommodations layout="grid" columns="3"]</code>
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_faqs]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">FAQ section with collapsible answers.</p>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_sessions]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Sessions & pricing information.</p>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_additional_info]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Additional camp information.</p>
					</div>
				<div class="shortcode-item">
					<code class="shortcode-code" onclick="copyShortcode(this)">[camp_social_media]</code>
					<span class="copy-notice">✓ Copied!</span>
					<p class="shortcode-desc">Display social media links as styled badges (Facebook, Twitter, Instagram, YouTube, LinkedIn, TikTok).</p>
					<div class="shortcode-params">
						<strong>Parameters:</strong> <code>class</code>
						<br><strong>Example:</strong> <code>[camp_social_media class="my-social"]</code>
						<br><strong>Note:</strong> Automatically hides if no social links are set.
					</div>
				</div>

				<div class="shortcode-item">
					<code class="shortcode-code" onclick="copyShortcode(this)">[camp_video]</code>
					<span class="copy-notice">✓ Copied!</span>
					<p class="shortcode-desc">Display camp video as responsive embed (YouTube/Vimeo). No autoplay.</p>
					<div class="shortcode-params">
						<strong>Parameters:</strong> <code>aspect_ratio</code> (16-9, 4-3 - default: 16-9), <code>class</code>
						<br><strong>Example:</strong> <code>[camp_video aspect_ratio="16-9"]</code>
						<br><strong>Note:</strong> Automatically hides if no video URL is set.
					</div>
				</div>				</div>

				<!-- Lists Section -->
				<div class="shortcode-section">
					<h2>📝 Camp Lists Shortcodes</h2>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camps_list]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Display a searchable/filterable list of all approved camps.</p>
						<div class="shortcode-params">
							<strong>Parameters:</strong> <code>columns</code> (2, 3, 4 - default: 3), <code>limit</code>, <code>state</code>, <code>type</code>
							<br><strong>Examples:</strong> 
							<br><code>[camps_list columns="4"]</code>
							<br><code>[camps_list state="NY" limit="12"]</code>
						</div>
					</div>
				</div>

				<!-- ============================================================ -->
				<!-- INDIVIDUAL CAMP PAGE SHORTCODES -->
				<!-- ============================================================ -->
				<div class="shortcode-section">
					<h2>🌐 General / Utility Shortcodes</h2>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[cdbs_login_bar]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Login status bar for the site header/navigation. <strong>Logged-in parents &amp; camp directors</strong> see their name, role pill, "My Dashboard" link, and "Log Out". <strong>Guests</strong> see "Log In", "Register (Camp)", and "Register (Parent)" links. Invisible to admins. Styled as a compact white-bordered pill — place it in your Elementor header.</p>
						<div class="shortcode-params">
							<strong>Recommended placement:</strong> Header / top navigation bar<br>
							<strong>Roles shown:</strong> <code>camp</code> → links to <code>/user-dashboard/</code> · <code>parent</code> → links to <code>/parent-dashboard/</code>
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[contact_form]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">General site contact form with name, email, phone, message, and captcha. Sends emails to the admin and a confirmation to the user.</p>
						<div class="shortcode-params">
							<strong>Recommended page URL:</strong> any page (e.g. <code>/contact/</code>)
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_favourite_button camp_id="123"]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Heart toggle button on individual camp pages. Logged-in parents can save/remove camps from their favourites. Guests see a "Log in to save" prompt. Non-parent roles see nothing.</p>
						<div class="shortcode-params">
							<strong>Parameters:</strong> <code>camp_id</code> (required)
							<br><strong>Example:</strong> <code>[camp_favourite_button camp_id="45"]</code>
							<br><strong>Find camp ID:</strong> Camp Management → All Camps
						</div>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_contact_form camp_id="123"]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Contact/message form on individual camp pages. Saves the message to the messages table and notifies the admin. Works for both logged-in users and guests.</p>
						<div class="shortcode-params">
							<strong>Parameters:</strong> <code>camp_id</code> (required)
							<br><strong>Example:</strong> <code>[camp_contact_form camp_id="45"]</code>
							<br><strong>View messages:</strong> Camp Management → Parent Messages
						</div>
					</div>
				</div>

			</div>
		</div>

		<script>
		function copyShortcode(element) {
			const text = element.textContent;
			const notice = element.nextElementSibling;
			
			// Copy to clipboard
			navigator.clipboard.writeText(text).then(function() {
				// Show success message
				notice.style.display = 'inline';
				setTimeout(function() {
					notice.style.display = 'none';
				}, 2000);
			});
		}
		</script>
		<?php
	}
}
