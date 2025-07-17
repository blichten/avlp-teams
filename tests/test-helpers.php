<?php
/**
 * Test helper functions for AVLP Teams plugin tests
 */

/**
 * Create a test user with specific data
 * 
 * @param array $user_data User data to create
 * @return int User ID
 */
function vlp_teams_create_test_user($user_data = array()) {
    $defaults = array(
        'user_login' => 'testuser_' . uniqid(),
        'user_email' => 'test_' . uniqid() . '@example.com',
        'user_pass' => 'password123',
        'first_name' => 'Test',
        'last_name' => 'User',
        'display_name' => 'Test User'
    );
    
    $user_data = wp_parse_args($user_data, $defaults);
    
    $user_id = wp_insert_user($user_data);
    
    if (is_wp_error($user_id)) {
        throw new Exception('Failed to create test user: ' . $user_id->get_error_message());
    }
    
    return $user_id;
}

/**
 * Create test blcs_user entry
 * 
 * @param int $wp_uid WordPress user ID
 * @param string $plan User plan (Free, Standard, Premium)
 * @return bool Success status
 */
function vlp_teams_create_test_blcs_user($wp_uid, $plan = 'Standard') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'blcs_user';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'wp_uid' => $wp_uid,
            'current_plan' => $plan,
            'ai_uid' => 'test_' . uniqid(),
            'created_date' => current_time('mysql', true),
            'modified_date' => current_time('mysql', true),
            'accepted_terms_date' => current_time('mysql', true),
            'accepted_terms_text' => 'Test terms acceptance'
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    return $result !== false;
}

/**
 * Create test organization
 * 
 * @param array $org_data Organization data
 * @return int Organization ID
 */
function vlp_teams_create_test_organization($org_data = array()) {
    if (!function_exists('vlp_org_create_organization')) {
        throw new Exception('Organization management plugin not available');
    }
    
    $defaults = array(
        'org_name' => 'Test Organization ' . uniqid(),
        'org_code' => 'TEST' . rand(1000, 9999),
        'plan' => 'Standard',
        'status' => 'Active'
    );
    
    $org_data = wp_parse_args($org_data, $defaults);
    
    $org_id = vlp_org_create_organization($org_data);
    
    if (!$org_id) {
        throw new Exception('Failed to create test organization');
    }
    
    return $org_id;
}

/**
 * Create test team
 * 
 * @param int $org_id Organization ID
 * @param array $team_data Team data
 * @return int Team ID
 */
function vlp_teams_create_test_team($org_id, $team_data = array()) {
    if (!function_exists('vlp_org_create_team')) {
        throw new Exception('Organization management plugin not available');
    }
    
    $defaults = array(
        'org_id' => $org_id,
        'team_name' => 'Test Team ' . uniqid(),
        'team_code' => 'TEAM' . rand(100, 999),
        'status' => 'Active'
    );
    
    $team_data = wp_parse_args($team_data, $defaults);
    
    $team_id = vlp_org_create_team($team_data);
    
    if (!$team_id) {
        throw new Exception('Failed to create test team');
    }
    
    return $team_id;
}

/**
 * Assign user to team with role
 * 
 * @param int $wp_uid WordPress user ID
 * @param int $org_id Organization ID
 * @param int $team_id Team ID
 * @param string $role_type Role type
 * @return bool Success status
 */
function vlp_teams_assign_test_user_to_team($wp_uid, $org_id, $team_id, $role_type = 'Individual') {
    if (!function_exists('vlp_org_assign_user_role')) {
        throw new Exception('Organization management plugin not available');
    }
    
    $result = vlp_org_assign_user_role($wp_uid, $role_type, $org_id, $team_id);
    
    return $result !== false;
}

/**
 * Create test personality summary data
 * 
 * @param int $wp_uid WordPress user ID
 * @param array $personality_data Personality data
 * @return bool Success status
 */
