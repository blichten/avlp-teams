<?php
/**
 * Plugin Name: AVLP - Teams
 * Plugin URI: https://virtualleadershipprograms.com
 * Description: Team management and display functionality for the Virtual Leadership Programs platform. Provides team member viewing with personality data integration.
 * Version: 1.0.0
 * Author: Virtual Leadership Programs
 * Author URI: https://virtualleadershipprograms.com
 * License: GPLv2 or later
 * Text Domain: avlp-teams
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VLP_TEAMS_VERSION', '1.0.0');
define('VLP_TEAMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VLP_TEAMS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once VLP_TEAMS_PLUGIN_DIR . 'includes/teams-core-functions.php';
require_once VLP_TEAMS_PLUGIN_DIR . 'includes/teams-shortcodes.php';
require_once VLP_TEAMS_PLUGIN_DIR . 'includes/teams-display-functions.php';

// Initialize plugin
add_action('init', 'vlp_teams_init');

/**
 * Initialize the teams plugin
 */
function vlp_teams_init() {
    // Register shortcodes
    vlp_teams_register_shortcodes();
    
    // Enqueue styles and scripts
    add_action('wp_enqueue_scripts', 'vlp_teams_enqueue_scripts');
}

/**
 * Enqueue plugin styles and scripts
 */
function vlp_teams_enqueue_scripts() {
    // Only enqueue if shortcode is present on the page
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'vlp_teams')) {
        wp_enqueue_style(
            'vlp-teams-style',
            VLP_TEAMS_PLUGIN_URL . 'css/teams-style.css',
            array(),
            VLP_TEAMS_VERSION
        );
        
        wp_enqueue_script(
            'vlp-teams-script',
            VLP_TEAMS_PLUGIN_URL . 'js/teams-script.js',
            array('jquery'),
            VLP_TEAMS_VERSION,
            true
        );
    }
}

/**
 * Plugin activation hook
 */
register_activation_hook(__FILE__, 'vlp_teams_activate');

function vlp_teams_activate() {
    // Plugin activation tasks if needed
    // Currently no database tables needed for this plugin
}

/**
 * Plugin deactivation hook
 */
register_deactivation_hook(__FILE__, 'vlp_teams_deactivate');

function vlp_teams_deactivate() {
    // Plugin deactivation cleanup if needed
} 