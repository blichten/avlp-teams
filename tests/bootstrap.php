<?php
/**
 * PHPUnit bootstrap file for AVLP Teams plugin tests
 */

// Define plugin constants
define('VLP_TEAMS_PLUGIN_FILE', dirname(dirname(__FILE__)) . '/default-teams.php');
define('VLP_TEAMS_PLUGIN_DIR', dirname(dirname(__FILE__)) . '/');

// Define test environment
define('VLP_TEAMS_TESTS_DIR', __DIR__);

// WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
    // Load required dependencies first
    require_once dirname(dirname(__FILE__)) . '/includes/teams-core-functions.php';
    require_once dirname(dirname(__FILE__)) . '/includes/teams-shortcodes.php';
    require_once dirname(dirname(__FILE__)) . '/includes/teams-display-functions.php';
    
    // Load the main plugin file
    require_once VLP_TEAMS_PLUGIN_FILE;
}

// Load the plugin
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Load test helper functions
require_once VLP_TEAMS_TESTS_DIR . '/test-helpers.php'; 