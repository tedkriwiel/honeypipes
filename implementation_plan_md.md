# Honeypipes - Simple Webhook to Attio Implementation Plan

## Overview
A dead-simple Laravel webhook receiver that captures email addresses from survey responses and sends them to Attio CRM. Built for one user, ~400 responses over a couple weeks.

## What This Does
1. Receives webhook POST requests from your survey tool
2. Verifies the webhook is legitimate (optional security check)
3. Extracts the email address from the survey response
4. Sends the email to Attio CRM
5. Returns success/failure response

## Files You'll Edit/Create

### 1. Environment Configuration (.env)
Add these lines to your `.env` file:
```
ATTIO_API_KEY=your_attio_api_key_here
WEBHOOK_SECRET=your_webhook_secret_here
```

### 2. Route Definition (routes/web.php)
Add this single route:
```php
Route::post('/webhook/survey', [WebhookController::class, 'handleSurvey']);
```

### 3. The Controller (app/Http/Controllers/WebhookController.php)
This ONE file handles everything:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleSurvey(Request $request)
    {
        // 1. Optional: Verify webhook signature
        if (config('services.webhook.secret')) {
            $signature = $request->header('X-Webhook-Signature');
            $expectedSignature = hash_hmac('sha256', $request->getContent(), config('services.webhook.secret'));
            
            if ($signature !== $expectedSignature) {
                Log::warning('Invalid webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        // 2. Extract email from survey response
        $data = $request->all();
        
        // Adjust this based on your survey tool's payload structure
        // Common patterns:
        $email = $data['email'] 
            ?? $data['respondent']['email'] 
            ?? $data['answers']['email'] 
            ?? null;

        if (!$email) {
            Log::error('No email found in webhook payload', ['data' => $data]);
            return response()->json(['error' => 'No email found'], 400);
        }

        // 3. Send to Attio
        try {
            $response = Http::withToken(config('services.attio.api_key'))
                ->post('https://api.attio.com/v2/objects/people/records', [
                    'data' => [
                        'values' => [
                            'email_addresses' => [
                                ['email_address' => $email]
                            ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                Log::info('Successfully sent email to Attio', ['email' => $email]);
                return response()->json(['success' => true]);
            }

            Log::error('Attio API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return response()->json(['error' => 'Failed to send to Attio'], 500);

        } catch (\Exception $e) {
            Log::error('Exception sending to Attio', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Server error'], 500);
        }
    }
}
```

### 4. Configuration (config/services.php)
Add this to the return array in `config/services.php`:
```php
'attio' => [
    'api_key' => env('ATTIO_API_KEY'),
],
'webhook' => [
    'secret' => env('WEBHOOK_SECRET'),
],
```

### 5. Disable CSRF for Webhook (app/Http/Middleware/VerifyCsrfToken.php)
Add your webhook URL to the except array:
```php
protected $except = [
    '/webhook/survey'
];
```

## Deployment Steps (5 minutes)

1. **Clone/Upload your Laravel project** to your server

2. **Install dependencies**:
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

3. **Set up environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Edit .env** with your actual values:
   - `ATTIO_API_KEY` - Get from Attio settings
   - `WEBHOOK_SECRET` - Generate a random string or use what your survey tool provides
   - Database settings (use SQLite for simplicity)

5. **Set permissions**:
   ```bash
   chmod -R 755 storage bootstrap/cache
   ```

6. **Configure your web server** to point to the `public` directory

7. **Test the webhook**:
   ```bash
   curl -X POST https://yourdomain.com/webhook/survey \
     -H "Content-Type: application/json" \
     -d '{"email": "test@example.com"}'
   ```

## Testing Locally

Use Laravel's built-in server:
```bash
php artisan serve
```

Then use ngrok or similar to expose your local server:
```bash
ngrok http 8000
```

## Common Survey Tool Payload Formats

**Typeform:**
```json
{
  "form_response": {
    "answers": [
      {
        "field": { "id": "email_field_id" },
        "email": "user@example.com"
      }
    ]
  }
}
```

**Google Forms (via Zapier/Make):**
```json
{
  "email": "user@example.com",
  "timestamp": "2024-01-20T10:30:00Z"
}
```

**SurveyMonkey:**
```json
{
  "pages": [
    {
      "questions": [
        {
          "answers": [
            { "text": "user@example.com" }
          ]
        }
      ]
    }
  ]
}
```

## Monitoring

Check Laravel logs for any issues:
```bash
tail -f storage/logs/laravel.log
```

## That's It!

No queues, no services, no complexity. Just one controller that:
- Receives webhook
- Validates it (optional)
- Extracts email
- Sends to Attio
- Logs everything

For 400 responses over a couple weeks, this will work perfectly.