<?php
/**
 * Test core functionality of AVLP Teams plugin
 */

class TestCoreFunctionality extends WP_UnitTestCase {
    
    private $test_data = array();
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create test user
        $this->test_data['user_id'] = vlp_teams_create_test_user(array(
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_title' => 'Software Engineer'
        ));
        
        // Create blcs_user entry
        vlp_teams_create_test_blcs_user($this->test_data['user_id'], 'Standard');
        
        // Create test organization and team if organization management is available
        if (function_exists('vlp_org_create_organization')) {
            $this->test_data['org_id'] = vlp_teams_create_test_organization();
            $this->test_data['team_id'] = vlp_teams_create_test_team($this->test_data['org_id']);
            vlp_teams_assign_test_user_to_team(
                $this->test_data['user_id'], 
                $this->test_data['org_id'], 
                $this->test_data['team_id']
            );
        }
        
        // Create personality data
        vlp_teams_create_test_personality_summary($this->test_data['user_id']);
    }
    
    /**
     * Clean up test environment
     */
    public function tearDown(): void {
        vlp_teams_cleanup_test_data(array(
            'users' => array($this->test_data['user_id']),
            'blcs_users' => array($this->test_data['user_id']),
            'personality' => array($this->test_data['user_id']),
            'organizations' => !empty($this->test_data['org_id']) ? array($this->test_data['org_id']) : array(),
            'teams' => !empty($this->test_data['team_id']) ? array($this->test_data['team_id']) : array()
        ));
        
        parent::tearDown();
    }
    
    /**
     * Test program subscription checking
     */
    public function test_user_has_paid_program() {
        // Test user with Standard plan
        $result = vlp_teams_user_has_paid_program($this->test_data['user_id']);
        $this->assertTrue($result, 'User with Standard plan should have paid program');
        
        // Test user with Free plan
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'blcs_user',
            array('current_plan' => 'Free'),
            array('wp_uid' => $this->test_data['user_id']),
            array('%s'),
            array('%d')
        );
        
        $result = vlp_teams_user_has_paid_program($this->test_data['user_id']);
        $this->assertFalse($result, 'User with Free plan should not have paid program');
        
        // Test non-existent user
        $result = vlp_teams_user_has_paid_program(99999);
        $this->assertFalse($result, 'Non-existent user should not have paid program');
    }
    
    /**
     * Test team membership checking
     */
    public function test_user_belongs_to_team() {
        if (!function_exists('vlp_org_get_user_teams')) {
            $this->markTestSkipped('Organization management plugin not available');
        }
        
        // Test user with team membership
        $result = vlp_teams_user_belongs_to_team($this->test_data['user_id']);
        $this->assertTrue($result, 'User assigned to team should belong to team');
        
        // Test user without team membership
        $new_user_id = vlp_teams_create_test_user();
        $result = vlp_teams_user_belongs_to_team($new_user_id);
        $this->assertFalse($result, 'User not assigned to team should not belong to team');
        
        // Clean up
        wp_delete_user($new_user_id);
    }
    
    /**
     * Test getting user's primary team
     */
    public function test_get_user_primary_team() {
        if (!function_exists('vlp_org_get_user_teams')) {
            $this->markTestSkipped('Organization management plugin not available');
        }
        
        // Test user with team
        $team = vlp_teams_get_user_primary_team($this->test_data['user_id']);
        $this->assertNotNull($team, 'User with team should have primary team');
        $this->assertEquals($this->test_data['team_id'], $team->team_id, 'Primary team should match assigned team');
        
        // Test user without team
        $new_user_id = vlp_teams_create_test_user();
        $team = vlp_teams_get_user_primary_team($new_user_id);
        $this->assertNull($team, 'User without team should not have primary team');
        
        // Clean up
        wp_delete_user($new_user_id);
    }
    
    /**
     * Test getting team members
     */
    public function test_get_team_members() {
        if (!function_exists('vlp_org_get_team_users')) {
            $this->markTestSkipped('Organization management plugin not available');
        }
        
        // Test getting members of existing team
        $members = vlp_teams_get_team_members($this->test_data['team_id']);
        $this->assertIsArray($members, 'Team members should be returned as array');
        $this->assertGreaterThan(0, count($members), 'Team should have at least one member');
        
        // Verify member data structure
        $member = $members[0];
        $this->assertObjectHasAttribute('wp_uid', $member, 'Member should have wp_uid');
        $this->assertObjectHasAttribute('first_name', $member, 'Member should have first_name');
        $this->assertObjectHasAttribute('last_name', $member, 'Member should have last_name');
        $this->assertObjectHasAttribute('role_type', $member, 'Member should have role_type');
        
        // Test empty team
        $empty_team_id = vlp_teams_create_test_team($this->test_data['org_id']);
        $members = vlp_teams_get_team_members($empty_team_id);
        $this->assertIsArray($members, 'Empty team should return empty array');
        $this->assertEquals(0, count($members), 'Empty team should have no members');
        
        // Test invalid team
        $members = vlp_teams_get_team_members(99999);
        $this->assertIsArray($members, 'Invalid team should return empty array');
        $this->assertEquals(0, count($members), 'Invalid team should have no members');
    }
    
    /**
     * Test getting personality summary
     */
    public function test_get_user_personality_summary() {
        // Test user with personality data
        $personality = vlp_teams_get_user_personality_summary($this->test_data['user_id']);
        $this->assertIsArray($personality, 'Personality data should be returned as array');
        $this->assertGreaterThan(0, count($personality), 'User should have personality data');
        
        // Verify personality data structure
        $trait = $personality[0];
        $this->assertObjectHasAttribute('trait', $trait, 'Personality record should have trait');
        $this->assertObjectHasAttribute('high_trait_type', $trait, 'Personality record should have high_trait_type');
        $this->assertObjectHasAttribute('high_trait_type_value', $trait, 'Personality record should have high_trait_type_value');
        $this->assertObjectHasAttribute('low_trait_type', $trait, 'Personality record should have low_trait_type');
        $this->assertObjectHasAttribute('low_trait_type_value', $trait, 'Personality record should have low_trait_type_value');
        
        // Test user without personality data
        $new_user_id = vlp_teams_create_test_user();
        $personality = vlp_teams_get_user_personality_summary($new_user_id);
        $this->assertIsArray($personality, 'User without personality data should return empty array');
        $this->assertEquals(0, count($personality), 'User without personality data should have empty array');
        
        // Clean up
        wp_delete_user($new_user_id);
    }
    
    /**
     * Test team lead checking
     */
    public function test_user_is_team_lead() {
        if (!function_exists('vlp_org_user_is_team_lead')) {
            $this->markTestSkipped('Organization management plugin not available');
        }
        
        // Test regular team member
        $is_lead = vlp_teams_user_is_team_lead($this->test_data['user_id'], $this->test_data['team_id']);
        $this->assertFalse($is_lead, 'Regular team member should not be team lead');
        
        // Create team lead
        $team_lead_id = vlp_teams_create_test_user();
        vlp_teams_assign_test_user_to_team(
            $team_lead_id, 
            $this->test_data['org_id'], 
            $this->test_data['team_id'], 
            'Team_Lead'
        );
        
        $is_lead = vlp_teams_user_is_team_lead($team_lead_id, $this->test_data['team_id']);
        $this->assertTrue($is_lead, 'User with Team_Lead role should be team lead');
        
        // Clean up
        wp_delete_user($team_lead_id);
    }
    
    /**
     * Test plugin initialization
     */
    public function test_plugin_initialization() {
        // Test that plugin constants are defined
        $this->assertTrue(defined('VLP_TEAMS_VERSION'), 'Plugin version constant should be defined');
        $this->assertTrue(defined('VLP_TEAMS_PLUGIN_DIR'), 'Plugin directory constant should be defined');
        $this->assertTrue(defined('VLP_TEAMS_PLUGIN_URL'), 'Plugin URL constant should be defined');
        
        // Test that required functions exist
        $this->assertTrue(function_exists('vlp_teams_init'), 'Plugin init function should exist');
        $this->assertTrue(function_exists('vlp_teams_enqueue_scripts'), 'Script enqueue function should exist');
        $this->assertTrue(function_exists('vlp_teams_register_shortcodes'), 'Shortcode registration function should exist');
    }
    
    /**
     * Test edge cases and error handling
     */
    public function test_edge_cases() {
        // Test functions with null/empty parameters
        $this->assertFalse(vlp_teams_user_has_paid_program(null), 'Null user ID should return false');
        $this->assertFalse(vlp_teams_user_has_paid_program(0), 'Zero user ID should return false');
        $this->assertFalse(vlp_teams_user_has_paid_program(''), 'Empty user ID should return false');
        
        $this->assertFalse(vlp_teams_user_belongs_to_team(null), 'Null user ID should return false');
        $this->assertNull(vlp_teams_get_user_primary_team(null), 'Null user ID should return null');
        
        $this->assertIsArray(vlp_teams_get_team_members(null), 'Null team ID should return empty array');
        $this->assertEquals(0, count(vlp_teams_get_team_members(null)), 'Null team ID should return empty array');
        
        $this->assertIsArray(vlp_teams_get_user_personality_summary(null), 'Null user ID should return empty array');
        $this->assertEquals(0, count(vlp_teams_get_user_personality_summary(null)), 'Null user ID should return empty array');
    }
} 