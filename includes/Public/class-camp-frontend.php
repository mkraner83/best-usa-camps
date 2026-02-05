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
		// Combined header shortcode
		add_shortcode( 'camp_header', [ $this, 'render_header' ] );
		
		// Individual header shortcodes (for flexibility)
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
		add_shortcode( 'camp_gallery', [ $this, 'render_gallery' ] );
		add_shortcode( 'camp_social_media', [ $this, 'render_social_media' ] );
		add_shortcode( 'camp_video', [ $this, 'render_video' ] );
		
		// Search shortcode
		add_shortcode( 'camp_search', [ $this, 'render_search' ] );
		
		// Debug shortcode
		add_shortcode( 'camp_debug', [ $this, 'render_debug' ] );
		
		// Enqueue frontend styles
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_styles' ] );
		add_action( 'wp_head', [ $this, 'add_fontawesome_fallback' ], 100 );
		
		// AJAX handlers for search
		add_action( 'wp_ajax_camp_search', [ $this, 'ajax_camp_search' ] );
		add_action( 'wp_ajax_nopriv_camp_search', [ $this, 'ajax_camp_search' ] );
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
		global $post;
		
		// Check if page has camp_id custom field OR camp_search shortcode
		$has_camp_id = is_a( $post, 'WP_Post' ) && get_post_meta( $post->ID, 'camp_id', true );
		$has_search = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'camp_search' );
		
		if ( $has_camp_id || $has_search ) {
			// FontAwesome 6.5.1 - Load with higher priority
			if ( ! wp_style_is( 'font-awesome', 'enqueued' ) && ! wp_style_is( 'fontawesome', 'enqueued' ) ) {
				wp_enqueue_style(
					'cdbs-font-awesome',
					'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
					[],
					'6.5.1'
				);
			}
		}
		
		// Camp detail page styles
		if ( $has_camp_id ) {
			// Camp frontend styles
			wp_enqueue_style(
				'camp-frontend',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/camp-frontend.css',
				[],
				CDBS_CAMP_VERSION
			);
			
			// Camp section visibility script
			wp_enqueue_script(
				'camp-section-visibility',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/camp-section-visibility.js',
				[],
				CDBS_CAMP_VERSION,
				true // Load in footer
			);
			
			// Add FontAwesome integrity check
			add_action( 'wp_head', function() {
				echo '<link rel="preconnect" href="https://cdnjs.cloudflare.com">' . "\n";
			}, 1 );
		}
		
		// Camp search page styles
		if ( $has_search ) {
			// Search page styles
			wp_enqueue_style(
				'camp-search',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/camp-search.css',
				[],
				CDBS_CAMP_VERSION
			);
			
			// Search page script (requires jQuery)
			wp_enqueue_script(
				'camp-search',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/camp-search.js',
				[ 'jquery' ],
				CDBS_CAMP_VERSION,
				true // Load in footer
			);
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
	 * Render complete camp header (logo, name, subtitle, contact bar, rating)
	 */
	public function render_header( $atts ) {
		$atts = shortcode_atts( [
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		global $wpdb;
		
		// Get camp types for subtitle
		$camp_types = $wpdb->get_col( $wpdb->prepare(
			"SELECT ct.name 
			FROM {$wpdb->prefix}camp_type_terms ct
			INNER JOIN {$wpdb->prefix}camp_management_types_map cmt ON ct.id = cmt.type_id
			WHERE cmt.camp_id = %d
			ORDER BY ct.sort_order ASC",
			$camp['id']
		) );
		
		// Show only first 2 types, add "and more +" if more
		if ( ! empty( $camp_types ) ) {
			if ( count( $camp_types ) > 2 ) {
				$types_text = implode( ', ', array_slice( $camp_types, 0, 2 ) ) . ', and more +';
			} elseif ( count( $camp_types ) === 2 ) {
				$types_text = implode( ', ', $camp_types );
			} else {
				$types_text = $camp_types[0];
			}
		} else {
			$types_text = 'Summer Camp';
		}
		
		$location = sprintf( '(%s, %s)', $camp['city'], $camp['state'] );
		$rating = isset( $camp['rating'] ) ? floatval( $camp['rating'] ) : 0;
		$full_address = $camp['address'] . ', ' . $camp['city'] . ', ' . $camp['state'] . ' ' . $camp['zip'];
		$maps_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode( $full_address );
		
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-header-wrapper<?php echo $custom_class; ?>">
			<div class="camp-header-top">
				<!-- Camp Name & Subtitle -->
				<div class="camp-header-info">
					<h1 class="camp-header-name"><?php echo esc_html( $camp['camp_name'] ); ?></h1>
					<div class="camp-header-subtitle"><?php echo esc_html( $types_text ); ?> <?php echo esc_html( $location ); ?></div>
				</div>
				
				<!-- Logo -->
				<div class="camp-header-logo">
					<?php if ( ! empty( $camp['logo'] ) ) : ?>
						<?php if ( pathinfo( $camp['logo'], PATHINFO_EXTENSION ) === 'pdf' ) : ?>
							<a href="<?php echo esc_url( $camp['logo'] ); ?>" target="_blank" class="camp-logo-pdf">
								<span>📄 View Logo (PDF)</span>
							</a>
						<?php else : ?>
							<img src="<?php echo esc_url( $camp['logo'] ); ?>" alt="<?php echo esc_attr( $camp['camp_name'] ); ?> Logo" class="camp-header-logo-img">
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
			
			<!-- Contact Bar & Rating -->
			<div class="camp-header-bottom">
				<div class="camp-header-contact">
					<?php if ( ! empty( $camp['address'] ) ) : ?>
						<div class="camp-header-contact-item">
							<i aria-hidden="true" class="icon icon-map-marker"></i>
							<a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $full_address ); ?></a>
						</div>
					<?php endif; ?>
					
					<?php if ( ! empty( $camp['email'] ) ) : ?>
						<div class="camp-header-contact-item">
							<i aria-hidden="true" class="icon icon-envelope2"></i>
							<a href="mailto:<?php echo esc_attr( $camp['email'] ); ?>"><?php echo esc_html( $camp['email'] ); ?></a>
						</div>
					<?php endif; ?>
					
					<?php if ( ! empty( $camp['phone'] ) ) : ?>
						<div class="camp-header-contact-item">
							<i aria-hidden="true" class="icon icon-phone-handset"></i>
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $camp['phone'] ) ); ?>"><?php echo esc_html( $camp['phone'] ); ?></a>
						</div>
					<?php endif; ?>
					
					<?php if ( ! empty( $camp['website'] ) ) : ?>
						<div class="camp-header-contact-item">
							<i aria-hidden="true" class="fa-solid fa-globe"></i>
							<a href="<?php echo esc_url( $camp['website'] ); ?>" target="_blank" rel="noopener">Website</a>
						</div>
					<?php endif; ?>
				</div>
				
				<!-- Rating -->
				<div class="camp-header-rating">
					<span class="camp-header-rating-label">Rating:</span>
					<div class="camp-header-rating-circles">
						<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<span class="rating-circle <?php echo $i <= $rating ? 'filled' : 'empty'; ?>"></span>
						<?php endfor; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
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
		$total_activities = count( $activities );
		$show_more = $total_activities > 5;
		
		ob_start();
		?>
		<div class="camp-section camp-activities<?php echo $custom_class; ?>">
			<div class="activities-grid">
				<?php foreach ( $activities as $index => $activity ) : ?>
					<span class="activity-tag<?php echo ( $index >= 5 ) ? ' activity-hidden' : ''; ?>"><?php echo esc_html( $activity ); ?></span>
				<?php endforeach; ?>
			</div>
			<?php if ( $show_more ) : ?>
				<button class="activities-show-more" onclick="this.previousElementSibling.classList.add('show-all'); this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
					Show More (<?php echo $total_activities - 5; ?> more)
				</button>
				<button class="activities-show-less" style="display: none;" onclick="this.previousElementSibling.previousElementSibling.classList.remove('show-all'); this.style.display='none'; this.previousElementSibling.style.display='inline-block';">
					Show Less
				</button>
			<?php endif; ?>
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
					<div class="accommodation-card">
						<div class="accommodation-header">
							<h3><?php echo esc_html( $acc['name'] ); ?></h3>
							<?php if ( ! empty( $acc['capacity'] ) ) : ?>
								<div class="capacity-badge">
									<i class="fa fa-users"></i> <?php echo esc_html( $acc['capacity'] ); ?>
								</div>
							<?php endif; ?>
						</div>
					<?php if ( ! empty( $acc['description'] ) ) : 
						$description = esc_html( $acc['description'] );
						$words = explode( ' ', $description );
						if ( count( $words ) > 50 ) {
							$description = implode( ' ', array_slice( $words, 0, 50 ) ) . '...';
						}
					?>
						<p class="accommodation-description"><?php echo $description; ?></p>
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
						<div class="session-header">
							<h3><?php echo esc_html( $session['session_name'] ); ?></h3>
						</div>
						
						<?php if ( ! empty( $session['start_date'] ) || ! empty( $session['end_date'] ) ) : ?>
							<div class="session-dates">
								<i class="fa fa-calendar"></i> 
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
							<div class="session-price">
								$<?php echo number_format( $session['price'], 2 ); ?>
							</div>
						<?php endif; ?>
						
<?php if ( ! empty( $session['description'] ) ) : 
					$description = esc_html( $session['description'] );
					$words = explode( ' ', $description );
					if ( count( $words ) > 50 ) {
						$description = implode( ' ', array_slice( $words, 0, 50 ) ) . '...';
					}
				?>
					<p class="session-description"><?php echo $description; ?></p>
						<?php endif; ?>
						
						<?php if ( ! empty( $session['notes'] ) ) : ?>
							<div class="session-note-badge"><?php echo esc_html( $session['notes'] ); ?></div>
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
			<div class="contact-list">
				<?php if ( ! empty( $camp['address'] ) || ! empty( $camp['city'] ) || ! empty( $camp['state'] ) || ! empty( $camp['zip'] ) ) : 
					$address_parts = array_filter( [ $camp['address'], $camp['city'], $camp['state'], $camp['zip'] ] );
					$full_address = implode( ', ', $address_parts );
					$maps_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode( $full_address );
				?>
					<div class="contact-list-item">
						<div class="contact-icon">
							<i class="fa-solid fa-location-dot"></i>
						</div>
						<div class="contact-content">
							<div class="contact-label">ADDRESS</div>
							<div class="contact-value">
								<a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener">
									<?php echo esc_html( $full_address ); ?>
								</a>
							</div>
						</div>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $camp['email'] ) ) : ?>
					<div class="contact-list-item">
						<div class="contact-icon">
							<i class="fa-solid fa-envelope"></i>
						</div>
						<div class="contact-content">
							<div class="contact-label">EMAIL</div>
							<div class="contact-value">
								<a href="mailto:<?php echo esc_attr( $camp['email'] ); ?>">
									<?php echo esc_html( $camp['email'] ); ?>
								</a>
							</div>
						</div>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $camp['phone'] ) ) : ?>
					<div class="contact-list-item">
						<div class="contact-icon">
							<i class="fa-solid fa-phone"></i>
						</div>
						<div class="contact-content">
							<div class="contact-label">PHONE</div>
							<div class="contact-value">
								<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $camp['phone'] ) ); ?>">
									<?php echo esc_html( $camp['phone'] ); ?>
								</a>
							</div>
						</div>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $camp['website'] ) ) : ?>
					<div class="contact-list-item">
						<div class="contact-icon">
							<i class="fa-solid fa-globe"></i>
						</div>
						<div class="contact-content">
							<div class="contact-label">WEBSITE</div>
							<div class="contact-value">
								<a href="<?php echo esc_url( $camp['website'] ); ?>" target="_blank" rel="noopener">
									<?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', $camp['website'] ) ); ?>
								</a>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
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

	/**
	 * Gallery Shortcode
	 * Displays camp photos in a responsive gallery with lightbox
	 * 
	 * @param array $atts Shortcode attributes
	 * @return string Gallery HTML
	 */
	public function render_gallery( $atts ) {
		$atts = shortcode_atts( [
			'columns' => 'auto', // auto (1-5 based on count), or specific number 1-5
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp || empty( $camp['photos'] ) ) {
			return '';
		}
		
		// Parse photos (comma-separated URLs)
		$photos = explode( ',', $camp['photos'] );
		$photos = array_map( 'trim', $photos );
		$photos = array_filter( $photos );
		
		if ( empty( $photos ) ) {
			return '';
		}
		
		// Determine grid layout based on photo count
		$photo_count = count( $photos );
		
		// Custom grid logic
		// 1-4: single row
		// 5: 3 first, 2 second
		// 6: 3 first, 3 second
		// 7: 4 first, 3 second
		// 8: 4 first, 4 second
		// 9: 5 first, 4 second
		// 10: 5 first, 5 second
		if ( $photo_count <= 4 ) {
			$grid_class = 'single-row';
			$first_row = $photo_count;
		} elseif ( $photo_count === 5 ) {
			$grid_class = 'two-rows';
			$first_row = 3;
		} elseif ( $photo_count === 6 ) {
			$grid_class = 'two-rows';
			$first_row = 3;
		} elseif ( $photo_count === 7 ) {
			$grid_class = 'two-rows';
			$first_row = 4;
		} elseif ( $photo_count === 8 ) {
			$grid_class = 'two-rows';
			$first_row = 4;
		} elseif ( $photo_count === 9 ) {
			$grid_class = 'two-rows';
			$first_row = 5;
		} else { // 10
			$grid_class = 'two-rows';
			$first_row = 5;
		}
		
		$gallery_id = 'camp-gallery-' . $camp['id'];
		
		ob_start();
		?>
		<div class="camp-gallery-container">
			<div class="camp-gallery camp-gallery-<?php echo $grid_class; ?>" data-photo-count="<?php echo $photo_count; ?>">
				<?php foreach ( $photos as $index => $photo_url ) : ?>
					<div class="camp-gallery-item <?php echo $index < $first_row ? 'first-row' : 'second-row'; ?>">
						<a href="<?php echo esc_url( $photo_url ); ?>" 
						   class="camp-gallery-link" 
						   data-elementor-open-lightbox="yes"
						   data-elementor-lightbox-slideshow="<?php echo esc_attr( $gallery_id ); ?>"
						   data-elementor-lightbox-title="<?php echo esc_attr( $camp['camp_name'] ); ?>">
							<img src="<?php echo esc_url( $photo_url ); ?>" 
							     alt="<?php echo esc_attr( $camp['camp_name'] ); ?> Photo <?php echo $index + 1; ?>" 
							     class="camp-gallery-img">
							<div class="camp-gallery-overlay">
								<i class="fa-solid fa-search-plus"></i>
							</div>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render camp search page
	 */
	public function render_search( $atts ) {
		$atts = shortcode_atts( [
			'results_per_page' => 12,
			'class' => '',
		], $atts );
		
		global $wpdb;
		
		// Get filter options from database
		$states = $wpdb->get_col( "SELECT DISTINCT state FROM {$wpdb->prefix}camp_management WHERE state != '' ORDER BY state ASC" );
		$camp_types = $wpdb->get_col( "SELECT DISTINCT camp_types FROM {$wpdb->prefix}camp_management WHERE camp_types != ''" );
		$weeks = $wpdb->get_col( "SELECT DISTINCT weeks FROM {$wpdb->prefix}camp_management WHERE weeks != ''" );
		$activities = $wpdb->get_col( "SELECT DISTINCT activities FROM {$wpdb->prefix}camp_management WHERE activities != ''" );
		
		// Parse comma-separated values and create unique arrays
		$all_types = [];
		foreach ( $camp_types as $types ) {
			$split = array_map( 'trim', explode( ',', $types ) );
			$all_types = array_merge( $all_types, $split );
		}
		$all_types = array_unique( array_filter( $all_types ) );
		sort( $all_types );
		
		$all_weeks = [];
		foreach ( $weeks as $week ) {
			$split = array_map( 'trim', explode( ',', $week ) );
			$all_weeks = array_merge( $all_weeks, $split );
		}
		$all_weeks = array_unique( array_filter( $all_weeks ) );
		sort( $all_weeks );
		
		$all_activities = [];
		foreach ( $activities as $activity ) {
			$split = array_map( 'trim', explode( ',', $activity ) );
			$all_activities = array_merge( $all_activities, $split );
		}
		$all_activities = array_unique( array_filter( $all_activities ) );
		sort( $all_activities );
		
		// Get price range
		$price_range = $wpdb->get_row( "SELECT MIN(price) as min_price, MAX(price) as max_price FROM " . \CreativeDBS\CampMgmt\DB::table_sessions() . " WHERE price > 0" );
		$min_price = $price_range ? floor( $price_range->min_price ) : 0;
		$max_price = $price_range ? ceil( $price_range->max_price ) : 10000;
		
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-search-wrapper<?php echo $custom_class; ?>">
			<!-- Search Bar -->
			<div class="camp-search-bar">
				<div class="search-input-wrapper">
					<i class="fa fa-search"></i>
					<input type="text" 
					       id="camp-search-input" 
					       placeholder="Search camps by name, location, activities, or any keyword..." 
					       class="camp-search-input">
				</div>
				<button id="camp-search-btn" class="camp-search-btn">
					<i class="fa fa-search"></i> Search
				</button>
				<button id="camp-clear-btn" class="camp-clear-btn">
					<i class="fa fa-times"></i> Clear
				</button>
			</div>
			
			<!-- Main Content Area -->
			<div class="camp-search-content">
				<!-- Filters Sidebar -->
				<aside class="camp-filters-sidebar">
					<div class="filters-header">
						<h3><i class="fa fa-filter"></i> Filters</h3>
						<button class="filters-toggle-mobile">
							<i class="fa fa-sliders"></i> Toggle Filters
						</button>
					</div>
					
					<div class="filters-container">
						<!-- State Filter -->
						<div class="filter-group">
							<label class="filter-label">
								<i class="fa fa-map-marker-alt"></i> State
							</label>
							<select id="filter-state" class="filter-select">
								<option value="">All States</option>
								<?php foreach ( $states as $state ) : ?>
									<option value="<?php echo esc_attr( $state ); ?>">
										<?php echo esc_html( $state ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						
						<!-- Start Date Filter -->
						<div class="filter-group">
							<label class="filter-label">
								<i class="fa fa-calendar-alt"></i> Start Date (From)
							</label>
							<input type="date" id="filter-start-date" class="filter-input">
						</div>
						
						<!-- End Date Filter -->
						<div class="filter-group">
							<label class="filter-label">
								<i class="fa fa-calendar-check"></i> End Date (To)
							</label>
							<input type="date" id="filter-end-date" class="filter-input">
						</div>
						
						<!-- Price Range Filter -->
						<div class="filter-group">
							<label class="filter-label">
								<i class="fa fa-dollar-sign"></i> Price Range
							</label>
							<div class="price-range-display">
								$<span id="min-price-display"><?php echo number_format( $min_price ); ?></span> - 
								$<span id="max-price-display"><?php echo number_format( $max_price ); ?></span>
							</div>
							<div class="price-slider-wrapper">
								<input type="range" 
								       id="filter-min-price" 
								       class="price-slider" 
								       min="<?php echo $min_price; ?>" 
								       max="<?php echo $max_price; ?>" 
								       value="<?php echo $min_price; ?>"
								       data-label="min">
								<input type="range" 
								       id="filter-max-price" 
								       class="price-slider" 
								       min="<?php echo $min_price; ?>" 
								       max="<?php echo $max_price; ?>" 
								       value="<?php echo $max_price; ?>"
								       data-label="max">
							</div>
						</div>
						
						<!-- Camp Types Filter -->
						<div class="filter-group">
							<label class="filter-label">
								<i class="fa fa-campground"></i> Camp Type
							</label>
							<div class="filter-checkboxes">
								<?php foreach ( $all_types as $type ) : ?>
									<label class="filter-checkbox-label">
										<input type="checkbox" name="camp_type" value="<?php echo esc_attr( $type ); ?>">
										<span><?php echo esc_html( $type ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
						
						<!-- Weeks Filter -->
						<div class="filter-group">
							<label class="filter-label">
								<i class="fa fa-calendar-week"></i> Duration (Weeks)
							</label>
							<div class="filter-checkboxes">
								<?php foreach ( $all_weeks as $week ) : ?>
									<label class="filter-checkbox-label">
										<input type="checkbox" name="weeks" value="<?php echo esc_attr( $week ); ?>">
										<span><?php echo esc_html( $week ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
						
						<!-- Activities Filter -->
						<div class="filter-group">
							<label class="filter-label">
								<i class="fa fa-running"></i> Activities Offered
							</label>
							<div class="filter-checkboxes scrollable">
								<?php foreach ( $all_activities as $activity ) : ?>
									<label class="filter-checkbox-label">
										<input type="checkbox" name="activities" value="<?php echo esc_attr( $activity ); ?>">
										<span><?php echo esc_html( $activity ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
			</div>
		</aside>

				<!-- Results Area -->
				<div class="camp-results-area">
					<!-- Results Header -->
					<div class="results-header">
						<div class="results-count">
							Showing <span id="results-count">0</span> camps
						</div>
						<div class="results-sort">
							<label for="sort-by">Sort by:</label>
							<select id="sort-by" class="sort-select">
								<option value="random">Random</option>
								<option value="name_asc">Name (A-Z)</option>
								<option value="name_desc">Name (Z-A)</option>
								<option value="rating_desc">Highest Rated</option>
								<option value="rating_asc">Lowest Rated</option>
								<option value="price_asc">Lowest Price</option>
								<option value="price_desc">Highest Price</option>
							</select>
						</div>
					</div>
					
					<!-- Loading Indicator -->
					<div id="search-loading" class="search-loading" style="display: none;">
						<i class="fa fa-spinner fa-spin"></i> Loading camps...
					</div>
					
					<!-- Results Grid -->
					<div id="camp-results-grid" class="camp-results-grid">
						<!-- Results will be loaded via AJAX -->
					</div>
					
					<!-- No Results Message -->
					<div id="no-results" class="no-results" style="display: none;">
						<i class="fa fa-search"></i>
						<h3>No camps found</h3>
						<p>Try adjusting your filters or search terms to find what you're looking for.</p>
					</div>
					
					<!-- Load More Button -->
					<div class="load-more-wrapper">
						<button id="load-more-btn" class="load-more-btn" style="display: none;">
							<i class="fa fa-plus-circle"></i> Load More Camps
						</button>
					</div>
				</div>
			</div>
		</div>
		
		<script>
		// Pass data to JavaScript
		var campSearchData = {
			ajaxUrl: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			resultsPerPage: <?php echo intval( $atts['results_per_page'] ); ?>,
			minPrice: <?php echo $min_price; ?>,
			maxPrice: <?php echo $max_price; ?>
		};
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX handler for camp search
	 */
	public function ajax_camp_search() {
		global $wpdb;
		
		// Get search parameters
		$search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
		$state = isset( $_POST['state'] ) ? sanitize_text_field( $_POST['state'] ) : '';
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
		$min_price = isset( $_POST['min_price'] ) ? intval( $_POST['min_price'] ) : 0;
		$max_price = isset( $_POST['max_price'] ) ? intval( $_POST['max_price'] ) : 999999;
		$camp_types = isset( $_POST['camp_types'] ) ? $_POST['camp_types'] : [];
		$weeks = isset( $_POST['weeks'] ) ? $_POST['weeks'] : [];
		$activities = isset( $_POST['activities'] ) ? $_POST['activities'] : [];
		$sort_by = isset( $_POST['sort_by'] ) ? sanitize_text_field( $_POST['sort_by'] ) : 'random';
		$page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 12;
		
		$offset = ( $page - 1 ) * $per_page;
		
		// Build query
		$where = [ '1=1' ];
		$join = '';
		
		// Search across all camp data
		if ( ! empty( $search ) ) {
			$search_like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = $wpdb->prepare(
				"(camp_name LIKE %s OR city LIKE %s OR state LIKE %s OR description LIKE %s OR activities LIKE %s OR camp_types LIKE %s OR weeks LIKE %s OR additional_info LIKE %s)",
				$search_like, $search_like, $search_like, $search_like, $search_like, $search_like, $search_like, $search_like
			);
		}
		
		// State filter
		if ( ! empty( $state ) ) {
			$where[] = $wpdb->prepare( "state = %s", $state );
		}
		
		// Date filters (operational dates)
		if ( ! empty( $start_date ) ) {
			$where[] = $wpdb->prepare( "start_date >= %s", $start_date );
		}
		if ( ! empty( $end_date ) ) {
			$where[] = $wpdb->prepare( "end_date <= %s", $end_date );
		}
		
		// Camp types filter
		if ( ! empty( $camp_types ) && is_array( $camp_types ) ) {
			$type_conditions = [];
			foreach ( $camp_types as $type ) {
				$type_conditions[] = $wpdb->prepare( "camp_types LIKE %s", '%' . $wpdb->esc_like( $type ) . '%' );
			}
			$where[] = '(' . implode( ' OR ', $type_conditions ) . ')';
		}
		
		// Weeks filter
		if ( ! empty( $weeks ) && is_array( $weeks ) ) {
			$week_conditions = [];
			foreach ( $weeks as $week ) {
				$week_conditions[] = $wpdb->prepare( "weeks LIKE %s", '%' . $wpdb->esc_like( $week ) . '%' );
			}
			$where[] = '(' . implode( ' OR ', $week_conditions ) . ')';
		}
		
		// Activities filter
		if ( ! empty( $activities ) && is_array( $activities ) ) {
			$activity_conditions = [];
			foreach ( $activities as $activity ) {
				$activity_conditions[] = $wpdb->prepare( "activities LIKE %s", '%' . $wpdb->esc_like( $activity ) . '%' );
			}
			$where[] = '(' . implode( ' OR ', $activity_conditions ) . ')';
		}
		
		// Price filter - join with sessions table
		if ( $min_price > 0 || $max_price < 999999 ) {
			$join = " LEFT JOIN " . \CreativeDBS\CampMgmt\DB::table_sessions() . " s ON c.id = s.camp_id";
			$where[] = $wpdb->prepare( "(s.price >= %d AND s.price <= %d)", $min_price, $max_price );
		}
		
		// Sorting
		$order_by = 'RAND()'; // Default random
		switch ( $sort_by ) {
			case 'name_asc':
				$order_by = 'c.camp_name ASC';
				break;
			case 'name_desc':
				$order_by = 'c.camp_name DESC';
				break;
			case 'rating_desc':
				$order_by = 'c.rating DESC, c.camp_name ASC';
				break;
			case 'rating_asc':
				$order_by = 'c.rating ASC, c.camp_name ASC';
				break;
			case 'price_asc':
				if ( empty( $join ) ) {
					$join = " LEFT JOIN " . \CreativeDBS\CampMgmt\DB::table_sessions() . " s ON c.id = s.camp_id";
				}
				$order_by = 'MIN(s.price) ASC';
				break;
			case 'price_desc':
				if ( empty( $join ) ) {
					$join = " LEFT JOIN " . \CreativeDBS\CampMgmt\DB::table_sessions() . " s ON c.id = s.camp_id";
				}
				$order_by = 'MAX(s.price) DESC';
				break;
		}
		
		$where_clause = implode( ' AND ', $where );
		
		// Get total count
		$count_query = "SELECT COUNT(DISTINCT c.id) FROM {$wpdb->prefix}camp_management c {$join} WHERE {$where_clause}";
		$total_results = $wpdb->get_var( $count_query );
		
		// Get camps
		$query = "SELECT DISTINCT c.* FROM {$wpdb->prefix}camp_management c {$join} WHERE {$where_clause} GROUP BY c.id ORDER BY {$order_by} LIMIT %d OFFSET %d";
		$camps = $wpdb->get_results( $wpdb->prepare( $query, $per_page, $offset ), ARRAY_A );
		
		// Format camp data for frontend
		$formatted_camps = [];
		foreach ( $camps as $camp ) {
			// Get min/max price for this camp
			$prices = $wpdb->get_row( $wpdb->prepare(
				"SELECT MIN(price) as min_price, MAX(price) as max_price FROM " . \CreativeDBS\CampMgmt\DB::table_sessions() . " WHERE camp_id = %d AND price > 0",
				$camp['id']
			) );
			
			// Get page URL if exists
			$page_query = $wpdb->get_var( $wpdb->prepare(
				"SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'page' AND post_status = 'publish' AND ID IN (SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = 'camp_id' AND meta_value = %d) LIMIT 1",
				$camp['id']
			) );
			
			$formatted_camps[] = [
				'id' => $camp['id'],
				'name' => $camp['camp_name'],
				'city' => $camp['city'],
				'state' => $camp['state'],
				'logo' => $camp['logo_url'],
				'rating' => $camp['rating'],
				'camp_types' => $camp['camp_types'],
				'min_price' => $prices ? $prices->min_price : null,
				'max_price' => $prices ? $prices->max_price : null,
				'url' => $page_query ? get_permalink( $page_query ) : '#',
			];
		}
		
		wp_send_json_success( [
			'camps' => $formatted_camps,
			'total' => $total_results,
			'page' => $page,
			'has_more' => ( $offset + $per_page ) < $total_results,
		] );
	}

	/**
	 * Render social media links as badges
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function render_social_media( $atts ) {
		$atts = shortcode_atts( [
			'class' => '',
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		// Get social media links from JSON
		$social_links = ! empty( $camp['social_media_links'] ) ? json_decode( $camp['social_media_links'], true ) : [];
		
		if ( empty( $social_links ) || ! is_array( $social_links ) ) {
			return '';
		}
		
		// Filter out empty links
		$social_links = array_filter( $social_links );
		
		if ( empty( $social_links ) ) {
			return '';
		}
		
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		
		ob_start();
		?>
		<div class="camp-section camp-social-media<?php echo $custom_class; ?>">
			<div class="social-links-container">
				<?php foreach ( $social_links as $key => $url ) : ?>
					<?php if ( ! empty( $url ) ) : 
						// Extract platform name from URL
						$platform = $this->extract_platform_from_url( $url );
						$platform_lower = strtolower( $platform );
					?>
						<a href="<?php echo esc_url( $url ); ?>" class="social-badge social-<?php echo esc_attr( $platform_lower ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $platform ); ?>">
							<?php
							// Icon mapping for common platforms
							$icons = [
								'facebook' => 'fa fa-facebook',
								'twitter' => 'fa fa-twitter',
								'x' => 'fa fa-twitter',
								'instagram' => 'fa fa-instagram',
								'youtube' => 'fa fa-youtube',
								'linkedin' => 'fa fa-linkedin',
								'tiktok' => 'fa fa-music',
								'pinterest' => 'fa fa-pinterest',
								'snapchat' => 'fa fa-snapchat',
							];
							$icon_class = isset( $icons[ $platform_lower ] ) ? $icons[ $platform_lower ] : 'fa fa-link';
							?>
							<i class="<?php echo esc_attr( $icon_class ); ?>"></i>
							<span class="platform-name"><?php echo esc_html( $platform ); ?></span>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render video embed (responsive, no autoplay)
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function render_video( $atts ) {
		$atts = shortcode_atts( [
			'class' => '',
			'aspect_ratio' => '16-9', // 16-9, 4-3
		], $atts );
		
		$camp = $this->get_camp_data();
		if ( ! $camp ) {
			return '';
		}
		
		$video_url = ! empty( $camp['video_url'] ) ? trim( $camp['video_url'] ) : '';
		
		if ( empty( $video_url ) ) {
			return '';
		}
		
		// Convert various video URLs to embed format
		$embed_url = $this->convert_to_embed_url( $video_url );
		
		if ( empty( $embed_url ) ) {
			return '';
		}
		
		$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		$aspect_class = 'aspect-' . esc_attr( $atts['aspect_ratio'] );
		
		ob_start();
		?>
		<div class="camp-section camp-video<?php echo $custom_class; ?>">
			<div class="video-wrapper <?php echo $aspect_class; ?>">
				<iframe 
					src="<?php echo esc_url( $embed_url ); ?>" 
					frameborder="0" 
					allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" 
					allowfullscreen
					loading="lazy"
				></iframe>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Convert video URL to embed format and disable autoplay
	 *
	 * @param string $url Video URL
	 * @return string Embed URL or empty string
	 */
	private function convert_to_embed_url( $url ) {
		// YouTube
		if ( preg_match( '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches ) ) {
			return 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0&modestbranding=1';
		}
		
		// Vimeo
		if ( preg_match( '/vimeo\.com\/(\d+)/', $url, $matches ) ) {
			return 'https://player.vimeo.com/video/' . $matches[1] . '?title=0&byline=0&portrait=0';
		}
		
		// If already an embed URL, ensure no autoplay
		if ( strpos( $url, 'youtube.com/embed/' ) !== false ) {
			$url = remove_query_arg( 'autoplay', $url );
			$url = add_query_arg( [ 'autoplay' => '0', 'rel' => '0', 'modestbranding' => '1' ], $url );
			return $url;
		}
		
		if ( strpos( $url, 'player.vimeo.com/video/' ) !== false ) {
			$url = remove_query_arg( 'autoplay', $url );
			$url = add_query_arg( [ 'autoplay' => '0' ], $url );
			return $url;
		}
		
		// Return as-is if unrecognized format (could be other embed)
		return $url;
	}

	/**
	 * Extract platform name from social media URL
	 *
	 * @param string $url Social media URL
	 * @return string Platform name
	 */
	private function extract_platform_from_url( $url ) {
		$url = strtolower( $url );
		
		if ( strpos( $url, 'facebook.com' ) !== false || strpos( $url, 'fb.com' ) !== false ) {
			return 'Facebook';
		}
		if ( strpos( $url, 'twitter.com' ) !== false ) {
			return 'Twitter';
		}
		if ( strpos( $url, 'x.com' ) !== false ) {
			return 'X';
		}
		if ( strpos( $url, 'instagram.com' ) !== false ) {
			return 'Instagram';
		}
		if ( strpos( $url, 'youtube.com' ) !== false || strpos( $url, 'youtu.be' ) !== false ) {
			return 'YouTube';
		}
		if ( strpos( $url, 'linkedin.com' ) !== false ) {
			return 'LinkedIn';
		}
		if ( strpos( $url, 'tiktok.com' ) !== false ) {
			return 'TikTok';
		}
		if ( strpos( $url, 'pinterest.com' ) !== false ) {
			return 'Pinterest';
		}
		if ( strpos( $url, 'snapchat.com' ) !== false ) {
			return 'Snapchat';
		}
		
		// Default: try to extract domain name
		$parsed = parse_url( $url );
		if ( isset( $parsed['host'] ) ) {
			$host = str_replace( 'www.', '', $parsed['host'] );
			$parts = explode( '.', $host );
			return ucfirst( $parts[0] );
		}
		
		return 'Link';
	}
}


// Initialize - safe for plugin updates
if ( ! isset( $GLOBALS['camp_frontend_initialized'] ) ) {
	$GLOBALS['camp_frontend_initialized'] = true;
	new Camp_Frontend();
}
