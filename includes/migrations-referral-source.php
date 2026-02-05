<?php
/**
 * Migration: Add referral_source column to camp_management table
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt;

defined( 'ABSPATH' ) || exit;

/**
 * Add referral_source column
 */
function add_referral_source_column() {
	global $wpdb;
	$table_name = DB::table_camps();
	
	// Check if column already exists
	$column_exists = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s 
			AND TABLE_NAME = %s 
			AND COLUMN_NAME = 'referral_source'",
			DB_NAME,
			$table_name
		)
	);
	
	if ( empty( $column_exists ) ) {
		$wpdb->query(
			"ALTER TABLE {$table_name} 
			ADD COLUMN referral_source VARCHAR(100) NULL 
			AFTER video_url"
		);
	}
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\add_referral_source_column' );
