<?php
/**
 * Featured Camps Frontend Shortcodes.
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt\PublicArea;

use CreativeDBS\CampMgmt\DB;

defined( 'ABSPATH' ) || exit;

class Featured_Camps_Frontend {

	public function __construct() {
		// Register shortcodes
		add_shortcode( 'featured_camps', [ $this, 'render_featured_camps' ] );
		add_shortcode( 'best_day_camps', [ $this, 'render_best_day_camps' ] );
		add_shortcode( 'best_overnight_camps', [ $this, 'render_best_overnight_camps' ] );
		add_shortcode( 'best_girls_camps', [ $this, 'render_best_girls_camps' ] );
		add_shortcode( 'best_boys_camps', [ $this, 'render_best_boys_camps' ] );
		add_shortcode( 'latest_camps', [ $this, 'render_latest_camps' ] );
		add_shortcode( 'single_camp', [ $this, 'render_single_camp' ] );

		// Enqueue styles
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}

	/**
	 * Enqueue frontend styles
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			'featured-camps',
			plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/featured-camps.css',
			[],
			CDBS_CAMP_VERSION
		);
	}

	/**
	 * Featured camps shortcode
	 */
	public function render_featured_camps( $atts ) {
		$atts = shortcode_atts( [ 'limit' => 100 ], $atts );
		return $this->render_camps( 'is_featured', 'featured_order', intval( $atts['limit'] ) );
	}

	/**
	 * Best day camps shortcode
	 */
	public function render_best_day_camps( $atts ) {
		$atts = shortcode_atts( [ 'limit' => 100 ], $atts );
		return $this->render_camps( 'is_best_day', 'day_order', intval( $atts['limit'] ) );
	}

	/**
	 * Best overnight camps shortcode
	 */
	public function render_best_overnight_camps( $atts ) {
		$atts = shortcode_atts( [ 'limit' => 100 ], $atts );
		return $this->render_camps( 'is_best_overnight', 'overnight_order', intval( $atts['limit'] ) );
	}

	/**
	 * Best all-girls camps shortcode
	 */
	public function render_best_girls_camps( $atts ) {
		$atts = shortcode_atts( [ 'limit' => 100 ], $atts );
		return $this->render_camps( 'is_best_girls', 'girls_order', intval( $atts['limit'] ) );
	}

	/**
	 * Best all-boys camps shortcode
	 */
	public function render_best_boys_camps( $atts ) {
		$atts = shortcode_atts( [ 'limit' => 100 ], $atts );
		return $this->render_camps( 'is_best_boys', 'boys_order', intval( $atts['limit'] ) );
	}

	/**
	 * Latest camps shortcode - shows most recently added approved camps
	 */
	public function render_latest_camps( $atts ) {
		$atts = shortcode_atts( [ 'limit' => 3 ], $atts );
		return $this->render_latest_camps_query( intval( $atts['limit'] ) );
	}

	/**
	 * Single camp shortcode - displays one specific camp card
	 * Usage: [single_camp id="123"] or [single_camp slug="camp-laney"]
	 */
	public function render_single_camp( $atts ) {
		global $wpdb;
		$table = DB::table_camps();

		// Parse attributes
		$atts = shortcode_atts( [
			'id' => 0,
			'slug' => '',
		], $atts );

		// Determine which field to search by
		if ( ! empty( $atts['id'] ) ) {
			$camp = $wpdb->get_row( $wpdb->prepare(
				"SELECT id, camp_name, city, state, logo, photos, internal_link, rating, 
				       opening_day, closing_day, minprice_2026, maxprice_2026
				FROM {$table} 
				WHERE id = %d 
				AND approved = 1 
				LIMIT 1",
				intval( $atts['id'] )
			) );
		} elseif ( ! empty( $atts['slug'] ) ) {
			$camp = $wpdb->get_row( $wpdb->prepare(
				"SELECT id, camp_name, city, state, logo, photos, internal_link, rating, 
				       opening_day, closing_day, minprice_2026, maxprice_2026
				FROM {$table} 
				WHERE slug = %s 
				AND approved = 1 
				LIMIT 1",
				sanitize_title( $atts['slug'] )
			) );
		} else {
			return '<p class="no-featured-camps">Please specify a camp ID or slug.</p>';
		}

		if ( ! $camp ) {
			return '<p class="no-featured-camps">Camp not found.</p>';
		}

		// Enrich camp with taxonomy data
		$camp->camp_types = $wpdb->get_col( $wpdb->prepare(
			"SELECT t.name FROM {$wpdb->prefix}camp_type_terms t
			 INNER JOIN {$wpdb->prefix}camp_management_types_map m ON t.id = m.type_id
			 WHERE m.camp_id = %d
			 ORDER BY t.sort_order ASC, t.name ASC",
			$camp->id
		) );
		
		$camp->weeks = $wpdb->get_col( $wpdb->prepare(
			"SELECT t.name FROM {$wpdb->prefix}camp_week_terms t
			 INNER JOIN {$wpdb->prefix}camp_management_weeks_map m ON t.id = m.week_id
			 WHERE m.camp_id = %d
			 ORDER BY t.sort_order ASC, t.name ASC",
			$camp->id
		) );
		
		$camp->activities_total = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}camp_management_activities_map
			 WHERE camp_id = %d",
			$camp->id
		) );
		
		$camp->activities = $wpdb->get_col( $wpdb->prepare(
			"SELECT t.name FROM {$wpdb->prefix}camp_activity_terms t
			 INNER JOIN {$wpdb->prefix}camp_management_activities_map m ON t.id = m.activity_id
			 WHERE m.camp_id = %d
			 ORDER BY t.sort_order ASC, t.name ASC
			 LIMIT 4",
			$camp->id
		) );

		// Render single camp card
		ob_start();
		?>
		<div class="featured-camps-grid" data-count="1" data-columns="1">
			<?php $this->render_camp_card( $camp ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render camps grid
	 */
	private function render_camps( $column, $order_column, $limit ) {
		global $wpdb;
		$table = DB::table_camps();

		// Get camps for this category
		$camps = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, camp_name, city, state, logo, photos, internal_link, rating, 
			       opening_day, closing_day, minprice_2026, maxprice_2026
			FROM {$table} 
			WHERE {$column} = 1 
			AND approved = 1 
			ORDER BY {$order_column} ASC, camp_name ASC 
			LIMIT %d",
			$limit
		) );

		// Enrich camps with taxonomy data
		foreach ( $camps as $camp ) {
			// Get camp types
			$camp->camp_types = $wpdb->get_col( $wpdb->prepare(
				"SELECT t.name FROM {$wpdb->prefix}camp_type_terms t
				 INNER JOIN {$wpdb->prefix}camp_management_types_map m ON t.id = m.type_id
				 WHERE m.camp_id = %d
				 ORDER BY t.sort_order ASC, t.name ASC",
				$camp->id
			) );
			
			// Get weeks
			$camp->weeks = $wpdb->get_col( $wpdb->prepare(
				"SELECT t.name FROM {$wpdb->prefix}camp_week_terms t
				 INNER JOIN {$wpdb->prefix}camp_management_weeks_map m ON t.id = m.week_id
				 WHERE m.camp_id = %d
				 ORDER BY t.sort_order ASC, t.name ASC",
				$camp->id
			) );
			
			// Get total activity count
			$camp->activities_total = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}camp_management_activities_map
				 WHERE camp_id = %d",
				$camp->id
			) );
			
			// Get activities (first 4 only)
			$camp->activities = $wpdb->get_col( $wpdb->prepare(
				"SELECT t.name FROM {$wpdb->prefix}camp_activity_terms t
				 INNER JOIN {$wpdb->prefix}camp_management_activities_map m ON t.id = m.activity_id
				 WHERE m.camp_id = %d
				 ORDER BY t.sort_order ASC, t.name ASC
				 LIMIT 4",
				$camp->id
			) );
		}

		if ( empty( $camps ) ) {
			return '<p class="no-featured-camps">No camps available at this time.</p>';
		}

		$count = count( $camps );
		// Determine grid columns: 1-3 = exact count, 4 = 4 cols, 5+ = 3 cols max
		$columns = ( $count <= 3 ) ? $count : ( ( $count === 4 ) ? 4 : 3 );

		ob_start();
		?>
		<div class="featured-camps-grid" data-count="<?php echo esc_attr( $count ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>">
			<?php foreach ( $camps as $camp ) : ?>
				<?php $this->render_camp_card( $camp ); ?>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render latest camps query
	 */
	private function render_latest_camps_query( $limit ) {
		global $wpdb;
		$table = DB::table_camps();

		// Get latest camps by creation date
		$camps = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, camp_name, city, state, logo, photos, internal_link, rating, 
			       opening_day, closing_day, minprice_2026, maxprice_2026, created_at
			FROM {$table} 
			WHERE approved = 1 
			ORDER BY created_at DESC, id DESC 
			LIMIT %d",
			$limit
		) );

		// Enrich camps with taxonomy data
		foreach ( $camps as $camp ) {
			// Get camp types
			$camp->camp_types = $wpdb->get_col( $wpdb->prepare(
				"SELECT t.name FROM {$wpdb->prefix}camp_type_terms t
				 INNER JOIN {$wpdb->prefix}camp_management_types_map m ON t.id = m.type_id
				 WHERE m.camp_id = %d
				 ORDER BY t.sort_order ASC, t.name ASC",
				$camp->id
			) );
			
			// Get weeks
			$camp->weeks = $wpdb->get_col( $wpdb->prepare(
				"SELECT t.name FROM {$wpdb->prefix}camp_week_terms t
				 INNER JOIN {$wpdb->prefix}camp_management_weeks_map m ON t.id = m.week_id
				 WHERE m.camp_id = %d
				 ORDER BY t.sort_order ASC, t.name ASC",
				$camp->id
			) );
			
			// Get total activity count
			$camp->activities_total = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}camp_management_activities_map
				 WHERE camp_id = %d",
				$camp->id
			) );
			
			// Get activities (first 4 only)
			$camp->activities = $wpdb->get_col( $wpdb->prepare(
				"SELECT t.name FROM {$wpdb->prefix}camp_activity_terms t
				 INNER JOIN {$wpdb->prefix}camp_management_activities_map m ON t.id = m.activity_id
				 WHERE m.camp_id = %d
				 ORDER BY t.sort_order ASC, t.name ASC
				 LIMIT 4",
				$camp->id
			) );
		}

		if ( empty( $camps ) ) {
			return '<p class="no-featured-camps">No camps available at this time.</p>';
		}

		$count = count( $camps );
		// Determine grid columns: 1-3 = exact count, 4 = 4 cols, 5+ = 3 cols max
		$columns = ( $count <= 3 ) ? $count : ( ( $count === 4 ) ? 4 : 3 );

		ob_start();
		?>
		<div class="featured-camps-grid" data-count="<?php echo esc_attr( $count ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>">
			<?php foreach ( $camps as $camp ) : ?>
				<?php $this->render_camp_card( $camp ); ?>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render individual camp card
	 */
	private function render_camp_card( $camp ) {
		// Get first photo for header
		$photos = ! empty( $camp->photos ) ? explode( ',', $camp->photos ) : [];
		$header_image = ! empty( $photos[0] ) ? trim( $photos[0] ) : '';

		// Get camp URL
		$camp_url = ! empty( $camp->internal_link ) ? $camp->internal_link : '#';

		?>
		<div class="featured-camp-card">
			<div class="featured-card-header">
				<?php if ( $header_image ) : ?>
					<img src="<?php echo esc_url( $header_image ); ?>" alt="<?php echo esc_attr( $camp->camp_name ); ?>" />
					<div class="featured-header-overlay"></div>
				<?php endif; ?>
				
				<?php if ( ! empty( $camp->logo ) ) : ?>
					<div class="featured-logo-circle">
						<img src="<?php echo esc_url( $camp->logo ); ?>" alt="<?php echo esc_attr( $camp->camp_name ); ?>" />
					</div>
				<?php else : ?>
					<div class="featured-logo-circle featured-logo-placeholder">
						<span><?php echo esc_html( substr( $camp->camp_name, 0, 1 ) ); ?></span>
					</div>
				<?php endif; ?>
			</div>
			
			<div class="featured-card-body">
					<h3 class="featured-camp-name"><?php echo esc_html( $camp->camp_name ); ?></h3>
					
					<?php if ( $camp->city || $camp->state ) : ?>
						<p class="featured-camp-location">
							<?php 
							$location = array_filter( [ $camp->city, $camp->state ] );
							echo esc_html( implode( ', ', $location ) );
							?>
						</p>
					<?php endif; ?>
					
					<?php if ( ! empty( $camp->camp_types ) ) : ?>
						<div class="featured-meta-row">
							<span class="featured-meta-label">Camp Types:</span>
							<div class="featured-badges">
								<?php foreach ( $camp->camp_types as $type ) : ?>
									<span class="featured-badge"><?php echo esc_html( $type ); ?></span>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
										<?php if ( ! empty( $camp->weeks ) ) : ?>
					<div class="featured-meta-row">
						<span class="featured-meta-label">Weeks:</span>
						<div class="featured-badges">
							<?php foreach ( $camp->weeks as $week ) : ?>
								<span class="featured-badge"><?php echo esc_html( $week ); ?></span>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
					
					<?php if ( ! empty( $camp->activities ) ) : ?>
					<div class="featured-meta-row">
						<span class="featured-meta-label">Activities:</span>
						<div class="featured-badges">
							<?php foreach ( $camp->activities as $activity ) : ?>
								<span class="featured-badge"><?php echo esc_html( $activity ); ?></span>
							<?php endforeach; ?>
							<?php if ( $camp->activities_total > 4 ) : ?>
								<span class="featured-badge featured-badge-plus">+<?php echo esc_html( $camp->activities_total - 4 ); ?></span>
							<?php endif; ?>
						</div>
					</div>
					<?php endif; ?>
										<div class="featured-dates-prices">
						<?php if ( $camp->opening_day ) : ?>
							<div class="featured-detail">
								<strong>Opening Day:</strong> <?php echo esc_html( date( 'M j, Y', strtotime( $camp->opening_day ) ) ); ?>
							</div>
						<?php endif; ?>
						
						<?php if ( $camp->closing_day ) : ?>
							<div class="featured-detail">
								<strong>Closing Day:</strong> <?php echo esc_html( date( 'M j, Y', strtotime( $camp->closing_day ) ) ); ?>
							</div>
						<?php endif; ?>
						
						<?php if ( $camp->minprice_2026 ) : ?>
							<div class="featured-detail">
								<strong>Lowest Rate:</strong> $<?php echo esc_html( number_format( $camp->minprice_2026, 2 ) ); ?>
							</div>
						<?php endif; ?>
						
						<?php if ( $camp->maxprice_2026 ) : ?>
							<div class="featured-detail">
								<strong>Highest Rate:</strong> $<?php echo esc_html( number_format( $camp->maxprice_2026, 2 ) ); ?>
							</div>
						<?php endif; ?>
					</div>
					
					<a href="<?php echo esc_url( $camp_url ); ?>" class="featured-visit-btn">
						Visit Camp Page
					</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render star rating with circles
	 */
	private function render_stars( $rating ) {
		$full_stars = floor( $rating );
		$empty_stars = 5 - $full_stars;

		// Full stars (filled circles)
		for ( $i = 0; $i < $full_stars; $i++ ) {
			echo '<span class="camp-star">●</span>';
		}
		
		// Empty stars
		for ( $i = 0; $i < $empty_stars; $i++ ) {
			echo '<span class="camp-star empty">●</span>';
		}
	}
}
