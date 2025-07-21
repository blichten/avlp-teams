<?php
/**
 * AJAX handlers for AVLP Teams plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler for getting goal updates
 */
function vlp_teams_ajax_get_goal_updates() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'vlp_teams_ajax_nonce')) {
        wp_die('Security check failed');
    }
    
    $goal_id = intval($_POST['goal_id']);
    
    if (empty($goal_id)) {
        wp_send_json_error('Invalid goal ID');
        return;
    }
    
    // Get goal updates
    $updates = vlp_teams_get_goal_updates($goal_id);
    
    if (empty($updates)) {
        wp_send_json_success([
            'html' => '<p class="vlp-teams-no-updates">No updates found for this goal.</p>'
        ]);
        return;
    }
    
    // Generate HTML for updates table
    $html = '<div class="vlp-teams-updates-table">';
    
    // Table headers
    $html .= '<div class="vlp-teams-updates-header-row">';
    $html .= '<div class="vlp-teams-updates-header">Date</div>';
    $html .= '<div class="vlp-teams-updates-header">Status</div>';
    $html .= '<div class="vlp-teams-updates-header">Progress</div>';
    $html .= '<div class="vlp-teams-updates-header">Updated</div>';
    $html .= '</div>';
    
    // Updates rows
    foreach ($updates as $update) {
        $html .= '<div class="vlp-teams-updates-data-row">';
        
        // Date
        $date = !empty($update->created_at) ? date('M j, Y g:i A', strtotime($update->created_at)) : 'N/A';
        $html .= '<div class="vlp-teams-updates-data">' . esc_html($date) . '</div>';
        
        // Status
        $status = !empty($update->status_after) ? $update->status_after : 'N/A';
        $html .= '<div class="vlp-teams-updates-data">' . esc_html($status) . '</div>';
        
        // Progress
        $progress = !empty($update->progress_after) ? intval($update->progress_after) . '%' : 'N/A';
        $html .= '<div class="vlp-teams-updates-data">' . esc_html($progress) . '</div>';
        
        // Update content
        $content = !empty($update->content) ? $update->content : 'No update content';
        $html .= '<div class="vlp-teams-updates-data vlp-teams-update-content">' . wp_kses_post($content) . '</div>';
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    wp_send_json_success(['html' => $html]);
}

// Register AJAX handlers
add_action('wp_ajax_vlp_teams_get_goal_updates', 'vlp_teams_ajax_get_goal_updates');
add_action('wp_ajax_nopriv_vlp_teams_get_goal_updates', 'vlp_teams_ajax_get_goal_updates'); 