<?php
/**
 * Migration: Parent System Tables
 *
 * Creates:
 *   wp_cdbs_parent_registrations  - one row per form submission
 *   wp_cdbs_parent_dynamic_options - admin-managed option lists (session_length, program)
 *   wp_cdbs_parent_favorites       - parent <-> camp relationships
 *   wp_cdbs_messages               - messages between parents and camp directors
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt;

defined( 'ABSPATH' ) || exit;

class Migration_Parents {

	public static function run() {
		$option_key = 'cdbs_parent_system_migrated_v1';

		if ( get_option( $option_key ) ) {
			return;
		}

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// -----------------------------------------------------------------------
		// 1. Parent registrations (form submissions)
		// -----------------------------------------------------------------------
		$sql_registrations = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cdbs_parent_registrations (
			id              bigint(20)   NOT NULL AUTO_INCREMENT,
			user_id         bigint(20)   DEFAULT NULL,
			parent_first    varchar(100) NOT NULL DEFAULT '',
			parent_last     varchar(100) NOT NULL DEFAULT '',
			email           varchar(150) NOT NULL DEFAULT '',
			phone           varchar(50)  DEFAULT NULL,
			child_first     varchar(100) NOT NULL DEFAULT '',
			child_last      varchar(100) DEFAULT NULL,
			gender          varchar(20)  DEFAULT NULL,
			birthday        varchar(20)  DEFAULT NULL,
			address         varchar(255) DEFAULT NULL,
			city            varchar(100) DEFAULT NULL,
			camper_state    varchar(100) DEFAULT NULL,
			country         varchar(100) DEFAULT 'United States',
			year_of_interest varchar(10) NOT NULL DEFAULT '',
			preferred_dates varchar(255) DEFAULT NULL,
			first_time_camper varchar(5) DEFAULT 'No',
			session_lengths text         DEFAULT NULL,
			preferred_locations text     DEFAULT NULL,
			preferred_programs  text     DEFAULT NULL,
			tell_us_more    text         DEFAULT NULL,
			referral_source varchar(100) DEFAULT NULL,
			submitted_at    datetime     DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY email (email),
			KEY submitted_at (submitted_at)
		) $charset_collate;";

		dbDelta( $sql_registrations );

		// -----------------------------------------------------------------------
		// 2. Dynamic options (session_length | program)
		//    NOTE: 'location' options are auto-pulled from camp states (not stored here)
		// -----------------------------------------------------------------------
		$sql_options = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cdbs_parent_dynamic_options (
			id         bigint(20)   NOT NULL AUTO_INCREMENT,
			type       varchar(50)  NOT NULL DEFAULT '',
			label      varchar(255) NOT NULL DEFAULT '',
			sort_order int(11)      NOT NULL DEFAULT 0,
			is_active  tinyint(1)   NOT NULL DEFAULT 1,
			created_at datetime     DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY type (type),
			KEY sort_order (sort_order)
		) $charset_collate;";

		dbDelta( $sql_options );

		// -----------------------------------------------------------------------
		// 3. Parent favorites (parent user <-> camp)
		// -----------------------------------------------------------------------
		$sql_favorites = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cdbs_parent_favorites (
			id         bigint(20) NOT NULL AUTO_INCREMENT,
			user_id    bigint(20) NOT NULL,
			camp_id    bigint(20) NOT NULL,
			created_at datetime   DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_camp (user_id, camp_id),
			KEY user_id (user_id),
			KEY camp_id (camp_id)
		) $charset_collate;";

		dbDelta( $sql_favorites );

		// -----------------------------------------------------------------------
		// 4. Messages (parent <-> camp director)
		// -----------------------------------------------------------------------
		$sql_messages = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cdbs_messages (
			id           bigint(20)   NOT NULL AUTO_INCREMENT,
			camp_id      bigint(20)   NOT NULL,
			sender_id    bigint(20)   NOT NULL,
			sender_role  varchar(20)  NOT NULL DEFAULT 'parent',
			subject      varchar(255) DEFAULT NULL,
			body         text         NOT NULL,
			is_read      tinyint(1)   NOT NULL DEFAULT 0,
			parent_reg_id bigint(20)  DEFAULT NULL,
			created_at   datetime     DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY camp_id (camp_id),
			KEY sender_id (sender_id),
			KEY is_read (is_read),
			KEY created_at (created_at)
		) $charset_collate;";

		dbDelta( $sql_messages );

		// -----------------------------------------------------------------------
		// Seed default dynamic options (mirrors the Ninja Form values)
		// -----------------------------------------------------------------------
		self::seed_default_options();

		update_option( $option_key, '1' );
	}

	/**
	 * Seed default option rows so the form works out-of-the-box.
	 * Only inserts if the table is currently empty for that type.
	 */
	private static function seed_default_options() {
		global $wpdb;
		$table = $wpdb->prefix . 'cdbs_parent_dynamic_options';

		// --- Session Lengths ---
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE type = %s", 'session_length' ) );
		if ( ! $exists ) {
			$session_lengths = [
				'One Week', 'Two Weeks', 'Three Weeks', 'Four Weeks',
				'Five Weeks', 'Six Weeks', 'Seven Weeks',
				'Full Summer', 'Half Summer', 'Other',
			];
			foreach ( $session_lengths as $i => $label ) {
				$wpdb->insert( $table, [ 'type' => 'session_length', 'label' => $label, 'sort_order' => $i + 1 ], [ '%s', '%s', '%d' ] );
			}
		}

		// --- Programs ---
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE type = %s", 'program' ) );
		if ( ! $exists ) {
			$programs = [
				'Overnight Camp', 'Day Camp', 'Co-Ed', 'All Boys', 'All Girls',
				'Traditional Camp', 'Specialty Camp (i.e. Visual Art, Performing Art,...)',
				'Sports Specialty', 'Wilderness / Outdoor Adventure', 'Teen Tours', 'Other',
			];
			foreach ( $programs as $i => $label ) {
				$wpdb->insert( $table, [ 'type' => 'program', 'label' => $label, 'sort_order' => $i + 1 ], [ '%s', '%s', '%d' ] );
			}
		}
	}
}

// Hook into admin_init with option guard (same pattern as all other migrations)
add_action( 'admin_init', [ '\\CreativeDBS\\CampMgmt\\Migration_Parents', 'run' ] );
