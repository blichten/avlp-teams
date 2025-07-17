<?php
/**
 * Display functions for AVLP Teams plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get user profile image URL following AVLP standards
 * 
 * @param int $user_id User ID
 * @param int $size Image size (width in pixels)
 * @return string Image URL
 */
function vlp_teams_get_user_avatar_url($user_id, $size = 150) {
    // First try to use AVLP's standard profile image function if available
    if (function_exists('vlp_get_user_image')) {
        $url = vlp_get_user_image($user_id);
        // If we got a valid URL, return it with size parameter if needed
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            // If the URL is from the default image, we can add size parameters
            if (strpos($url, 'default-profile.webp') !== false) {
                return $url . '?w=' . $size . '&h=' . $size . '&fit=crop';
            }
            return $url;
        }
    }
    
    // Check for direct attachment ID in user meta (fallback)
    $attachment_id = get_user_meta($user_id, 'vlp_profile_image', true);
    if ($attachment_id) {
        $url = wp_get_attachment_url($attachment_id);
        if ($url) {
            return $url;
        }
    }
    
    // Check if AVLP General plugin is active and has a default image
    if (defined('VLP_GENERAL_PLUGIN_DIR')) {
        $avlp_default = VLP_GENERAL_PLUGIN_DIR . 'img/default-profile.webp';
        if (file_exists($avlp_default)) {
            return VLP_GENERAL_PLUGIN_URL . 'img/default-profile.webp';
        }
    }
    
    // Fall back to WordPress default avatar
    return get_avatar_url($user_id, array('size' => $size));
}

/**
 * Format personality trait display
 * 
 * @param string $trait_type Trait type string
 * @param string $trait_value Trait value
 * @param string $css_class CSS class for styling
 * @return string Formatted HTML
 */
function vlp_teams_format_personality_trait($trait_type, $trait_value, $css_class) {
    if (empty($trait_type)) {
        return '';
    }
    
    $first_char = strtoupper(substr($trait_type, 0, 1));
    $output = '<span class="' . esc_attr($css_class) . '">' . esc_html($first_char) . '</span>';
    
    if (!empty($trait_value)) {
        $output .= esc_html($trait_value);
    }
    
    return $output;
}

/**
 * Get team lead indicator HTML
 * 
 * @param bool $is_team_lead Whether user is team lead
 * @return string HTML for team lead indicator
 */
function vlp_teams_get_team_lead_indicator($is_team_lead) {
    if (!$is_team_lead) {
        return '';
    }
    
    return '<span class="vlp-teams-lead-indicator" title="Team Lead">★</span>';
}

/**
 * Sanitize and format user role display
 * 
 * @param string $role_type Raw role type from database
 * @return string Formatted role display
 */
function vlp_teams_format_role_display($role_type) {
    if (empty($role_type)) {
        return '';
    }
    
    // Replace underscores with spaces and capitalize words
    $formatted = str_replace('_', ' ', $role_type);
    return ucwords($formatted);
}

/**
 * Generate responsive grid classes for team members
 * 
 * @param int $member_count Number of team members
 * @return string CSS classes for responsive grid
 */
function vlp_teams_get_grid_classes($member_count) {
    $classes = 'vlp-teams-grid';
    
    if ($member_count <= 2) {
        $classes .= ' vlp-teams-grid-small';
    } elseif ($member_count <= 4) {
        $classes .= ' vlp-teams-grid-medium';
    } else {
        $classes .= ' vlp-teams-grid-large';
    }
    
    return $classes;
}

/**
 * Check if personality data should be displayed
 * 
 * @param array $personality_data Personality data array
 * @return bool Whether to display personality data
 */
function vlp_teams_should_display_personality($personality_data) {
    if (empty($personality_data)) {
        return false;
    }
    
    // Check if at least one record has meaningful data
    foreach ($personality_data as $record) {
        if (!empty($record->trait) && 
            (!empty($record->high_trait_type) || !empty($record->low_trait_type))) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get default team member data structure
 * 
 * @return array Default member data
 */
function vlp_teams_get_default_member_data() {
    return array(
        'wp_uid' => 0,
        'first_name' => '',
        'last_name' => '',
        'display_name' => '',
        'user_email' => '',
        'user_title' => '',
        'role_type' => 'Individual'
    );
}

/**
 * Validate team member data
 * 
 * @param object $member Member object
 * @return bool Whether member data is valid
 */
function vlp_teams_validate_member_data($member) {
    if (empty($member) || !is_object($member)) {
        return false;
    }
    
    // Must have a valid WordPress user ID
    if (empty($member->wp_uid) || !is_numeric($member->wp_uid)) {
        return false;
    }
    
    // Must have some form of name
    $has_name = !empty($member->first_name) || 
                !empty($member->last_name) || 
                !empty($member->display_name);
    
    return $has_name;
}

/**
 * Get team display mode based on member count
 * 
 * @param int $member_count Number of team members
 * @return string Display mode (card, list, compact)
 */
function vlp_teams_get_display_mode($member_count) {
    if ($member_count <= 3) {
        return 'card';
    } elseif ($member_count <= 8) {
        return 'list';
    } else {
        return 'compact';
    }
} 