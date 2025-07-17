<?php
/**
 * Shortcode functions for AVLP Teams plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register all shortcodes
 */
function vlp_teams_register_shortcodes() {
    add_shortcode('vlp_teams', 'vlp_teams_shortcode_handler');
}

/**
 * Main teams shortcode handler
 * 
 * Usage: [vlp_teams]
 */
function vlp_teams_shortcode_handler($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'user_id' => get_current_user_id()
    ), $atts);
    
    $user_id = intval($atts['user_id']);
    
    // Check if user is logged in
    if (!is_user_logged_in() && empty($user_id)) {
        return '<div class="vlp-teams-error"><p>' . __('Please log in to view team information.', 'avlp-teams') . '</p></div>';
    }
    
    // Use current user if no user_id specified
    if (empty($user_id)) {
        $user_id = get_current_user_id();
    }
    
    // Check if user has a program other than free
    if (!vlp_teams_user_has_paid_program($user_id)) {
        return '<div class="vlp-teams-subscription-error">
            <p>Oops. Team features are not available in your current subscription plan. 
            <a href="/programs">Find out more, here.</a></p>
        </div>';
    }
    
    // Check if user belongs to a team
    if (!vlp_teams_user_belongs_to_team($user_id)) {
        return '<div class="vlp-teams-membership-error">
            <p>Oops! You\'re not part of a team. Check with your organization admin. 
            If you are the organization admin, click here.</p>
        </div>';
    }
    
    // Get user's primary team
    $user_team = vlp_teams_get_user_primary_team($user_id);
    
    if (!$user_team) {
        return '<div class="vlp-teams-error">
            <p>Unable to load team information. Please try again later.</p>
        </div>';
    }
    
    // Get team members
    $team_members = vlp_teams_get_team_members($user_team->team_id);
    
    if (empty($team_members)) {
        return '<div class="vlp-teams-empty">
            <h2>' . esc_html($user_team->team_name) . '</h2>
            <p>No team members found.</p>
        </div>';
    }
    
    // Generate the team display
    return vlp_teams_generate_team_display($user_team, $team_members);
}

/**
 * Generate the team display HTML
 * 
 * @param object $team Team object
 * @param array $team_members Array of team member objects
 * @return string HTML output
 */
function vlp_teams_generate_team_display($team, $team_members) {
    $output = '<div class="vlp-teams-container">';
    
    // Team name as title
    $output .= '<h2 class="vlp-teams-title">' . esc_html($team->team_name) . '</h2>';
    
    // Sort members: team leads first, then by last name
    usort($team_members, function($a, $b) {
        // Check if either is a team lead
        $a_is_lead = vlp_teams_user_is_team_lead($a->wp_uid, $team->team_id);
        $b_is_lead = vlp_teams_user_is_team_lead($b->wp_uid, $team->team_id);
        
        // Team leads come first
        if ($a_is_lead && !$b_is_lead) {
            return -1;
        } elseif (!$a_is_lead && $b_is_lead) {
            return 1;
        }
        
        // Sort by last name
        return strcmp($a->last_name, $b->last_name);
    });
    
    $output .= '<div class="vlp-teams-members">';
    
    foreach ($team_members as $member) {
        $output .= vlp_teams_generate_member_card($member, $team->team_id);
    }
    
    $output .= '</div>'; // Close vlp-teams-members
    $output .= '</div>'; // Close vlp-teams-container
    
    return $output;
}

/**
 * Generate a single team member card
 * 
 * @param object $member Team member object
 * @param int $team_id Team ID
 * @return string HTML output for member card
 */
function vlp_teams_generate_member_card($member, $team_id) {
    $is_team_lead = vlp_teams_user_is_team_lead($member->wp_uid, $team_id);
    
    $card_class = 'vlp-teams-member-card';
    if ($is_team_lead) {
        $card_class .= ' vlp-teams-team-lead';
    }
    
    $output = '<div class="' . $card_class . '">';
    
    // Member name
    $full_name = trim($member->first_name . ' ' . $member->last_name);
    if (empty($full_name)) {
        $full_name = $member->display_name;
    }
    $output .= '<h3 class="vlp-teams-member-name">' . esc_html($full_name) . '</h3>';
    
    // Member role
    $role_display = str_replace('_', ' ', $member->role_type);
    $output .= '<p class="vlp-teams-member-role">' . esc_html($role_display) . '</p>';
    
    // Member title (if present)
    if (!empty($member->user_title)) {
        $output .= '<p class="vlp-teams-member-title">' . esc_html($member->user_title) . '</p>';
    }
    
    // Personality summary
    $personality_data = vlp_teams_get_user_personality_summary($member->wp_uid);
    if (!empty($personality_data)) {
        $output .= '<div class="vlp-teams-personality-summary">';
        $output .= vlp_teams_generate_personality_display($personality_data);
        $output .= '</div>';
    }
    
    $output .= '</div>'; // Close member card
    
    return $output;
}

/**
 * Generate personality display for a team member
 * 
 * @param array $personality_data Array of personality summary records
 * @return string HTML output for personality display
 */
function vlp_teams_generate_personality_display($personality_data) {
    $output = '<div class="vlp-teams-personality-row">';
    
    $personality_items = array();
    
    foreach ($personality_data as $record) {
        $item = '';
        
        // Display trait
        $item .= esc_html($record->trait) . ' ';
        
        // Display high trait type first character with orange background
        if (!empty($record->high_trait_type)) {
            $first_char = strtoupper(substr($record->high_trait_type, 0, 1));
            $item .= '<span class="vlp-teams-trait-high">' . esc_html($first_char) . '</span>';
        }
        
        // Display high trait type value
        if (!empty($record->high_trait_type_value)) {
            $item .= esc_html($record->high_trait_type_value) . ' ';
        }
        
        // Display low trait type first character with blue background
        if (!empty($record->low_trait_type)) {
            $first_char = strtoupper(substr($record->low_trait_type, 0, 1));
            $item .= '<span class="vlp-teams-trait-low">' . esc_html($first_char) . '</span>';
        }
        
        $personality_items[] = $item;
    }
    
    // Join personality items with 4 spaces between them
    $output .= implode('&nbsp;&nbsp;&nbsp;&nbsp;', $personality_items);
    
    $output .= '</div>';
    
    return $output;
} 