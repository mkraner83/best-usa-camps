<?php
/**
 * Simple Camps List - Server-side rendering only
 */
namespace CreativeDBS\CampMgmt\PublicArea;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Camps_List {
	
	public function __construct() {
		add_shortcode( 'camps_list', [ $this, 'render' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}
	
	public function enqueue_styles() {
		global $post;
		
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'camps_list' ) ) {
			wp_enqueue_style(
				'camps-list',
				plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/camps-list.css',
				[],
				CDBS_CAMP_VERSION
			);
		}
	}
	
	public function render( $atts ) {
		$atts = shortcode_atts( [
			'columns' => '3',
			'show_on_load' => 'yes',
		], $atts );
		
		global $wpdb;
		
		// Get search query
		$search_query = isset( $_GET['camp_search'] ) ? sanitize_text_field( $_GET['camp_search'] ) : '';
		
		// Get sort parameter
		$sort_by = isset( $_GET['camp_sort'] ) ? sanitize_text_field( $_GET['camp_sort'] ) : 'random';
		
		// Get filter parameters
		$filter_state = isset( $_GET['filter_state'] ) ? sanitize_text_field( $_GET['filter_state'] ) : '';
		$filter_type = isset( $_GET['filter_type'] ) ? intval( $_GET['filter_type'] ) : 0;
		$filter_duration = isset( $_GET['filter_duration'] ) ? intval( $_GET['filter_duration'] ) : 0;
		$filter_price_min = isset( $_GET['filter_price_min'] ) ? floatval( $_GET['filter_price_min'] ) : '';
		$filter_price_max = isset( $_GET['filter_price_max'] ) ? floatval( $_GET['filter_price_max'] ) : '';
		$filter_date_from = isset( $_GET['filter_date_from'] ) ? sanitize_text_field( $_GET['filter_date_from'] ) : '';
		$filter_date_to = isset( $_GET['filter_date_to'] ) ? sanitize_text_field( $_GET['filter_date_to'] ) : '';
		
		// Get pagination parameter
		$current_page = isset( $_GET['camp_page'] ) ? max( 1, intval( $_GET['camp_page'] ) ) : 1;
		$per_page = 20;
		
		// Check if any filters/search are active
		$has_active_filters = ! empty( $search_query ) || ! empty( $filter_state ) || ! empty( $filter_type ) || 
		                      ! empty( $filter_duration ) || $filter_price_min !== '' || $filter_price_max !== '' || 
		                      ! empty( $filter_date_from ) || ! empty( $filter_date_to );
		
		// If show_on_load is 'no' and no filters are active, hide camps initially
		$hide_camps_initially = ( $atts['show_on_load'] === 'no' && ! $has_active_filters );
		
		// Build WHERE clause
		$where = "WHERE approved = 1";
		$prepare_args = [];
		
		if ( ! empty( $search_query ) ) {
			$like = '%' . $wpdb->esc_like( $search_query ) . '%';
			$where .= " AND (
				camp_name LIKE %s OR
				city LIKE %s OR
				state LIKE %s OR
				zip LIKE %s OR
				address LIKE %s OR
				about_camp LIKE %s OR
				activities LIKE %s OR
				camp_directors LIKE %s OR
				email LIKE %s OR
				phone LIKE %s OR
				website LIKE %s
			)";
			$prepare_args = array_fill( 0, 11, $like );
		}
		
		// Add filter conditions
		if ( ! empty( $filter_state ) ) {
			$where .= " AND state = %s";
			$prepare_args[] = $filter_state;
		}
		
		// Price filter: Show camps that have ANY overlap with the selected range
		// Camp overlaps if: camp's minprice <= range_max AND camp's maxprice >= range_min
		if ( ! empty( $filter_price_min ) && ! empty( $filter_price_max ) ) {
			// Both min and max selected
			$where .= " AND minprice_2026 <= %f AND maxprice_2026 >= %f";
			$prepare_args[] = $filter_price_max; // camp's lowest price must be <= range max
			$prepare_args[] = $filter_price_min; // camp's highest price must be >= range min
		} elseif ( ! empty( $filter_price_min ) ) {
			// Only min selected: show camps that have prices >= min
			$where .= " AND maxprice_2026 >= %f";
			$prepare_args[] = $filter_price_min;
		} elseif ( ! empty( $filter_price_max ) ) {
			// Only max selected: show camps that have prices <= max
			$where .= " AND minprice_2026 <= %f";
			$prepare_args[] = $filter_price_max;
		}
		
