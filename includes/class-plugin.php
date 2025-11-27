<?php
/**
 * Core plugin class.
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt;

defined( 'ABSPATH' ) || exit;

class Plugin {

	/** @var Plugin */
	private static $instance;

	/** @var I18n */
	private $i18n;

	/** @var Assets */
	private $assets;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->i18n  = new I18n();
		$this->assets = new Assets();

		add_action( 'plugins_loaded', [ $this->i18n, 'load_textdomain' ] );
		add_action( 'init', [ $this, 'init' ] );
		add_action( 'admin_init', [ $this, 'admin_init' ] );
	}

	public function init() {
		// Frontend hooks can be added here if needed.
	}

	public function admin_init() {
		// Admin-only hooks can be added here.
	}
}
