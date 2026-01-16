<?php
/**
 * Migration: Add wordpress_user_id column to wp_camp_management
 * 
 * This migration adds a foreign key relationship between camps and WordPress users,
 * enabling automatic user creation during camp imports.
 * 
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt;

defined( 'ABSPATH' ) || exit;

class Migration_Add_WordPress_User_ID {

    /**
     * Run the migration.
     */
    public static function run() {
        global $wpdb;
        
        $table = DB::table_camps();
        
        // Check if column already exists
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = %s AND COLUMN_NAME = 'wordpress_user_id'",
                $wpdb->prefix . 'camp_management'
            )
        );
        
        if ( ! empty( $column_exists ) ) {
            // Column already exists, nothing to do
            return;
        }
        
        // Add the column
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query(
            "ALTER TABLE {$table} 
            ADD COLUMN wordpress_user_id BIGINT UNSIGNED NULL 
            AFTER updated_at,
            ADD INDEX idx_wordpress_user_id (wordpress_user_id)"
        );
        
        // Log the migration
        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( 'Migration: Added wordpress_user_id column to wp_camp_management' );
        }
    }
}