		// Date filter: Show camps that are OPEN during the selected date range
		// Camp is open during range if: opening_day <= range_end AND closing_day >= range_start
		if ( ! empty( $filter_date_from ) && ! empty( $filter_date_to ) ) {
			// Both dates selected: camp must be open sometime during the range
			$where .= " AND opening_day <= %s AND closing_day >= %s";
			$prepare_args[] = $filter_date_to;   // opening before or on end date
			$prepare_args[] = $filter_date_from; // closing on or after start date
		} elseif ( ! empty( $filter_date_from ) ) {
			// Only start date: camp must still be open on or after this date
			$where .= " AND closing_day >= %s";
			$prepare_args[] = $filter_date_from;
		} elseif ( ! empty( $filter_date_to ) ) {
			// Only end date: camp must open on or before this date
			$where .= " AND opening_day <= %s";
			$prepare_args[] = $filter_date_to;
		}
		
		// Determine ORDER BY clause
		$order_by = "camp_name ASC";
		switch ( $sort_by ) {
			case 'name-asc':
				$order_by = "camp_name ASC";
				break;
			case 'name-desc':
				$order_by = "camp_name DESC";
				break;
			case 'price-high':
				$order_by = "maxprice_2026 DESC";
				break;
			case 'price-low':
				$order_by = "minprice_2026 ASC";
				break;
			case 'start-early':
				$order_by = "opening_day ASC";
				break;
			case 'start-late':
				$order_by = "opening_day DESC";
				break;
			case 'random':
			default:
				$order_by = "RAND()";
				break;
		}
		
// Build JOIN for camp type and duration filters
		$join = '';
		if ( ! empty( $filter_type ) && ! empty( $filter_duration ) ) {
			// Both type and duration filters
			$join = " INNER JOIN {$wpdb->prefix}camp_management_types_map ON {$wpdb->prefix}camp_management.id = {$wpdb->prefix}camp_management_types_map.camp_id";
			$join .= " INNER JOIN {$wpdb->prefix}camp_management_weeks_map ON {$wpdb->prefix}camp_management.id = {$wpdb->prefix}camp_management_weeks_map.camp_id";
			$where .= " AND {$wpdb->prefix}camp_management_types_map.type_id = %d";
			$where .= " AND {$wpdb->prefix}camp_management_weeks_map.week_id = %d";
			$prepare_args[] = $filter_type;
			$prepare_args[] = $filter_duration;
		} elseif ( ! empty( $filter_type ) ) {
			// Only type filter
			$join = " INNER JOIN {$wpdb->prefix}camp_management_types_map ON {$wpdb->prefix}camp_management.id = {$wpdb->prefix}camp_management_types_map.camp_id";
			$where .= " AND {$wpdb->prefix}camp_management_types_map.type_id = %d";
			$prepare_args[] = $filter_type;
		} elseif ( ! empty( $filter_duration ) ) {
			// Only duration filter
			$join = " INNER JOIN {$wpdb->prefix}camp_management_weeks_map ON {$wpdb->prefix}camp_management.id = {$wpdb->prefix}camp_management_weeks_map.camp_id";
			$where .= " AND {$wpdb->prefix}camp_management_weeks_map.week_id = %d";
			$prepare_args[] = $filter_duration;
		}
		
