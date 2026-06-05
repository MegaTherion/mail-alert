<?php

namespace App\Http\Controllers;

use App\Models\MailAlert;
use App\Services\FCMService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MailAlertController extends Controller
{
    private FCMService $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Store a new mail alert
     * POST /api/mail-alert
     */
    public function store(Request $request): JsonResponse
    {
        // Validate secret
        $secret = $request->input('secret');
        if ($secret !== config('services.alert.secret')) {
            Log::warning('Invalid secret attempted for mail-alert endpoint');
            return response()->json(
                ['error' => 'Invalid secret'],
                401
            );
        }

        // Validate input
        try {
            $validated = $request->validate([
                'rule' => 'required|string|max:255',
                'priority' => 'required|in:high,emergency',
                'from' => 'required|string|max:255',
                'subject' => 'required|string|max:255',
                'snippet' => 'required|string',
                'timestamp' => 'required|date_format:Y-m-d\TH:i:s\Z',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed for mail-alert', $e->errors());
            return response()->json(
                ['error' => 'Validation failed', 'details' => $e->errors()],
                422
            );
        }

        try {
            // Create alert in database
            $alert = MailAlert::create([
                'rule' => $validated['rule'],
                'priority' => $validated['priority'],
                'from_address' => $validated['from'],
                'subject' => $validated['subject'],
                'snippet' => $validated['snippet'],
                'timestamp' => $validated['timestamp'],
            ]);

            // Send FCM notification
            $fcmSent = $this->fcmService->sendToTopic(
                topic: 'mail-alerts',
                title: $validated['subject'],
                body: $validated['snippet'],
                data: [
                    'rule' => $validated['rule'],
                    'priority' => $validated['priority'],
                    'from' => $validated['from'],
                    'subject' => $validated['subject'],
                    'snippet' => $validated['snippet'],
                ],
                priority: $validated['priority']
            );

            // Update sent_at if FCM was successful
            if ($fcmSent) {
                $alert->update(['sent_at' => now()]);
                Log::info("Alert {$alert->id} sent via FCM");
            } else {
                Log::warning("FCM notification not sent for alert {$alert->id}");
            }

            return response()->json([
                'success' => true,
                'alert_id' => $alert->id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating alert: ' . $e->getMessage());
            return response()->json(
                ['error' => 'Failed to create alert'],
                500
            );
        }
    }

    /**
     * List recent alerts
     * GET /api/alerts
     */
    public function index(Request $request): JsonResponse
    {
        // Validate Bearer token
        $authHeader = $request->header('Authorization', '');
        $token = str_replace('Bearer ', '', $authHeader);

        if (empty($token) || $token !== config('services.alert.secret')) {
            Log::warning('Unauthorized access attempt to /api/alerts');
            return response()->json(
                ['error' => 'Unauthorized'],
                401
            );
        }

        try {
            $alerts = MailAlert::orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(function ($alert) {
                    return [
                        'id' => $alert->id,
                        'rule' => $alert->rule,
                        'priority' => $alert->priority,
                        'from_address' => $alert->from_address,
                        'subject' => $alert->subject,
                        'snippet' => $alert->snippet,
                        'timestamp' => $alert->timestamp?->toIso8601String(),
                        'sent_at' => $alert->sent_at?->toIso8601String(),
                        'created_at' => $alert->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $alerts,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching alerts: ' . $e->getMessage());
            return response()->json(
                ['error' => 'Failed to fetch alerts'],
                500
            );
        }
    }
}
