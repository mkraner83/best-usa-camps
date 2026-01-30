<?php
/**
 * Migration: Add social media and video URL fields
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt;

defined( 'ABSPATH' ) || exit;

/**
 * Run migration to add social media and video URL columns
 */
function migrate_social_video_fields() {
	global $wpdb;
	
	// Check if migration already ran
	if ( get_option( 'creativedbs_campmgmt_social_video_migrated' ) ) {
		return;
	}
	
	$table = $wpdb->prefix . 'camp_management';
	
	// Add social media columns (JSON field to store multiple links)
	$wpdb->query( "ALTER TABLE {$table} ADD COLUMN social_media_links TEXT DEFAULT NULL COMMENT 'JSON array of social media links'" );
	
	// Add video URL column
	$wpdb->query( "ALTER TABLE {$table} ADD COLUMN video_url VARCHAR(500) DEFAULT NULL COMMENT 'YouTube, Vimeo, or other video URL'" );
	
	// Mark migration as complete
	update_option( 'creativedbs_campmgmt_social_video_migrated', true );
}

// Run migration on admin init
add_action( 'admin_init', __NAMESPACE__ . '\\migrate_social_video_fields' );
