<?php
/**
 * Assets manager.
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt;


if ( ! class_exists(__NAMESPACE__ . '\\Assets', false) ) {
defined( 'ABSPATH' ) || exit;

class Assets {
    /** Plugin slug used for asset handles */
    public const SLUG = 'creativedbs-camp-mgmt';
    /** Plugin version for cache-busting */
    public const VERSION = '2.0.1';
    /** Plugin slug used for handles */
//     public const SLUG = 'creativedbs-camp-mgmt';


	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );
		add_action('admin_enqueue_scripts', [ $this, 'public_assets' ] );
	}

	public function admin_assets(){
		// No admin assets detected yet.
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if ( $screen && isset( $screen->id ) ) {
			if ( false === strpos( $screen->id, 'camp' ) && false === strpos( $screen->id, 'creativedbs' ) ) {
				return;
			}
		}
	}

	public function public_assets(){
        if ( ! is_admin() ) { return; }
				// Moved from legacy file:
		wp_enqueue_style(self::SLUG, plugin_dir_url(__FILE__) . 'assets/admin.css', [], self::VERSION);
		wp_enqueue_style('creativedbs-camp-mgmt-inline');
	}
}
}
