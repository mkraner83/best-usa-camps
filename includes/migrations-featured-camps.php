<?php
/**
 * Migration: Add featured camps columns to camp_management table.
 *
 * @package CreativeDBS\CampMgmt
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$table = $wpdb->prefix . 'camp_management';

// Check if columns already exist
$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$table}" );
$column_names = wp_list_pluck( $columns, 'Field' );

// Add featured category columns
$columns_to_add = [
	'is_featured' => "ALTER TABLE {$table} ADD COLUMN is_featured TINYINT(1) DEFAULT 0",
	'is_best_day' => "ALTER TABLE {$table} ADD COLUMN is_best_day TINYINT(1) DEFAULT 0",
	'is_best_overnight' => "ALTER TABLE {$table} ADD COLUMN is_best_overnight TINYINT(1) DEFAULT 0",
	'is_best_girls' => "ALTER TABLE {$table} ADD COLUMN is_best_girls TINYINT(1) DEFAULT 0",
	'is_best_boys' => "ALTER TABLE {$table} ADD COLUMN is_best_boys TINYINT(1) DEFAULT 0",
	'featured_order' => "ALTER TABLE {$table} ADD COLUMN featured_order INT DEFAULT 0",
	'day_order' => "ALTER TABLE {$table} ADD COLUMN day_order INT DEFAULT 0",
	'overnight_order' => "ALTER TABLE {$table} ADD COLUMN overnight_order INT DEFAULT 0",
	'girls_order' => "ALTER TABLE {$table} ADD COLUMN girls_order INT DEFAULT 0",
	'boys_order' => "ALTER TABLE {$table} ADD COLUMN boys_order INT DEFAULT 0",
];

foreach ( $columns_to_add as $column => $sql ) {
	if ( ! in_array( $column, $column_names ) ) {
		$wpdb->query( $sql );
		error_log( "CDBS: Added column {$column} to {$table}" );
	}
}

// Mark migration as complete
update_option( 'creativedbs_campmgmt_featured_migrated', 1 );

error_log( 'CDBS: Featured camps migration completed' );
