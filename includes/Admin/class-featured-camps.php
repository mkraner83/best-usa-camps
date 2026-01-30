<?php
/**
 * Featured Camps Admin Management.
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt\Admin;

use CreativeDBS\CampMgmt\DB;

defined( 'ABSPATH' ) || exit;

class Featured_Camps {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'wp_ajax_update_featured_camps', [ $this, 'ajax_update_featured_camps' ] );
		add_action( 'wp_ajax_search_camps_for_featured', [ $this, 'ajax_search_camps' ] );
		add_action( 'wp_ajax_filter_camps_by_type', [ $this, 'ajax_filter_camps_by_type' ] );
		add_action( 'wp_ajax_filter_camps_by_type_id', [ $this, 'ajax_filter_camps_by_type_id' ] );
	}

	/**
	 * Add submenu page
	 */
	public function add_menu_page() {
		add_submenu_page(
			'creativedbs-camp-mgmt',
			'Featured Camps',
			'Featured Camps',
			'manage_options',
			'creativedbs-featured-camps',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'camp-management_page_creativedbs-featured-camps' !== $hook ) {
			return;
		}

		// jQuery UI Sortable is included in WordPress
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_style(
			'featured-camps-admin',
			plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/featured-camps-admin.css',
			[],
			CDBS_CAMP_VERSION
		);

		wp_enqueue_script(
			'featured-camps-admin',
			plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/featured-camps-admin.js',
			[ 'jquery', 'jquery-ui-sortable' ],
			CDBS_CAMP_VERSION,
			true
		);

		wp_localize_script(
			'featured-camps-admin',
			'featuredCampsAdmin',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'featured_camps_nonce' ),
			]
		);
	}

	/**
	 * Render admin page
	 */
	public function render_page() {
		global $wpdb;
		$table = DB::table_camps();

		$active_tab = $_GET['tab'] ?? 'featured';

		?>
		<div class="wrap featured-camps-admin">
			<h1>Featured Camps Management</h1>
			<p class="description">Manage which camps appear in featured shortcodes on your homepage. Drag and drop to reorder.</p>

			<h2 class="nav-tab-wrapper">
				<a href="?page=creativedbs-featured-camps&tab=featured" class="nav-tab <?php echo $active_tab === 'featured' ? 'nav-tab-active' : ''; ?>">
					Featured Camps
				</a>
				<a href="?page=creativedbs-featured-camps&tab=day" class="nav-tab <?php echo $active_tab === 'day' ? 'nav-tab-active' : ''; ?>">
					Best Day Camps
				</a>
				<a href="?page=creativedbs-featured-camps&tab=overnight" class="nav-tab <?php echo $active_tab === 'overnight' ? 'nav-tab-active' : ''; ?>">
					Best Overnight Camps
				</a>
				<a href="?page=creativedbs-featured-camps&tab=girls" class="nav-tab <?php echo $active_tab === 'girls' ? 'nav-tab-active' : ''; ?>">
					Best All-Girls Camps
				</a>
				<a href="?page=creativedbs-featured-camps&tab=boys" class="nav-tab <?php echo $active_tab === 'boys' ? 'nav-tab-active' : ''; ?>">
					Best All-Boys Camps
				</a>
			</h2>

			<div class="featured-camps-content">
				<?php $this->render_tab_content( $active_tab ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render tab content
	 */
	private function render_tab_content( $tab ) {
		global $wpdb;
		$table = DB::table_camps();

		// Map tab to database columns
		$tab_config = [
			'featured'  => [ 'column' => 'is_featured', 'order' => 'featured_order', 'label' => 'Featured Camps', 'shortcode' => '[featured_camps limit="4"]' ],
			'day'       => [ 'column' => 'is_best_day', 'order' => 'day_order', 'label' => 'Best Day Camps', 'shortcode' => '[best_day_camps limit="4"]' ],
			'overnight' => [ 'column' => 'is_best_overnight', 'order' => 'overnight_order', 'label' => 'Best Overnight Camps', 'shortcode' => '[best_overnight_camps limit="4"]' ],
			'girls'     => [ 'column' => 'is_best_girls', 'order' => 'girls_order', 'label' => 'Best All-Girls Camps', 'shortcode' => '[best_girls_camps limit="4"]' ],
			'boys'      => [ 'column' => 'is_best_boys', 'order' => 'boys_order', 'label' => 'Best All-Boys Camps', 'shortcode' => '[best_boys_camps limit="4"]' ],
		];

		$config = $tab_config[ $tab ];
		$column = $config['column'];
		$order_column = $config['order'];

		// Get currently selected camps for this category
		$selected_camps = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, camp_name, city, state, logo, photos 
			FROM {$table} 
			WHERE {$column} = 1 
			AND approved = 1
			ORDER BY {$order_column} ASC, camp_name ASC"
		) );

		?>
		<div class="featured-tab-content" data-category="<?php echo esc_attr( $tab ); ?>">
			<div class="featured-header">
				<div class="featured-header-left">
					<h3><?php echo esc_html( $config['label'] ); ?></h3>
					<p class="description">Shortcode: <code><?php echo esc_html( $config['shortcode'] ); ?></code></p>
				</div>
				<div class="featured-header-right">
					<button type="button" class="button button-primary add-camp-btn">
						<span class="dashicons dashicons-plus-alt"></span> Add Camps
					</button>
				</div>
			</div>

			<div class="selected-camps-list">
				<h4>Selected Camps (<?php echo count( $selected_camps ); ?>)</h4>
				<?php if ( ! empty( $selected_camps ) ) : ?>
					<ul class="sortable-camps" data-category="<?php echo esc_attr( $tab ); ?>">
						<?php foreach ( $selected_camps as $camp ) : ?>
							<?php $this->render_camp_item( $camp ); ?>
						<?php endforeach; ?>
					</ul>
					<p class="description">
						<span class="dashicons dashicons-move"></span> Drag and drop to reorder. Changes are saved automatically.
					</p>
				<?php else : ?>
					<p class="no-camps">No camps selected yet. Click "Add Camps" to get started.</p>
				<?php endif; ?>
			</div>

			<!-- Search Modal -->
			<div class="featured-search-modal" style="display: none;">
				<div class="modal-overlay"></div>
				<div class="modal-content">
					<div class="modal-header">
						<h2>Add Camps to <?php echo esc_html( $config['label'] ); ?></h2>
						<button type="button" class="modal-close">&times;</button>
					</div>
					<div class="modal-body">
						<div class="search-filters">
							<input type="text" class="camp-search-input" placeholder="Search by camp name..." />
							<select class="camp-state-filter">
								<option value="">All States</option>
								<?php $this->render_state_options(); ?>
							</select>
							<button type="button" class="button search-camps-btn">Search</button>
							<button type="button" class="button show-all-camps-btn">Show All Camps</button>
						</div>
						<div class="camp-type-filters" style="margin: 15px 0; text-align: center;">
							<strong style="margin-right: 10px;">Quick Filters:</strong>
							<button type="button" class="button filter-day-camps">Show All Day Camps</button>
							<button type="button" class="button filter-overnight-camps">Show All Overnight Camps</button>
							<button type="button" class="button filter-girls-camps">Show All Girls Camps</button>
							<button type="button" class="button filter-boys-camps">Show All Boys Camps</button>
						</div>
						<div class="search-results">
							<p class="search-prompt">Enter a camp name or select a state to search</p>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="button modal-close">Close</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render individual camp item
	 */
	private function render_camp_item( $camp ) {
		$logo = ! empty( $camp->logo ) ? $camp->logo : '';
		$photos = ! empty( $camp->photos ) ? explode( ',', $camp->photos ) : [];
		$image = $logo ?: ( ! empty( $photos[0] ) ? trim( $photos[0] ) : '' );

		?>
		<li class="camp-item" data-camp-id="<?php echo esc_attr( $camp->id ); ?>">
			<span class="drag-handle dashicons dashicons-menu"></span>
			<div class="camp-item-content">
				<?php if ( $image ) : ?>
					<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $camp->camp_name ); ?>" class="camp-thumb" />
				<?php else : ?>
					<div class="camp-thumb no-image"><span class="dashicons dashicons-camera"></span></div>
				<?php endif; ?>
				<div class="camp-info">
					<strong><?php echo esc_html( $camp->camp_name ); ?></strong>
					<span class="camp-location"><?php echo esc_html( $camp->city . ', ' . $camp->state ); ?></span>
				</div>
			</div>
			<button type="button" class="remove-camp-btn" data-camp-id="<?php echo esc_attr( $camp->id ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</li>
		<?php
	}

	/**
	 * Render state options
	 */
	private function render_state_options() {
		$states = [
			'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
			'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia',
			'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
			'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
			'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri',
			'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
			'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
			'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
			'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
			'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming'
		];

		foreach ( $states as $code => $name ) {
			echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $name ) . '</option>';
		}
	}

	/**
	 * AJAX: Search camps
	 */
	public function ajax_search_camps() {
		check_ajax_referer( 'featured_camps_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		global $wpdb;
		$table = DB::table_camps();

		$search = sanitize_text_field( $_POST['search'] ?? '' );
		$state = sanitize_text_field( $_POST['state'] ?? '' );
		$category = sanitize_text_field( $_POST['category'] ?? 'featured' );

		// Map category to column
		$column_map = [
			'featured'  => 'is_featured',
			'day'       => 'is_best_day',
			'overnight' => 'is_best_overnight',
			'girls'     => 'is_best_girls',
			'boys'      => 'is_best_boys',
		];

		$column = $column_map[ $category ] ?? 'is_featured';

		$where = "WHERE approved = 1";

		if ( ! empty( $search ) ) {
			$where .= $wpdb->prepare( " AND camp_name LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' );
		}

		if ( ! empty( $state ) ) {
			$where .= $wpdb->prepare( " AND state = %s", $state );
		}

		$sql = "SELECT id, camp_name, city, state, logo, photos, {$column} as is_selected 
				FROM {$table} 
				{$where} 
				ORDER BY camp_name ASC 
				LIMIT 50";

		$results = $wpdb->get_results( $sql );

		wp_send_json_success( $results );
	}

	/**
	 * AJAX: Filter camps by type ID (more reliable than name)
	 */
	public function ajax_filter_camps_by_type_id() {
		check_ajax_referer( 'featured_camps_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		global $wpdb;
		$camps_table = DB::table_camps();
		$junction_table = $wpdb->prefix . 'camp_management_types_map';

		$type_id = intval( $_POST['type_id'] ?? 0 );
		$category = sanitize_text_field( $_POST['category'] ?? 'featured' );

		if ( ! $type_id ) {
			wp_send_json_error( 'Invalid type ID' );
		}

		// Map category to column
		$column_map = [
			'featured'  => 'is_featured',
			'day'       => 'is_best_day',
			'overnight' => 'is_best_overnight',
			'girls'     => 'is_best_girls',
			'boys'      => 'is_best_boys',
		];

		$column = $column_map[ $category ] ?? 'is_featured';

		// Get camps with this type
		$sql = $wpdb->prepare(
			"SELECT DISTINCT c.id, c.camp_name, c.city, c.state, c.logo, c.photos, c.{$column} as is_selected 
			FROM {$camps_table} c
			INNER JOIN {$junction_table} jct ON c.id = jct.camp_id
			WHERE c.approved = 1 
			AND jct.type_id = %d
			ORDER BY c.camp_name ASC 
			LIMIT 100",
			$type_id
		);

		$results = $wpdb->get_results( $sql );

		wp_send_json_success( $results );
	}

	/**
	 * AJAX: Filter camps by type
	 */
	public function ajax_filter_camps_by_type() {
		check_ajax_referer( 'featured_camps_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		global $wpdb;
		$camps_table = DB::table_camps();
		$types_table = $wpdb->prefix . 'camp_type_terms';
		$junction_table = $wpdb->prefix . 'camp_management_types_map';

		$camp_type = sanitize_text_field( $_POST['camp_type'] ?? '' );
		$category = sanitize_text_field( $_POST['category'] ?? 'featured' );

		// Map category to column
		$column_map = [
			'featured'  => 'is_featured',
			'day'       => 'is_best_day',
			'overnight' => 'is_best_overnight',
			'girls'     => 'is_best_girls',
			'boys'      => 'is_best_boys',
		];

		$column = $column_map[ $category ] ?? 'is_featured';

		// Get type ID
		$type_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$types_table} WHERE name = %s",
			$camp_type
		) );

		if ( ! $type_id ) {
			wp_send_json_error( 'Camp type not found' );
		}

		// Get camps with this type
		$sql = $wpdb->prepare(
			"SELECT DISTINCT c.id, c.camp_name, c.city, c.state, c.logo, c.photos, c.{$column} as is_selected 
			FROM {$camps_table} c
			INNER JOIN {$junction_table} jct ON c.id = jct.camp_id
			WHERE c.approved = 1 
			AND jct.type_id = %d
			ORDER BY c.camp_name ASC 
			LIMIT 100",
			$type_id
		);

		$results = $wpdb->get_results( $sql );

		wp_send_json_success( $results );
	}

	/**
	 * AJAX: Update featured camps
	 */
	public function ajax_update_featured_camps() {
		check_ajax_referer( 'featured_camps_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		global $wpdb;
		$table = DB::table_camps();

		$category = sanitize_text_field( $_POST['category'] ?? 'featured' );
		$camp_ids = array_map( 'intval', $_POST['camp_ids'] ?? [] );
		$action_type = sanitize_text_field( $_POST['action_type'] ?? 'reorder' );

		// Map category to columns
		$column_map = [
			'featured'  => [ 'flag' => 'is_featured', 'order' => 'featured_order' ],
			'day'       => [ 'flag' => 'is_best_day', 'order' => 'day_order' ],
			'overnight' => [ 'flag' => 'is_best_overnight', 'order' => 'overnight_order' ],
			'girls'     => [ 'flag' => 'is_best_girls', 'order' => 'girls_order' ],
			'boys'      => [ 'flag' => 'is_best_boys', 'order' => 'boys_order' ],
		];

		$columns = $column_map[ $category ] ?? $column_map['featured'];

		if ( $action_type === 'add' ) {
			// Add camp to category
			foreach ( $camp_ids as $camp_id ) {
				$wpdb->update(
					$table,
					[ $columns['flag'] => 1 ],
					[ 'id' => $camp_id ],
					[ '%d' ],
					[ '%d' ]
				);
			}
		} elseif ( $action_type === 'remove' ) {
			// Remove camp from category
			foreach ( $camp_ids as $camp_id ) {
				$wpdb->update(
					$table,
					[ $columns['flag'] => 0, $columns['order'] => 0 ],
					[ 'id' => $camp_id ],
					[ '%d', '%d' ],
					[ '%d' ]
				);
			}
		} elseif ( $action_type === 'reorder' ) {
			// Update order
			foreach ( $camp_ids as $order => $camp_id ) {
				$wpdb->update(
					$table,
					[ $columns['order'] => $order + 1 ],
					[ 'id' => $camp_id ],
					[ '%d' ],
					[ '%d' ]
				);
			}
		}

		wp_send_json_success( 'Updated successfully' );
	}
}
