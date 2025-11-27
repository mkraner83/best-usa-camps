<?php
/**
 * Phase 7 migrations: add `internal_link` and create `camp_credentials`.
 *
 * @package CreativeDBS\CampMgmt
 */
namespace CreativeDBS\CampMgmt;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Migrations_Phase7 {

    /**
     * Run the migration once for admins in wp-admin.
     */
    public static function run() {
        if ( ! is_admin() ) {
            return;
        }
        if ( get_option( 'creativedbs_campmgmt_phase7_migrated' ) ) {
            return;
        }

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $camps   = $wpdb->prefix . 'camp_management';
        $charset = $wpdb->get_charset_collate();

        // 1) Add `internal_link` column if missing.
        $col = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$camps} LIKE %s", 'internal_link' ) );
        if ( ! $col ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query( "ALTER TABLE {$camps} ADD COLUMN internal_link VARCHAR(255) NULL AFTER website" );
        }

        // 2) Create credentials table keyed by unique_key.
        $creds = $wpdb->prefix . 'camp_credentials';
        $sql   = "CREATE TABLE {$creds} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            unique_key VARCHAR(64) NOT NULL,
            username VARCHAR(191) NULL,
            secret_enc LONGTEXT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY unique_key (unique_key)
        ) {$charset};";
        dbDelta( $sql );

        update_option( 'creativedbs_campmgmt_phase7_migrated', 1 );
    }
}