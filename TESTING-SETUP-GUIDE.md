# AVLP Teams Plugin - Testing Setup Guide

This guide provides complete instructions for setting up and running the comprehensive testing framework for the AVLP Teams plugin.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Installation](#installation)
4. [Unit Testing](#unit-testing)
5. [End-to-End Testing](#end-to-end-testing)
6. [CI/CD Pipeline](#cicd-pipeline)
7. [Production Monitoring](#production-monitoring)
8. [Troubleshooting](#troubleshooting)

## Overview

The AVLP Teams plugin implements a comprehensive testing framework that includes:

- **Unit Tests**: PHPUnit tests for all core functionality
- **End-to-End Tests**: Playwright tests for user workflows
- **Security Scanning**: Automated security vulnerability detection
- **Performance Testing**: Lighthouse performance benchmarking
- **Accessibility Testing**: WCAG 2.1 AA compliance verification
- **CI/CD Pipeline**: GitHub Actions for automated testing and deployment
- **Production Monitoring**: Real-time health checks and alerting

## Prerequisites

### System Requirements

- PHP 7.4 or higher
- Node.js 18 or higher
- MySQL 5.7 or higher
- WordPress 5.9 or higher
- Composer
- Git

### Required WordPress Plugins

The testing framework requires the following plugins to be available:

- **AVLP Organization Management**: For team and organization functionality
- **AVLP General**: For shared functions and utilities

### Environment Setup

1. **Development Environment Variables**

Create a `.env` file in the plugin root:

```bash
# WordPress Test Database
WP_TESTS_DIR=/tmp/wordpress-tests-lib
WP_CORE_DIR=/tmp/wordpress/
DB_NAME=wordpress_test
DB_USER=wp_user
DB_PASSWORD=wp_pass
DB_HOST=localhost

# Test Configuration
WP_TESTS_DOMAIN=example.org
WP_TESTS_EMAIL=admin@example.org
WP_TESTS_TITLE=Test Blog
```

## Installation

### 1. Clone and Setup

```bash
# Clone the repository
git clone https://github.com/your-org/avlp-teams.git
cd avlp-teams

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 2. Install WordPress Test Suite

```bash
# Install WordPress test environment
bash bin/install-wp-tests.sh wordpress_test wp_user wp_pass localhost latest

# Or use composer script
composer run install-wp-tests
```

### 3. Setup Database

```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE wordpress_test;"
mysql -u root -p -e "CREATE USER 'wp_user'@'localhost' IDENTIFIED BY 'wp_pass';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON wordpress_test.* TO 'wp_user'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"
```

### 4. Install Playwright Browsers

```bash
# Install Playwright browsers
npx playwright install --with-deps
```

## Unit Testing

### Running Unit Tests

```bash
# Run all unit tests
composer test

# Run specific test file
vendor/bin/phpunit tests/test-core-functionality.php

# Run with coverage report
composer test-coverage
```

### Test Structure

```
tests/
├── bootstrap.php              # Test bootstrap
├── test-helpers.php          # Test helper functions
├── test-core-functionality.php # Core function tests
├── test-shortcodes.php       # Shortcode tests
└── coverage/                 # Coverage reports
```

### Writing Unit Tests

Example test structure:

```php
<?php
class TestNewFeature extends WP_UnitTestCase {
    
    private $test_data = array();
    
    public function setUp(): void {
        parent::setUp();
        $this->test_data = vlp_teams_create_test_scenario();
    }
    
    public function tearDown(): void {
        vlp_teams_cleanup_test_data($this->test_data);
        parent::tearDown();
    }
    
    public function test_new_functionality() {
        // Test implementation
        $result = vlp_teams_new_function($this->test_data['user_id']);
        $this->assertTrue($result);
    }
}
```

### Test Coverage Requirements

- **Minimum Coverage**: 80% line coverage
- **Critical Functions**: 100% coverage for security-related functions
- **Edge Cases**: All error conditions must be tested
- **Integration**: Test plugin interactions with WordPress core

## End-to-End Testing

### Running E2E Tests

```bash
# Run all E2E tests
npx playwright test

# Run specific test file
npx playwright test tests/e2e/team-display.spec.js

# Run with UI mode
npx playwright test --ui

# Run in headed mode
npx playwright test --headed
```

### Test Structure

```
tests/e2e/
├── team-display.spec.js      # Team display functionality
├── subscription-checks.spec.js # Subscription validation
├── personality-display.spec.js # Personality data display
└── fixtures/                 # Test data fixtures
```

### Writing E2E Tests

Example test structure:

```javascript
import { test, expect } from '@playwright/test';

test.describe('Team Display', () => {
    test.beforeEach(async ({ page }) => {
        // Setup test data
        await page.goto('/wp-admin/');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'password');
        await page.click('#wp-submit');
    });

    test('should display team members correctly', async ({ page }) => {
        await page.goto('/test-team-page/');
        
        // Verify team container exists
        await expect(page.locator('.vlp-teams-container')).toBeVisible();
        
        // Verify team members are displayed
        await expect(page.locator('.vlp-teams-member-card')).toHaveCount(3);
        
        // Verify personality data is shown
        await expect(page.locator('.vlp-teams-personality-summary')).toBeVisible();
    });
});
```

## CI/CD Pipeline

### GitHub Actions Workflow

The CI/CD pipeline automatically runs on:

- **Push to main/develop branches**
- **Pull requests**
- **Daily scheduled runs**

### Pipeline Stages

1. **Unit Tests**: Run PHPUnit tests across multiple PHP/WordPress versions
2. **Security Scan**: Check for vulnerabilities and coding standards
3. **E2E Tests**: Run Playwright tests in multiple browsers
4. **Performance Tests**: Run Lighthouse performance audits
5. **Accessibility Tests**: Verify WCAG 2.1 AA compliance
6. **Deployment**: Deploy to staging environment (main branch only)

### Local Pipeline Testing

```bash
# Run the same checks locally
composer cs          # Code standards
composer psalm       # Static analysis
npm run test:e2e     # E2E tests
npm run test:perf    # Performance tests
npm run test:a11y    # Accessibility tests
```

## Production Monitoring

### Health Check Setup

1. **Create monitoring script**:

```bash
cp monitoring/teams-functionality-monitor.php /path/to/wordpress/wp-content/plugins/avlp-teams/monitoring/
```

2. **Setup cron job**:

```bash
# Add to crontab
*/5 * * * * /usr/bin/php /path/to/wordpress/wp-content/plugins/avlp-teams/monitoring/teams-functionality-monitor.php
```

### Monitoring Features

- **Functionality Health Checks**: Verify core plugin functions
- **Performance Monitoring**: Track response times and resource usage
- **Error Detection**: Monitor for PHP errors and exceptions
- **Usage Analytics**: Track shortcode usage and user engagement
- **Alerting**: Email notifications for critical issues

### Monitoring Dashboard

Access the monitoring dashboard at:
`/wp-admin/admin.php?page=avlp-teams-monitoring`

## Troubleshooting

### Common Issues

#### Unit Tests Failing

**Issue**: Tests fail with database connection errors

**Solution**:
```bash
# Verify database setup
mysql -u wp_user -p wordpress_test -e "SELECT 1;"

# Reinstall test suite
rm -rf /tmp/wordpress-tests-lib
composer run install-wp-tests
```

#### E2E Tests Timing Out

**Issue**: Playwright tests timeout waiting for elements

**Solution**:
```bash
# Increase timeout in playwright.config.js
timeout: 30000,
actionTimeout: 10000,
navigationTimeout: 30000,
```

#### Coverage Reports Not Generated

**Issue**: Code coverage reports are empty or missing

**Solution**:
```bash
# Install Xdebug
sudo apt-get install php-xdebug

# Verify Xdebug is enabled
php -m | grep xdebug
```

### Debug Mode

Enable debug mode for detailed test output:

```bash
# Unit tests
WP_DEBUG=1 vendor/bin/phpunit --debug

# E2E tests
DEBUG=1 npx playwright test --debug
```

### Performance Optimization

For faster test execution:

```bash
# Run tests in parallel
vendor/bin/phpunit --parallel 4

# Use specific test groups
vendor/bin/phpunit --group core-functionality
```

## Best Practices

### Test Organization

1. **One test per feature**: Keep tests focused and specific
2. **Descriptive names**: Use clear, descriptive test method names
3. **Setup/teardown**: Always clean up test data
4. **Isolation**: Tests should not depend on each other
5. **Assertions**: Use specific assertions with clear messages

### Data Management

1. **Test fixtures**: Use consistent test data across tests
2. **Factory patterns**: Create reusable test data generators
3. **Cleanup**: Always clean up test data in tearDown()
4. **Isolation**: Use transactions for database tests

### Continuous Integration

1. **Fast feedback**: Keep test suite execution under 10 minutes
2. **Comprehensive coverage**: Test all user-facing functionality
3. **Environment parity**: Test in production-like environments
4. **Failure handling**: Provide clear error messages and debugging info

## Support

For testing framework support:

- **Documentation**: Check this guide and inline code comments
- **Issues**: Report bugs via GitHub Issues
- **Discussions**: Use GitHub Discussions for questions
- **Team Chat**: Contact the development team directly

---

**Last Updated**: December 2024  
**Framework Version**: 1.0  
**Plugin Version**: 1.0.0 