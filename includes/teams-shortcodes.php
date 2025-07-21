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
    add_shortcode('vlp_teams_debug', 'vlp_teams_debug_shortcode_handler');
}

/**
 * Debug shortcode to check subscription status
 * 
 * Usage: [vlp_teams_debug]
 */
function vlp_teams_debug_shortcode_handler($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'user_id' => get_current_user_id()
    ), $atts);
    
    $user_id = intval($atts['user_id']);
    
    if (empty($user_id)) {
        return '<div class="vlp-teams-debug"><p>No user ID provided</p></div>';
    }
    
    global $wpdb;
    
    // Check blcs_user table for current_plan
    $blcs_user_table = $wpdb->prefix . 'blcs_user';
    
    $output = '<div class="vlp-teams-debug">';
    $output .= '<h3>AVLP Teams Debug Information</h3>';
    $output .= '<p><strong>User ID:</strong> ' . $user_id . '</p>';
    $output .= '<p><strong>Table:</strong> ' . $blcs_user_table . '</p>';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$blcs_user_table'") != $blcs_user_table) {
        $output .= '<p><strong>Table Status:</strong> <span style="color: red;">DOES NOT EXIST</span></p>';
        $output .= '</div>';
        return $output;
    }
    
    $output .= '<p><strong>Table Status:</strong> <span style="color: green;">EXISTS</span></p>';
    
    // Get user record
    $user_record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $blcs_user_table WHERE wp_uid = %d",
        $user_id
    ));
    
    if (!$user_record) {
        $output .= '<p><strong>User Record:</strong> <span style="color: orange;">NOT FOUND</span></p>';
        $output .= '<p><strong>Current Plan:</strong> N/A (no record)</p>';
        $output .= '<p><strong>Is Free Plan:</strong> <span style="color: green;">NO (no record = allow access)</span></p>';
        
        $has_paid_program = vlp_teams_user_has_paid_program($user_id);
        $output .= '<p><strong>Function Result:</strong> ' . ($has_paid_program ? '<span style="color: green;">ALLOW ACCESS</span>' : '<span style="color: red;">BLOCK ACCESS</span>') . '</p>';
        
        $output .= '</div>';
        return $output;
    }
    
    $output .= '<p><strong>User Record:</strong> <span style="color: green;">FOUND</span></p>';
    $output .= '<p><strong>Current Plan:</strong> "' . $user_record->current_plan . '"</p>';
    $output .= '<p><strong>Current Plan (trimmed/lowercase):</strong> "' . strtolower(trim($user_record->current_plan)) . '"</p>';
    
    $is_free = strtolower(trim($user_record->current_plan)) === 'free';
    $output .= '<p><strong>Is Free Plan:</strong> ' . ($is_free ? '<span style="color: red;">YES (will show error)</span>' : '<span style="color: green;">NO (will allow access)</span>') . '</p>';
    
    $has_paid_program = vlp_teams_user_has_paid_program($user_id);
    $output .= '<p><strong>Function Result:</strong> ' . ($has_paid_program ? '<span style="color: green;">ALLOW ACCESS</span>' : '<span style="color: red;">BLOCK ACCESS</span>') . '</p>';
    
    $output .= '</div>';
    
    return $output;
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
    
    // Use vertical layout instead of grid
    $output .= '<div class="vlp-teams-members-vertical">';
    
    foreach ($team_members as $member) {
        $output .= vlp_teams_generate_member_card($member, $team->team_id);
    }
    
    $output .= '</div>'; // Close vlp-teams-members-vertical
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
    } else {
        $card_class .= ' vlp-teams-individual';
    }
    
    $output = '<div class="' . $card_class . '">';
    
    // Profile image
    $avatar_url = vlp_teams_get_user_avatar_url($member->wp_uid, 60);
    $output .= '<div class="vlp-teams-member-avatar">';
    $output .= '<img src="' . esc_url($avatar_url) . '" alt="' . esc_attr($member->display_name) . '" class="vlp-teams-avatar-img">';
    $output .= '</div>';
    
    // Member content container
    $output .= '<div class="vlp-teams-member-content">';
    
    // Member name
    $full_name = trim($member->first_name . ' ' . $member->last_name);
    if (empty($full_name)) {
        $full_name = $member->display_name;
    }
    $output .= '<h3 class="vlp-teams-member-name">' . esc_html($full_name);
    
    // Team lead indicator
    if ($is_team_lead) {
        $output .= ' <span class="vlp-teams-lead-indicator" title="Team Lead">★</span>';
    }
    
    $output .= '</h3>';
    
    // Member role with label
    $role_display = str_replace('_', ' ', $member->role_type);
    $output .= '<p class="vlp-teams-member-role"><span class="vlp-teams-role-label">Role:</span> ' . esc_html($role_display) . '</p>';
    
    // Member title (if present)
    if (!empty($member->user_title)) {
        $output .= '<p class="vlp-teams-member-title">' . esc_html($member->user_title) . '</p>';
    }
    
    // Goals summary
    $goals_data = vlp_teams_get_user_active_goals($member->wp_uid);
    if (!empty($goals_data)) {
        $output .= '<div class="vlp-teams-goals-container">';
        $output .= vlp_teams_generate_goals_display($goals_data, $member->wp_uid);
        $output .= '</div>';
    }
    
    // Personality summary
    $personality_data = vlp_teams_get_user_personality_summary($member->wp_uid);
    if (!empty($personality_data)) {
        $output .= '<div class="vlp-teams-personality-container">';
        $output .= vlp_teams_generate_personality_display($personality_data);
        $output .= '</div>';
    }
    
    $output .= '</div>'; // Close member content
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
    if (empty($personality_data)) {
        return '';
    }
    
    // Generate unique ID for this personality section
    $unique_id = 'personality-' . uniqid();
    
    $output = '<div class="vlp-teams-personality-container">';
    
    // Summary row with user primary traits
    $output .= '<div class="vlp-teams-personality-summary" onclick="vlpTeamsTogglePersonality(\'' . $unique_id . '\')">';
    $output .= '<span class="vlp-teams-personality-label"><strong>Personality:</strong> </span>';
    
    $primary_traits = array();
    foreach ($personality_data as $record) {
        if (!empty($record->user_primary_trait)) {
            $primary_traits[] = esc_html($record->user_primary_trait);
        }
    }
    
    $output .= implode('; ', $primary_traits);
    $output .= ' <span class="vlp-teams-personality-toggle" id="toggle-' . $unique_id . '">▼</span>';
    $output .= '</div>';
    
    // Detailed view (initially hidden)
    $output .= '<div class="vlp-teams-personality-details" id="' . $unique_id . '" style="display: none;">';
    
    foreach ($personality_data as $record) {
        $output .= '<div class="vlp-teams-personality-detail-row">';
        
        // Trait name in bold (column 1)
        $output .= '<div class="vlp-teams-personality-trait-name">' . esc_html($record->trait) . ':</div>';
        
        // High trait type with orange background (column 2)
        $output .= '<div class="vlp-teams-personality-high-trait">';
        if (!empty($record->high_trait_type)) {
            // Determine if high trait should be faded (value < 50)
            $high_trait_class = 'vlp-teams-trait-badge vlp-teams-trait-high-badge';
            if (!empty($record->high_trait_type_value) && intval($record->high_trait_type_value) < 50) {
                $high_trait_class .= ' vlp-teams-trait-faded';
            }
            $output .= '<span class="' . $high_trait_class . '">' . esc_html($record->high_trait_type) . '</span>';
        }
        $output .= '</div>';
        
        // High trait value in parentheses (column 3)
        $output .= '<div class="vlp-teams-personality-high-value">';
        if (!empty($record->high_trait_type_value)) {
            $output .= '<span class="vlp-teams-trait-value">(' . esc_html($record->high_trait_type_value) . ')</span>';
        }
        $output .= '</div>';
        
        // Low trait type with blue background (column 4)
        $output .= '<div class="vlp-teams-personality-low-trait">';
        if (!empty($record->low_trait_type)) {
            // Determine if low trait should be faded (value < 50)
            $low_trait_class = 'vlp-teams-trait-badge vlp-teams-trait-low-badge';
            if (!empty($record->low_trait_type_value) && intval($record->low_trait_type_value) < 50) {
                $low_trait_class .= ' vlp-teams-trait-faded';
            }
            $output .= '<span class="' . $low_trait_class . '">' . esc_html($record->low_trait_type) . '</span>';
        }
        $output .= '</div>';
        
        // Low trait value in parentheses (column 5)
        $output .= '<div class="vlp-teams-personality-low-value">';
        if (!empty($record->low_trait_type_value)) {
            $output .= '<span class="vlp-teams-trait-value">(' . esc_html($record->low_trait_type_value) . ')</span>';
        }
        $output .= '</div>';
        
        $output .= '</div>';
    }
    
    $output .= '</div>'; // Close details
    $output .= '</div>'; // Close container
    
    return $output;
}

