<?php
/**
 * Test shortcode functionality of AVLP Teams plugin
 */

class TestShortcodes extends WP_UnitTestCase {
    
    private $test_data = array();
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Register shortcodes
        vlp_teams_register_shortcodes();
        
        // Create test scenario
        $this->test_data = vlp_teams_create_test_scenario(array(
            'user_plan' => 'Standard',
            'role_type' => 'Individual',
            'create_personality' => true
        ));
    }
    
    /**
     * Clean up test environment
     */
    public function tearDown(): void {
        vlp_teams_cleanup_test_data(array(
            'users' => array($this->test_data['user_id']),
            'blcs_users' => array($this->test_data['user_id']),
            'personality' => array($this->test_data['user_id']),
            'organizations' => array($this->test_data['org_id']),
            'teams' => array($this->test_data['team_id'])
        ));
        
        parent::tearDown();
    }
    
    /**
     * Test shortcode registration
     */
    public function test_shortcode_registration() {
        global $shortcode_tags;
        
        $this->assertArrayHasKey('vlp_teams', $shortcode_tags, 'vlp_teams shortcode should be registered');
        $this->assertEquals('vlp_teams_shortcode_handler', $shortcode_tags['vlp_teams'], 'Shortcode should map to correct handler');
    }
    
    /**
     * Test successful team display
     */
    public function test_successful_team_display() {
        if (!function_exists('vlp_org_get_user_teams')) {
            $this->markTestSkipped('Organization management plugin not available');
        }
        
        // Mock user login
        wp_set_current_user($this->test_data['user_id']);
        
        $output = do_shortcode('[vlp_teams]');
        
        // Should contain team container
        $this->assertStringContainsString('vlp-teams-container', $output, 'Output should contain team container');
        
        // Should contain team title
        $this->assertStringContainsString('vlp-teams-title', $output, 'Output should contain team title');
        
        // Should contain member cards
        $this->assertStringContainsString('vlp-teams-member-card', $output, 'Output should contain member cards');
        
        // Should contain personality data
        $this->assertStringContainsString('vlp-teams-personality-summary', $output, 'Output should contain personality data');
        
        // Should contain trait styling
        $this->assertStringContainsString('vlp-teams-trait-high', $output, 'Output should contain high trait styling');
        $this->assertStringContainsString('vlp-teams-trait-low', $output, 'Output should contain low trait styling');
    }
    
    /**
     * Test subscription error message
     */
    public function test_subscription_error_message() {
        // Create user with free plan
        $free_user_id = vlp_teams_create_test_user();
        vlp_teams_create_test_blcs_user($free_user_id, 'Free');
        
        wp_set_current_user($free_user_id);
        
        $output = do_shortcode('[vlp_teams]');
        
        // Should contain subscription error
        $this->assertStringContainsString('vlp-teams-subscription-error', $output, 'Output should contain subscription error');
        $this->assertStringContainsString('Team features are not available', $output, 'Output should contain subscription error message');
        $this->assertStringContainsString('/programs', $output, 'Output should contain programs link');
        
        // Clean up
        wp_delete_user($free_user_id);
        vlp_teams_cleanup_test_data(array('blcs_users' => array($free_user_id)));
    }
    
    /**
     * Test team membership error message
     */
    public function test_team_membership_error_message() {
        // Create user with paid plan but no team membership
        $no_team_user_id = vlp_teams_create_test_user();
        vlp_teams_create_test_blcs_user($no_team_user_id, 'Standard');
        
        wp_set_current_user($no_team_user_id);
        
        $output = do_shortcode('[vlp_teams]');
        
        // Should contain membership error
        $this->assertStringContainsString('vlp-teams-membership-error', $output, 'Output should contain membership error');
        $this->assertStringContainsString('not part of a team', $output, 'Output should contain membership error message');
        $this->assertStringContainsString('organization admin', $output, 'Output should contain admin reference');
        
        // Clean up
        wp_delete_user($no_team_user_id);
        vlp_teams_cleanup_test_data(array('blcs_users' => array($no_team_user_id)));
    }
    
    /**
     * Test login required message
     */
    public function test_login_required_message() {
        // Ensure no user is logged in
        wp_set_current_user(0);
        
        $output = do_shortcode('[vlp_teams]');
        
        // Should contain login error
        $this->assertStringContainsString('vlp-teams-error', $output, 'Output should contain login error');
        $this->assertStringContainsString('Please log in', $output, 'Output should contain login message');
    }
    
    /**
     * Test team lead identification
     */
    public function test_team_lead_identification() {
        if (!function_exists('vlp_org_assign_user_role')) {
            $this->markTestSkipped('Organization management plugin not available');
        }
        
        // Create team lead
        $team_lead_id = vlp_teams_create_test_user(array(
            'first_name' => 'Jane',
            'last_name' => 'Leader'
        ));
        vlp_teams_create_test_blcs_user($team_lead_id, 'Standard');
        vlp_teams_assign_test_user_to_team($team_lead_id, $this->test_data['org_id'], $this->test_data['team_id'], 'Team_Lead');
        
        wp_set_current_user($team_lead_id);
        
        $output = do_shortcode('[vlp_teams]');
        
        // Should contain team lead styling
        $this->assertStringContainsString('vlp-teams-team-lead', $output, 'Output should contain team lead styling');
        
        // Clean up
        wp_delete_user($team_lead_id);
        vlp_teams_cleanup_test_data(array('blcs_users' => array($team_lead_id)));
    }
    
    /**
     * Test member sorting (team leads first, then by last name)
     */
    public function test_member_sorting() {
        if (!function_exists('vlp_org_assign_user_role')) {
            $this->markTestSkipped('Organization management plugin not available');
        }
        
        // Create additional team members
        $member_a_id = vlp_teams_create_test_user(array(
            'first_name' => 'Alice',
            'last_name' => 'Anderson'
        ));
        $member_z_id = vlp_teams_create_test_user(array(
            'first_name' => 'Zoe',
            'last_name' => 'Zulu'
        ));
        $team_lead_id = vlp_teams_create_test_user(array(
            'first_name' => 'Mike',
            'last_name' => 'Manager'
        ));
        
        // Create blcs_user entries
        vlp_teams_create_test_blcs_user($member_a_id, 'Standard');
        vlp_teams_create_test_blcs_user($member_z_id, 'Standard');
        vlp_teams_create_test_blcs_user($team_lead_id, 'Standard');
        
        // Assign to team
        vlp_teams_assign_test_user_to_team($member_a_id, $this->test_data['org_id'], $this->test_data['team_id'], 'Individual');
        vlp_teams_assign_test_user_to_team($member_z_id, $this->test_data['org_id'], $this->test_data['team_id'], 'Individual');
        vlp_teams_assign_test_user_to_team($team_lead_id, $this->test_data['org_id'], $this->test_data['team_id'], 'Team_Lead');
        
        wp_set_current_user($this->test_data['user_id']);
        
        $output = do_shortcode('[vlp_teams]');
        
        // Team lead should appear first
        $team_lead_pos = strpos($output, 'Mike Manager');
        $member_a_pos = strpos($output, 'Alice Anderson');
        $member_z_pos = strpos($output, 'Zoe Zulu');
        
        $this->assertLessThan($member_a_pos, $team_lead_pos, 'Team lead should appear before regular members');
        $this->assertLessThan($member_z_pos, $member_a_pos, 'Members should be sorted by last name');
        
        // Clean up
        wp_delete_user($member_a_id);
        wp_delete_user($member_z_id);
        wp_delete_user($team_lead_id);
        vlp_teams_cleanup_test_data(array(
            'blcs_users' => array($member_a_id, $member_z_id, $team_lead_id)
        ));
    }
    
    /**
     * Test personality trait display formatting
     */
    public function test_personality_trait_formatting() {
        wp_set_current_user($this->test_data['user_id']);
        
        $output = do_shortcode('[vlp_teams]');
        
        // Should contain personality traits
        $this->assertStringContainsString('Extraversion', $output, 'Output should contain trait names');
        $this->assertStringContainsString('Conscientiousness', $output, 'Output should contain trait names');
        
        // Should contain formatted trait values
        $this->assertStringContainsString('vlp-teams-trait-high', $output, 'Output should contain high trait formatting');
        $this->assertStringContainsString('vlp-teams-trait-low', $output, 'Output should contain low trait formatting');
        
        // Should contain first characters of trait types
        $this->assertStringContainsString('>O<', $output, 'Output should contain first character of high trait type');
        $this->assertStringContainsString('>R<', $output, 'Output should contain first character of low trait type');
    }
    
    /**
     * Test shortcode attributes
     */
    public function test_shortcode_attributes() {
        // Test with specific user_id attribute
        $output = do_shortcode('[vlp_teams user_id="' . $this->test_data['user_id'] . '"]');
        
        if (function_exists('vlp_org_get_user_teams')) {
            // Should contain team display for specified user
            $this->assertStringContainsString('vlp-teams-container', $output, 'Output should contain team container for specified user');
        } else {
            // Should contain membership error if org management not available
            $this->assertStringContainsString('vlp-teams-membership-error', $output, 'Output should contain membership error');
        }
        
        // Test with invalid user_id
        $output = do_shortcode('[vlp_teams user_id="99999"]');
        $this->assertStringContainsString('vlp-teams-subscription-error', $output, 'Output should contain subscription error for invalid user');
    }
    
    /**
     * Test empty team scenario
     */
    public function test_empty_team_scenario() {
        if (!function_exists('vlp_org_create_team')) {
            $this->markTestSkipped('Organization management plugin not available');
        }
        
        // Create empty team
        $empty_team_id = vlp_teams_create_test_team($this->test_data['org_id']);
        
        // Create user and assign to empty team
        $empty_team_user_id = vlp_teams_create_test_user();
        vlp_teams_create_test_blcs_user($empty_team_user_id, 'Standard');
        vlp_teams_assign_test_user_to_team($empty_team_user_id, $this->test_data['org_id'], $empty_team_id, 'Individual');
        
        wp_set_current_user($empty_team_user_id);
        
        $output = do_shortcode('[vlp_teams]');
        
        // Should contain empty team message
        $this->assertStringContainsString('vlp-teams-empty', $output, 'Output should contain empty team container');
        $this->assertStringContainsString('No team members found', $output, 'Output should contain empty team message');
        
        // Clean up
        wp_delete_user($empty_team_user_id);
        vlp_teams_cleanup_test_data(array(
            'blcs_users' => array($empty_team_user_id),
            'teams' => array($empty_team_id)
        ));
    }
    
    /**
     * Test HTML output structure and security
     */
    public function test_html_output_security() {
        wp_set_current_user($this->test_data['user_id']);
        
        $output = do_shortcode('[vlp_teams]');
        
        // Should not contain any unescaped user input
        $this->assertStringNotContainsString('<script>', $output, 'Output should not contain script tags');
        $this->assertStringNotContainsString('javascript:', $output, 'Output should not contain javascript: URLs');
        
        // Should contain properly escaped HTML
        $this->assertStringContainsString('class="vlp-teams-', $output, 'Output should contain properly formatted CSS classes');
        
        // Should contain proper HTML structure
        $this->assertStringContainsString('<div class="vlp-teams-container">', $output, 'Output should contain opening container div');
        $this->assertStringContainsString('</div>', $output, 'Output should contain closing divs');
    }
} 