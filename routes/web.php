<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\WebhookController;

// Health check endpoint
Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'HoneyPipes Webhook Processor',
        'timestamp' => now()->toIso8601String()
    ]);
});

// Health check endpoint (alternative)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'checks' => [
            'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
            'environment' => app()->environment()
        ]
    ]);
});

// SurveyMonkey webhook endpoint
Route::post('/webhook/surveymonkey', [WebhookController::class, 'handleSurveyMonkey']);
