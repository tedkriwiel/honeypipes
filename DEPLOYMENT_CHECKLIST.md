# HoneyPipes Laravel Cloud Deployment Checklist

## Pre-Deployment Setup

1. **Environment Variables**
   - [ ] Copy `.env.example` to `.env` in Laravel Cloud
   - [ ] Generate new APP_KEY: `php artisan key:generate`
   - [ ] Set `SURVEYMONKEY_ACCESS_TOKEN` from your SurveyMonkey app
   - [ ] Set `SURVEYMONKEY_WEBHOOK_SECRET` (optional but recommended)
   - [ ] Set `ATTIO_API_KEY` from your Attio account

2. **SurveyMonkey Setup**
   - [ ] Get Access Token from SurveyMonkey App Dashboard
   - [ ] Configure webhook URL: `https://your-app.laravelcloud.com/webhook/surveymonkey`
   - [ ] Set webhook to trigger on "response_completed" events
   - [ ] Note the webhook secret if using signature verification

3. **Attio Setup**
   - [ ] Get API Key from Attio Settings
   - [ ] Ensure "people" object has required fields configured
   - [ ] Map survey fields to Attio fields in `WebhookController::transformForAttio()`

## Deployment Steps

1. **Push to Laravel Cloud**
   - [ ] Ensure all code is committed
   - [ ] Push to your Laravel Cloud repository

2. **Database Setup**
   - [ ] Run migrations: `php artisan migrate`
   - [ ] SQLite database will be created automatically

3. **Verify Deployment**
   - [ ] Check health endpoint: `https://your-app.laravelcloud.com/`
   - [ ] Check detailed health: `https://your-app.laravelcloud.com/health`
   - [ ] Review logs for any errors

4. **Test Webhook**
   - [ ] Submit a test survey response
   - [ ] Check Laravel logs for webhook receipt
   - [ ] Verify data appears in Attio

## Post-Deployment

1. **Monitoring**
   - [ ] Set up log monitoring/alerts
   - [ ] Monitor webhook failures
   - [ ] Track successful Attio submissions

2. **Security**
   - [ ] Ensure APP_DEBUG is false
   - [ ] Verify webhook signature validation is enabled
   - [ ] Check all API keys are properly secured

## Troubleshooting

- **Webhook not receiving data**: Check SurveyMonkey webhook configuration
- **401 errors**: Verify API tokens are correct
- **500 errors**: Check Laravel logs for detailed error messages
- **Data not in Attio**: Verify field mapping in `transformForAttio()` method

## Field Mapping Notes

The webhook controller currently maps fields based on question text containing:
- "email" → email_addresses
- "first name" → name.first_name
- "last name" → name.last_name
- Other fields → custom fields (needs configuration)

Update the `transformForAttio()` method to match your specific survey structure.