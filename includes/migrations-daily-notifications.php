<?php
/**
 * Migration: Add daily notification queue table
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt;

defined( 'ABSPATH' ) || exit;

/**
 * Run migration to add notification queue table
 */
function migrate_daily_notification_queue() {
	global $wpdb;
	
	// Check if migration already ran
	if ( get_option( 'creativedbs_campmgmt_daily_notifications_migrated' ) ) {
		return;
	}
	
	$table_name = $wpdb->prefix . 'camp_notification_queue';
	$charset_collate = $wpdb->get_charset_collate();
	
	$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		camp_id BIGINT UNSIGNED NOT NULL,
		camp_name VARCHAR(255) NOT NULL,
		update_type VARCHAR(50) NOT NULL,
		update_time DATETIME NOT NULL,
		photos_uploaded TINYINT(1) DEFAULT 0,
		logo_uploaded TINYINT(1) DEFAULT 0,
		PRIMARY KEY (id),
		KEY camp_id (camp_id),
		KEY update_time (update_time)
	) {$charset_collate};";
	
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	
	// Mark migration as complete
	update_option( 'creativedbs_campmgmt_daily_notifications_migrated', true );
}

// Run migration on admin init
add_action( 'admin_init', __NAMESPACE__ . '\\migrate_daily_notification_queue' );