		// Get approved camps with all needed fields
		// Use subquery or GROUP BY to prevent duplicates from JOINs
		if ( ! empty( $join ) ) {
			// When using JOINs, we need to use GROUP BY to prevent duplicates
			$query = "SELECT {$wpdb->prefix}camp_management.id, camp_name, city, state, logo, opening_day, closing_day, minprice_2026, maxprice_2026, internal_link
				FROM {$wpdb->prefix}camp_management
				{$join}
				{$where}
				GROUP BY {$wpdb->prefix}camp_management.id
				ORDER BY {$order_by}";
		} else {
			// No JOINs, DISTINCT is fine
			$query = "SELECT DISTINCT {$wpdb->prefix}camp_management.id, camp_name, city, state, logo, opening_day, closing_day, minprice_2026, maxprice_2026, internal_link
				FROM {$wpdb->prefix}camp_management
				{$where}
				ORDER BY {$order_by}";
		}
		
		if ( ! empty( $prepare_args ) ) {
			$camps = $wpdb->get_results( $wpdb->prepare( $query, $prepare_args ), ARRAY_A );
		} else {
			$camps = $wpdb->get_results( $query, ARRAY_A );
		}
		
		// Always deduplicate to ensure no duplicate camp IDs (many-to-many JOINs can cause this)
		if ( ! empty( $camps ) ) {
			$camps_by_id = [];
			foreach ( $camps as $camp ) {
				// Keep only the first occurrence of each camp ID
				if ( ! isset( $camps_by_id[ $camp['id'] ] ) ) {
					$camps_by_id[ $camp['id'] ] = $camp;
				}
			}
			$camps = array_values( $camps_by_id );
		}
		
		// Calculate total camps and pagination
		$total_camps = count( $camps );
		$total_pages = ceil( $total_camps / $per_page );
		
		// Ensure current page is valid
		if ( $current_page > $total_pages && $total_pages > 0 ) {
			$current_page = $total_pages;
		}
		
		// Slice camps array for current page
		$offset = ( $current_page - 1 ) * $per_page;
		$camps_on_page = array_slice( $camps, $offset, $per_page );
		
