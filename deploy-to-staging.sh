#!/bin/bash

# AVLP Teams Plugin - Deploy to Staging
# Usage: ./deploy-to-staging.sh

echo "🚀 Deploying AVLP Teams plugin to staging..."

# Staging server details
STAGING_HOST="ssh.virtualleadershipprograms.com"
STAGING_PORT="18765"
STAGING_USER="u4-gb7cem5fkumj"
STAGING_PATH="./www/staging9.virtualleadershipprograms.com/public_html/wp-content/plugins/avlp-teams"

# Check if SSH connection works
echo "📡 Testing SSH connection..."
if ! ssh -p $STAGING_PORT -o ConnectTimeout=10 $STAGING_USER@$STAGING_HOST "echo 'SSH connection successful'"; then
    echo "❌ SSH connection failed. Please check your SSH key and connection."
    exit 1
fi

echo "✅ SSH connection successful"

# Deploy core functions
echo "📤 Uploading teams-core-functions.php..."
scp -P $STAGING_PORT includes/teams-core-functions.php $STAGING_USER@$STAGING_HOST:$STAGING_PATH/includes/

# Deploy shortcodes
echo "📤 Uploading teams-shortcodes.php..."
scp -P $STAGING_PORT includes/teams-shortcodes.php $STAGING_USER@$STAGING_HOST:$STAGING_PATH/includes/

# Verify deployment
echo "🔍 Verifying deployment..."
ssh -p $STAGING_PORT $STAGING_USER@$STAGING_HOST "ls -la $STAGING_PATH/includes/teams-*.php"

echo "✅ Deployment complete!"
echo ""
echo "🧪 To test the fix:"
echo "1. Visit your test page with [vlp_teams] shortcode"
echo "2. Add [vlp_teams_debug] shortcode to see detailed debug info"
echo "3. Check that users with 'Paid' plan can now access team features"
echo ""
echo "🔧 Debug shortcode usage:"
echo "   [vlp_teams_debug] - Debug current user"
echo "   [vlp_teams_debug user_id=\"123\"] - Debug specific user" 