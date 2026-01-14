<?php
/**
 * Frontend shortcodes for displaying camp data on public pages
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt\PublicArea;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Camp_Frontend {

	/**
	 * Register shortcodes
	 */
	public function __construct() {
		// Header shortcodes
		add_shortcode( 'camp_logo', [ $this, 'render_logo' ] );
		add_shortcode( 'camp_name', [ $this, 'render_name' ] );
		add_shortcode( 'camp_subtitle', [ $this, 'render_subtitle' ] );
		add_shortcode( 'camp_contact_bar', [ $this, 'render_contact_bar' ] );
		add_shortcode( 'camp_rating', [ $this, 'render_rating' ] );
		
		// Content shortcodes
		add_shortcode( 'camp_description', [ $this, 'render_description' ] );
		add_shortcode( 'camp_activities', [ $this, 'render_activities' ] );
		add_shortcode( 'camp_types_weeks', [ $this, 'render_types_weeks' ] );
		add_shortcode( 'camp_accommodations', [ $this, 'render_accommodations' ] );
		add_shortcode( 'camp_faqs', [ $this, 'render_faqs' ] );
		add_shortcode( 'camp_sessions', [ $this, 'render_sessions' ] );
		add_shortcode( 'camp_additional_info', [ $this, 'render_additional_info' ] );
		
		// Enqueue frontend styles
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_styles' ] );
	}

	/**
	 * Get camp data from page custom field camp_id
	 */
	private function get_camp_data() {
		global $wpdb, $post;
		
		// Get camp_id from custom field
		$camp_id = get_post_meta( $post->ID, 'camp_id', true );
		
		if ( ! $camp_id ) {
			return null;
		}
		
		// Get camp data
		$camp = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}camp_management WHERE id = %d",
			$camp_id
		), ARRAY_A );
		
		return $camp;
	}

	/**
	 * Enqueue frontend styles
	 */
	public function enqueue_frontend_styles() {
		// Check if page has camp_id custom field
		global $post;
		if ( is_a( $post, 'WP_Post' ) && get_post_meta( $post->ID, 'camp_id', true ) ) {
			wp_enqueue_style(
				'camp-frontend',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/camp-frontend.css',
				[],
				CDBS_CAMP_VERSION
			);
		}
	}

	/**
	 * Render camp logo
	 */
	public function render_logo( $atts ) {
		$atts = shortcode_atts( [
			'size' => 'medium', // small, medium, large
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp || empty( $camp['logo'] ) ) {
			return '';
		}
		
		$size_class = 'camp-logo-' . esc_attr( $atts['size'] );
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-logo-wrapper <?php echo $size_class . $custom_class; ?>">
			<?php if ( pathinfo( $camp['logo'], PATHINFO_EXTENSION ) === 'pdf' ) : ?>
				<a href="<?php echo esc_url( $camp['logo'] ); ?>" target="_blank" class="camp-logo-pdf">
					<span>📄 View Logo (PDF)</span>
				</a>
			<?php else : ?>
				<img src="<?php echo esc_url( $camp['logo'] ); ?>" alt="<?php echo esc_attr( $camp['camp_name'] ); ?> Logo" class="camp-logo">
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render camp name (H1 - SEO friendly)
	 */
	public function render_name( $atts ) {
		$atts = shortcode_atts( [
			'tag' => 'h1', // h1, h2, h3, div, span
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		$tag = in_array( $atts['tag'], [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span' ] ) ? $atts['tag'] : 'h1';
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		return sprintf(
			'<%1$s class="camp-name%2$s">%3$s</%1$s>',
			$tag,
			$custom_class,
			esc_html( $camp['camp_name'] )
		);
	}

	/**
	 * Render camp subtitle (auto-generated from camp types and location)
	 */
	public function render_subtitle( $atts ) {
		$atts = shortcode_atts( [
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		global $wpdb;
		
		// Get camp types
		$camp_types = $wpdb->get_col( $wpdb->prepare(
			"SELECT ct.name 
			FROM {$wpdb->prefix}camp_type_terms ct
			INNER JOIN {$wpdb->prefix}camp_management_types_map cmt ON ct.id = cmt.type_id
			WHERE cmt.camp_id = %d
			ORDER BY ct.sort_order ASC",
			$camp['id']
		) );
		
		$types_text = ! empty( $camp_types ) ? implode( ' ', $camp_types ) : 'Summer Camp';
		$location = sprintf( '%s, %s', $camp['city'], $camp['state'] );
		
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		return sprintf(
			'<div class="camp-subtitle%s">%s – %s</div>',
			$custom_class,
			esc_html( $types_text ),
			esc_html( $location )
		);
	}

	/**
	 * Render contact bar (address, email, phone, website)
	 */
	public function render_contact_bar( $atts ) {
		$atts = shortcode_atts( [
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-contact-bar<?php echo $custom_class; ?>">
			<?php if ( ! empty( $camp['address'] ) ) : ?>
				<div class="contact-item">
					<i aria-hidden="true" class="icon icon-map-marker"></i>
					<span class="contact-text"><?php echo esc_html( $camp['address'] . ', ' . $camp['city'] . ', ' . $camp['state'] . ' ' . $camp['zip'] ); ?></span>
				</div>
			<?php endif; ?>
			
			<?php if ( ! empty( $camp['email'] ) ) : ?>
				<div class="contact-item">
					<i aria-hidden="true" class="icon icon-envelope2"></i>
					<a href="mailto:<?php echo esc_attr( $camp['email'] ); ?>" class="contact-text"><?php echo esc_html( $camp['email'] ); ?></a>
				</div>
			<?php endif; ?>
			
			<?php if ( ! empty( $camp['phone'] ) ) : ?>
				<div class="contact-item">
					<i aria-hidden="true" class="icon icon-phone-handset"></i>
					<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $camp['phone'] ) ); ?>" class="contact-text"><?php echo esc_html( $camp['phone'] ); ?></a>
				</div>
			<?php endif; ?>
			
			<?php if ( ! empty( $camp['website'] ) ) : ?>
				<div class="contact-item">
					<i aria-hidden="true" class="icon icon-phone-handset"></i>
					<a href="<?php echo esc_url( $camp['website'] ); ?>" target="_blank" rel="noopener" class="contact-text">Website</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render rating (5 circles)
	 */
	public function render_rating( $atts ) {
		$atts = shortcode_atts( [
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		$rating = isset( $camp['rating'] ) ? floatval( $camp['rating'] ) : 0;
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-rating-display<?php echo $custom_class; ?>">
			<span class="rating-label">Rating:</span>
			<div class="rating-circles">
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
					<span class="rating-circle <?php echo $i <= $rating ? 'filled' : 'empty'; ?>"></span>
				<?php endfor; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render camp description
	 */
	public function render_description( $atts ) {
		$atts = shortcode_atts( [
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp || empty( $camp['about_camp'] ) ) {
			return '';
		}
		
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-section camp-description<?php echo $custom_class; ?>">
			<h2>About <?php echo esc_html( $camp['camp_name'] ); ?></h2>
			<div class="description-content">
				<?php echo wpautop( wp_kses_post( $camp['about_camp'] ) ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render activities
	 */
	public function render_activities( $atts ) {
		$atts = shortcode_atts( [
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		global $wpdb;
		$activities = $wpdb->get_col( $wpdb->prepare(
			"SELECT at.name 
			FROM {$wpdb->prefix}camp_activity_terms at
			INNER JOIN {$wpdb->prefix}camp_management_activities_map amt ON at.id = amt.activity_id
			WHERE amt.camp_id = %d
			ORDER BY at.name ASC",
			$camp['id']
		) );
		
		if ( empty( $activities ) ) {
			return '';
		}
		
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-section camp-activities<?php echo $custom_class; ?>">
			<h2>Activities Offered</h2>
			<div class="activities-grid">
				<?php foreach ( $activities as $activity ) : ?>
					<span class="activity-tag"><?php echo esc_html( $activity ); ?></span>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render camp types and available weeks
	 */
	public function render_types_weeks( $atts ) {
		$atts = shortcode_atts( [
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		global $wpdb;
		
		// Get camp types
		$camp_types = $wpdb->get_col( $wpdb->prepare(
			"SELECT ct.name 
			FROM {$wpdb->prefix}camp_type_terms ct
			INNER JOIN {$wpdb->prefix}camp_management_types_map cmt ON ct.id = cmt.type_id
			WHERE cmt.camp_id = %d
			ORDER BY ct.sort_order ASC",
			$camp['id']
		) );
		
		// Get available weeks
		$camp_weeks = $wpdb->get_col( $wpdb->prepare(
			"SELECT wt.name 
			FROM {$wpdb->prefix}camp_week_terms wt
			INNER JOIN {$wpdb->prefix}camp_management_weeks_map cwm ON wt.id = cwm.week_id
			WHERE cwm.camp_id = %d
			ORDER BY wt.sort_order ASC",
			$camp['id']
		) );
		
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-section camp-types-weeks<?php echo $custom_class; ?>">
			<?php if ( ! empty( $camp_types ) ) : ?>
				<div class="types-section">
					<h2>Camp Types</h2>
					<div class="types-list">
						<?php foreach ( $camp_types as $type ) : ?>
							<span class="type-badge"><?php echo esc_html( $type ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
			
			<?php if ( ! empty( $camp_weeks ) ) : ?>
				<div class="weeks-section">
					<h2>Available Weeks</h2>
					<div class="weeks-list">
						<?php foreach ( $camp_weeks as $week ) : ?>
							<span class="week-badge"><?php echo esc_html( $week ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render accommodations
	 */
	public function render_accommodations( $atts ) {
		$atts = shortcode_atts( [
			'layout' => 'list', // list, cards
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		global $wpdb;
		$accommodations = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM " . \CreativeDBS\CampMgmt\DB::table_accommodations() . " WHERE camp_id = %d ORDER BY sort_order ASC",
			$camp['id']
		), ARRAY_A );
		
		if ( empty( $accommodations ) ) {
			return '';
		}
		
		$layout_class = 'layout-' . esc_attr( $atts['layout'] );
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-section camp-accommodations <?php echo $layout_class . $custom_class; ?>">
			<h2>Accommodation Facilities</h2>
			<div class="accommodations-container">
				<?php foreach ( $accommodations as $acc ) : ?>
					<div class="accommodation-item">
						<h3><?php echo esc_html( $acc['name'] ); ?></h3>
						<?php if ( ! empty( $acc['capacity'] ) ) : ?>
							<p class="capacity">Capacity: <strong><?php echo esc_html( $acc['capacity'] ); ?></strong></p>
						<?php endif; ?>
						<?php if ( ! empty( $acc['description'] ) ) : ?>
							<p class="description"><?php echo esc_html( $acc['description'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render FAQs
	 */
	public function render_faqs( $atts ) {
		$atts = shortcode_atts( [
			'style' => 'accordion', // accordion, list
			'open_first' => 'true',
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		global $wpdb;
		$faqs = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM " . \CreativeDBS\CampMgmt\DB::table_faqs() . " WHERE camp_id = %d ORDER BY sort_order ASC",
			$camp['id']
		), ARRAY_A );
		
		if ( empty( $faqs ) ) {
			return '';
		}
		
		$style_class = 'style-' . esc_attr( $atts['style'] );
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		$open_first = $atts['open_first'] === 'true';
		
		ob_start();
		?>
		<div class="camp-section camp-faqs <?php echo $style_class . $custom_class; ?>">
			<h2>Frequently Asked Questions</h2>
			<div class="faqs-container">
				<?php foreach ( $faqs as $index => $faq ) : ?>
					<div class="faq-item <?php echo ( $atts['style'] === 'accordion' && $index === 0 && $open_first ) ? 'open' : ''; ?>">
						<div class="faq-question">
							<?php if ( $atts['style'] === 'accordion' ) : ?>
								<button class="faq-toggle" aria-expanded="<?php echo ( $index === 0 && $open_first ) ? 'true' : 'false'; ?>">
									<span><?php echo esc_html( $faq['question'] ); ?></span>
									<span class="faq-icon">+</span>
								</button>
							<?php else : ?>
								<h3><?php echo esc_html( $faq['question'] ); ?></h3>
							<?php endif; ?>
						</div>
						<div class="faq-answer">
							<?php echo wpautop( esc_html( $faq['answer'] ) ); ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		
		<?php if ( $atts['style'] === 'accordion' ) : ?>
		<script>
		(function() {
			const faqToggles = document.querySelectorAll('.faq-toggle');
			faqToggles.forEach(toggle => {
				toggle.addEventListener('click', function() {
					const faqItem = this.closest('.faq-item');
					const isOpen = faqItem.classList.contains('open');
					
					// Close all
					document.querySelectorAll('.faq-item').forEach(item => item.classList.remove('open'));
					
					// Open clicked if it wasn't open
					if (!isOpen) {
						faqItem.classList.add('open');
						this.setAttribute('aria-expanded', 'true');
					} else {
						this.setAttribute('aria-expanded', 'false');
					}
				});
			});
		})();
		</script>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render sessions
	 */
	public function render_sessions( $atts ) {
		$atts = shortcode_atts( [
			'layout' => 'grid', // grid, list
			'columns' => '2',
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		global $wpdb;
		$sessions = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM " . \CreativeDBS\CampMgmt\DB::table_sessions() . " WHERE camp_id = %d ORDER BY sort_order ASC",
			$camp['id']
		), ARRAY_A );
		
		if ( empty( $sessions ) ) {
			return '';
		}
		
		$layout_class = 'layout-' . esc_attr( $atts['layout'] );
		$columns_class = 'columns-' . esc_attr( $atts['columns'] );
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-section camp-sessions <?php echo $layout_class . ' ' . $columns_class . $custom_class; ?>">
			<h2>Sessions & Pricing</h2>
			<div class="sessions-container">
				<?php foreach ( $sessions as $session ) : ?>
					<div class="session-card">
						<h3><?php echo esc_html( $session['session_name'] ); ?></h3>
						
						<?php if ( ! empty( $session['start_date'] ) || ! empty( $session['end_date'] ) ) : ?>
							<div class="session-dates">
								<?php
								if ( ! empty( $session['start_date'] ) && ! empty( $session['end_date'] ) ) {
									echo esc_html( date( 'M d', strtotime( $session['start_date'] ) ) ) . ' - ' . esc_html( date( 'M d, Y', strtotime( $session['end_date'] ) ) );
								} elseif ( ! empty( $session['start_date'] ) ) {
									echo 'Starts: ' . esc_html( date( 'M d, Y', strtotime( $session['start_date'] ) ) );
								}
								?>
							</div>
						<?php endif; ?>
						
						<?php if ( ! empty( $session['price'] ) && $session['price'] > 0 ) : ?>
							<div class="session-price">$<?php echo number_format( $session['price'], 2 ); ?></div>
						<?php endif; ?>
						
						<?php if ( ! empty( $session['description'] ) ) : ?>
							<p class="session-description"><?php echo esc_html( $session['description'] ); ?></p>
						<?php endif; ?>
						
						<?php if ( ! empty( $session['notes'] ) ) : ?>
							<p class="session-notes"><?php echo esc_html( $session['notes'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render additional info (ages, year established, gender, etc.)
	 */
	public function render_additional_info( $atts ) {
		$atts = shortcode_atts( [
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-section camp-additional-info<?php echo $custom_class; ?>">
			<h2>Camp Information</h2>
			<div class="info-grid">
				<?php if ( ! empty( $camp['camp_directors'] ) ) : ?>
					<div class="info-item">
						<span class="info-label">Director(s):</span>
						<span class="info-value"><?php echo esc_html( $camp['camp_directors'] ); ?></span>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $camp['opening_day'] ) ) : ?>
					<div class="info-item">
						<span class="info-label">Opening Day:</span>
						<span class="info-value"><?php echo esc_html( date( 'F d, Y', strtotime( $camp['opening_day'] ) ) ); ?></span>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $camp['closing_day'] ) ) : ?>
					<div class="info-item">
						<span class="info-label">Closing Day:</span>
						<span class="info-value"><?php echo esc_html( date( 'F d, Y', strtotime( $camp['closing_day'] ) ) ); ?></span>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

// Initialize
new Camp_Frontend();