		// Enrich camps with taxonomy data and camp page URL (only if we have camps)
		if ( ! empty( $camps_on_page ) ) {
			foreach ( $camps_on_page as &$camp ) {
				// Get camp types
				$camp['camp_types'] = $wpdb->get_col( $wpdb->prepare(
					"SELECT t.name FROM {$wpdb->prefix}camp_type_terms t
					INNER JOIN {$wpdb->prefix}camp_management_types_map m ON t.id = m.type_id
					WHERE m.camp_id = %d",
					$camp['id']
				) );
				
				// Get weeks
				$camp['weeks'] = $wpdb->get_col( $wpdb->prepare(
					"SELECT t.name FROM {$wpdb->prefix}camp_week_terms t
					INNER JOIN {$wpdb->prefix}camp_management_weeks_map m ON t.id = m.week_id
					WHERE m.camp_id = %d",
					$camp['id']
				) );
				
				// Get total activity count
				$camp['activities_total'] = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}camp_management_activities_map
					WHERE camp_id = %d",
					$camp['id']
				) );
				
				// Get activities (first 4 only)
				$camp['activities'] = $wpdb->get_col( $wpdb->prepare(
					"SELECT t.name FROM {$wpdb->prefix}camp_activity_terms t
					INNER JOIN {$wpdb->prefix}camp_management_activities_map m ON t.id = m.activity_id
					WHERE m.camp_id = %d
					LIMIT 4",
					$camp['id']
				) );
				
				// Use internal_link from database if available
				// Ensure it's a clean URL without inheriting query parameters
				if ( ! empty( $camp['internal_link'] ) ) {
					$url = $camp['internal_link'];
					// If it's a relative URL, convert to absolute
					if ( strpos( $url, 'http' ) !== 0 && $url !== '#' ) {
						$url = home_url( $url );
					}
					$camp['page_url'] = $url;
				} else {
					$camp['page_url'] = '#';
				}
			}
			unset($camp); // Destroy the reference to avoid issues in subsequent loops
		}
		
		$columns = intval( $atts['columns'] );
		if ( $columns < 2 || $columns > 4 ) {
			$columns = 3;
		}
		
		// Debug: Log camp IDs to check for duplicates
		if ( ! empty( $camps_on_page ) ) {
			error_log( 'Camps List - Total camps: ' . $total_camps );
			error_log( 'Camps List - Current page: ' . $current_page . ' of ' . $total_pages );
			error_log( 'Camps List - Camp IDs on page: ' . implode( ', ', array_column( $camps_on_page, 'id' ) ) );
		}
		
		ob_start();
		
		// Get unique states for filter
		$states = $wpdb->get_col( "SELECT DISTINCT state FROM {$wpdb->prefix}camp_management WHERE approved = 1 AND state != '' ORDER BY state ASC" );
		
		// Get camp types for filter
		$camp_types = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}camp_type_terms ORDER BY sort_order ASC, name ASC" );
		
		// Get durations/weeks for filter
		$durations = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}camp_week_terms ORDER BY sort_order ASC, name ASC" );
		
		?>
		<div class="camps-list">
			<!-- Search Bar -->
			<div class="camps-search-bar">
				<form method="get" action="" class="camps-search-form" id="camps-filter-form">
					<?php
					// Preserve existing query parameters (excluding camp_page for form submission)
					foreach ( $_GET as $key => $value ) {
						if ( ! in_array( $key, [ 'camp_search', 'camp_sort', 'filter_state', 'filter_type', 'filter_duration', 'filter_price_min', 'filter_price_max', 'filter_date_from', 'filter_date_to', 'camp_page' ] ) ) {
							echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
						}
					}
					?>
					<input 
						type="text" 
						name="camp_search" 
						class="camps-search-input" 
						placeholder="Search camps by name, location, activities..." 
						value="<?php echo esc_attr( $search_query ); ?>"
					>
					<button type="submit" class="camps-search-btn">Search</button>
					<a href="<?php echo esc_url( strtok( $_SERVER['REQUEST_URI'], '?' ) ); ?>#camps-filter-form" class="camps-reset-btn">Reset All</a>
				
					<!-- Filters Row -->
					<div class="camps-filters-row">
						<div class="filter-col">
							<label for="filter_state">State</label>
							<select name="filter_state" id="filter_state" class="camps-filter-select" onchange="this.form.submit()">
								<option value="">All States</option>
								<?php foreach ( $states as $state ) : ?>
									<option value="<?php echo esc_attr( $state ); ?>" <?php selected( $filter_state, $state ); ?>>
										<?php echo esc_html( $state ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						
						<div class="filter-col">
							<label for="filter_type">Camp Type</label>
							<select name="filter_type" id="filter_type" class="camps-filter-select" onchange="this.form.submit()">
								<option value="">All Types</option>
								<?php foreach ( $camp_types as $type ) : ?>
									<option value="<?php echo esc_attr( $type->id ); ?>" <?php selected( $filter_type, $type->id ); ?>>
										<?php echo esc_html( $type->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						
						<div class="filter-col">
							<label for="filter_duration">Duration</label>
							<select name="filter_duration" id="filter_duration" class="camps-filter-select" onchange="this.form.submit()">
								<option value="">All Durations</option>
								<?php foreach ( $durations as $duration ) : ?>
									<option value="<?php echo esc_attr( $duration->id ); ?>" <?php selected( $filter_duration, $duration->id ); ?>>
										<?php echo esc_html( $duration->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						
						<div class="filter-col">
							<label>Price Range</label>
							<div class="filter-price-group">
								<select name="filter_price_min" class="camps-filter-select-small" onchange="this.form.submit()">
									<option value="">Min $</option>
									<?php for ( $i = 0; $i <= 20000; $i += 1000 ) : ?>
										<option value="<?php echo $i; ?>" <?php selected( $filter_price_min, $i ); ?>>
											$<?php echo number_format( $i ); ?>
										</option>
									<?php endfor; ?>
								</select>
								<select name="filter_price_max" class="camps-filter-select-small" onchange="this.form.submit()">
									<option value="">Max $</option>
									<?php for ( $i = 0; $i <= 20000; $i += 1000 ) : ?>
										<option value="<?php echo $i; ?>" <?php selected( $filter_price_max, $i ); ?>>
											$<?php echo number_format( $i ); ?>
										</option>
									<?php endfor; ?>
								</select>
							</div>
						</div>
						
						<div class="filter-col">
							<label>Opening Dates</label>
							<div class="filter-date-group">
								<input 
									type="date" 
									name="filter_date_from" 
									class="camps-filter-input-small"
									value="<?php echo esc_attr( $filter_date_from ); ?>"
									onchange="this.form.submit()"
								>
								<input 
									type="date" 
									name="filter_date_to" 
									class="camps-filter-input-small"
									value="<?php echo esc_attr( $filter_date_to ); ?>"
									onchange="this.form.submit()"
								>
							</div>
						</div>
					</div>
					
					<?php
					// Build search criteria display
					$criteria = array();
					
					if ( ! empty( $search_query ) ) {
						$criteria[] = $search_query;
					}
					
					if ( ! empty( $filter_state ) ) {
						// Convert state abbreviation to full name
						$state_map = array(
							'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
							'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
							'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
							'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
							'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
							'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
							'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
							'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
							'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
							'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
							'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
							'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
							'WI' => 'Wisconsin', 'WY' => 'Wyoming'
						);
						$state_name = isset( $state_map[ $filter_state ] ) ? $state_map[ $filter_state ] : $filter_state;
						$criteria[] = $state_name;
					}
					
					if ( ! empty( $filter_type ) ) {
						$type_result = $wpdb->get_row( $wpdb->prepare(
							"SELECT name FROM {$wpdb->prefix}camp_type_terms WHERE id = %d",
							$filter_type
						) );
						if ( $type_result ) {
							$criteria[] = $type_result->name;
						}
					}
					
					if ( ! empty( $filter_duration ) ) {
						$duration_result = $wpdb->get_row( $wpdb->prepare(
							"SELECT name FROM {$wpdb->prefix}camp_week_terms WHERE id = %d",
							$filter_duration
						) );
						if ( $duration_result ) {
							$criteria[] = $duration_result->name;
						}
					}
					
					if ( ! empty( $filter_price_min ) || ! empty( $filter_price_max ) ) {
						if ( ! empty( $filter_price_min ) && ! empty( $filter_price_max ) ) {
							$criteria[] = '$' . number_format( $filter_price_min ) . ' - $' . number_format( $filter_price_max );
						} elseif ( ! empty( $filter_price_min ) ) {
							$criteria[] = 'From $' . number_format( $filter_price_min );
						} elseif ( ! empty( $filter_price_max ) ) {
							$criteria[] = 'Up to $' . number_format( $filter_price_max );
						}
					}
					
					if ( ! empty( $filter_date_from ) || ! empty( $filter_date_to ) ) {
						if ( ! empty( $filter_date_from ) && ! empty( $filter_date_to ) ) {
							$criteria[] = date( 'M j', strtotime( $filter_date_from ) ) . ' - ' . date( 'M j, Y', strtotime( $filter_date_to ) );
						} elseif ( ! empty( $filter_date_from ) ) {
							$criteria[] = 'From ' . date( 'M j, Y', strtotime( $filter_date_from ) );
						} elseif ( ! empty( $filter_date_to ) ) {
							$criteria[] = 'Until ' . date( 'M j, Y', strtotime( $filter_date_to ) );
						}
					}
					
					$has_criteria = ! empty( $criteria );
					$criteria_text = $has_criteria ? implode( ', ', $criteria ) : '';
					?>
					
					<!-- Sort and Count Row -->
					<div class="camps-sort-row">
						<div class="sort-group">
							<?php if ( $has_criteria ) : ?>
								<span class="search-criteria" style="color: #4a6b5a; font-weight: 600; margin-right: 10px;">
									Search for: <?php echo esc_html( $criteria_text ); ?> |
								</span>
							<?php endif; ?>
							<span class="camps-count"><?php echo $total_camps; ?> camps</span>
							<?php if ( $total_pages > 1 ) : ?>
								<span class="camps-page-info"> - Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
							<?php endif; ?>
							<select name="camp_sort" id="camp_sort" class="camps-sort-select" onchange="this.form.submit()">
								<option value="random" <?php selected( $sort_by, 'random' ); ?>>Random Order</option>
								<option value="name-asc" <?php selected( $sort_by, 'name-asc' ); ?>>Name A-Z</option>
								<option value="name-desc" <?php selected( $sort_by, 'name-desc' ); ?>>Name Z-A</option>
								<option value="price-high" <?php selected( $sort_by, 'price-high' ); ?>>Highest Price</option>
								<option value="price-low" <?php selected( $sort_by, 'price-low' ); ?>>Lowest Price</option>
								<option value="start-early" <?php selected( $sort_by, 'start-early' ); ?>>Earliest Start</option>
								<option value="start-late" <?php selected( $sort_by, 'start-late' ); ?>>Latest Start</option>
							</select>
						</div>
					</div>
				</form>
			</div>
			
			<?php if ( $hide_camps_initially ) : ?>
				<div class="camps-empty">
					<p>Use the search or filters above to find camps.</p>
				</div>
			<?php elseif ( empty( $camps_on_page ) ) : ?>
				<div class="camps-empty">
					<p>No camps match your search criteria. Try adjusting your filters.</p>
				</div>
			<?php else : ?>
			<div class="camps-grid camps-grid-<?php echo $columns; ?>">
				<?php 
				$camp_index = $offset; // Start from the offset position
				foreach ( $camps_on_page as $camp ) : 
					$camp_index++;
				?>
					<!-- Camp #<?php echo $camp_index; ?> - ID: <?php echo $camp['id']; ?> -->
					<div class="camp-card">
						<?php if ( ! empty( $camp['logo'] ) ) : ?>
							<div class="camp-logo-circle">
								<img src="<?php echo esc_url( $camp['logo'] ); ?>" 
								     alt="<?php echo esc_attr( $camp['camp_name'] ); ?>">
							</div>
						<?php else : ?>
							<div class="camp-logo-circle camp-logo-placeholder">
								<span><?php echo esc_html( substr( $camp['camp_name'], 0, 1 ) ); ?></span>
							</div>
						<?php endif; ?>
						
						<div class="camp-info">
							<h3 class="camp-name"><?php echo esc_html( $camp['camp_name'] ); ?></h3>
							
							<?php if ( $camp['city'] || $camp['state'] ) : ?>
								<p class="camp-location">
									<?php 
									$location = array_filter( [ $camp['city'], $camp['state'] ] );
									echo esc_html( implode( ', ', $location ) );
									?>
								</p>
							<?php endif; ?>
							
							<?php if ( ! empty( $camp['camp_types'] ) ) : ?>
								<div class="camp-meta-row">
									<span class="meta-label">Camp Types:</span>
									<div class="camp-badges">
										<?php foreach ( $camp['camp_types'] as $type ) : ?>
											<span class="camp-badge"><?php echo esc_html( $type ); ?></span>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
							
							<?php if ( ! empty( $camp['weeks'] ) ) : ?>
								<div class="camp-meta-row">
									<span class="meta-label">Weeks:</span>
									<div class="camp-badges">
										<?php foreach ( $camp['weeks'] as $week ) : ?>
											<span class="camp-badge"><?php echo esc_html( $week ); ?></span>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
							
							<?php if ( ! empty( $camp['activities'] ) ) : ?>
								<div class="camp-meta-row">
									<span class="meta-label">Activities:</span>
									<div class="camp-badges">
										<?php foreach ( $camp['activities'] as $activity ) : ?>
											<span class="camp-badge"><?php echo esc_html( $activity ); ?></span>
										<?php endforeach; ?>
									<?php if ( $camp['activities_total'] > 4 ) : ?>
										<span class="camp-badge camp-badge-plus">+</span>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>
						
						<div class="camp-dates-prices">
							<?php if ( $camp['opening_day'] ) : ?>
								<div class="camp-detail">
										<strong>Opening Day:</strong> <?php echo esc_html( date( 'M j, Y', strtotime( $camp['opening_day'] ) ) ); ?>
									</div>
								<?php endif; ?>
								
								<?php if ( $camp['closing_day'] ) : ?>
									<div class="camp-detail">
										<strong>Closing Day:</strong> <?php echo esc_html( date( 'M j, Y', strtotime( $camp['closing_day'] ) ) ); ?>
									</div>
								<?php endif; ?>
								
								<?php if ( $camp['minprice_2026'] ) : ?>
									<div class="camp-detail">
										<strong>Lowest Rate:</strong> $<?php echo esc_html( number_format( $camp['minprice_2026'], 2 ) ); ?>
									</div>
								<?php endif; ?>
								
								<?php if ( $camp['maxprice_2026'] ) : ?>
									<div class="camp-detail">
										<strong>Highest Rate:</strong> $<?php echo esc_html( number_format( $camp['maxprice_2026'], 2 ) ); ?>
									</div>
								<?php endif; ?>
							</div>
							
							<a href="<?php echo esc_url( $camp['page_url'] ); ?>" class="camp-visit-btn">
								Visit Camp Page
							</a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			
			<?php if ( $total_pages > 1 ) : ?>
				<div class="camps-pagination">
					<?php
					// Build base URL with all current filters
					$base_url_params = $_GET;
					unset( $base_url_params['camp_page'] ); // Remove page parameter, we'll add it separately
					$base_url = add_query_arg( $base_url_params, strtok( $_SERVER['REQUEST_URI'], '?' ) );
					
					// Previous button
					if ( $current_page > 1 ) :
						$prev_url = add_query_arg( 'camp_page', $current_page - 1, $base_url );
						?>
						<a href="<?php echo esc_url( $prev_url . '#camps-filter-form' ); ?>" class="pagination-btn pagination-prev">← Previous</a>
					<?php else : ?>
						<span class="pagination-btn pagination-prev pagination-disabled">← Previous</span>
					<?php endif; ?>
					
					<div class="pagination-numbers">
						<?php
						// Show page numbers with smart truncation
						$range = 2; // How many pages to show on each side of current page
						
						for ( $i = 1; $i <= $total_pages; $i++ ) :
							// Always show first page, last page, current page, and pages within range
							if ( $i == 1 || $i == $total_pages || ( $i >= $current_page - $range && $i <= $current_page + $range ) ) :
								if ( $i == $current_page ) : ?>
									<span class="pagination-number pagination-current"><?php echo $i; ?></span>
								<?php else :
									$page_url = add_query_arg( 'camp_page', $i, $base_url );
									?>
									<a href="<?php echo esc_url( $page_url . '#camps-filter-form' ); ?>" class="pagination-number"><?php echo $i; ?></a>
								<?php endif;
							elseif ( $i == $current_page - $range - 1 || $i == $current_page + $range + 1 ) :
								// Show ellipsis
								?>
								<span class="pagination-ellipsis">...</span>
							<?php endif;
						endfor;
						?>
					</div>
					
					<!-- Next button -->
					<?php if ( $current_page < $total_pages ) :
						$next_url = add_query_arg( 'camp_page', $current_page + 1, $base_url );
						?>
						<a href="<?php echo esc_url( $next_url . '#camps-filter-form' ); ?>" class="pagination-btn pagination-next">Next →</a>
					<?php else : ?>
						<span class="pagination-btn pagination-next pagination-disabled">Next →</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php endif; ?>
		</div>
		
		<script>
		(function() {
			// Scroll to search form if filters or search were submitted
			var urlParams = new URLSearchParams(window.location.search);
			var hasFilters = urlParams.has('camp_search') || 
							 urlParams.has('filter_state') || 
							 urlParams.has('filter_type') || 
							 urlParams.has('filter_duration') || 
							 urlParams.has('filter_price_min') || 
							 urlParams.has('filter_price_max') || 
							 urlParams.has('filter_date_from') || 
							 urlParams.has('filter_date_to') ||
							 urlParams.has('camp_sort');
			
			if (hasFilters) {
				setTimeout(function() {
					var searchForm = document.querySelector('.camps-search-bar');
					if (searchForm) {
						var offset = 100; // Offset from top
						var elementPosition = searchForm.getBoundingClientRect().top;
						var offsetPosition = elementPosition + window.pageYOffset - offset;
						
						window.scrollTo({
							top: offsetPosition,
							behavior: 'smooth'
						});
					}
				}, 100);
			}
		})();
		</script>
		
		<?php
		return ob_get_clean();
	}
}

// Initialize
new Camps_List();
