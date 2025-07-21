<?php
/**
 * Core functions for AVLP Teams plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if user has a program subscription other than free
 * 
 * @param int $wp_uid WordPress user ID (optional, defaults to current user)
 * @return bool True if user has non-free program, false otherwise
 */
function vlp_teams_user_has_paid_program($wp_uid = null) {
    if (empty($wp_uid)) {
        $wp_uid = get_current_user_id();
    }
    
    if (empty($wp_uid)) {
        return true; // If no user ID, don't block access
    }
    
    global $wpdb;
    
    // Check blcs_user table for current_plan
    $blcs_user_table = $wpdb->prefix . 'blcs_user';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$blcs_user_table'") != $blcs_user_table) {
        return true; // If table doesn't exist, don't block access
    }
    
    $current_plan = $wpdb->get_var($wpdb->prepare(
        "SELECT current_plan FROM $blcs_user_table WHERE wp_uid = %d",
        $wp_uid
    ));
    
    // If no plan found, allow access (don't assume free)
    if (empty($current_plan)) {
        return true;
    }
    
    // Only block access if plan is specifically "Free" (case insensitive)
    $cleaned_plan = strtolower(trim($current_plan));
    return $cleaned_plan !== 'free';
}

/**
 * Check if user belongs to a team
 * 
 * @param int $wp_uid WordPress user ID (optional, defaults to current user)
 * @return bool True if user belongs to a team, false otherwise
 */
function vlp_teams_user_belongs_to_team($wp_uid = null) {
    if (empty($wp_uid)) {
        $wp_uid = get_current_user_id();
    }
    
    if (empty($wp_uid)) {
        return false;
    }
    
    // Check if organization management plugin is active
    if (!function_exists('vlp_org_get_user_teams')) {
        return false;
    }
    
    $user_teams = vlp_org_get_user_teams($wp_uid);
    
    return !empty($user_teams);
}

/**
 * Get user's primary team
 * 
 * @param int $wp_uid WordPress user ID (optional, defaults to current user)
 * @return object|null Team object or null if user has no team
 */
function vlp_teams_get_user_primary_team($wp_uid = null) {
    if (empty($wp_uid)) {
        $wp_uid = get_current_user_id();
    }
    
    if (empty($wp_uid)) {
        return null;
    }
    
    // Check if organization management plugin is active
    if (!function_exists('vlp_org_get_user_teams')) {
        return null;
    }
    
    $user_teams = vlp_org_get_user_teams($wp_uid);
    
    if (empty($user_teams)) {
        return null;
    }
    
    // Return the first team (could be enhanced with priority logic later)
    return $user_teams[0];
}

/**
 * Get all members of a team
 * 
 * @param int $team_id Team ID
 * @return array Array of team member objects with user details
 */
function vlp_teams_get_team_members($team_id) {
    if (empty($team_id)) {
        return array();
    }
    
    // Check if organization management plugin is active
    if (!function_exists('vlp_org_get_team_users')) {
        return array();
    }
    
    $team_members = vlp_org_get_team_users($team_id);
    
    if (empty($team_members)) {
        return array();
    }
    
    // Enhance member data with additional user information
    $enhanced_members = array();
    
    foreach ($team_members as $member) {
        $user_data = get_userdata($member->wp_uid);
        if ($user_data) {
            $member->first_name = $user_data->first_name;
            $member->last_name = $user_data->last_name;
            $member->display_name = $user_data->display_name;
            $member->user_email = $user_data->user_email;
            
            // Get user's title from user meta
            $member->user_title = get_user_meta($member->wp_uid, 'user_title', true);
            
            $enhanced_members[] = $member;
        }
    }
    
    return $enhanced_members;
}

/**
 * Get personality summary data for a user
 * 
 * @param int $wp_uid WordPress user ID
 * @return array Array of personality summary records
 */
function vlp_teams_get_user_personality_summary($wp_uid) {
    if (empty($wp_uid)) {
        return array();
    }
    
    global $wpdb;
    
    $personality_table = $wpdb->prefix . 'blcs_personality_summary';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$personality_table'") != $personality_table) {
        return array();
    }
    
    $personality_data = $wpdb->get_results($wpdb->prepare(
        "SELECT trait, high_trait_type, high_trait_type_value, low_trait_type, low_trait_type_value, user_primary_trait
         FROM $personality_table 
         WHERE wp_id = %d 
         ORDER BY trait",
        $wp_uid
    ));
    
    return $personality_data ? $personality_data : array();
}

/**
 * Check if user is a team lead
 * 
 * @param int $wp_uid WordPress user ID
 * @param int $team_id Team ID (optional)
 * @return bool True if user is team lead, false otherwise
 */
function vlp_teams_user_is_team_lead($wp_uid, $team_id = null) {
    if (empty($wp_uid)) {
        return false;
    }
    
    // Check if organization management plugin is active
    if (!function_exists('vlp_org_user_is_team_lead')) {
        return false;
    }
    
    return vlp_org_user_is_team_lead($wp_uid, $team_id);
}

/**
 * Get active goals for a user
 * 
 * @param int $wp_uid WordPress user ID
 * @return array Array of active goal records
 */
function vlp_teams_get_user_active_goals($wp_uid) {
    if (empty($wp_uid)) {
        return array();
    }
    
    global $wpdb;
    
    $goals_table = $wpdb->prefix . 'avlp_goals';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$goals_table'") != $goals_table) {
        return array();
    }
    
    $goals_data = $wpdb->get_results($wpdb->prepare(
        "SELECT id, goal_name, status, start_date, end_date, progress, updated_at
         FROM $goals_table 
         WHERE vlp_id = %d 
         AND status NOT IN ('Complete', 'Canceled', 'Cancelled', 'Archived')
         ORDER BY end_date ASC",
        $wp_uid
    ));
    
    return $goals_data ? $goals_data : array();
}

/**
 * Get goal updates for a specific goal
 * 
 * @param int $goal_id Goal ID
 * @return array Array of goal update records
 */
function vlp_teams_get_goal_updates($goal_id) {
    if (empty($goal_id)) {
        return array();
    }
    
    global $wpdb;
    
    $updates_table = $wpdb->prefix . 'avlp_goal_updates';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$updates_table'") != $updates_table) {
        return array();
    }
    
    $updates_data = $wpdb->get_results($wpdb->prepare(
        "SELECT created_at, status_after, progress_after, content
         FROM $updates_table 
         WHERE goal_id = %d 
         ORDER BY created_at DESC",
        $goal_id
    ));
    
    return $updates_data ? $updates_data : array();
}