/**
 * Add JavaScript for personality toggle functionality
 */
function vlp_teams_add_personality_script() {
    static $script_added = false;
    
    if (!$script_added) {
        ?>
        <script type="text/javascript">
        function vlpTeamsTogglePersonality(elementId) {
            var details = document.getElementById(elementId);
            var toggle = document.getElementById('toggle-' + elementId);
            
            if (details.style.display === 'none' || details.style.display === '') {
                details.style.display = 'block';
                toggle.innerHTML = '▲';
            } else {
                details.style.display = 'none';
                toggle.innerHTML = '▼';
            }
        }
        </script>
        <?php
        $script_added = true;
    }
}

// Add the script to the footer when the shortcode is used
add_action('wp_footer', 'vlp_teams_add_personality_script');

/**
 * Generate goals display for a team member
 * 
 * @param array $goals_data Array of goal records
 * @param int $wp_uid WordPress user ID
 * @return string HTML output for goals display
 */
function vlp_teams_generate_goals_display($goals_data, $wp_uid) {
    if (empty($goals_data)) {
        return '';
    }
    
    // Generate unique ID for this goals section
    $unique_id = 'goals-' . uniqid();
    
    $output = '<div class="vlp-teams-goals-container">';
    
    // Summary row
    $output .= '<div class="vlp-teams-goals-summary" onclick="vlpTeamsToggleGoals(\'' . $unique_id . '\')">';
    $output .= '<span class="vlp-teams-goals-label"><strong>Goals:</strong> </span>';
    $output .= count($goals_data) . ' active goal' . (count($goals_data) !== 1 ? 's' : '');
    $output .= ' <span class="vlp-teams-goals-toggle" id="toggle-' . $unique_id . '">▼</span>';
    $output .= '</div>';
    
    // Detailed view (initially hidden)
    $output .= '<div class="vlp-teams-goals-details" id="' . $unique_id . '" style="display: none;">';
    
    // Table headers
    $output .= '<div class="vlp-teams-goals-table">';
    $output .= '<div class="vlp-teams-goals-header-row">';
    $output .= '<div class="vlp-teams-goals-header">Goal</div>';
    $output .= '<div class="vlp-teams-goals-header">Status</div>';
    $output .= '<div class="vlp-teams-goals-header">Start</div>';
    $output .= '<div class="vlp-teams-goals-header">End</div>';
    $output .= '<div class="vlp-teams-goals-header">Progress</div>';
    $output .= '<div class="vlp-teams-goals-header">Updated</div>';
    $output .= '</div>';
    
    // Goal rows
    foreach ($goals_data as $goal) {
        $output .= '<div class="vlp-teams-goals-data-row">';
        
        // Goal name
        $output .= '<div class="vlp-teams-goals-data">' . esc_html($goal->goal_name) . '</div>';
        
        // Status
        $output .= '<div class="vlp-teams-goals-data">' . esc_html($goal->status) . '</div>';
        
        // Start date
        $start_date = !empty($goal->start_date) ? date('M j, Y', strtotime($goal->start_date)) : 'N/A';
        $output .= '<div class="vlp-teams-goals-data">' . esc_html($start_date) . '</div>';
        
        // End date
        $end_date = !empty($goal->end_date) ? date('M j, Y', strtotime($goal->end_date)) : 'N/A';
        $output .= '<div class="vlp-teams-goals-data">' . esc_html($end_date) . '</div>';
        
        // Progress
        $progress = intval($goal->progress);
        $output .= '<div class="vlp-teams-goals-data">' . $progress . '%</div>';
        
        // Last Updated (clickable)
        $last_updated = !empty($goal->updated_at) ? date('M j, Y', strtotime($goal->updated_at)) : 'N/A';
        $output .= '<div class="vlp-teams-goals-data">';
        $output .= '<span class="vlp-teams-goals-updates-link" onclick="vlpTeamsShowGoalUpdates(' . intval($goal->id) . ', \'' . esc_js($goal->goal_name) . '\')">';
        $output .= esc_html($last_updated);
        $output .= '</span>';
        $output .= '</div>';
        
        $output .= '</div>';
    }
    
    $output .= '</div>'; // Close table
    $output .= '</div>'; // Close details
    $output .= '</div>'; // Close container
    
    return $output;
}

