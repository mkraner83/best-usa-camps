<?php
/*
Plugin Name: CreativeDBS Camp Management
Description: Ultimate US Summer Camp Management Application.
Version: 3.2.0
Author: CreativeDBS
Text Domain: creativedbs-camp-mgmt
Requires at least: 5.8
Requires PHP: 7.4
*/

define('CDBS_CAMP_VERSION', '3.2.0');

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'CREATIVE_DBS_CAMPMGMT_FILE' ) ) {
	define( 'CREATIVE_DBS_CAMPMGMT_FILE', __FILE__ );
}

// Load required classes
$required_files = [
    __DIR__ . '/includes/class-i18n.php',
    __DIR__ . '/includes/class-assets.php',
    __DIR__ . '/includes/class-plugin.php',
    __DIR__ . '/includes/class-db.php',
    __DIR__ . '/includes/class-helpers.php',
    __DIR__ . '/includes/migrations-phase7.php',
    __DIR__ . '/includes/migrations-modules.php',
    __DIR__ . '/includes/migrations-add-user-id.php',
    __DIR__ . '/includes/Public/class-public-controller.php',
    __DIR__ . '/includes/Public/class-camp-dashboard.php',
    __DIR__ . '/includes/Public/class-camp-signup-form.php',
    __DIR__ . '/includes/Public/class-camp-frontend.php',
    __DIR__ . '/includes/Public/class-camps-list.php',
    __DIR__ . '/includes/Admin/class-admin.php',
    __DIR__ . '/includes/Admin/class-import-export.php',
];

foreach ( $required_files as $file ) {
    if ( file_exists( $file ) ) {
        require_once $file;
    }
}

if ( is_admin() ) { 
    require_once __DIR__ . '/includes/admin-credentials.php';
    add_action( 'admin_menu', [ '\\CreativeDBS\\CampMgmt\\Admin_Creds', 'register_menu' ], 99 );
}
add_action( 'admin_init', [ '\\CreativeDBS\\CampMgmt\\Migrations_Phase7', 'run' ] );
add_action( 'admin_init', [ '\\CreativeDBS\\CampMgmt\\Migrations_Modules', 'run' ] );
add_action( 'admin_init', [ '\\CreativeDBS\\CampMgmt\\Migration_Add_WordPress_User_ID', 'run' ] );
if ( function_exists( 'register_uninstall_hook' ) ) {
    if ( ! function_exists( 'creativedbs_campmgmt_uninstall_marker' ) ) {
        function creativedbs_campmgmt_uninstall_marker() {}
    }
    register_uninstall_hook( __FILE__, 'creativedbs_campmgmt_uninstall_marker' );
}

// Instantiate plugin early.
add_action( 'plugins_loaded', function() {
	if ( ! class_exists( '\\CreativeDBS\\CampMgmt\\Plugin' ) ) {
		return;
	}
	\CreativeDBS\CampMgmt\Plugin::instance();
	new \CreativeDBS\CampMgmt\Admin\Admin();
	if ( is_admin() ) {
		new \CreativeDBS\CampMgmt\Admin\Import_Export();
	}
	new \CreativeDBS\CampMgmt\PublicArea\Public_Controller();
	new \CreativeDBS\CampMgmt\PublicArea\Camp_Dashboard();
	new \CreativeDBS\CampMgmt\PublicArea\Camp_Signup_Form();
}, 0);

// === Legacy code below kept for backward-compatibility. ===



if (!defined('ABSPATH')) { exit; }

if (!class_exists('CreativeDBS_Camp_Management')):

class CreativeDBS_Camp_Management {
    const VERSION = '2.1.4';
    const SLUG    = 'creativedbs-camp-mgmt';

    private static $instance = null;
    public static function instance() { return self::$instance ?: (self::$instance = new self()); }

    private function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('admin_init', [$this, 'ensure_tables']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('admin_footer', [$this, 'admin_footer_js']);
        add_action('admin_footer', [$this, 'taxonomy_sortable_js']);
        add_action('admin_init', [$this, 'handle_inline_actions']);
        add_action('wp_ajax_cdbs_toggle_approval', [$this, 'ajax_toggle_approval']);
        add_action('wp_ajax_cdbs_save_accommodation', [$this, 'ajax_save_accommodation']);
        add_action('wp_ajax_cdbs_delete_accommodation', [$this, 'ajax_delete_accommodation']);
        add_action('wp_ajax_cdbs_save_faq', [$this, 'ajax_save_faq']);
        add_action('wp_ajax_cdbs_delete_faq', [$this, 'ajax_delete_faq']);
        add_action('wp_ajax_cdbs_save_session', [$this, 'ajax_save_session']);
        add_action('wp_ajax_cdbs_delete_session', [$this, 'ajax_delete_session']);
        add_action('wp_ajax_cdbs_update_term_order', [$this, 'ajax_update_term_order']);
    }

    // Tables
    public static function table_camps() { global $wpdb; return $wpdb->prefix . 'camp_management'; }
    public static function table_type_terms() { global $wpdb; return $wpdb->prefix . 'camp_type_terms'; }
    public static function table_camp_type_pivot() { global $wpdb; return $wpdb->prefix . 'camp_management_types_map'; }
    public static function table_week_terms() { global $wpdb; return $wpdb->prefix . 'camp_week_terms'; }
    public static function table_camp_week_pivot() { global $wpdb; return $wpdb->prefix . 'camp_management_weeks_map'; }
    public static function table_activity_terms() { global $wpdb; return $wpdb->prefix . 'camp_activity_terms'; }
    public static function table_camp_activity_pivot() { global $wpdb; return $wpdb->prefix . 'camp_management_activities_map'; }

    public function activate() { $this->ensure_tables(); }

