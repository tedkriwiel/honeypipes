# Laravel Cloud Deployment Guide for Honeypipes

## Overview
Deploy your simple SurveyMonkey → Attio webhook processor to Laravel Cloud in 5 minutes.

## Prerequisites

1. **Laravel Cloud Account**
   - Sign up at [https://cloud.laravel.com](https://cloud.laravel.com)
   - Connect your GitHub account

2. **GitHub Repository**
   - Your code is at: `https://github.com/tedkriwiel/honeypipes`
   - All changes committed and pushed

3. **API Keys Ready**
   - SurveyMonkey Access Token
   - Attio API Key

## Step-by-Step Deployment

### Step 1: Create Application in Laravel Cloud

1. Log into [Laravel Cloud](https://cloud.laravel.com)
2. Click "Create Application"
3. Select:
   - Type: "Web Application"
   - Framework: "Laravel"

### Step 2: Connect Repository

1. Click "Connect GitHub Repository"
2. Select `tedkriwiel/honeypipes`
3. Choose branch: `main`

### Step 3: Configure Application

#### Basic Settings:
- **Application Name**: `honeypipes`
- **Region**: Choose closest to you
- **Environment**: `production`

#### Build Configuration:
```yaml
build:
  - composer install --no-dev --optimize-autoloader
  - php artisan migrate --force
```

That's it! No npm, no assets to build.

### Step 4: Set Environment Variables

Click "Environment" and add:

```env
# Application Basics
APP_NAME="Honeypipes"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://honeypipes.laravel.cloud

# Database (SQLite for simplicity)
DB_CONNECTION=sqlite
DB_DATABASE=/app/database/database.sqlite

# Your API Keys
SURVEYMONKEY_ACCESS_TOKEN=your_surveymonkey_token_here
SURVEYMONKEY_WEBHOOK_SECRET=optional_webhook_secret
ATTIO_API_KEY=your_attio_api_key_here

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info
```

### Step 5: Deploy!

1. Click "Deploy Application"
2. Watch the deployment logs (takes ~2-3 minutes)
3. Your webhook is now live!

## Your Webhook URLs

Once deployed, your endpoints are:

- **Webhook endpoint**: `https://honeypipes.laravel.cloud/webhook/surveymonkey`
- **Health check**: `https://honeypipes.laravel.cloud/health`
- **Test endpoint**: `https://honeypipes.laravel.cloud/`

## Configure SurveyMonkey Webhook

1. Go to SurveyMonkey API Apps
2. Create/edit your app
3. Add webhook subscription:
   - URL: `https://honeypipes.laravel.cloud/webhook/surveymonkey`
   - Event Type: `response_completed`
   - Object Type: `survey`

## Testing Your Deployment

### Quick Health Check:
```bash
curl https://honeypipes.laravel.cloud/health
```

### Test the Webhook:
```bash
curl -X POST https://honeypipes.laravel.cloud/webhook/surveymonkey \
  -H "Content-Type: application/json" \
  -d '{
    "event_type": "response_completed",
    "resources": {
      "survey_id": "test123",
      "response_id": "test456"
    }
  }'
```

## Monitoring

1. **View Logs**: 
   - Laravel Cloud Dashboard → Your App → Logs
   - All webhook activities are logged

2. **Check Database**:
   - Laravel Cloud provides database access
   - SQLite file at `/app/database/database.sqlite`

## Troubleshooting

### Webhook Not Receiving Data?
1. Check Laravel Cloud logs for incoming requests
2. Verify SurveyMonkey webhook is active
3. Ensure API tokens are correct in environment variables

### 500 Errors?
1. Check logs for specific error
2. Common issues:
   - Missing API tokens
   - Database not created (migrations failed)
   - Wrong permissions on storage

### Email Not Found?
- The webhook looks for email in survey responses
- Check logs to see the response structure
- May need to adjust email extraction logic in `WebhookController.php`

## Scaling (If Needed)

For 400 responses over 2 weeks, the default plan is fine. But if you need to scale:

1. Go to Laravel Cloud dashboard
2. Click "Scale"
3. Adjust resources as needed

## Quick Deployment Checklist

Before deploying:
- [ ] GitHub repo is up to date
- [ ] Have SurveyMonkey Access Token
- [ ] Have Attio API Key

During deployment:
- [ ] Watch deployment logs
- [ ] Check for any errors

After deployment:
- [ ] Test health endpoint
- [ ] Configure SurveyMonkey webhook
- [ ] Do a test survey submission
- [ ] Check Attio for new record

## Total Time: ~5 minutes

That's it! Your webhook processor is live and will automatically:
1. Receive SurveyMonkey webhooks
2. Extract email addresses
3. Create Attio person records
4. Handle ~400 responses with zero maintenance

No servers to manage, no complex configuration, just works! 🚀