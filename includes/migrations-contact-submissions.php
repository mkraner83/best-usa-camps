<?php
/**
 * Migration: Contact Submissions Table
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt;

defined( 'ABSPATH' ) || exit;

class Migration_Contact_Submissions {
	
	public static function run() {
		$option_key = 'cdbs_contact_submissions_migrated';
		
		if ( get_option( $option_key ) ) {
			return;
		}
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'cdbs_contact_submissions';
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			first_name varchar(100) NOT NULL,
			last_name varchar(100) NOT NULL,
			email varchar(100) NOT NULL,
			phone varchar(50) DEFAULT NULL,
			message text NOT NULL,
			status varchar(20) DEFAULT 'success',
			error_message text DEFAULT NULL,
			submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY email (email),
			KEY status (status),
			KEY submitted_at (submitted_at)
		) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		update_option( $option_key, '1' );
	}
}

// Run migration
add_action( 'admin_init', [ '\\CreativeDBS\\CampMgmt\\Migration_Contact_Submissions', 'run' ] );
