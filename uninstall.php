<?php
// Uninstall handler for CreativeDBS – Camp Management.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

if ( apply_filters( 'creativedbs_campmgmt_drop_tables_on_uninstall', false ) ) {
    require_once __DIR__ . '/includes/class-db.php';
    if ( class_exists( '\CreativeDBS\CampMgmt\DB' ) ) {
        \CreativeDBS\CampMgmt\DB::drop_tables();
    }
}
