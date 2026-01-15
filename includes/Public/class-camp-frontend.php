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
		add_shortcode( 'camp_name_text', [ $this, 'render_name_text' ] );
		add_shortcode( 'camp_subtitle', [ $this, 'render_subtitle' ] );
		add_shortcode( 'camp_contact_bar', [ $this, 'render_contact_bar' ] );
		add_shortcode( 'camp_rating', [ $this, 'render_rating' ] );
		
		// Content shortcodes
		add_shortcode( 'camp_description', [ $this, 'render_description' ] );
		add_shortcode( 'camp_activities', [ $this, 'render_activities' ] );
		add_shortcode( 'camp_types', [ $this, 'render_types' ] );
		add_shortcode( 'camp_weeks', [ $this, 'render_weeks' ] );
		add_shortcode( 'camp_types_weeks', [ $this, 'render_types_weeks' ] ); // Backward compatibility
		add_shortcode( 'camp_accommodations', [ $this, 'render_accommodations' ] );
		add_shortcode( 'camp_faqs', [ $this, 'render_faqs' ] );
		add_shortcode( 'camp_sessions', [ $this, 'render_sessions' ] );
		add_shortcode( 'camp_additional_info', [ $this, 'render_additional_info' ] );
		add_shortcode( 'camp_contact_info', [ $this, 'render_contact_info' ] );
		
		// Debug shortcode
		add_shortcode( 'camp_debug', [ $this, 'render_debug' ] );
		
		// Enqueue frontend styles
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_styles' ] );
		add_action( 'wp_head', [ $this, 'add_fontawesome_fallback' ], 100 );
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
			// FontAwesome 6.5.1 - Load with higher priority
			if ( ! wp_style_is( 'font-awesome', 'enqueued' ) && ! wp_style_is( 'fontawesome', 'enqueued' ) ) {
				wp_enqueue_style(
					'cdbs-font-awesome',
					'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
					[],
					'6.5.1'
				);
			}
			
			// Camp frontend styles
			wp_enqueue_style(
				'camp-frontend',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/camp-frontend.css',
				[],
				CDBS_CAMP_VERSION
			);
			
			// Add FontAwesome integrity check
			add_action( 'wp_head', function() {
				echo '<link rel="preconnect" href="https://cdnjs.cloudflare.com">' . "\n";
			}, 1 );
		}
	}

	/**
	 * Add FontAwesome fallback to ensure it loads
	 */
	public function add_fontawesome_fallback() {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && get_post_meta( $post->ID, 'camp_id', true ) ) {
			// Direct stylesheet link as fallback
			echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />' . "\n";
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
	 * Render camp name as plain text (for SEO titles, meta descriptions)
	 */
	public function render_name_text( $atts ) {
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		return esc_html( $camp['camp_name'] );
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
			<?php if ( ! empty( $camp['address'] ) ) : 
				$full_address = $camp['address'] . ', ' . $camp['city'] . ', ' . $camp['state'] . ' ' . $camp['zip'];
				$maps_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode( $full_address );
			?>
				<div class="contact-item">
					<i aria-hidden="true" class="icon icon-map-marker"></i>
					<a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener" class="contact-text"><?php echo esc_html( $full_address ); ?></a>
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
	 * Render camp types only
	 */
	public function render_types( $atts ) {
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
			ORDER BY ct.name ASC",
			$camp['id']
		) );
		
		if ( empty( $camp_types ) ) {
			return '';
		}
		
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-section camp-types<?php echo $custom_class; ?>">
			<div class="types-list">
				<?php foreach ( $camp_types as $type ) : ?>
					<span class="type-badge"><?php echo esc_html( $type ); ?></span>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render camp weeks only
	 */
	public function render_weeks( $atts ) {
		$atts = shortcode_atts( [
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		global $wpdb;
		
		// Get available weeks
		$camp_weeks = $wpdb->get_col( $wpdb->prepare(
			"SELECT wt.name 
			FROM {$wpdb->prefix}camp_week_terms wt
			INNER JOIN {$wpdb->prefix}camp_management_weeks_map cwm ON wt.id = cwm.week_id
			WHERE cwm.camp_id = %d
			ORDER BY wt.name ASC",
			$camp['id']
		) );
		
		if ( empty( $camp_weeks ) ) {
			return '';
		}
		
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-section camp-weeks<?php echo $custom_class; ?>">
			<div class="weeks-list">
				<?php foreach ( $camp_weeks as $week ) : ?>
					<span class="week-badge"><?php echo esc_html( $week ); ?></span>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render camp types and available weeks (backward compatibility)
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
			ORDER BY ct.name ASC",
			$camp['id']
		) );
		
		// Get available weeks
		$camp_weeks = $wpdb->get_col( $wpdb->prepare(
			"SELECT wt.name 
			FROM {$wpdb->prefix}camp_week_terms wt
			INNER JOIN {$wpdb->prefix}camp_management_weeks_map cwm ON wt.id = cwm.week_id
			WHERE cwm.camp_id = %d
			ORDER BY wt.name ASC",
			$camp['id']
		) );
		
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-section camp-types-weeks<?php echo $custom_class; ?>">
			<?php if ( ! empty( $camp_types ) ) : ?>
				<div class="types-section">
					<div class="types-list">
						<?php foreach ( $camp_types as $type ) : ?>
							<span class="type-badge"><?php echo esc_html( $type ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
			
			<?php if ( ! empty( $camp_weeks ) ) : ?>
				<div class="weeks-section">
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
		
		// Determine columns based on accommodation count (max 3)
		$accommodation_count = count( $accommodations );
		$dynamic_columns = min( $accommodation_count, 3 );
		
		$layout_class = 'layout-' . esc_attr( $atts['layout'] );
		$columns_class = 'columns-' . $dynamic_columns;
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-section camp-accommodations">
			<div class="accommodations-container <?php echo $layout_class . ' ' . $columns_class . $custom_class; ?>">
				<?php foreach ( $accommodations as $acc ) : ?>
					<div class="accommodation-item">
						<h3><?php echo esc_html( $acc['name'] ); ?></h3>
						<?php if ( ! empty( $acc['capacity'] ) ) : ?>
							<p class="capacity">Capacity: <strong><?php echo esc_html( $acc['capacity'] ); ?></strong></p>
						<?php endif; ?>
					<?php if ( ! empty( $acc['description'] ) ) : 
						$description = esc_html( $acc['description'] );
						$words = explode( ' ', $description );
						if ( count( $words ) > 90 ) {
							$description = implode( ' ', array_slice( $words, 0, 90 ) ) . '...';
						}
					?>
						<p class="description"><?php echo $description; ?></p>
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
			'open_first' => 'false',
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
		
		// Determine columns based on session count (max 3)
		$session_count = count( $sessions );
		$dynamic_columns = min( $session_count, 3 );
		
		$layout_class = 'layout-' . esc_attr( $atts['layout'] );
		$columns_class = 'columns-' . $dynamic_columns;
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-section camp-sessions">
			<div class="sessions-container <?php echo $layout_class . ' ' . $columns_class . $custom_class; ?>">
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
						
<?php if ( ! empty( $session['description'] ) ) : 
					$description = esc_html( $session['description'] );
					$words = explode( ' ', $description );
					if ( count( $words ) > 90 ) {
						$description = implode( ' ', array_slice( $words, 0, 90 ) ) . '...';
					}
				?>
					<p class="session-description"><?php echo $description; ?></p>
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
	 * Render additional info (directors, dates, rates)
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
			<div class="info-grid info-grid-5">
				<?php if ( ! empty( $camp['camp_directors'] ) ) : ?>
					<div class="info-card">
						<div class="info-icon">
							<i class="fa-solid fa-users"></i>
						</div>
						<div class="info-label">Director(s)</div>
						<div class="info-value"><?php echo esc_html( $camp['camp_directors'] ); ?></div>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $camp['opening_day'] ) ) : ?>
					<div class="info-card">
						<div class="info-icon">
							<i class="fa-regular fa-calendar-check"></i>
						</div>
						<div class="info-label">Opening Day</div>
						<div class="info-value"><?php echo esc_html( date( 'M j, Y', strtotime( $camp['opening_day'] ) ) ); ?></div>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $camp['closing_day'] ) ) : ?>
					<div class="info-card">
						<div class="info-icon">
							<i class="fa-regular fa-calendar-xmark"></i>
						</div>
						<div class="info-label">Closing Day</div>
						<div class="info-value"><?php echo esc_html( date( 'M j, Y', strtotime( $camp['closing_day'] ) ) ); ?></div>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $camp['minprice_2026'] ) && $camp['minprice_2026'] > 0 ) : ?>
					<div class="info-card">
						<div class="info-icon">
							<i class="fa-solid fa-tag"></i>
						</div>
						<div class="info-label">Lowest Rate</div>
						<div class="info-value">$<?php echo number_format( $camp['minprice_2026'], 0 ); ?></div>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $camp['maxprice_2026'] ) && $camp['maxprice_2026'] > 0 ) : ?>
					<div class="info-card">
						<div class="info-icon">
							<i class="fa-solid fa-tags"></i>
						</div>
						<div class="info-label">Highest Rate</div>
						<div class="info-value">$<?php echo number_format( $camp['maxprice_2026'], 0 ); ?></div>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render contact information (sidebar format - name, address, contact details stacked)
	 */
	public function render_contact_info( $atts ) {
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
		<div class="camp-section camp-contact-info<?php echo $custom_class; ?>">
			<?php if ( ! empty( $camp['address'] ) || ! empty( $camp['city'] ) || ! empty( $camp['state'] ) || ! empty( $camp['zip'] ) ) : 
				$address_parts = array_filter( [ $camp['address'], $camp['city'], $camp['state'], $camp['zip'] ] );
				$full_address = implode( ', ', $address_parts );
				$maps_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode( $full_address );
			?>
				<div class="contact-info-item contact-address">
					<div class="contact-info-label">Address</div>
					<?php if ( ! empty( $camp['address'] ) ) : ?>
						<div class="contact-info-value">
							<a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $camp['address'] ); ?></a>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $camp['city'] ) || ! empty( $camp['state'] ) || ! empty( $camp['zip'] ) ) : ?>
						<div class="contact-info-value">
							<a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener">
								<?php 
								$location_parts = array_filter( [ $camp['city'], $camp['state'], $camp['zip'] ] );
								echo esc_html( implode( ', ', $location_parts ) );
								?>
							</a>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			
			<?php if ( ! empty( $camp['email'] ) ) : ?>
				<div class="contact-info-item contact-email">
					<div class="contact-info-label">Email</div>
					<div class="contact-info-value">
						<a href="mailto:<?php echo esc_attr( $camp['email'] ); ?>"><?php echo esc_html( $camp['email'] ); ?></a>
					</div>
				</div>
			<?php endif; ?>
			
			<?php if ( ! empty( $camp['phone'] ) ) : ?>
				<div class="contact-info-item contact-phone">
					<div class="contact-info-label">Phone</div>
					<div class="contact-info-value">
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $camp['phone'] ) ); ?>"><?php echo esc_html( $camp['phone'] ); ?></a>
					</div>
				</div>
			<?php endif; ?>
			
			<?php if ( ! empty( $camp['website'] ) ) : ?>
				<div class="contact-info-item contact-website">
					<div class="contact-info-label">Website</div>
					<div class="contact-info-value">
						<a href="<?php echo esc_url( $camp['website'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', $camp['website'] ) ); ?></a>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render debug information (for troubleshooting only)
	 */
	public function render_debug( $atts ) {
		// Only show to logged-in administrators
		if ( ! current_user_can( 'manage_options' ) ) {
			return '<!-- Debug info only visible to administrators -->';
		}
		
		global $wpdb, $post;
		
		$camp_id = get_post_meta( $post->ID, 'camp_id', true );
		$camp = $this->get_camp_data();
		
		ob_start();
		?>
		<div style="background: #f5f5f5; border: 2px solid #333; padding: 20px; margin: 20px 0; font-family: monospace;">
			<h3 style="margin-top: 0;">🔍 Camp Debug Information</h3>
			<table style="width: 100%; border-collapse: collapse;">
				<tr>
					<td style="padding: 5px; border: 1px solid #ccc;"><strong>Page ID:</strong></td>
					<td style="padding: 5px; border: 1px solid #ccc;"><?php echo esc_html( $post->ID ); ?></td>
				</tr>
				<tr>
					<td style="padding: 5px; border: 1px solid #ccc;"><strong>Camp ID (custom field):</strong></td>
					<td style="padding: 5px; border: 1px solid #ccc;"><?php echo esc_html( $camp_id ? $camp_id : 'NOT SET' ); ?></td>
				</tr>
				<tr>
					<td style="padding: 5px; border: 1px solid #ccc;"><strong>Camp Found:</strong></td>
					<td style="padding: 5px; border: 1px solid #ccc;"><?php echo $camp ? 'YES' : 'NO'; ?></td>
				</tr>
				<?php if ( $camp ) : ?>
				<tr>
					<td style="padding: 5px; border: 1px solid #ccc;"><strong>Camp Name:</strong></td>
					<td style="padding: 5px; border: 1px solid #ccc;"><?php echo esc_html( $camp['camp_name'] ); ?></td>
				</tr>
				<tr>
					<td style="padding: 5px; border: 1px solid #ccc;"><strong>Logo URL (DB):</strong></td>
					<td style="padding: 5px; border: 1px solid #ccc; word-break: break-all;">
						<?php echo esc_html( $camp['logo'] ? $camp['logo'] : 'EMPTY' ); ?>
					</td>
				</tr>
				<tr>
					<td style="padding: 5px; border: 1px solid #ccc;"><strong>Logo Filename:</strong></td>
					<td style="padding: 5px; border: 1px solid #ccc;">
						<?php echo esc_html( $camp['logo'] ? basename( $camp['logo'] ) : 'N/A' ); ?>
					</td>
				</tr>
				<tr>
					<td style="padding: 5px; border: 1px solid #ccc;"><strong>Logo Preview:</strong></td>
					<td style="padding: 5px; border: 1px solid #ccc;">
						<?php if ( ! empty( $camp['logo'] ) && pathinfo( $camp['logo'], PATHINFO_EXTENSION ) !== 'pdf' ) : ?>
							<img src="<?php echo esc_url( $camp['logo'] ); ?>" style="max-width: 200px; max-height: 100px; display: block; margin-top: 10px;" alt="Logo">
						<?php elseif ( ! empty( $camp['logo'] ) ) : ?>
							PDF File
						<?php else : ?>
							No logo set
						<?php endif; ?>
					</td>
				</tr>
				<?php endif; ?>
			</table>
			<?php if ( $camp ) : ?>
			<details style="margin-top: 15px;">
				<summary style="cursor: pointer; font-weight: bold;">Show Full Camp Data</summary>
				<pre style="background: #fff; padding: 10px; overflow: auto; max-height: 400px; margin-top: 10px;"><?php print_r( $camp ); ?></pre>
			</details>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}

// Initialize
new Camp_Frontend();
