<?php
/**
 * PHPUnit bootstrap file for FieldForge.
 *
 * Loads the WordPress test suite and the plugin.
 *
 * @package FieldForge
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo 'Could not find ' . $_tests_dir . '/includes/functions.php. ' .
		 'Set the WP_TESTS_DIR environment variable.' . PHP_EOL;
	exit( 1 );
}

// Load the WordPress test bootstrap.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin before the test suite.
 */
function _manually_load_fieldforge(): void {
	require dirname( __DIR__ ) . '/fieldforge.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_fieldforge' );

require $_tests_dir . '/includes/bootstrap.php';