/**
 * Add JavaScript for goals functionality
 */
function vlp_teams_add_goals_script() {
    static $script_added = false;
    
    if (!$script_added) {
        ?>
        <script type="text/javascript">
        // Define AJAX variables directly
        window.vlp_teams_ajax = {
            ajax_url: "<?php echo admin_url('admin-ajax.php'); ?>",
            nonce: "<?php echo wp_create_nonce('vlp_teams_ajax_nonce'); ?>"
        };
        
        function vlpTeamsToggleGoals(uniqueId) {
            var details = document.getElementById(uniqueId);
            var toggle = document.getElementById('toggle-' + uniqueId);
            
            if (details.style.display === 'none') {
                details.style.display = 'block';
                toggle.innerHTML = '▲';
            } else {
                details.style.display = 'none';
                toggle.innerHTML = '▼';
            }
        }
        
        function vlpTeamsShowGoalUpdates(goalId, goalName) {
            // Create modal backdrop
            var backdrop = document.createElement('div');
            backdrop.className = 'vlp-teams-modal-backdrop';
            backdrop.onclick = function() { vlpTeamsCloseModal(); };
            
            // Create modal container
            var modal = document.createElement('div');
            modal.className = 'vlp-teams-modal';
            modal.id = 'vlp-teams-goal-updates-modal';
            
            // Create modal content
            var content = '<div class="vlp-teams-modal-header">';
            content += '<h3>Goal Updates: ' + goalName + '</h3>';
            content += '<span class="vlp-teams-modal-close" onclick="vlpTeamsCloseModal()">&times;</span>';
            content += '</div>';
            content += '<div class="vlp-teams-modal-body">';
            content += '<div class="vlp-teams-loading">Loading updates...</div>';
            content += '</div>';
            
            modal.innerHTML = content;
            backdrop.appendChild(modal);
            document.body.appendChild(backdrop);
            
            // Load goal updates via AJAX
            vlpTeamsLoadGoalUpdates(goalId);
        }
        
        function vlpTeamsLoadGoalUpdates(goalId) {
            var modalBody = document.querySelector('#vlp-teams-goal-updates-modal .vlp-teams-modal-body');
            
            // Show loading state
            modalBody.innerHTML = '<div class="vlp-teams-loading">Loading goal updates...</div>';
            
            // Check if AJAX variables are available
            if (typeof vlp_teams_ajax === 'undefined') {
                modalBody.innerHTML = '<div class="vlp-teams-error">AJAX configuration not found. Please refresh the page.</div>';
                return;
            }
            
            // Make AJAX request
            var xhr = new XMLHttpRequest();
            xhr.open('POST', vlp_teams_ajax.ajax_url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                modalBody.innerHTML = response.data.html;
                            } else {
                                modalBody.innerHTML = '<div class="vlp-teams-error">Error: ' + (response.data || 'Unknown error') + '</div>';
                            }
                        } catch (e) {
                            modalBody.innerHTML = '<div class="vlp-teams-error">Error parsing response: ' + e.message + '</div>';
                        }
                    } else {
                        modalBody.innerHTML = '<div class="vlp-teams-error">HTTP Error: ' + xhr.status + '</div>';
                    }
                }
            };
            
            var data = 'action=vlp_teams_get_goal_updates&goal_id=' + goalId + '&nonce=' + vlp_teams_ajax.nonce;
            xhr.send(data);
        }
        
        function vlpTeamsCloseModal() {
            var backdrop = document.querySelector('.vlp-teams-modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }
        </script>
        <?php
        $script_added = true;
    }
}

// Add the goals script to the footer when the shortcode is used
add_action('wp_footer', 'vlp_teams_add_goals_script'); 