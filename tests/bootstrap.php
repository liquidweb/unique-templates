<?php
/**
 * Bootstrap file for PHPUnit.
 *
 * @package LiquidWeb\UniqueTemplates
 * @author  Liquid Web
 */

use WP_CLI\Loggers\Quiet as Logger;

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', function () {
	require_once dirname( __DIR__ ) . '/command.php';
} );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once $_tests_dir . '/includes/bootstrap.php';
