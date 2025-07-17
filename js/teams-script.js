/**
 * AVLP Teams Plugin JavaScript
 * Following VLP Development Standards
 */

(function($) {
    'use strict';

    // Check if jQuery is available
    if (typeof $ === 'undefined') {
        console.error('VLP Teams: jQuery is required but not loaded');
        return;
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        vlpTeamsInit();
    });

    /**
     * Initialize teams functionality
     */
    function vlpTeamsInit() {
        // Check if teams container exists
        if (!$('.vlp-teams-container').length) {
            return;
        }

        // Initialize team member cards
        initMemberCards();
        
        // Initialize personality tooltips
        initPersonalityTooltips();
        
        // Initialize responsive behavior
        initResponsiveBehavior();
    }

    /**
     * Initialize member card interactions
     */
    function initMemberCards() {
        $('.vlp-teams-member-card').each(function() {
            var $card = $(this);
            
            // Add hover effects
            $card.on('mouseenter', function() {
                $(this).addClass('vlp-teams-card-hover');
            }).on('mouseleave', function() {
                $(this).removeClass('vlp-teams-card-hover');
            });
            
            // Add click functionality for accessibility
            $card.on('click', function(e) {
                // Don't trigger if clicking on links
                if ($(e.target).is('a')) {
                    return;
                }
                
                // Toggle expanded state
                $(this).toggleClass('vlp-teams-card-expanded');
            });
            
            // Add keyboard navigation
            $card.attr('tabindex', '0').on('keydown', function(e) {
                if (e.keyCode === 13 || e.keyCode === 32) { // Enter or Space
                    e.preventDefault();
                    $(this).click();
                }
            });
        });
    }

    /**
     * Initialize personality trait tooltips
     */
    function initPersonalityTooltips() {
        $('.vlp-teams-trait-high, .vlp-teams-trait-low').each(function() {
            var $trait = $(this);
            var traitText = $trait.text();
            var traitType = $trait.hasClass('vlp-teams-trait-high') ? 'High' : 'Low';
            
            // Add tooltip functionality
            $trait.attr('title', traitType + ' trait: ' + traitText);
            
            // Enhanced tooltip with hover delay
            var hoverTimer;
            $trait.on('mouseenter', function() {
                var $this = $(this);
                hoverTimer = setTimeout(function() {
                    showTooltip($this);
                }, 500);
            }).on('mouseleave', function() {
                clearTimeout(hoverTimer);
                hideTooltip();
            });
        });
    }

    /**
     * Show custom tooltip
     */
    function showTooltip($element) {
        var tooltipText = $element.attr('title');
        var $tooltip = $('<div class="vlp-teams-tooltip">' + tooltipText + '</div>');
        
        $('body').append($tooltip);
        
        var offset = $element.offset();
        $tooltip.css({
            top: offset.top - $tooltip.outerHeight() - 10,
            left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
        });
        
        $tooltip.fadeIn(200);
    }

    /**
     * Hide custom tooltip
     */
    function hideTooltip() {
        $('.vlp-teams-tooltip').fadeOut(200, function() {
            $(this).remove();
        });
    }

    /**
     * Initialize responsive behavior
     */
    function initResponsiveBehavior() {
        var $container = $('.vlp-teams-container');
        var $members = $('.vlp-teams-members');
        
        // Check and update layout on window resize
        $(window).on('resize', debounce(function() {
            updateLayout();
        }, 250));
        
        // Initial layout update
        updateLayout();
        
        function updateLayout() {
            var containerWidth = $container.width();
            var memberCount = $('.vlp-teams-member-card').length;
            
            // Update grid classes based on container width and member count
            $members.removeClass('vlp-teams-grid-small vlp-teams-grid-medium vlp-teams-grid-large');
            
            if (containerWidth < 768) {
                $members.addClass('vlp-teams-grid-small');
            } else if (memberCount <= 4) {
                $members.addClass('vlp-teams-grid-medium');
            } else {
                $members.addClass('vlp-teams-grid-large');
            }
        }
    }

    /**
     * Debounce function to limit rapid function calls
     */
    function debounce(func, wait) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;
            
            var later = function() {
                timeout = null;
                func.apply(context, args);
            };
            
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Utility function to check if element exists
     */
    function elementExists(selector) {
        return $(selector).length > 0;
    }

    /**
     * Handle errors gracefully
     */
    function handleError(error, context) {
        console.error('VLP Teams Error in ' + context + ':', error);
    }

    // Add CSS for dynamic elements
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .vlp-teams-card-hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 12px rgba(0,0,0,0.2) !important;
            }
            
            .vlp-teams-card-expanded {
                background-color: #f8f9fa;
            }
            
            .vlp-teams-tooltip {
                position: absolute;
                background: rgba(0,0,0,0.8);
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 1000;
                white-space: nowrap;
                display: none;
            }
            
            .vlp-teams-tooltip::after {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                margin-left: -5px;
                border-width: 5px;
                border-style: solid;
                border-color: rgba(0,0,0,0.8) transparent transparent transparent;
            }
        `)
        .appendTo('head');

})(jQuery); 