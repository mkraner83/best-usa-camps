<?php
/**
 * Plugin Name: Ninja Forms Debug Suppressor
 * Description: Suppresses specific Ninja Forms warnings in debug.log without breaking functionality
 * Version: 1.0.0
 * Author: CreativeDBS
 * 
 * Installation: Place this file in wp-content/mu-plugins/ directory
 * (create mu-plugins folder if it doesn't exist)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Custom error handler to suppress specific Ninja Forms warnings
 */
function cdbs_suppress_ninja_forms_warnings( $errno, $errstr, $errfile, $errline ) {
	// List of errors to suppress
	$suppress_patterns = [
		'Translation loading for the ninja-forms-uploads domain was triggered too early',
		'Implicit conversion from float',
	];

	// Check if this error should be suppressed
	foreach ( $suppress_patterns as $pattern ) {
		if ( strpos( $errstr, $pattern ) !== false ) {
			// Suppress by returning true (don't log it)
			return true;
		}
	}

	// Let WordPress handle other errors normally
	return false;
}

// Set custom error handler with high priority
set_error_handler( 'cdbs_suppress_ninja_forms_warnings', E_ALL );

/**
 * Alternative approach: Filter WordPress error logging
 * Uncomment this section if the above doesn't work
 */
/*
add_filter( 'doing_it_wrong_trigger_error', function( $trigger, $function_name, $message ) {
	// Suppress Ninja Forms translation warning
	if ( strpos( $message, 'ninja-forms-uploads' ) !== false ) {
		return false;
	}
	return $trigger;
}, 10, 3 );

add_filter( 'deprecated_function_trigger_error', '__return_false' );
add_filter( 'deprecated_argument_trigger_error', '__return_false' );
*/

/**
 * Another alternative: Increase WP_DEBUG_LOG error level
 * Add this to wp-config.php instead:
 * 
 * // Only log errors, not warnings or notices
 * if ( WP_DEBUG ) {
 *     error_reporting( E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR );
 * }
 */
