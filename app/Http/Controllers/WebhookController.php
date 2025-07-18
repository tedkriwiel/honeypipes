<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleSurveyMonkey(Request $request)
    {
        // Log the incoming webhook
        Log::info('SurveyMonkey webhook received', [
            'payload' => $request->all()
        ]);

        // Simple verification - check if we have required data
        $eventType = $request->input('event_type');
        $surveyId = $request->input('object_id');
        $responseId = $request->input('resources.response_id');

        if (!$eventType || !$surveyId || !$responseId) {
            Log::error('Missing required webhook data');
            return response()->json(['error' => 'Missing required data'], 400);
        }

        // Only process response completed events
        if ($eventType !== 'response_completed') {
            Log::info("Ignoring event type: $eventType");
            return response()->json(['message' => 'Event ignored'], 200);
        }

        // Fetch the complete response from SurveyMonkey
        try {
            $surveyResponse = Http::withToken(config('services.surveymonkey.access_token'))
                ->get("https://api.surveymonkey.com/v3/surveys/{$surveyId}/responses/{$responseId}/details");

            if (!$surveyResponse->successful()) {
                Log::error('Failed to fetch SurveyMonkey response', [
                    'status' => $surveyResponse->status(),
                    'body' => $surveyResponse->body()
                ]);
                return response()->json(['error' => 'Failed to fetch response'], 500);
            }

            $responseData = $surveyResponse->json();
            
            // Extract email from the response
            $email = $this->extractEmailFromResponse($responseData);
            
            if (!$email) {
                Log::warning('No email found in survey response', [
                    'response_id' => $responseId
                ]);
                return response()->json(['error' => 'No email found'], 400);
            }
            
            // Create person in Attio with just the email
            $attioData = [
                'data' => [
                    'values' => [
                        'email_addresses' => [
                            [
                                'email_address' => $email
                            ]
                        ]
                    ]
                ]
            ];
            
            // Send to Attio
            $attioResponse = Http::withToken(config('services.attio.api_key'))
                ->post('https://api.attio.com/v2/objects/people/records', $attioData);

            if (!$attioResponse->successful()) {
                Log::error('Failed to create person in Attio', [
                    'email' => $email,
                    'status' => $attioResponse->status(),
                    'body' => $attioResponse->body()
                ]);
                return response()->json(['error' => 'Failed to create person in Attio'], 500);
            }

            Log::info('Successfully created person in Attio', [
                'email' => $email,
                'survey_id' => $surveyId,
                'response_id' => $responseId,
                'attio_response' => $attioResponse->json()
            ]);

            return response()->json(['message' => 'Success'], 200);

        } catch (\Exception $e) {
            Log::error('Exception processing webhook', [
                'message' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    private function extractEmailFromResponse($surveyResponse)
    {
        // Parse through the survey response pages and questions to find email
        foreach ($surveyResponse['pages'] ?? [] as $page) {
            foreach ($page['questions'] ?? [] as $question) {
                $questionText = strtolower($question['heading'] ?? '');
                $answers = $question['answers'] ?? [];
                
                // Look for email question by checking if question text contains 'email'
                if (str_contains($questionText, 'email') && !empty($answers)) {
                    // Get the first answer's text value
                    $email = $answers[0]['text'] ?? '';
                    
                    // Basic email validation
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        return $email;
                    }
                }
            }
        }
        
        // If no email found by question heading, try to find any valid email in answers
        foreach ($surveyResponse['pages'] ?? [] as $page) {
            foreach ($page['questions'] ?? [] as $question) {
                $answers = $question['answers'] ?? [];
                
                foreach ($answers as $answer) {
                    $text = $answer['text'] ?? '';
                    if (filter_var($text, FILTER_VALIDATE_EMAIL)) {
                        return $text;
                    }
                }
            }
        }
        
        return null;
    }
}