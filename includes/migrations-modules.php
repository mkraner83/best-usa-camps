<?php
/**
 * Database migrations for camp modules: Accommodations, FAQs, Sessions
 *
 * @package CreativeDBS\CampMgmt
 */
namespace CreativeDBS\CampMgmt;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Migrations_Modules {

	/**
	 * Run the migration for new modules
	 */
	public static function run() {
		if ( ! is_admin() ) {
			return;
		}

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		// Ensure accommodation_type exists even on already-migrated installs
		$accommodations = $wpdb->prefix . 'camp_accommodations';
		$accommodations_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $accommodations ) );
		if ( $accommodations_exists ) {
			$col_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$accommodations} LIKE 'accommodation_type'" );
			if ( empty( $col_exists ) ) {
				$wpdb->query( "ALTER TABLE {$accommodations} ADD COLUMN accommodation_type VARCHAR(255) NULL AFTER capacity" );
			}
		}

		if ( get_option( 'creativedbs_campmgmt_modules_migrated' ) >= 1 ) {
			return;
		}

		// 1) Create camp_accommodations table
		$accommodations = $wpdb->prefix . 'camp_accommodations';
		$sql_accommodations = "CREATE TABLE {$accommodations} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id BIGINT UNSIGNED NOT NULL,
			name VARCHAR(255) NOT NULL,
			description TEXT NULL,
			capacity INT NULL,
			accommodation_type VARCHAR(255) NULL,
			sort_order INT DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY camp_id (camp_id)
		) {$charset};";
		dbDelta( $sql_accommodations );

		// 2) Create camp_faqs table
		$faqs = $wpdb->prefix . 'camp_faqs';
		$sql_faqs = "CREATE TABLE {$faqs} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id BIGINT UNSIGNED NOT NULL,
			question TEXT NOT NULL,
			answer TEXT NOT NULL,
			sort_order INT DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY camp_id (camp_id)
		) {$charset};";
		dbDelta( $sql_faqs );

		// 3) Create camp_sessions table (for rates & dates cards)
		$sessions = $wpdb->prefix . 'camp_sessions';
		$sql_sessions = "CREATE TABLE {$sessions} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			camp_id BIGINT UNSIGNED NOT NULL,
			session_name VARCHAR(255) NOT NULL,
			start_date DATE NULL,
			end_date DATE NULL,
			price DECIMAL(10,2) NULL,
			notes TEXT NULL,
			description TEXT NULL,
			sort_order INT DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY camp_id (camp_id)
		) {$charset};";
		dbDelta( $sql_sessions );

		update_option( 'creativedbs_campmgmt_modules_migrated', 1 );
		error_log( 'CDBS Camp: Created modules tables (accommodations, faqs, sessions)' );
	}
}
