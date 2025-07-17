# AVLP Teams Plugin

A WordPress plugin that provides team management and display functionality for the Virtual Leadership Programs platform. This plugin enables users to view their team members with detailed personality data integration.

## Features

### 🎯 Core Functionality
- **Team Member Display**: Show all members of a user's team in an organized card layout
- **Personality Integration**: Display personality summary data with visual trait indicators
- **Subscription Validation**: Ensure team features are only available to paid subscribers
- **Team Lead Identification**: Highlight team leads with special styling
- **Responsive Design**: Mobile-friendly layout that works on all devices

### 🔐 Access Control
- **Subscription Checking**: Validates user has non-free program subscription
- **Team Membership**: Verifies user belongs to a team before displaying content
- **Organization Integration**: Seamlessly integrates with AVLP Organization Management plugin

### 🎨 Visual Features
- **Card-Based Layout**: Clean, modern card design for team member display
- **Personality Traits**: Color-coded personality indicators (Orange for high traits, Blue for low traits)
- **Team Lead Highlighting**: Special background and styling for team leads
- **Sorting**: Team leads displayed first, then members sorted by last name

## Installation

### Requirements
- WordPress 5.9 or higher
- PHP 7.4 or higher
- AVLP Organization Management plugin (for team functionality)
- AVLP General plugin (for shared functions)

### Manual Installation
1. Download the plugin files
2. Upload the `avlp-teams` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin
4. Ensure required dependencies are installed and activated

### Composer Installation
```bash
composer require avlp/teams
```

## Usage

### Basic Shortcode
Display the current user's team:
```
[vlp_teams]
```

### With User ID
Display a specific user's team:
```
[vlp_teams user_id="123"]
```

### User Flow

1. **Subscription Check**: Plugin first verifies user has a paid subscription
2. **Team Membership**: Checks if user belongs to a team
3. **Team Display**: Shows team members with personality data
4. **Error Handling**: Provides clear messages for various error states

## Technical Details

### Database Dependencies
- `blcs_user`: User subscription and plan information
- `blcs_personality_summary`: Personality trait data
- `avlp_organizations`: Organization information (via Organization Management plugin)
- `avlp_teams`: Team information (via Organization Management plugin)
- `avlp_user_roles`: User role assignments (via Organization Management plugin)

### File Structure
```
avlp-teams/
├── default-teams.php              # Main plugin file
├── css/
│   └── teams-style.css           # Plugin styles
├── js/
│   └── teams-script.js           # Plugin JavaScript
├── includes/
│   ├── teams-core-functions.php  # Core functionality
│   ├── teams-shortcodes.php      # Shortcode handlers
│   └── teams-display-functions.php # Display utilities
├── tests/                        # Comprehensive test suite
├── .github/workflows/            # CI/CD pipeline
├── monitoring/                   # Production monitoring
└── README.md                     # This file
```

### Key Functions

#### Core Functions
- `vlp_teams_user_has_paid_program()`: Check subscription status
- `vlp_teams_user_belongs_to_team()`: Verify team membership
- `vlp_teams_get_team_members()`: Retrieve team member data
- `vlp_teams_get_user_personality_summary()`: Get personality data

#### Display Functions
- `vlp_teams_generate_team_display()`: Main display generation
- `vlp_teams_generate_member_card()`: Individual member cards
- `vlp_teams_generate_personality_display()`: Personality trait formatting

## Styling

### CSS Classes
- `.vlp-teams-container`: Main container
- `.vlp-teams-title`: Team name title
- `.vlp-teams-members`: Member grid container
- `.vlp-teams-member-card`: Individual member card
- `.vlp-teams-team-lead`: Team lead special styling
- `.vlp-teams-personality-summary`: Personality data container
- `.vlp-teams-trait-high`: High trait indicator (orange)
- `.vlp-teams-trait-low`: Low trait indicator (blue)

### Color Scheme
Following VLP Development Standards:
- **Primary**: #0066ff (Blue)
- **CTA/Focus**: #ff6600 (Orange)
- **Faded Primary**: #bbd5fc (Light Blue)

## Error Messages

### Subscription Error
```
Oops. Team features are not available in your current subscription plan. 
Find out more, here.
```

### Team Membership Error
```
Oops! You're not part of a team. Check with your organization admin. 
If you are the organization admin, click here.
```

### Login Required
```
Please log in to view team information.
```

## Testing

### Comprehensive Test Suite
The plugin includes a complete testing framework:

- **Unit Tests**: PHPUnit tests for all core functionality
- **End-to-End Tests**: Playwright tests for user workflows
- **Security Scanning**: Automated vulnerability detection
- **Performance Testing**: Lighthouse performance audits
- **Accessibility Testing**: WCAG 2.1 AA compliance verification

### Running Tests
```bash
# Unit tests
composer test

# E2E tests
npx playwright test

# All tests with coverage
composer test-coverage
```

See [TESTING-SETUP-GUIDE.md](TESTING-SETUP-GUIDE.md) for detailed testing instructions.

## Development

### Contributing
1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

### Code Standards
- Follow WordPress Coding Standards
- Maintain 80%+ test coverage
- Use VLP naming conventions (`vlp_teams_` prefix)
- Include comprehensive PHPDoc comments

### Development Environment
```bash
# Install dependencies
composer install
npm install

# Setup test environment
composer run install-wp-tests

# Run development server
npm run dev
```

## Security

### Input Validation
- All user inputs are sanitized using WordPress functions
- Database queries use prepared statements
- Nonce verification for sensitive operations
- Capability checks for access control

### Data Protection
- No sensitive data stored in browser
- Secure handling of personality data
- Proper escaping of all output
- CSRF protection on all forms

## Performance

### Optimization Features
- Conditional script/style loading (only when shortcode present)
- Efficient database queries with proper indexing
- Responsive images and lazy loading
- Minified CSS and JavaScript in production

### Monitoring
- Real-time performance monitoring
- Error tracking and alerting
- Usage analytics and reporting
- Health check endpoints

## Compatibility

### WordPress Versions
- WordPress 5.9+
- WordPress 6.0+
- WordPress 6.1+
- WordPress 6.2+

### PHP Versions
- PHP 7.4+
- PHP 8.0+
- PHP 8.1+
- PHP 8.2+

### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Changelog

### Version 1.0.0
- Initial release
- Team member display functionality
- Personality data integration
- Subscription validation
- Comprehensive testing framework
- CI/CD pipeline implementation

## Support

### Documentation
- [Testing Setup Guide](TESTING-SETUP-GUIDE.md)
- [API Documentation](docs/api.md)
- [Troubleshooting Guide](docs/troubleshooting.md)

### Getting Help
- **GitHub Issues**: Report bugs and request features
- **GitHub Discussions**: Ask questions and share ideas
- **Email Support**: admin@virtualleadershipprograms.com

### Professional Support
For enterprise support and custom development:
- **Website**: https://virtualleadershipprograms.com
- **Contact**: admin@virtualleadershipprograms.com

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Credits

### Development Team
- **Virtual Leadership Programs**: Core development and maintenance
- **Contributors**: See [CONTRIBUTORS.md](CONTRIBUTORS.md) for full list

### Dependencies
- **WordPress**: Content management system
- **PHPUnit**: Unit testing framework
- **Playwright**: End-to-end testing framework
- **Composer**: PHP dependency management
- **GitHub Actions**: CI/CD pipeline

---

**Plugin Version**: 1.0.0  
**WordPress Tested**: 6.2  
**PHP Tested**: 8.2  
**Last Updated**: December 2024 