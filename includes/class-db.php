<?php
/**
 * Database helpers for CreativeDBS – Camp Management.
 */
namespace CreativeDBS\CampMgmt;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class DB {
    public static function table_camps() { global $wpdb; return $wpdb->prefix . 'camp_management'; }
    public static function table_type_terms() { global $wpdb; return $wpdb->prefix . 'camp_type_terms'; }
    public static function table_camp_type_pivot() { global $wpdb; return $wpdb->prefix . 'camp_management_types_map'; }
    public static function table_week_terms() { global $wpdb; return $wpdb->prefix . 'camp_week_terms'; }
    public static function table_camp_week_pivot() { global $wpdb; return $wpdb->prefix . 'camp_management_weeks_map'; }
    public static function table_activity_terms() { global $wpdb; return $wpdb->prefix . 'camp_activity_terms'; }
    public static function table_camp_activity_pivot() { global $wpdb; return $wpdb->prefix . 'camp_management_activities_map'; }
    public static function table_accommodations() { global $wpdb; return $wpdb->prefix . 'camp_accommodations'; }
    public static function table_faqs() { global $wpdb; return $wpdb->prefix . 'camp_faqs'; }
    public static function table_sessions() { global $wpdb; return $wpdb->prefix . 'camp_sessions'; }

    public static function drop_tables() {
        global $wpdb;
        $tables = array(
            self::table_camps(),
            self::table_type_terms(),
            self::table_camp_type_pivot(),
            self::table_week_terms(),
            self::table_camp_week_pivot(),
            self::table_activity_terms(),
            self::table_camp_activity_pivot(),
            self::table_accommodations(),
            self::table_faqs(),
            self::table_sessions(),
        );
        foreach ( $tables as $tbl ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query( "DROP TABLE IF EXISTS {$tbl}" );
        }
    }

    public static function prepare( $query, $args = array() ) {
        global $wpdb;
        if ( ! is_array( $args ) ) { $args = array( $args ); }
        return call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $query ), $args ) );
    }

    public static function get_results( $query, $args = array(), $output = OBJECT ) {
        global $wpdb;
        if ( ! empty( $args ) ) { $query = self::prepare( $query, $args ); }
        return $wpdb->get_results( $query, $output );
    }

    public static function get_var( $query, $args = array(), $x = 0, $y = 0 ) {
        global $wpdb;
        if ( ! empty( $args ) ) { $query = self::prepare( $query, $args ); }
        return $wpdb->get_var( $query, $x, $y );
    }

    public static function get_row( $query, $args = array(), $output = OBJECT, $y = 0 ) {
        global $wpdb;
        if ( ! empty( $args ) ) { $query = self::prepare( $query, $args ); }
        return $wpdb->get_row( $query, $output, $y );
    }
}
