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
			<p class="description">Click on any shortcode to copy it to clipboard.</p>

			<div class="shortcodes-guide">

				<!-- Featured Camps Section -->
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
				</div>

				<!-- Forms & Search Section -->
				<div class="shortcode-section">
					<h2>🔍 Forms & Search Shortcodes</h2>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_search]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Camp search form with filters and results.</p>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_signup_form]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Camp registration/signup form for new camps.</p>
					</div>

					<div class="shortcode-item">
						<code class="shortcode-code" onclick="copyShortcode(this)">[camp_dashboard]</code>
						<span class="copy-notice">✓ Copied!</span>
						<p class="shortcode-desc">Camp owner dashboard for managing their camp.</p>
					</div>
				</div>

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
