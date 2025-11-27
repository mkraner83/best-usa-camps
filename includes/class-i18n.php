<?php
/**
 * Internationalization handler.
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt;

defined( 'ABSPATH' ) || exit;

class I18n {

	public function load_textdomain(){
		load_plugin_textdomain(
			'creativedbs-camp-mgmt',
			false,
			dirname( plugin_basename( CREATIVE_DBS_CAMPMGMT_FILE ) ) . '/languages/'
		);
	}
}
