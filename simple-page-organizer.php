<?php
/**
 * Plugin Name: Creative DBS - Simple Page Organizer
 * Description: Organize WordPress pages into categories (System, Camp, Website) with visual filters and color-coded labels.
 * Version: 1.0.0
 * Author: CreativeDBS
 * Text Domain: simple-page-organizer
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

class Simple_Page_Organizer {
    
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'register_taxonomy']);
        add_action('admin_init', [$this, 'create_default_terms']);
        add_action('restrict_manage_posts', [$this, 'add_category_tabs']);
        add_action('manage_pages_custom_column', [$this, 'display_taxonomy_column'], 10, 2);
        add_filter('manage_pages_columns', [$this, 'add_taxonomy_column']);
        add_action('admin_head', [$this, 'add_custom_styles']);
        add_action('save_post_page', [$this, 'auto_categorize_page'], 10, 3);
        add_action('admin_notices', [$this, 'show_admin_notice']);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_filter('parse_query', [$this, 'filter_pages_by_category']);
    }
    
    /**
     * Register Page Category taxonomy
     */
    public function register_taxonomy() {
        $labels = [
            'name'              => 'Page Categories',
            'singular_name'     => 'Page Category',
            'search_items'      => 'Search Categories',
            'all_items'         => 'All Categories',
            'edit_item'         => 'Edit Category',
            'update_item'       => 'Update Category',
            'add_new_item'      => 'Add New Category',
            'new_item_name'     => 'New Category Name',
            'menu_name'         => 'Page Categories',
        ];
        
        $args = [
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => false, // We'll add our own custom column
            'show_in_rest'      => true,
            'show_in_quick_edit' => true,
            'meta_box_cb'       => 'post_categories_meta_box',
            'rewrite'           => false,
        ];
        
        register_taxonomy('page_category', ['page'], $args);
    }
    
    /**
     * Create default terms on first activation
     */
    public function create_default_terms() {
        $default_terms = [
            [
                'name' => 'System Pages',
                'slug' => 'system-pages',
                'description' => 'Login, dashboard, password reset, and other system pages'
            ],
            [
                'name' => 'Camp Pages',
                'slug' => 'camp-pages',
                'description' => 'Individual camp profile pages'
            ],
            [
                'name' => 'Website Pages',
                'slug' => 'website-pages',
                'description' => 'About, contact, and other general website pages'
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'description' => 'Miscellaneous pages'
            ],
            [
                'name' => 'Drafts/Old/Backup',
                'slug' => 'drafts-old-backup',
                'description' => 'Draft pages, old versions, and backups'
            ],
        ];
        
        // Always check and create missing terms (allows adding new categories in updates)
        foreach ($default_terms as $term) {
            if (!term_exists($term['slug'], 'page_category')) {
                wp_insert_term($term['name'], 'page_category', [
                    'slug' => $term['slug'],
                    'description' => $term['description']
                ]);
            }
        }
    }
    
    /**
     * Add category tabs to pages list
     */
    public function add_category_tabs($post_type) {
        if ('page' !== $post_type) {
            return;
        }
        
        $taxonomy = 'page_category';
        $current_category = isset($_GET['page_category_tab']) ? sanitize_text_field($_GET['page_category_tab']) : 'uncategorized';
        
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);
        
        // Count uncategorized pages
        $uncategorized_count = $this->get_uncategorized_count();
        
        // Output script to move tabs to new row after page load
        echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            var tabs = $(".spo-category-tabs");
            if (tabs.length) {
                $(".tablenav.top .actions").first().after(tabs);
            }
        });
        </script>';
        
        echo '<div class="spo-category-tabs">';
        
        // Uncategorized tab (default)
        $uncategorized_class = ($current_category === 'uncategorized') ? 'spo-tab-active' : '';
        printf(
            '<a href="%s" class="spo-tab %s spo-tab-uncategorized">📄 Uncategorized (%d)</a>',
            admin_url('edit.php?post_type=page&page_category_tab=uncategorized'),
            $uncategorized_class,
            $uncategorized_count
        );
        
        // Category tabs
        if (!empty($terms)) {
            foreach ($terms as $term) {
                $is_active = ($current_category === $term->slug) ? 'spo-tab-active' : '';
                $icon = $this->get_category_icon($term->slug);
                $count = $this->get_category_count($term->slug);
                printf(
                    '<a href="%s" class="spo-tab %s spo-tab-%s">%s %s (%d)</a>',
                    admin_url('edit.php?post_type=page&page_category_tab=' . $term->slug),
                    $is_active,
                    esc_attr($term->slug),
                    $icon,
                    esc_html($term->name),
                    $count
                );
            }
        }
        
        echo '</div>';

    }
    
    /**
     * Get icon for category
     */
    private function get_category_icon($slug) {
        $icons = [
            'system-pages' => '⚙️',
            'camp-pages' => '🏕️',
            'website-pages' => '🌐',
            'other' => '📦',
            'drafts-old-backup' => '🗄️',
        ];
        return isset($icons[$slug]) ? $icons[$slug] : '📁';
    }
    
    /**
     * Get count of uncategorized pages
     */
    private function get_uncategorized_count() {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = %s)
            WHERE p.post_type = %s
            AND p.post_status IN ('publish', 'draft', 'pending', 'private')
            AND tt.term_taxonomy_id IS NULL
        ", 'page_category', 'page'));
        
        return (int) $count;
    }
    
    /**
     * Get accurate count for each category including drafts
     */
    private function get_category_count($term_slug) {
        global $wpdb;
        
        $term = get_term_by('slug', $term_slug, 'page_category');
        if (!$term) {
            return 0;
        }
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE p.post_type = %s
            AND p.post_status IN ('publish', 'draft', 'pending', 'private')
            AND tt.taxonomy = %s
            AND tt.term_id = %d
        ", 'page', 'page_category', $term->term_id));
        
        return (int) $count;
    }
    
    /**
     * Filter pages by selected category tab
     */
    public function filter_pages_by_category($query) {
        global $pagenow, $typenow;
        
        if (!is_admin() || $pagenow !== 'edit.php' || $typenow !== 'page') {
            return $query;
        }
        
        $current_category = isset($_GET['page_category_tab']) ? sanitize_text_field($_GET['page_category_tab']) : 'uncategorized';
        
        // Include all post statuses (published, draft, pending, private)
        $query->set('post_status', ['publish', 'draft', 'pending', 'private']);
        
        if ($current_category === 'uncategorized') {
            // Show only uncategorized pages
            $query->set('tax_query', [
                [
                    'taxonomy' => 'page_category',
                    'operator' => 'NOT EXISTS'
                ]
            ]);
        } else {
            // Show pages in selected category
            $query->set('tax_query', [
                [
                    'taxonomy' => 'page_category',
                    'field' => 'slug',
                    'terms' => $current_category
                ]
            ]);
        }
        
        return $query;
    }
    
    /**
     * Add custom column to pages list
     */
    public function add_taxonomy_column($columns) {
        // Insert after title column
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ('title' === $key) {
                $new_columns['page_category'] = 'Category';
            }
        }
        return $new_columns;
    }
    
    /**
     * Display taxonomy in custom column
     */
    public function display_taxonomy_column($column, $post_id) {
        if ('page_category' !== $column) {
            return;
        }
        
        $terms = get_the_terms($post_id, 'page_category');
        
        if (empty($terms) || is_wp_error($terms)) {
            echo '<span class="spo-label spo-uncategorized">Uncategorized</span>';
            return;
        }
        
        $term_names = [];
        foreach ($terms as $term) {
            $class = 'spo-label spo-' . esc_attr($term->slug);
            $term_names[] = sprintf(
                '<span class="%s">%s</span>',
                $class,
                esc_html($term->name)
            );
        }
        
        echo implode(', ', $term_names);
    }
    
    /**
     * Add custom CSS for category labels
     */
    public function add_custom_styles() {
        $screen = get_current_screen();
        if (!$screen || 'edit-page' !== $screen->id) {
            return;
        }
        ?>
        <style>
            .spo-label {
                display: inline-block;
                padding: 3px 10px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                white-space: nowrap;
            }
            .spo-system-pages {
                background: #e3f2fd;
                color: #1565c0;
                border: 1px solid #90caf9;
            }
            .spo-camp-pages {
                background: #f3e5f5;
                color: #6a1b9a;
                border: 1px solid #ce93d8;
            }
            .spo-website-pages {
                background: #e8f5e9;
                color: #2e7d32;
                border: 1px solid #81c784;
            }
            .spo-other {
                background: #fff3e0;
                color: #e65100;
                border: 1px solid #ffb74d;
            }
            .spo-drafts-old-backup {
                background: #fce4ec;
                color: #c2185b;
                border: 1px solid #f48fb1;
            }
            .spo-uncategorized {
                background: #fafafa;
                color: #757575;
                border: 1px solid #e0e0e0;
            }
            
            /* Category Tabs */
            .spo-category-tabs {
                display: flex;
                gap: 8px;
                margin: 15px 0 15px 0;
                padding: 12px;
                border-top: 1px solid #ddd;
                border-bottom: 2px solid #ddd;
                flex-wrap: wrap;
                width: 100% !important;
                clear: both !important;
                background: #f9f9f9;
                border-radius: 4px;
                float: none !important;
                display: block !important;
            }
            .spo-category-tabs::after {
                content: "";
                display: table;
                clear: both;
            }
            .spo-tab {
                display: inline-block;
                padding: 8px 16px;
                background: #f0f0f0;
                color: #444;
                text-decoration: none;
                border-radius: 6px 6px 0 0;
                font-weight: 500;
                font-size: 13px;
                transition: all 0.2s ease;
                border: 1px solid #ddd;
                border-bottom: none;
                position: relative;
                bottom: -2px;
            }
            .spo-tab:hover {
                background: #e8e8e8;
                color: #000;
            }
            .spo-tab-active {
                background: #fff;
                color: #000;
                border-color: #ddd;
                border-bottom: 2px solid #fff;
                font-weight: 600;
                box-shadow: 0 -2px 4px rgba(0,0,0,0.05);
            }
            .spo-tab-uncategorized.spo-tab-active {
                border-top: 3px solid #757575;
            }
            .spo-tab-system-pages.spo-tab-active {
                border-top: 3px solid #1565c0;
            }
            .spo-tab-camp-pages.spo-tab-active {
                border-top: 3px solid #6a1b9a;
            }
            .spo-tab-website-pages.spo-tab-active {
                border-top: 3px solid #2e7d32;
            }
            .spo-tab-other.spo-tab-active {
                border-top: 3px solid #e65100;
            }
            .spo-tab-drafts-old-backup.spo-tab-active {
                border-top: 3px solid #c2185b;
            }
        </style>
        <?php
    }
    
    /**
     * Auto-categorize pages based on patterns
     */
    public function auto_categorize_page($post_id, $post, $update) {
        // Skip autosaves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Skip if already has a category
        $existing_terms = wp_get_post_terms($post_id, 'page_category');
        if (!empty($existing_terms) && !is_wp_error($existing_terms)) {
            return;
        }
        
        $title = strtolower($post->post_title);
        $slug = $post->post_name;
        $category = null;
        
        // System pages patterns
        $system_patterns = [
            'dashboard', 'login', 'logout', 'password', 'reset', 'sign up', 
            'signup', 'register', 'camp-login', 'camp-dashboard', 'set-password',
            'camp-signup'
        ];
        
        foreach ($system_patterns as $pattern) {
            if (stripos($title, $pattern) !== false || stripos($slug, $pattern) !== false) {
                $category = 'system-pages';
                break;
            }
        }
        
        // Camp pages patterns (if not system)
        if (!$category) {
            $camp_patterns = ['camp-'];
            foreach ($camp_patterns as $pattern) {
                if (stripos($slug, $pattern) !== false) {
                    $category = 'camp-pages';
                    break;
                }
            }
        }
        
        // Apply category if detected
        if ($category) {
            $term = get_term_by('slug', $category, 'page_category');
            if ($term) {
                wp_set_post_terms($post_id, [$term->term_id], 'page_category');
            }
        }
    }
    
    /**
     * Show admin notice with instructions
     */
    public function show_admin_notice() {
        $screen = get_current_screen();
        if (!$screen || 'edit-page' !== $screen->id) {
            return;
        }
        
        // Only show once
        if (get_option('spo_notice_dismissed')) {
            return;
        }
        
        ?>
        <div class="notice notice-success is-dismissible" data-spo-notice="1">
            <p><strong>Simple Page Organizer activated!</strong></p>
            <p>📁 Your pages can now be organized into: <strong>System Pages</strong>, <strong>Camp Pages</strong>, and <strong>Website Pages</strong>.</p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li>Use the dropdown filter above to view pages by category</li>
                <li>Edit any page and select a category from the right sidebar</li>
                <li>New camp pages will be auto-categorized</li>
                <li>Bulk edit multiple pages to assign categories at once</li>
            </ul>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('[data-spo-notice] .notice-dismiss').on('click', function() {
                $.post(ajaxurl, {
                    action: 'spo_dismiss_notice'
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Add settings/help page
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=page',
            'Page Organizer Help',
            'Organizer Help',
            'manage_options',
            'page-organizer-help',
            [$this, 'render_help_page']
        );
    }
    
    /**
     * Render help page
     */
    public function render_help_page() {
        ?>
        <div class="wrap">
            <h1>Simple Page Organizer - Help</h1>
            
            <div class="card" style="max-width: 800px;">
                <h2>How to Use</h2>
                
                <h3>📊 View Pages by Category</h3>
                <p>On the Pages screen, use the "All Page Categories" dropdown to filter your pages. This is especially useful when you have hundreds of camp pages!</p>
                
                <h3>🏷️ Categorize Pages</h3>
                <p>There are three ways to categorize your pages:</p>
                <ol>
                    <li><strong>Edit Individual Page:</strong> Open any page and select a category from the "Page Categories" box in the right sidebar.</li>
                    <li><strong>Bulk Edit:</strong> Select multiple pages, choose "Edit" from the Bulk Actions dropdown, and assign a category to all at once.</li>
                    <li><strong>Auto-Categorization:</strong> New camp pages (with "camp-" in the slug) are automatically categorized as "Camp Pages".</li>
                </ol>
                
                <h3>🎨 Category Types</h3>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><strong style="color: #1565c0;">System Pages</strong> - Login, Dashboard, Password Reset, etc.</li>
                    <li><strong style="color: #6a1b9a;">Camp Pages</strong> - Individual camp profile pages</li>
                    <li><strong style="color: #2e7d32;">Website Pages</strong> - About, Contact, and other general pages</li>
                </ul>
                
                <h3>⚡ Auto-Categorization Rules</h3>
                <p>Pages are automatically categorized based on their title/slug:</p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li>Contains "dashboard", "login", "password", "signup" → <strong>System Pages</strong></li>
                    <li>Slug starts with "camp-" → <strong>Camp Pages</strong></li>
                </ul>
                
                <h3>🔧 Manage Categories</h3>
                <p>Visit <strong>Pages → Page Categories</strong> to add, edit, or delete categories. You can create custom categories beyond the default three!</p>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Tips for Managing 500+ Camp Pages</h2>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li>Use the category filter dropdown to quickly find camps</li>
                    <li>Use WordPress's search box to find specific camps by name</li>
                    <li>Consider creating sub-categories like "Featured Camps" or "Draft Camps" if needed</li>
                    <li>The colored labels make it easy to spot page types at a glance</li>
                </ul>
            </div>
        </div>
        <?php
    }
}

// AJAX handler for dismissing notice
add_action('wp_ajax_spo_dismiss_notice', function() {
    update_option('spo_notice_dismissed', true);
    wp_die();
});

// Initialize plugin
add_action('plugins_loaded', function() {
    Simple_Page_Organizer::instance();
});

// Activation hook to ensure terms are created
register_activation_hook(__FILE__, function() {
    delete_option('spo_terms_created');
    delete_option('spo_notice_dismissed');
});