function vlp_teams_create_test_personality_summary($wp_uid, $personality_data = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'blcs_personality_summary';
    
    $defaults = array(
        array(
            'wp_id' => $wp_uid,
            'trait' => 'Extraversion',
            'high_trait_type' => 'Outgoing',
            'high_trait_type_value' => '75',
            'low_trait_type' => 'Reserved',
            'low_trait_type_value' => '25'
        ),
        array(
            'wp_id' => $wp_uid,
            'trait' => 'Conscientiousness',
            'high_trait_type' => 'Organized',
            'high_trait_type_value' => '80',
            'low_trait_type' => 'Flexible',
            'low_trait_type_value' => '20'
        )
    );
    
    if (empty($personality_data)) {
        $personality_data = $defaults;
    }
    
    $success = true;
    
    foreach ($personality_data as $record) {
        $result = $wpdb->insert(
            $table_name,
            $record,
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            $success = false;
        }
    }
    
    return $success;
}

/**
 * Clean up test data
 * 
 * @param array $cleanup_data Data to clean up
 */
function vlp_teams_cleanup_test_data($cleanup_data = array()) {
    global $wpdb;
    
    // Clean up users
    if (!empty($cleanup_data['users'])) {
        foreach ($cleanup_data['users'] as $user_id) {
            wp_delete_user($user_id);
        }
    }
    
    // Clean up blcs_user entries
    if (!empty($cleanup_data['blcs_users'])) {
        foreach ($cleanup_data['blcs_users'] as $wp_uid) {
            $wpdb->delete(
                $wpdb->prefix . 'blcs_user',
                array('wp_uid' => $wp_uid),
                array('%d')
            );
        }
    }
    
    // Clean up personality summary
    if (!empty($cleanup_data['personality'])) {
        foreach ($cleanup_data['personality'] as $wp_uid) {
            $wpdb->delete(
                $wpdb->prefix . 'blcs_personality_summary',
                array('wp_id' => $wp_uid),
                array('%d')
            );
        }
    }
    
    // Clean up organizations (if function exists)
    if (!empty($cleanup_data['organizations']) && function_exists('vlp_org_delete_organization')) {
        foreach ($cleanup_data['organizations'] as $org_id) {
            vlp_org_delete_organization($org_id);
        }
    }
    
    // Clean up teams (if function exists)
    if (!empty($cleanup_data['teams']) && function_exists('vlp_org_delete_team')) {
        foreach ($cleanup_data['teams'] as $team_id) {
            vlp_org_delete_team($team_id);
        }
    }
}

/**
 * Create complete test scenario with user, organization, team, and personality data
 * 
 * @param array $scenario_data Scenario configuration
 * @return array Created data IDs
 */
function vlp_teams_create_test_scenario($scenario_data = array()) {
    $defaults = array(
        'user_plan' => 'Standard',
        'role_type' => 'Individual',
        'create_personality' => true
    );
    
    $scenario_data = wp_parse_args($scenario_data, $defaults);
    
    // Create user
    $user_id = vlp_teams_create_test_user();
    
    // Create blcs_user entry
    vlp_teams_create_test_blcs_user($user_id, $scenario_data['user_plan']);
    
    // Create organization
    $org_id = vlp_teams_create_test_organization();
    
    // Create team
    $team_id = vlp_teams_create_test_team($org_id);
    
    // Assign user to team
    vlp_teams_assign_test_user_to_team($user_id, $org_id, $team_id, $scenario_data['role_type']);
    
    // Create personality data
    if ($scenario_data['create_personality']) {
        vlp_teams_create_test_personality_summary($user_id);
    }
    
    return array(
        'user_id' => $user_id,
        'org_id' => $org_id,
        'team_id' => $team_id
    );
}

/**
 * Mock WordPress functions for testing
 */
function vlp_teams_mock_wordpress_functions() {
    // Mock current_user_can if not available
    if (!function_exists('current_user_can')) {
        function current_user_can($capability) {
            return true; // Allow all capabilities in tests
        }
    }
    
    // Mock get_current_user_id if not available
    if (!function_exists('get_current_user_id')) {
        function get_current_user_id() {
            return 1; // Default test user ID
        }
    }
} 