    public function ensure_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        
        // Add last_edited column if it doesn't exist
        $camps_table = self::table_camps();
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$camps_table} LIKE 'last_edited'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$camps_table} ADD COLUMN last_edited DATETIME NULL AFTER updated_at");
        }
        
        $camps = self::table_camps();
        dbDelta("CREATE TABLE {$camps} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ninja_entry_id BIGINT UNSIGNED NULL,
            unique_key VARCHAR(64) NULL,
            camp_name VARCHAR(255) NOT NULL,
            opening_day DATE NULL,
            closing_day DATE NULL,
            minprice_2026 DECIMAL(10,2) NULL,
            maxprice_2026 DECIMAL(10,2) NULL,
            activities TEXT NULL,
            email VARCHAR(190) NULL,
            phone VARCHAR(64) NULL,
            website VARCHAR(255) NULL,
            camp_directors TEXT NULL,
            address VARCHAR(255) NULL,
            city VARCHAR(190) NULL,
            state VARCHAR(64) NULL,
            zip VARCHAR(32) NULL,
            about_camp LONGTEXT NULL,
            photos LONGTEXT NULL,
            logo VARCHAR(255) NULL,
            search_image VARCHAR(255) NULL,
            approved TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_edited DATETIME NULL,
            PRIMARY KEY (id)
        ) {$charset};");

        $terms = self::table_type_terms();
        dbDelta("CREATE TABLE {$terms} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) {$charset};");
        $type_pivot = self::table_camp_type_pivot();
        dbDelta("CREATE TABLE {$type_pivot} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            camp_id BIGINT UNSIGNED NOT NULL,
            type_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            KEY camp_id (camp_id),
            KEY type_id (type_id)
        ) {$charset};");

        $weeks = self::table_week_terms();
        dbDelta("CREATE TABLE {$weeks} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) {$charset};");
        $week_pivot = self::table_camp_week_pivot();
        dbDelta("CREATE TABLE {$week_pivot} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            camp_id BIGINT UNSIGNED NOT NULL,
            week_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            KEY camp_id (camp_id),
            KEY week_id (week_id)
        ) {$charset};");

        $acts = self::table_activity_terms();
        dbDelta("CREATE TABLE {$acts} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) {$charset};");
        $act_pivot = self::table_camp_activity_pivot();
        dbDelta("CREATE TABLE {$act_pivot} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            camp_id BIGINT UNSIGNED NOT NULL,
            activity_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            KEY camp_id (camp_id),
            KEY activity_id (activity_id)
        ) {$charset};");

        // Migration: Add last_edited column if it doesn't exist
        $table_name = self::table_camps();
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'last_edited'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN last_edited DATETIME NULL AFTER updated_at");
        }
        
        // Migration: Add sort_order column to taxonomy tables if it doesn't exist
        $taxonomy_tables = [
            self::table_type_terms(),
            self::table_week_terms(),
            self::table_activity_terms()
        ];
        foreach ($taxonomy_tables as $tax_table) {
            $sort_column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$tax_table} LIKE 'sort_order'");
            if (empty($sort_column_exists)) {
                $wpdb->query("ALTER TABLE {$tax_table} ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER is_active");
            }
        }
    }

    public function register_admin_menu() {
        add_menu_page(
            __('Camp Management', 'creativedbs-camp-mgmt'),
            __('Camp Management', 'creativedbs-camp-mgmt'),
            'manage_options',
            self::SLUG,
            [$this, 'render_admin_page'],
            'dashicons-location-alt',
            26
        );

        add_submenu_page(self::SLUG, __('Camp Types', 'creativedbs'), __('Camp Types', 'creativedbs'), 'manage_options', self::SLUG.'-types', [$this, 'render_types_page']);
        add_submenu_page(self::SLUG, __('Durations / Weeks', 'creativedbs'), __('Durations / Weeks', 'creativedbs'), 'manage_options', self::SLUG.'-weeks', [$this, 'render_weeks_page']);
        add_submenu_page(self::SLUG, __('Activities', 'creativedbs'), __('Activities', 'creativedbs'), 'manage_options', self::SLUG.'-activities', [$this, 'render_activities_page']);
        add_submenu_page(self::SLUG, __('Import / Export', 'creativedbs'), __('Import / Export', 'creativedbs'), 'manage_options', self::SLUG.'-import-export', ['\\CreativeDBS\\CampMgmt\\Admin\\Import_Export', 'render_page']);
        add_submenu_page(self::SLUG, __('Add Camp', 'creativedbs'), __('Add Camp', 'creativedbs'), 'manage_options', self::SLUG.'-add', [$this, 'render_add_camp_page']);
    }

    public function admin_assets($hook) {
        if (strpos($hook, self::SLUG) === false) return;
        wp_enqueue_style(self::SLUG, plugin_dir_url(__FILE__) . 'assets/admin.css', [], self::VERSION);
        wp_enqueue_media();
        
        // Enqueue jQuery UI Sortable for taxonomy pages
        if (strpos($hook, '-types') !== false || strpos($hook, '-weeks') !== false || strpos($hook, '-activities') !== false) {
            wp_enqueue_script('jquery-ui-sortable');
        }
        
        // Enqueue wp-pointer to prevent console errors (WordPress help system)
        wp_enqueue_script('wp-pointer');
        wp_enqueue_style('wp-pointer');
    }

    public function admin_footer_js() {
        if (!isset($_GET['page'])) return;
        if ($_GET['page'] !== self::SLUG) return; // only main page
        ?>
        <script>
        (function(){
            document.addEventListener('click', function(e){
                var el = e.target.closest('[data-cdbs-delete]');
                if(!el) return;
                e.preventDefault();
                var typed = prompt('Type DELETE to confirm permanent deletion of this camp. This cannot be undone.');
                if(typed && typed.trim().toUpperCase() === 'DELETE'){
                    var url = new URL(el.getAttribute('href'), window.location.href);
                    url.searchParams.set('confirm', 'DELETE');
                    window.location = url.toString();
                }
            }, {passive:false});
            function pickOne(btnId, inputId, previewId){
                var btn = document.getElementById(btnId);
                if(!btn) return;
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    var frame = wp.media({title:'Select Image', button:{text:'Use this image'}, multiple:false});
                    frame.on('select', function(){
                        var att = frame.state().get('selection').first().toJSON();
                        document.getElementById(inputId).value = att.url;
                        if(previewId){ var img=document.getElementById(previewId); if(img){ img.src = att.url; img.style.display='inline-block'; }}
                    });
                    frame.open();
                });
            }
            pickOne('cdbs-btn-logo','logo','logo_preview');
            pickOne('cdbs-btn-search-image','search_image','search_image_preview');
            
            // Handle approved checkbox toggle
            document.addEventListener('change', function(e){
                if(!e.target.classList.contains('cdbs-approve-checkbox')) return;
                var checkbox = e.target;
                var campId = checkbox.getAttribute('data-camp-id');
                var approved = checkbox.checked ? 1 : 0;
                
                // Send AJAX request
                var xhr = new XMLHttpRequest();
                xhr.open('POST', ajaxurl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function(){
                    if(xhr.status === 200){
                        var response = JSON.parse(xhr.responseText);
                        if(!response.success){
                            checkbox.checked = !checkbox.checked;
                            alert('Failed to update approval status');
                        }
                    }
                };
                xhr.send('action=cdbs_toggle_approval&camp_id=' + campId + '&approved=' + approved + '&nonce=' + '<?php echo wp_create_nonce("cdbs_toggle_approval"); ?>');
            });
        })();
        </script>
        <?php
    }
    
    public function taxonomy_sortable_js() {
        if (!isset($_GET['page'])) return;
        $page = $_GET['page'];
        
        // Only add sortable on taxonomy pages
        if (strpos($page, '-types') === false && strpos($page, '-weeks') === false && strpos($page, '-activities') === false) {
            return;
        }
        
        // Determine table type
        $tableType = '';
        if (strpos($page, '-types') !== false) $tableType = 'types';
        elseif (strpos($page, '-weeks') !== false) $tableType = 'weeks';
        elseif (strpos($page, '-activities') !== false) $tableType = 'activities';
        
        if (!$tableType) return;
        ?>
        <script>
        jQuery(document).ready(function($) {
            var tbody = $('.cdbs-sortable-tbody');
            if (tbody.length === 0) return;
            
            tbody.sortable({
                handle: 'td:first-child',
                cursor: 'move',
                opacity: 0.6,
                helper: function(e, tr) {
                    var $originals = tr.children();
                    var $helper = tr.clone();
                    $helper.children().each(function(index) {
                        $(this).width($originals.eq(index).width());
                    });
                    return $helper;
                },
                update: function(event, ui) {
                    // Get new order
                    var order = [];
                    tbody.find('tr[data-id]').each(function() {
                        order.push($(this).data('id'));
                    });
                    
                    // Save via AJAX
                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'cdbs_update_term_order',
                            table_type: '<?php echo esc_js($tableType); ?>',
                            order: order.join(','),
                            nonce: '<?php echo wp_create_nonce("cdbs_update_term_order"); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Show brief success indicator
                                var successMsg = $('<div style="position:fixed;top:32px;right:20px;background:#46b450;color:white;padding:10px 20px;border-radius:3px;z-index:99999;">Order saved!</div>');
                                $('body').append(successMsg);
                                setTimeout(function() {
                                    successMsg.fadeOut(function() {
                                        successMsg.remove();
                                    });
                                }, 2000);
                            } else {
                                alert('Failed to save order');
                            }
                        },
                        error: function() {
                            alert('Failed to save order');
                        }
                    });
                }
            });
            
            // Add visual feedback
            tbody.find('tr').css('cursor', 'move');
            tbody.find('td:first-child').css('cursor', 'grab');
        });
        </script>
        <?php
    }

    public function ajax_toggle_approval() {
        check_ajax_referer('cdbs_toggle_approval', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $camp_id = isset($_POST['camp_id']) ? absint($_POST['camp_id']) : 0;
        $approved = isset($_POST['approved']) ? absint($_POST['approved']) : 0;
        
        if (!$camp_id) {
            wp_send_json_error('Invalid camp ID');
        }
        
        global $wpdb;
        $result = $wpdb->update(
            self::table_camps(),
            ['approved' => $approved],
            ['id' => $camp_id],
            ['%d'],
            ['%d']
        );
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Database update failed');
        }
    }

    public function ajax_save_accommodation() {
        check_ajax_referer('cdbs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $camp_id = isset($_POST['camp_id']) ? absint($_POST['camp_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $capacity = isset($_POST['capacity']) ? absint($_POST['capacity']) : 0;
        $type = isset($_POST['accommodation_type']) ? sanitize_text_field($_POST['accommodation_type']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        
        if (!$camp_id || !$name) {
            wp_send_json_error('Missing required fields');
        }
        
        global $wpdb;
        $table = \CreativeDBS\CampMgmt\DB::table_accommodations();
        
        if ($id > 0) {
            // Update existing
            $result = $wpdb->update(
                $table,
                [
                    'name' => $name,
                    'capacity' => $capacity,
                    'accommodation_type' => $type,
                    'description' => $description
                ],
                ['id' => $id],
                ['%s', '%d', '%s', '%s'],
                ['%d']
            );
        } else {
            // Insert new
            $result = $wpdb->insert(
                $table,
                [
                    'camp_id' => $camp_id,
                    'name' => $name,
                    'capacity' => $capacity,
                    'accommodation_type' => $type,
                    'description' => $description
                ],
                ['%d', '%s', '%d', '%s', '%s']
            );
        }
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Database operation failed');
        }
    }

    public function ajax_delete_accommodation() {
        check_ajax_referer('cdbs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error('Invalid ID');
        }
        
        global $wpdb;
        $result = $wpdb->delete(
            \CreativeDBS\CampMgmt\DB::table_accommodations(),
            ['id' => $id],
            ['%d']
        );
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Delete failed');
        }
    }

    public function ajax_save_faq() {
        check_ajax_referer('cdbs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $camp_id = isset($_POST['camp_id']) ? absint($_POST['camp_id']) : 0;
        $question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';
        $answer = isset($_POST['answer']) ? sanitize_textarea_field($_POST['answer']) : '';
        
        if (!$camp_id || !$question) {
            wp_send_json_error('Missing required fields');
        }
        
        global $wpdb;
        $table = \CreativeDBS\CampMgmt\DB::table_faqs();
        
        if ($id > 0) {
            // Update existing
            $result = $wpdb->update(
                $table,
                [
                    'question' => $question,
                    'answer' => $answer
                ],
                ['id' => $id],
                ['%s', '%s'],
                ['%d']
            );
        } else {
            // Insert new - get max sort_order
            $max_order = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(sort_order) FROM {$table} WHERE camp_id = %d",
                $camp_id
            ));
            $sort_order = ($max_order !== null) ? $max_order + 1 : 0;
            
            $result = $wpdb->insert(
                $table,
                [
                    'camp_id' => $camp_id,
                    'question' => $question,
                    'answer' => $answer,
                    'sort_order' => $sort_order
                ],
                ['%d', '%s', '%s', '%d']
            );
        }
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Database operation failed');
        }
    }

    public function ajax_delete_faq() {
        check_ajax_referer('cdbs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error('Invalid ID');
        }
        
        global $wpdb;
        $result = $wpdb->delete(
            \CreativeDBS\CampMgmt\DB::table_faqs(),
            ['id' => $id],
            ['%d']
        );
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Delete failed');
        }
    }

    public function ajax_save_session() {
        check_ajax_referer('cdbs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $camp_id = isset($_POST['camp_id']) ? absint($_POST['camp_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $duration = isset($_POST['duration']) ? sanitize_text_field($_POST['duration']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        
        if (!$camp_id || !$name || !$start_date || !$end_date) {
            wp_send_json_error('Missing required fields');
        }
        
        global $wpdb;
        $table = \CreativeDBS\CampMgmt\DB::table_sessions();
        
        if ($id > 0) {
            // Update existing
            $result = $wpdb->update(
                $table,
                [
                    'name' => $name,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'price' => $price,
                    'duration' => $duration,
                    'description' => $description
                ],
                ['id' => $id],
                ['%s', '%s', '%s', '%f', '%s', '%s'],
                ['%d']
            );
        } else {
            // Insert new
            $result = $wpdb->insert(
                $table,
                [
                    'camp_id' => $camp_id,
                    'name' => $name,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'price' => $price,
                    'duration' => $duration,
                    'description' => $description
                ],
                ['%d', '%s', '%s', '%s', '%f', '%s', '%s']
            );
        }
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Database operation failed');
        }
    }

    public function ajax_delete_session() {
        check_ajax_referer('cdbs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error('Invalid ID');
        }
        
        global $wpdb;
        $result = $wpdb->delete(
            \CreativeDBS\CampMgmt\DB::table_sessions(),
            ['id' => $id],
            ['%d']
        );
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Delete failed');
        }
    }

    public function ajax_update_term_order() {
        check_ajax_referer('cdbs_update_term_order', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $table_type = isset($_POST['table_type']) ? sanitize_text_field($_POST['table_type']) : '';
        $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : '';
        
        if (!$table_type || !$order) {
            wp_send_json_error('Missing required fields');
        }
        
        // Determine which table to update
        global $wpdb;
        switch ($table_type) {
            case 'types':
                $table = self::table_type_terms();
                break;
            case 'weeks':
                $table = self::table_week_terms();
                break;
            case 'activities':
                $table = self::table_activity_terms();
                break;
            default:
                wp_send_json_error('Invalid table type');
                return;
        }
        
        // Parse order
        $ids = explode(',', $order);
        $position = 0;
        
        foreach ($ids as $id) {
            $id = absint($id);
            if ($id > 0) {
                $wpdb->update(
                    $table,
                    ['sort_order' => $position],
                    ['id' => $id],
                    ['%d'],
                    ['%d']
                );
                $position++;
            }
        }
        
        wp_send_json_success();
    }

    public function handle_inline_actions(){
        if (!is_admin() || !current_user_can('manage_options')) return;

        // Delete a camp from main list
        if (isset($_GET['page']) && $_GET['page'] === self::SLUG && (isset($_GET['action']) && $_GET['action'] === 'delete_camp')) {
            $camp_id = isset($_GET['camp']) ? intval($_GET['camp']) : 0;
            if (!$camp_id) return;
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_camp_'.$camp_id)) return;
            if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'DELETE') return;
            global $wpdb;
            $wpdb->delete(self::table_camp_type_pivot(), ['camp_id'=>$camp_id]);
            $wpdb->delete(self::table_camp_week_pivot(), ['camp_id'=>$camp_id]);
            $wpdb->delete(self::table_camp_activity_pivot(), ['camp_id'=>$camp_id]);
            $wpdb->delete(self::table_camps(), ['id'=>$camp_id]);
            wp_safe_redirect(remove_query_arg(['action','camp','_wpnonce','confirm']));
            exit;
        }

        // Master lists add/edit/delete (NO prompt)
        if (!isset($_GET['page'])) return;
        $page = $_GET['page'];
        if ($page === self::SLUG.'-types') $this->handle_master_actions('types');
        if ($page === self::SLUG.'-weeks') $this->handle_master_actions('weeks');
        if ($page === self::SLUG.'-activities') $this->handle_master_actions('activities');
    }

    private function handle_master_actions($kind) {
        global $wpdb;
        $table = ($kind==='types') ? self::table_type_terms() : (($kind==='weeks') ? self::table_week_terms() : self::table_activity_terms());
        if (!current_user_can('manage_options')) return;

        // Add / Update
        if (isset($_POST['cdbs_master_save']) && check_admin_referer('cdbs_master_'.$kind)) {
            $id   = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $name = sanitize_text_field($_POST['name'] ?? '');
            $slug = sanitize_title(isset($_POST['slug']) && $_POST['slug'] ? $_POST['slug'] : $name);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $data = ['name'=>$name, 'slug'=>$slug, 'is_active'=>$is_active, 'updated_at'=>current_time('mysql')];
            if ($id) {
                $wpdb->update($table, $data, ['id'=>$id]);
                add_action('admin_notices', function(){ echo '<div class="updated"><p>Item updated.</p></div>'; });
            } else {
                $data['created_at'] = current_time('mysql');
                $wpdb->insert($table, $data);
                add_action('admin_notices', function(){ echo '<div class="updated"><p>Item added.</p></div>'; });
            }
            wp_safe_redirect(remove_query_arg(['action','id']));
            exit;
        }

        // Delete
        if (isset($_GET['action'], $_GET['id']) && $_GET['action']==='delete' && isset($_GET['_wpnonce'])) {
            $id = intval($_GET['id']);
            if ($id && wp_verify_nonce($_GET['_wpnonce'], 'cdbs_master_delete_'.$kind.'_'.$id)) {
                $wpdb->delete($table, ['id'=>$id]);
                wp_safe_redirect(remove_query_arg(['action','id','_wpnonce']));
                exit;
            }
        }
    }

    private static function full_state_name($abbr){
        static $map = [
            'AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas','CA'=>'California','CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware','DC'=>'District of Columbia','FL'=>'Florida','GA'=>'Georgia','HI'=>'Hawaii','ID'=>'Idaho','IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa','KS'=>'Kansas','KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland','MA'=>'Massachusetts','MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi','MO'=>'Missouri','MT'=>'Montana','NE'=>'Nebraska','NV'=>'Nevada','NH'=>'New Hampshire','NJ'=>'New Jersey','NM'=>'New Mexico','NY'=>'New York','NC'=>'North Carolina','ND'=>'North Dakota','OH'=>'Ohio','OK'=>'Oklahoma','OR'=>'Oregon','PA'=>'Pennsylvania','RI'=>'Rhode Island','SC'=>'South Carolina','SD'=>'South Dakota','TN'=>'Tennessee','TX'=>'Texas','UT'=>'Utah','VT'=>'Vermont','VA'=>'Virginia','WA'=>'Washington','WV'=>'West Virginia','WI'=>'Wisconsin','WY'=>'Wyoming',
        ];
        $abbr = strtoupper(trim((string)$abbr));
        return isset($map[$abbr]) ? $map[$abbr] : '';
    }

    private static function arrayize($val) {
        if (empty($val)) return array();
        if (is_array($val)) return array_values(array_filter(array_map('trim', $val), 'strlen'));
        $parts = array_map('trim', preg_split('/\s*,\s*/', (string)$val));
        return array_values(array_filter($parts, 'strlen'));
    }
    private static function parse_date($val) { if (empty($val)) return null; if (is_array($val)) $val = reset($val); $ts = strtotime((string)$val); return ($ts && $ts > 0) ? date('Y-m-d', $ts) : null; }
    private static function to_money($val) { if ($val === null || $val === '') return null; if (is_array($val)) $val = reset($val); $val = (string)$val; $val = str_replace('$','', $val); if (strpos($val, ',') !== false) { $val = str_replace('.', '', $val); $val = str_replace(',', '.', $val); } $val = preg_replace('/[^\d\.\-]/', '', $val); return $val === '' ? null : $val; }

    /*** MAIN PAGE (list + full edit) ***/
    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $table = self::table_camps();

        // Sorting params
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
        $order   = isset($_GET['order']) ? strtoupper(sanitize_text_field($_GET['order'])) : 'DESC';
        $allowed = ['camp_name'=>'camp_name','created_at'=>'created_at','state'=>'state','website'=>'website'];
        $orderby_sql = isset($allowed[$orderby]) ? $allowed[$orderby] : 'created_at';
        $order_sql = ($order === 'ASC') ? 'ASC' : 'DESC';

        // Save edits
        if (isset($_POST['creativedbs_save']) && check_admin_referer('save_camp')) {
            $camp_id = absint($_POST['camp_id'] ?? 0);
            $activity_names = array_map('sanitize_text_field', self::arrayize($_POST['activity_names'] ?? ''));
            $photos = sanitize_textarea_field($_POST['photos'] ?? '');

            $wpdb->update($table, [
                'camp_name'      => sanitize_text_field($_POST['camp_name'] ?? ''),
                'opening_day'    => self::parse_date($_POST['opening_day'] ?? ''),
                'closing_day'    => self::parse_date($_POST['closing_day'] ?? ''),
                'minprice_2026'  => self::to_money($_POST['minprice_2026'] ?? ''),
                'maxprice_2026'  => self::to_money($_POST['maxprice_2026'] ?? ''),
                'activities'     => implode(',', $activity_names),
                'email'          => sanitize_email($_POST['email'] ?? ''),
                'phone'          => sanitize_text_field($_POST['phone'] ?? ''),
                'website'        => esc_url_raw($_POST['website'] ?? ''),
                'camp_directors' => sanitize_textarea_field($_POST['camp_directors'] ?? ''),
                'address'        => sanitize_text_field($_POST['address'] ?? ''),
                'city'           => sanitize_text_field($_POST['city'] ?? ''),
                'state'          => sanitize_text_field($_POST['state'] ?? ''),
                'zip'            => sanitize_text_field($_POST['zip'] ?? ''),
                'about_camp'     => wp_kses_post($_POST['about_camp'] ?? ''),
                'photos'         => $photos,
                'logo'           => esc_url_raw($_POST['logo'] ?? ''),
                'search_image'   => esc_url_raw($_POST['search_image'] ?? ''),
                'rating'         => floatval($_POST['rating'] ?? 0),
                'approved'       => isset($_POST['approved']) ? 1 : 0,
                'wordpress_user_id' => absint($_POST['wordpress_user_id'] ?? 0),
                'updated_at'     => current_time('mysql'),
                'last_edited'    => current_time('mysql'),
            ], ['id' => $camp_id]);

            // Sync link tables
            $selected_types = array_map('intval', $_POST['type_ids'] ?? []);
            $selected_weeks = array_map('intval', $_POST['week_ids'] ?? []);

            // Activities: auto-create
            $terms_table = self::table_activity_terms();
            $pivot_act   = self::table_camp_activity_pivot();
            $existing = $wpdb->get_results("SELECT id, name, slug FROM {$terms_table}");
            $by_name = []; $by_slug = [];
            if ($existing) { foreach ($existing as $t){ $by_name[strtolower($t->name)] = intval($t->id); $by_slug[$t->slug] = intval($t->id); } }
            $link_act_ids = [];
            foreach ($activity_names as $label) {
                $slug = sanitize_title($label); $key = strtolower($label);
                $aid = isset($by_name[$key]) ? $by_name[$key] : (isset($by_slug[$slug]) ? $by_slug[$slug] : 0);
                if (!$aid) {
                    $wpdb->insert($terms_table, ['name'=>$label,'slug'=>$slug,'is_active'=>1,'created_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')]);
                    $aid = intval($wpdb->insert_id); $by_name[$key]=$aid; $by_slug[$slug]=$aid;
                }
                $link_act_ids[$aid] = true;
            }
            $wpdb->delete($pivot_act, ['camp_id'=>$camp_id]);
            foreach (array_keys($link_act_ids) as $aid) { $wpdb->insert($pivot_act, ['camp_id'=>$camp_id,'activity_id'=>$aid]); }

            // Types
            $pivot_types = self::table_camp_type_pivot();
            $wpdb->delete($pivot_types, ['camp_id'=>$camp_id]);
            foreach ($selected_types as $tid) { if ($tid>0) $wpdb->insert($pivot_types, ['camp_id'=>$camp_id,'type_id'=>$tid]); }
            // Weeks
            $pivot_weeks = self::table_camp_week_pivot();
            $wpdb->delete($pivot_weeks, ['camp_id'=>$camp_id]);
            foreach ($selected_weeks as $wid) { if ($wid>0) $wpdb->insert($pivot_weeks, ['camp_id'=>$camp_id,'week_id'=>$wid]); }

            echo '<div class="updated"><p>Camp updated.</p></div>';
        }

        // List
        $items = $wpdb->get_results("SELECT id, camp_name, state, website, logo, approved, created_at, last_edited FROM {$table} ORDER BY {$orderby_sql} {$order_sql}, id DESC LIMIT 500");
        $base_admin = admin_url('admin.php');

        $build_sort = function($key, $label) use ($base_admin, $orderby, $order) {
            $new_order = ($orderby === $key && strtoupper($order) === 'ASC') ? 'DESC' : 'ASC';
            $url = add_query_arg(['page'=>self::SLUG,'orderby'=>$key,'order'=>$new_order], $base_admin);
            $arrow = ($orderby === $key) ? (' ' . (strtoupper($order)==='ASC' ? '▲' : '▼')) : '';
            return '<a href="'.esc_url($url).'">'.esc_html($label.$arrow).'</a>';
        };

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Camps', 'creativedbs'); ?></h1>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo $build_sort('camp_name','Camp Name'); ?></th>
                        <th><?php esc_html_e('Logo'); ?></th>
                        <th><?php echo $build_sort('created_at','Date Added'); ?></th>
                        <th><?php echo $build_sort('last_edited','Date Edited'); ?></th>
                        <th><?php echo $build_sort('state','State'); ?></th>
                        <th><?php echo $build_sort('website','Website'); ?></th>
                        <th><?php esc_html_e('Approved'); ?></th>
                        <th><?php esc_html_e('Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($items)) : ?>
                    <tr><td colspan="8"><?php esc_html_e('No camps found.', 'creativedbs'); ?></td></tr>
                <?php else: foreach ($items as $row):
                    $edit_url = add_query_arg(['page'=>self::SLUG,'action'=>'edit','camp'=>$row->id], $base_admin);
                    $edit_url = wp_nonce_url($edit_url, 'edit_camp');
                    $del_url = add_query_arg(['page'=>self::SLUG,'action'=>'delete_camp','camp'=>$row->id], $base_admin);
                    $del_url = wp_nonce_url($del_url, 'delete_camp_'.$row->id);
                    $state_full = self::full_state_name($row->state);
                    $state_text = trim(($row->state ? strtoupper($row->state) : '') . ($state_full ? ', '.$state_full : ''));
                    $website_link = $row->website ? \CreativeDBS\CampMgmt\Helpers::format_website_link( $row->website ) : '—';
?>
                    <tr>
                        <td><strong><?php echo esc_html($row->camp_name); ?></strong></td>
<td><?php
    $logo_html = \CreativeDBS\CampMgmt\Helpers::format_logo_or_favicon( $row->logo, $row->website, $row->camp_name, 36 );
    if ( $logo_html && $row->website ) {
        $website_url = $row->website;
        if ( ! preg_match( '#^https?://#i', $website_url ) ) {
            $website_url = 'https://' . $website_url;
        }
        echo '<a href="' . esc_url( $website_url ) . '" target="_blank" rel="noopener">' . $logo_html . '</a>';
    } else {
        echo $logo_html;
    }
?></td>
                        <td><?php echo esc_html($row->created_at ? date_i18n(get_option('date_format').' '.get_option('time_format'), strtotime($row->created_at)) : '—'); ?></td>
                        <td><?php echo esc_html($row->last_edited ? date_i18n(get_option('date_format').' '.get_option('time_format'), strtotime($row->last_edited)) : '—'); ?></td>
                        <td><?php echo esc_html($state_text ?: '—'); ?></td>
                        <td><?php echo $website_link ?: '—'; ?></td>
                        <td style="text-align: center;">
                            <input type="checkbox" 
                                class="cdbs-approve-checkbox" 
                                data-camp-id="<?php echo absint($row->id); ?>"
                                <?php checked($row->approved, 1); ?> />
                        </td>
                        <td>
                            <a class="button" href="<?php echo esc_url($edit_url); ?>">Edit</a>
                            <a class="button button-link-delete" href="<?php echo esc_url($del_url); ?>" data-cdbs-delete="1">Delete</a>
                            <a class="button" href="<?php
                                echo esc_url( add_query_arg(
                                    ['page' => 'creativedbs-camp-mgmt-creds', 'camp' => intval($row->id)],
                                    admin_url('admin.php')
                                ) );
                            ?>"><?php esc_html_e('Edit Password', 'creativedbs-camp-mgmt'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>

            <?php
            if (isset($_GET['action'], $_GET['camp']) && $_GET['action']==='edit' && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'edit_camp')):
                $camp_id = absint($_GET['camp']);
                $camp = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d", $camp_id));
                if ($camp):
                    $types = $wpdb->get_results("SELECT id, name, is_active FROM ".self::table_type_terms()." ORDER BY sort_order ASC, name ASC");
                    $weeks = $wpdb->get_results("SELECT id, name, is_active FROM ".self::table_week_terms()." ORDER BY sort_order ASC, name ASC");
                    $sel_types = $wpdb->get_col($wpdb->prepare("SELECT type_id FROM ".self::table_camp_type_pivot()." WHERE camp_id=%d", $camp_id)) ?: [];
                    $sel_weeks = $wpdb->get_col($wpdb->prepare("SELECT week_id FROM ".self::table_camp_week_pivot()." WHERE camp_id=%d", $camp_id)) ?: [];
                    $act_terms = $wpdb->get_results("SELECT id, name FROM ".self::table_activity_terms()." ORDER BY sort_order ASC, name ASC");
                    $sel_acts = $wpdb->get_col($wpdb->prepare("SELECT activity_id FROM ".self::table_camp_activity_pivot()." WHERE camp_id=%d", $camp_id)) ?: [];
                    $sel_act_names = [];
                    if ($act_terms) { foreach ($act_terms as $t){ if (in_array($t->id, $sel_acts, true)) $sel_act_names[] = $t->name; } }
            ?>
                <hr />
                <h2><?php esc_html_e('Edit Camp', 'creativedbs'); ?></h2>
                <form method="post">
                    <?php wp_nonce_field('save_camp'); ?>
                    <input type="hidden" name="camp_id" value="<?php echo esc_attr($camp->id); ?>" />
                    <table class="form-table" role="presentation">
                        <tr><th><?php esc_html_e('Camp Name'); ?></th><td><input type="text" name="camp_name" class="regular-text" value="<?php echo esc_attr($camp->camp_name); ?>" required /></td></tr>
                        <tr><th><?php esc_html_e('Opening Day'); ?></th><td><input type="date" name="opening_day" value="<?php echo esc_attr($camp->opening_day); ?>" /></td></tr>
                        <tr><th><?php esc_html_e('Closing Day'); ?></th><td><input type="date" name="closing_day" value="<?php echo esc_attr($camp->closing_day); ?>" /></td></tr>
                        <tr><th><?php esc_html_e('Lowest Rate'); ?></th><td><input type="text" name="minprice_2026" value="<?php echo esc_attr($camp->minprice_2026); ?>" /></td></tr>
                        <tr><th><?php esc_html_e('Highest Rate'); ?></th><td><input type="text" name="maxprice_2026" value="<?php echo esc_attr($camp->maxprice_2026); ?>" /></td></tr>

                        <tr><th><?php esc_html_e('Email'); ?></th><td><input type="email" name="email" class="regular-text" value="<?php echo esc_attr($camp->email); ?>" /></td></tr>
                        <tr><th><?php esc_html_e('Phone'); ?></th><td><input type="text" name="phone" class="regular-text" value="<?php echo esc_attr($camp->phone); ?>" /></td></tr>
                        <tr><th><?php esc_html_e('Website URL'); ?></th><td><input type="url" name="website" class="regular-text" value="<?php echo esc_attr($camp->website); ?>" /></td></tr>
                        <tr><th><?php esc_html_e('Camp Directors'); ?></th><td><textarea name="camp_directors" class="large-text" rows="2"><?php echo esc_textarea($camp->camp_directors); ?></textarea></td></tr>

                        <tr><th><?php esc_html_e('Address'); ?></th><td><input type="text" name="address" class="regular-text" value="<?php echo esc_attr($camp->address); ?>" /></td></tr>
                        <tr><th><?php esc_html_e('City'); ?></th><td><input type="text" name="city" class="regular-text" value="<?php echo esc_attr($camp->city); ?>" /></td></tr>
                        <tr><th><?php esc_html_e('State'); ?></th><td><input type="text" name="state" class="regular-text" value="<?php echo esc_attr($camp->state); ?>" /></td></tr>
                        <tr><th><?php esc_html_e('Zip'); ?></th><td><input type="text" name="zip" class="regular-text" value="<?php echo esc_attr($camp->zip); ?>" /></td></tr>

                        <tr><th><?php esc_html_e('About Camp'); ?></th><td><?php wp_editor($camp->about_camp, 'about_camp', ['textarea_name'=>'about_camp','textarea_rows'=>6]); ?></td></tr>

                        <tr><th><?php esc_html_e('Photos (CSV of URLs)'); ?></th><td><textarea name="photos" class="large-text" rows="3"><?php echo esc_textarea($camp->photos); ?></textarea></td></tr>

                        <tr><th><?php esc_html_e('Logo URL'); ?></th>
                            <td>
                                <img id="logo_preview" src="<?php echo esc_url($camp->logo); ?>" style="max-height:60px;<?php echo $camp->logo?'':'display:none;'; ?>" />
                                <input type="text" id="logo" name="logo" class="regular-text" value="<?php echo esc_attr($camp->logo); ?>" />
                                <button id="cdbs-btn-logo" class="button"><?php esc_html_e('Select image from library'); ?></button>
                            </td>
                        </tr>
                        <tr><th><?php esc_html_e('Search Image URL'); ?></th>
                            <td>
                                <img id="search_image_preview" src="<?php echo esc_url($camp->search_image); ?>" style="max-height:60px;<?php echo $camp->search_image?'':'display:none;'; ?>" />
                                <input type="text" id="search_image" name="search_image" class="regular-text" value="<?php echo esc_attr($camp->search_image); ?>" />
                                <button id="cdbs-btn-search-image" class="button"><?php esc_html_e('Select image from library'); ?></button>
                            </td>
                        </tr>

                        <tr><th><?php esc_html_e('Camp Types'); ?></th>
                            <td>
                                <?php foreach ($types as $t): ?>
                                    <label style="display:inline-block;margin:0 12px 6px 0;">
                                        <input type="checkbox" name="type_ids[]" value="<?php echo esc_attr($t->id); ?>" <?php checked(in_array($t->id, $sel_types, true)); ?> />
                                        <?php echo esc_html($t->name); ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>

                        <tr><th><?php esc_html_e('Duration / Weeks'); ?></th>
                            <td>
                                <?php foreach ($weeks as $w): ?>
                                    <label style="display:inline-block;margin:0 12px 6px 0;">
                                        <input type="checkbox" name="week_ids[]" value="<?php echo esc_attr($w->id); ?>" <?php checked(in_array($w->id, $sel_weeks, true)); ?> />
                                        <?php echo esc_html($w->name); ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>

                        <tr><th><?php esc_html_e('Activities'); ?></th>
                            <td>
                                <ul id="cdbs-activities-list" class="cdbs-chip-list" style="margin:0;padding:0;list-style:none;"></ul>
                                <input type="hidden" id="cdbs-activities-hidden" name="activity_names" value="<?php echo esc_attr(implode(',', $sel_act_names)); ?>" />
                                <input type="text" id="cdbs-activities-field" class="regular-text" placeholder="Type an activity and press Enter or comma" />
                                <p class="description">Existing activities will appear as chips. Type new ones to add; they will be auto-created and linked to this camp on save.</p>
                                <script>
                                (function(){
                                    function tokens(fieldId,listId,inputHiddenId){
                                        var field=document.getElementById(fieldId);
                                        var list=document.getElementById(listId);
                                        var hidden=document.getElementById(inputHiddenId);
                                        if(!field||!list||!hidden)return;
                                        function normalizeLabel(s){return s.replace(/\s+/g,' ').trim();}
                                        function syncHidden(){
                                            var vals=[];
                                            list.querySelectorAll('li[data-val]').forEach(function(li){vals.push(li.getAttribute('data-val'));});
                                            hidden.value=vals.join(',');
                                        }
                                        function addToken(label){
                                            label=normalizeLabel(label);
                                            if(!label) return;
                                            var exists=Array.from(list.querySelectorAll('li[data-val]')).some(function(li){return li.getAttribute('data-val').toLowerCase()===label.toLowerCase();});
                                            if(exists) return;
                                            var li=document.createElement('li'); li.setAttribute('data-val',label); li.className='cdbs-chip';
                                            li.innerHTML='<span>'+label+'</span><button type="button" aria-label="Remove">×</button>';
                                            li.querySelector('button').addEventListener('click',function(){ li.remove(); syncHidden(); });
                                            list.appendChild(li); syncHidden();
                                        }
                                        if(hidden.value){ hidden.value.split(',').map(function(s){return s.trim();}).filter(Boolean).forEach(addToken); }
                                        field.addEventListener('keydown', function(e){
                                            if(e.key==='Enter' || e.key===','){ e.preventDefault(); var val=field.value; field.value=''; addToken(val); }
                                        });
                                        var form = field.closest('form'); if(form){ form.addEventListener('submit', syncHidden); }
                                    }
                                    document.addEventListener('DOMContentLoaded', function(){
                                        tokens('cdbs-activities-field','cdbs-activities-list','cdbs-activities-hidden');
                                    });
                                })();
                                </script>
                            </td>
                        </tr>

                        <!-- Accommodations / Cabins Section -->
                        <tr><th colspan="2" style="background:#f5f5f5;padding:10px;">
                            <h3 style="margin:0;display:inline-block;"><?php esc_html_e('Accommodation Facilities (Cabins)'); ?></h3>
                            <button type="button" class="button button-small" style="float:right;" onclick="toggleAccommodationForm(0)"><?php esc_html_e('+ Add New'); ?></button>
                        </th></tr>
                        <tr>
                            <td colspan="2">
                                <div id="accommodation-form-0" style="display:none;border:1px solid #4CAF50;padding:15px;margin-bottom:15px;background:#f9fff9;">
                                    <h4 style="margin-top:0;">Add New Accommodation</h4>
                                    <p><label><strong>Name:</strong><br/><input type="text" id="acc-name-0" class="regular-text" /></label></p>
                                    <p><label><strong>Capacity:</strong><br/><input type="number" id="acc-capacity-0" class="small-text" /></label></p>
                                    <p><label><strong>Type:</strong><br/><input type="text" id="acc-type-0" class="regular-text" placeholder="e.g., Cabin, Lodge, Tent" /></label></p>
                                    <p><label><strong>Description:</strong><br/><textarea id="acc-description-0" class="large-text" rows="3"></textarea></label></p>
                                    <button type="button" class="button button-primary" onclick="saveAccommodation(0, <?php echo $camp_id; ?>)">Save</button>
                                    <button type="button" class="button" onclick="toggleAccommodationForm(0)">Cancel</button>
                                </div>
                                <?php
                                $accommodations = $wpdb->get_results($wpdb->prepare(
                                    "SELECT * FROM " . \CreativeDBS\CampMgmt\DB::table_accommodations() . " WHERE camp_id = %d ORDER BY name ASC",
                                    $camp_id
                                ));
                                if ($accommodations): foreach ($accommodations as $acc):
                                ?>
                                    <div id="accommodation-view-<?php echo $acc->id; ?>" style="border:1px solid #ddd;padding:10px;margin-bottom:10px;background:#fff;position:relative;">
                                        <strong><?php echo esc_html($acc->name); ?></strong><br/>
                                        <small><em>Capacity:</em> <?php echo esc_html($acc->capacity); ?> | <em>Type:</em> <?php echo esc_html($acc->accommodation_type); ?></small><br/>
                                        <?php if ($acc->description): ?>
                                            <small><?php echo esc_html($acc->description); ?></small>
                                        <?php endif; ?>
                                        <div style="position:absolute;top:10px;right:10px;">
                                            <button type="button" class="button button-small" onclick="toggleAccommodationForm(<?php echo $acc->id; ?>)">Edit</button>
                                            <button type="button" class="button button-small" style="color:#a00;" onclick="deleteAccommodation(<?php echo $acc->id; ?>, <?php echo $camp_id; ?>)">Delete</button>
                                        </div>
                                    </div>
                                    <div id="accommodation-form-<?php echo $acc->id; ?>" style="display:none;border:1px solid #4CAF50;padding:15px;margin-bottom:15px;background:#f9fff9;">
                                        <h4 style="margin-top:0;">Edit Accommodation</h4>
                                        <p><label><strong>Name:</strong><br/><input type="text" id="acc-name-<?php echo $acc->id; ?>" class="regular-text" value="<?php echo esc_attr($acc->name); ?>" /></label></p>
                                        <p><label><strong>Capacity:</strong><br/><input type="number" id="acc-capacity-<?php echo $acc->id; ?>" class="small-text" value="<?php echo esc_attr($acc->capacity); ?>" /></label></p>
                                        <p><label><strong>Type:</strong><br/><input type="text" id="acc-type-<?php echo $acc->id; ?>" class="regular-text" value="<?php echo esc_attr($acc->accommodation_type); ?>" /></label></p>
                                        <p><label><strong>Description:</strong><br/><textarea id="acc-description-<?php echo $acc->id; ?>" class="large-text" rows="3"><?php echo esc_textarea($acc->description); ?></textarea></label></p>
                                        <button type="button" class="button button-primary" onclick="saveAccommodation(<?php echo $acc->id; ?>, <?php echo $camp_id; ?>)">Save</button>
                                        <button type="button" class="button" onclick="toggleAccommodationForm(<?php echo $acc->id; ?>)">Cancel</button>
                                    </div>
                                <?php endforeach; else: ?>
                                    <p class="description">No accommodations added yet.</p>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- FAQs Section -->
                        <tr><th colspan="2" style="background:#f5f5f5;padding:10px;">
                            <h3 style="margin:0;display:inline-block;"><?php esc_html_e('FAQs'); ?></h3>
                            <button type="button" class="button button-small" style="float:right;" onclick="toggleFaqForm(0)"><?php esc_html_e('+ Add New'); ?></button>
                        </th></tr>
                        <tr>
                            <td colspan="2">
                                <div id="faq-form-0" style="display:none;border:1px solid #4CAF50;padding:15px;margin-bottom:15px;background:#f9fff9;">
                                    <h4 style="margin-top:0;">Add New FAQ</h4>
                                    <p><label><strong>Question:</strong><br/><input type="text" id="faq-question-0" class="large-text" /></label></p>
                                    <p><label><strong>Answer:</strong><br/><textarea id="faq-answer-0" class="large-text" rows="4"></textarea></label></p>
                                    <button type="button" class="button button-primary" onclick="saveFaq(0, <?php echo $camp_id; ?>)">Save</button>
                                    <button type="button" class="button" onclick="toggleFaqForm(0)">Cancel</button>
                                </div>
                                <?php
                                $faqs = $wpdb->get_results($wpdb->prepare(
                                    "SELECT * FROM " . \CreativeDBS\CampMgmt\DB::table_faqs() . " WHERE camp_id = %d ORDER BY sort_order ASC",
                                    $camp_id
                                ));
                                if ($faqs): foreach ($faqs as $faq):
                                ?>
                                    <div id="faq-view-<?php echo $faq->id; ?>" style="border:1px solid #ddd;padding:10px;margin-bottom:10px;background:#fff;position:relative;">
                                        <strong><?php echo esc_html($faq->question); ?></strong><br/>
                                        <small><?php echo nl2br(esc_html($faq->answer)); ?></small>
                                        <div style="position:absolute;top:10px;right:10px;">
                                            <button type="button" class="button button-small" onclick="toggleFaqForm(<?php echo $faq->id; ?>)">Edit</button>
                                            <button type="button" class="button button-small" style="color:#a00;" onclick="deleteFaq(<?php echo $faq->id; ?>, <?php echo $camp_id; ?>)">Delete</button>
                                        </div>
                                    </div>
                                    <div id="faq-form-<?php echo $faq->id; ?>" style="display:none;border:1px solid #4CAF50;padding:15px;margin-bottom:15px;background:#f9fff9;">
                                        <h4 style="margin-top:0;">Edit FAQ</h4>
                                        <p><label><strong>Question:</strong><br/><input type="text" id="faq-question-<?php echo $faq->id; ?>" class="large-text" value="<?php echo esc_attr($faq->question); ?>" /></label></p>
                                        <p><label><strong>Answer:</strong><br/><textarea id="faq-answer-<?php echo $faq->id; ?>" class="large-text" rows="4"><?php echo esc_textarea($faq->answer); ?></textarea></label></p>
                                        <button type="button" class="button button-primary" onclick="saveFaq(<?php echo $faq->id; ?>, <?php echo $camp_id; ?>)">Save</button>
                                        <button type="button" class="button" onclick="toggleFaqForm(<?php echo $faq->id; ?>)">Cancel</button>
                                    </div>
                                <?php endforeach; else: ?>
                                    <p class="description">No FAQs added yet.</p>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Sessions Section -->
                        <tr><th colspan="2" style="background:#f5f5f5;padding:10px;">
                            <h3 style="margin:0;display:inline-block;"><?php esc_html_e('Sessions (Rates & Dates)'); ?></h3>
                            <button type="button" class="button button-small" style="float:right;" onclick="toggleSessionForm(0)"><?php esc_html_e('+ Add New'); ?></button>
                        </th></tr>
                        <tr>
                            <td colspan="2">
                                <div id="session-form-0" style="display:none;border:1px solid #4CAF50;padding:15px;margin-bottom:15px;background:#f9fff9;">
                                    <h4 style="margin-top:0;">Add New Session</h4>
                                    <p><label><strong>Session Name:</strong><br/><input type="text" id="session-name-0" class="regular-text" placeholder="e.g., Week 1" /></label></p>
                                    <p><label><strong>Start Date:</strong><br/><input type="date" id="session-start-0" class="regular-text" /></label></p>
                                    <p><label><strong>End Date:</strong><br/><input type="date" id="session-end-0" class="regular-text" /></label></p>
                                    <p><label><strong>Price ($):</strong><br/><input type="number" id="session-price-0" class="regular-text" step="0.01" /></label></p>
                                    <p><label><strong>Duration:</strong><br/><input type="text" id="session-duration-0" class="regular-text" placeholder="e.g., 1 week, 5 days" /></label></p>
                                    <p><label><strong>Description:</strong><br/><textarea id="session-description-0" class="large-text" rows="3"></textarea></label></p>
                                    <button type="button" class="button button-primary" onclick="saveSession(0, <?php echo $camp_id; ?>)">Save</button>
                                    <button type="button" class="button" onclick="toggleSessionForm(0)">Cancel</button>
                                </div>
                                <?php
                                $sessions = $wpdb->get_results($wpdb->prepare(
                                    "SELECT * FROM " . \CreativeDBS\CampMgmt\DB::table_sessions() . " WHERE camp_id = %d ORDER BY start_date ASC",
                                    $camp_id
                                ));
                                if ($sessions): foreach ($sessions as $session):
                                ?>
                                    <div id="session-view-<?php echo $session->id; ?>" style="border:1px solid #ddd;padding:10px;margin-bottom:10px;background:#fff;position:relative;">
                                        <strong><?php echo esc_html($session->name); ?></strong><br/>
                                        <small>
                                            <em>Dates:</em> <?php echo esc_html(date('M j, Y', strtotime($session->start_date))); ?> - <?php echo esc_html(date('M j, Y', strtotime($session->end_date))); ?><br/>
                                            <em>Price:</em> $<?php echo number_format($session->price, 2); ?>
                                            <?php if ($session->duration): ?>
                                                | <em>Duration:</em> <?php echo esc_html($session->duration); ?>
                                            <?php endif; ?>
                                        </small>
                                        <?php if ($session->description): ?>
                                            <br/><small><?php echo nl2br(esc_html($session->description)); ?></small>
                                        <?php endif; ?>
                                        <div style="position:absolute;top:10px;right:10px;">
                                            <button type="button" class="button button-small" onclick="toggleSessionForm(<?php echo $session->id; ?>)">Edit</button>
                                            <button type="button" class="button button-small" style="color:#a00;" onclick="deleteSession(<?php echo $session->id; ?>, <?php echo $camp_id; ?>)">Delete</button>
                                        </div>
                                    </div>
                                    <div id="session-form-<?php echo $session->id; ?>" style="display:none;border:1px solid #4CAF50;padding:15px;margin-bottom:15px;background:#f9fff9;">
                                        <h4 style="margin-top:0;">Edit Session</h4>
                                        <p><label><strong>Session Name:</strong><br/><input type="text" id="session-name-<?php echo $session->id; ?>" class="regular-text" value="<?php echo esc_attr($session->name); ?>" /></label></p>
                                        <p><label><strong>Start Date:</strong><br/><input type="date" id="session-start-<?php echo $session->id; ?>" class="regular-text" value="<?php echo esc_attr($session->start_date); ?>" /></label></p>
                                        <p><label><strong>End Date:</strong><br/><input type="date" id="session-end-<?php echo $session->id; ?>" class="regular-text" value="<?php echo esc_attr($session->end_date); ?>" /></label></p>
                                        <p><label><strong>Price ($):</strong><br/><input type="number" id="session-price-<?php echo $session->id; ?>" class="regular-text" step="0.01" value="<?php echo esc_attr($session->price); ?>" /></label></p>
                                        <p><label><strong>Duration:</strong><br/><input type="text" id="session-duration-<?php echo $session->id; ?>" class="regular-text" value="<?php echo esc_attr($session->duration); ?>" /></label></p>
                                        <p><label><strong>Description:</strong><br/><textarea id="session-description-<?php echo $session->id; ?>" class="large-text" rows="3"><?php echo esc_textarea($session->description); ?></textarea></label></p>
                                        <button type="button" class="button button-primary" onclick="saveSession(<?php echo $session->id; ?>, <?php echo $camp_id; ?>)">Save</button>
                                        <button type="button" class="button" onclick="toggleSessionForm(<?php echo $session->id; ?>)">Cancel</button>
                                    </div>
                                <?php endforeach; else: ?>
                                    <p class="description">No sessions added yet.</p>
                                <?php endif; ?>
                            </td>
                        </tr>


                        <tr><th><?php esc_html_e('Camp Rating (0-5 stars)'); ?></th>
                            <td>
                                <select name="rating">
                                    <?php for ( $i = 0; $i <= 5; $i++ ) : ?>
                                        <option value="<?php echo $i; ?>" <?php selected( floatval( $camp->rating ?? 0 ), $i ); ?>>
                                            <?php echo $i; ?> <?php echo $i == 1 ? 'Star' : 'Stars'; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <p class="description">This rating will be displayed on the public camp page</p>
                            </td>
                        </tr>

                        <tr><th><?php esc_html_e('Approved'); ?></th><td><label><input type="checkbox" name="approved" <?php checked(intval($camp->approved)===1); ?> /> <?php esc_html_e('Approved'); ?></label></td></tr>
                        
                        <tr>
                            <th><?php esc_html_e('WordPress User ID'); ?></th>
                            <td>
                                <input type="number" name="wordpress_user_id" class="small-text" value="<?php echo esc_attr($camp->wordpress_user_id ?? 0); ?>" min="0" />
                                <p class="description">Link this camp to a WordPress user account. Leave as 0 if no user account exists. Find User IDs in WordPress Users list.</p>
                            </td>
                        </tr>
                    </table>
                    <p><button type="submit" name="creativedbs_save" class="button button-primary"><?php esc_html_e('Save'); ?></button></p>
                </form>

                <script>
                // Accommodation Functions
                function toggleAccommodationForm(id) {
                    var form = document.getElementById('accommodation-form-' + id);
                    var view = document.getElementById('accommodation-view-' + id);
                    if (form) {
                        form.style.display = form.style.display === 'none' ? 'block' : 'none';
                        if (view) view.style.display = form.style.display === 'none' ? 'block' : 'none';
                    }
                }

                function saveAccommodation(id, campId) {
                    var data = {
                        action: 'cdbs_save_accommodation',
                        nonce: '<?php echo wp_create_nonce('cdbs_admin_nonce'); ?>',
                        id: id,
                        camp_id: campId,
                        name: document.getElementById('acc-name-' + id).value,
                        capacity: document.getElementById('acc-capacity-' + id).value,
                        accommodation_type: document.getElementById('acc-type-' + id).value,
                        description: document.getElementById('acc-description-' + id).value
                    };

                    jQuery.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            alert('Accommodation saved successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data || 'Failed to save'));
                        }
                    });
                }

                function deleteAccommodation(id, campId) {
                    if (!confirm('Are you sure you want to delete this accommodation?')) return;

                    var data = {
                        action: 'cdbs_delete_accommodation',
                        nonce: '<?php echo wp_create_nonce('cdbs_admin_nonce'); ?>',
                        id: id
                    };

                    jQuery.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data || 'Failed to delete'));
                        }
                    });
                }

                // FAQ Functions
                function toggleFaqForm(id) {
                    var form = document.getElementById('faq-form-' + id);
                    var view = document.getElementById('faq-view-' + id);
                    if (form) {
                        form.style.display = form.style.display === 'none' ? 'block' : 'none';
                        if (view) view.style.display = form.style.display === 'none' ? 'block' : 'none';
                    }
                }

                function saveFaq(id, campId) {
                    var data = {
                        action: 'cdbs_save_faq',
                        nonce: '<?php echo wp_create_nonce('cdbs_admin_nonce'); ?>',
                        id: id,
                        camp_id: campId,
                        question: document.getElementById('faq-question-' + id).value,
                        answer: document.getElementById('faq-answer-' + id).value
                    };

                    jQuery.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            alert('FAQ saved successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data || 'Failed to save'));
                        }
                    });
                }

                function deleteFaq(id, campId) {
                    if (!confirm('Are you sure you want to delete this FAQ?')) return;

                    var data = {
                        action: 'cdbs_delete_faq',
                        nonce: '<?php echo wp_create_nonce('cdbs_admin_nonce'); ?>',
                        id: id
                    };

                    jQuery.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data || 'Failed to delete'));
                        }
                    });
                }

                // Session Functions
                function toggleSessionForm(id) {
                    var form = document.getElementById('session-form-' + id);
                    var view = document.getElementById('session-view-' + id);
                    if (form) {
                        form.style.display = form.style.display === 'none' ? 'block' : 'none';
                        if (view) view.style.display = form.style.display === 'none' ? 'block' : 'none';
                    }
                }

                function saveSession(id, campId) {
                    var data = {
                        action: 'cdbs_save_session',
                        nonce: '<?php echo wp_create_nonce('cdbs_admin_nonce'); ?>',
                        id: id,
                        camp_id: campId,
                        name: document.getElementById('session-name-' + id).value,
                        start_date: document.getElementById('session-start-' + id).value,
                        end_date: document.getElementById('session-end-' + id).value,
                        price: document.getElementById('session-price-' + id).value,
                        duration: document.getElementById('session-duration-' + id).value,
                        description: document.getElementById('session-description-' + id).value
                    };

                    jQuery.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            alert('Session saved successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data || 'Failed to save'));
                        }
                    });
                }

                function deleteSession(id, campId) {
                    if (!confirm('Are you sure you want to delete this session?')) return;

                    var data = {
                        action: 'cdbs_delete_session',
                        nonce: '<?php echo wp_create_nonce('cdbs_admin_nonce'); ?>',
                        id: id
                    };

                    jQuery.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data || 'Failed to delete'));
                        }
                    });
                }
                </script>

            <?php endif; endif; ?>
        </div>
        <?php
    }

    /*** SUBPAGES: TYPES / WEEKS / ACTIVITIES ***/
    private function render_master_page($kind, $title) {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $table = ($kind==='types') ? self::table_type_terms() : (($kind==='weeks') ? self::table_week_terms() : self::table_activity_terms());
        
        // Determine pivot table for counting usage
        $pivot_table = '';
        $pivot_column = '';
        if ($kind === 'types') {
            $pivot_table = self::table_camp_type_pivot();
            $pivot_column = 'type_id';
        } elseif ($kind === 'weeks') {
            $pivot_table = self::table_camp_week_pivot();
            $pivot_column = 'week_id';
        } else {
            $pivot_table = self::table_camp_activity_pivot();
            $pivot_column = 'activity_id';
        }
        
        // Get items with usage count
        $items = $wpdb->get_results("
            SELECT t.*, COUNT(p.camp_id) as usage_count
            FROM {$table} t
            LEFT JOIN {$pivot_table} p ON t.id = p.{$pivot_column}
            GROUP BY t.id
            ORDER BY t.sort_order ASC, t.name ASC
        ");
        
        $editing = null;
        if (isset($_GET['action'], $_GET['id']) && $_GET['action']==='edit') {
            $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d", intval($_GET['id'])));
        }
        $base = admin_url('admin.php?page='.self::SLUG.'-'.$kind);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($title); ?></h1>
            <form method="post" style="max-width:800px;">
                <?php wp_nonce_field('cdbs_master_'.$kind); ?>
                <input type="hidden" name="id" value="<?php echo $editing ? esc_attr($editing->id) : 0; ?>" />
                <h2 class="title" style="margin-top:12px;"><?php echo $editing ? 'Edit' : 'Add New'; ?> <?php echo esc_html(rtrim($title, 's')); ?></h2>
                <table class="form-table" role="presentation">
                    <tr><th><label for="name">Name</label></th><td><input type="text" id="name" name="name" class="regular-text" value="<?php echo $editing ? esc_attr($editing->name) : ''; ?>" required /></td></tr>
                    <tr><th><label for="slug">Slug</label></th><td><input type="text" id="slug" name="slug" class="regular-text" value="<?php echo $editing ? esc_attr($editing->slug) : ''; ?>" /></td></tr>
                    <tr><th><label for="is_active">Active</label></th><td><label><input type="checkbox" id="is_active" name="is_active" <?php echo ($editing ? intval($editing->is_active) : 1) ? 'checked' : ''; ?> /> Is active</label></td></tr>
                </table>
                <p><button class="button button-primary" name="cdbs_master_save" value="1"><?php echo $editing ? 'Update' : 'Add'; ?> <?php echo esc_html(rtrim($title, 's')); ?></button>
                <?php if ($editing): ?><a class="button" href="<?php echo esc_url($base); ?>">Cancel</a><?php endif; ?></p>
            </form>

            <h2 class="title" style="margin-top:24px;">All <?php echo esc_html($title); ?></h2>
            <p class="description" style="margin-bottom:10px;">💡 Drag and drop rows to reorder</p>
            <table class="widefat fixed striped cdbs-sortable-table" data-table-type="<?php echo esc_attr($kind); ?>">
                <thead><tr><th style="width:40px;"></th><th>Name</th><th>Slug</th><th>Status</th><th style="width:80px;">Usage</th><th>Actions</th></tr></thead>
                <tbody class="cdbs-sortable-tbody">
                    <?php if (!$items): ?>
                        <tr><td colspan="6">No records yet. Add one above.</td></tr>
                    <?php else: foreach ($items as $it):
                        $edit = wp_nonce_url(add_query_arg(['action'=>'edit','id'=>$it->id], $base), 'cdbs_master_edit_'.$kind.'_'.$it->id);
                        $del  = wp_nonce_url(add_query_arg(['action'=>'delete','id'=>$it->id], $base), 'cdbs_master_delete_'.$kind.'_'.$it->id);
                    ?>
                        <tr data-id="<?php echo esc_attr($it->id); ?>" style="cursor:move;">
                            <td style="text-align:center;cursor:grab;"><span class="dashicons dashicons-menu" style="color:#999;"></span></td>
                            <td><strong><?php echo esc_html($it->name); ?></strong></td>
                            <td><?php echo esc_html($it->slug); ?></td>
                            <td><?php echo $it->is_active ? 'Active' : 'Inactive'; ?></td>
                            <td style="text-align:center;">
                                <span class="dashicons dashicons-groups" style="color:#999;"></span>
                                <strong><?php echo intval($it->usage_count); ?></strong>
                            </td>
                            <td>
                                <a class="button" href="<?php echo esc_url($edit); ?>">Edit</a>
                                <a class="button button-link-delete" href="<?php echo esc_url($del); ?>">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_types_page(){ $this->render_master_page('types', 'Camp Types'); }
    public function render_weeks_page(){ $this->render_master_page('weeks', 'Durations / Weeks'); }
    public function render_activities_page(){ $this->render_master_page('activities', 'Activities'); }

    /*** IMPORT / EXPORT ***/
    
    public function render_add_camp_page(){
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $table = self::table_camps();

        // Handle POST (create)
        if (isset($_POST['creativedbs_create']) && check_admin_referer('create_camp')) {
            $camp_name = sanitize_text_field($_POST['camp_name'] ?? '');
            if ($camp_name === '') {
                echo '<div class="error"><p>Please enter a Camp Name.</p></div>';
            } else {
                $activity_names = array_map('sanitize_text_field', self::arrayize($_POST['activity_names'] ?? ''));
                $photos = sanitize_textarea_field($_POST['photos'] ?? '');

                $data = [
                    'unique_key'     => md5(uniqid('camp_', true)),
                    'camp_name'      => $camp_name,
                    'opening_day'    => self::parse_date($_POST['opening_day'] ?? ''),
                    'closing_day'    => self::parse_date($_POST['closing_day'] ?? ''),
                    'minprice_2026'  => self::to_money($_POST['minprice_2026'] ?? ''),
                    'maxprice_2026'  => self::to_money($_POST['maxprice_2026'] ?? ''),
                    'activities'     => implode(',', $activity_names),
                    'email'          => sanitize_email($_POST['email'] ?? ''),
                    'phone'          => sanitize_text_field($_POST['phone'] ?? ''),
                    'website'        => esc_url_raw($_POST['website'] ?? ''),
                    'camp_directors' => sanitize_textarea_field($_POST['camp_directors'] ?? ''),
                    'address'        => sanitize_text_field($_POST['address'] ?? ''),
                    'city'           => sanitize_text_field($_POST['city'] ?? ''),
                    'state'          => sanitize_text_field($_POST['state'] ?? ''),
                    'zip'            => sanitize_text_field($_POST['zip'] ?? ''),
                    'about_camp'     => wp_kses_post($_POST['about_camp'] ?? ''),
                    'photos'         => $photos,
                    'logo'           => esc_url_raw($_POST['logo'] ?? ''),
                    'search_image'   => esc_url_raw($_POST['search_image'] ?? ''),
                    'approved'       => isset($_POST['approved']) ? 1 : 0,
                    'created_at'     => current_time('mysql'),
                    'updated_at'     => current_time('mysql'),
                ];
                $ok = $wpdb->insert($table, $data);
                if ($ok) {
                    $camp_id = intval($wpdb->insert_id);

                    // Link terms
                    $selected_types = array_map('intval', $_POST['type_ids'] ?? []);
                    $selected_weeks = array_map('intval', $_POST['week_ids'] ?? []);

                    // Activities: ensure term creation + linking
                    $terms_table = self::table_activity_terms();
                    $pivot_act   = self::table_camp_activity_pivot();
                    $existing = $wpdb->get_results("SELECT id, name, slug FROM {$terms_table}");
                    $by_name = []; $by_slug = [];
                    if ($existing) { foreach ($existing as $t){ $by_name[strtolower($t->name)] = intval($t->id); $by_slug[$t->slug] = intval($t->id); } }
                    $link_act_ids = [];
                    foreach ($activity_names as $label) {
                        $slug = sanitize_title($label); $key = strtolower($label);
                        $aid = isset($by_name[$key]) ? $by_name[$key] : (isset($by_slug[$slug]) ? $by_slug[$slug] : 0);
                        if (!$aid) {
                            $wpdb->insert($terms_table, ['name'=>$label,'slug'=>$slug,'is_active'=>1,'created_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')]);
                            $aid = intval($wpdb->insert_id); $by_name[$key]=$aid; $by_slug[$slug]=$aid;
                        }
                        $link_act_ids[$aid] = true;
                    }
                    foreach (array_keys($link_act_ids) as $aid) { $wpdb->insert($pivot_act, ['camp_id'=>$camp_id,'activity_id'=>$aid]); }

                    // Types
                    $pivot_types = self::table_camp_type_pivot();
                    foreach ($selected_types as $tid) { if ($tid>0) $wpdb->insert($pivot_types, ['camp_id'=>$camp_id,'type_id'=>$tid]); }
                    // Weeks
                    $pivot_weeks = self::table_camp_week_pivot();
                    foreach ($selected_weeks as $wid) { if ($wid>0) $wpdb->insert($pivot_weeks, ['camp_id'=>$camp_id,'week_id'=>$wid]); }

                    // Redirect to main list
                    wp_safe_redirect( add_query_arg(['page'=>self::SLUG], admin_url('admin.php')) );
                    exit;
                } else {
                    echo '<div class="error"><p>Insert failed. Please try again.</p></div>';
                }
            }
        }

        // Load term lists
        $types = $wpdb->get_results("SELECT id, name, is_active FROM ".self::table_type_terms()." WHERE is_active=1 ORDER BY sort_order ASC, name ASC");
        $weeks = $wpdb->get_results("SELECT id, name, is_active FROM ".self::table_week_terms()." WHERE is_active=1 ORDER BY sort_order ASC, name ASC");
        $act_terms = $wpdb->get_results("SELECT id, name, is_active FROM ".self::table_activity_terms()." WHERE is_active=1 ORDER BY sort_order ASC, name ASC");

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Add Camp', 'creativedbs'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('create_camp'); ?>
                <table class="form-table" role="presentation">
                    <tr><th><?php esc_html_e('Camp Name'); ?></th><td><input type="text" name="camp_name" class="regular-text" required /></td></tr>
                    <tr><th><?php esc_html_e('Opening Day'); ?></th><td><input type="date" name="opening_day" /></td></tr>
                    <tr><th><?php esc_html_e('Closing Day'); ?></th><td><input type="date" name="closing_day" /></td></tr>
                    <tr><th><?php esc_html_e('Lowest Rate'); ?></th><td><input type="text" name="minprice_2026" /></td></tr>
                    <tr><th><?php esc_html_e('Highest Rate'); ?></th><td><input type="text" name="maxprice_2026" /></td></tr>

                    <tr><th><?php esc_html_e('Email'); ?></th><td><input type="email" name="email" class="regular-text" /></td></tr>
                    <tr><th><?php esc_html_e('Phone'); ?></th><td><input type="text" name="phone" class="regular-text" /></td></tr>
                    <tr><th><?php esc_html_e('Website URL'); ?></th><td><input type="url" name="website" class="regular-text" /></td></tr>
                    <tr><th><?php esc_html_e('Camp Directors'); ?></th><td><textarea name="camp_directors" class="large-text" rows="2"></textarea></td></tr>

                    <tr><th><?php esc_html_e('Address'); ?></th><td><input type="text" name="address" class="regular-text" /></td></tr>
                    <tr><th><?php esc_html_e('City'); ?></th><td><input type="text" name="city" class="regular-text" /></td></tr>
                    <tr><th><?php esc_html_e('State'); ?></th><td><input type="text" name="state" class="regular-text" /></td></tr>
                    <tr><th><?php esc_html_e('Zip'); ?></th><td><input type="text" name="zip" class="regular-text" /></td></tr>

                    <tr><th><?php esc_html_e('About Camp'); ?></th><td><?php wp_editor('', 'about_camp', ['textarea_name'=>'about_camp','textarea_rows'=>6]); ?></td></tr>

                    <tr><th><?php esc_html_e('Photos (CSV of URLs)'); ?></th><td><textarea name="photos" class="large-text" rows="3"></textarea></td></tr>

                    <tr><th><?php esc_html_e('Logo URL'); ?></th>
                        <td>
                            <img id="logo_preview" src="" style="max-height:60px;display:none;" />
                            <input type="text" id="logo" name="logo" class="regular-text" />
                            <button id="cdbs-btn-logo" class="button"><?php esc_html_e('Select image from library'); ?></button>
                        </td>
                    </tr>
                    <tr><th><?php esc_html_e('Search Image URL'); ?></th>
                        <td>
                            <img id="search_image_preview" src="" style="max-height:60px;display:none;" />
                            <input type="text" id="search_image" name="search_image" class="regular-text" />
                            <button id="cdbs-btn-search-image" class="button"><?php esc_html_e('Select image from library'); ?></button>
                        </td>
                    </tr>

                    <tr><th><?php esc_html_e('Camp Types'); ?></th>
                        <td>
                            <?php foreach ($types as $t): ?>
                                <label style="display:inline-block;margin:0 12px 6px 0;">
                                    <input type="checkbox" name="type_ids[]" value="<?php echo esc_attr($t->id); ?>" />
                                    <?php echo esc_html($t->name); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>

                    <tr><th><?php esc_html_e('Duration / Weeks'); ?></th>
                        <td>
                            <?php foreach ($weeks as $w): ?>
                                <label style="display:inline-block;margin:0 12px 6px 0;">
                                    <input type="checkbox" name="week_ids[]" value="<?php echo esc_attr($w->id); ?>" />
                                    <?php echo esc_html($w->name); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>

                    <tr><th><?php esc_html_e('Activities'); ?></th>
                        <td>
                            <ul id="cdbs-activities-list" class="cdbs-chip-list" style="margin:0;padding:0;list-style:none;"></ul>
                            <input type="hidden" id="cdbs-activities-hidden" name="activity_names" value="" />
                            <input type="text" id="cdbs-activities-field" class="regular-text" placeholder="Type an activity and press Enter or comma" />
                            <p class="description">Type new activities to add; they will be auto-created and linked to this camp on save.</p>
                            <script>
                            (function(){
                                function tokens(fieldId,listId,inputHiddenId){
                                    var field=document.getElementById(fieldId);
                                    var list=document.getElementById(listId);
                                    var hidden=document.getElementById(inputHiddenId);
                                    if(!field||!list||!hidden)return;
                                    function normalizeLabel(s){return s.replace(/\s+/g,' ').trim();}
                                    function syncHidden(){
                                        var vals=[];
                                        list.querySelectorAll('li[data-val]').forEach(function(li){vals.push(li.getAttribute('data-val'));});
                                        hidden.value=vals.join(',');
                                    }
                                    function addToken(label){
                                        label=normalizeLabel(label);
                                        if(!label) return;
                                        var exists=Array.from(list.querySelectorAll('li[data-val]')).some(function(li){return li.getAttribute('data-val').toLowerCase()===label.toLowerCase();});
                                        if(exists) return;
                                        var li=document.createElement('li'); li.setAttribute('data-val',label); li.className='cdbs-chip';
                                        li.innerHTML='<span>'+label+'</span><button type="button" aria-label="Remove">×</button>';
                                        li.querySelector('button').addEventListener('click',function(){ li.remove(); syncHidden(); });
                                        list.appendChild(li); syncHidden();
                                    }
                                    field.addEventListener('keydown', function(e){
                                        if(e.key==='Enter' || e.key===','){ e.preventDefault(); var val=field.value; field.value=''; addToken(val); }
                                    });
                                    var form = field.closest('form'); if(form){ form.addEventListener('submit', syncHidden); }
                                }
                                document.addEventListener('DOMContentLoaded', function(){
                                    tokens('cdbs-activities-field','cdbs-activities-list','cdbs-activities-hidden');
                                });
                            })();
                            </script>
                        </td>
                    </tr>

                    <tr><th><?php esc_html_e('Approved'); ?></th><td><label><input type="checkbox" name="approved" /> <?php esc_html_e('Approved'); ?></label></td></tr>
                </table>
                <p><button type="submit" name="creativedbs_create" class="button button-primary"><?php esc_html_e('Create'); ?></button></p>
            </form>
        </div>
        <?php
    }
}

CreativeDBS_Camp_Management::instance();

// Inline chips style
add_action('admin_init', function(){
    $css = ".cdbs-chip{display:inline-flex;align-items:center;gap:6px;background:#eef3fb;border:1px solid #c6d8ff;border-radius:999px;padding:2px 8px;margin:0 6px 6px 0;font-size:12px;line-height:20px}.cdbs-chip button{border:0;background:transparent;cursor:pointer;font-size:14px;line-height:14px}.cdbs-chip-list .cdbs-chip{background:#f3f4f6;border-color:#d1d5db}";
    wp_register_style('creativedbs-camp-mgmt-inline', false);
    wp_enqueue_style('creativedbs-camp-mgmt-inline');
    wp_add_inline_style('creativedbs-camp-mgmt-inline', $css);
});

endif;