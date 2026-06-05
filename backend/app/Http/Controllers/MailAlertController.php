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

    public function store(Request $request): JsonResponse
    {
        // Validar secret
        $secret = $request->input('secret');
        if ($secret !== config('services.alert.secret')) {
            return response()->json(
                ['error' => 'Invalid secret'],
                401
            );
        }

        // Validar datos
        $validated = $request->validate([
            'rule' => 'required|string',
            'priority' => 'required|in:high,emergency',
            'from' => 'required|string',
            'subject' => 'required|string',
            'snippet' => 'required|string',
            'timestamp' => 'required|date_format:Y-m-d\TH:i:s\Z',
        ]);

        // Crear alerta
        $alert = MailAlert::create([
            'rule' => $validated['rule'],
            'priority' => $validated['priority'],
            'from_address' => $validated['from'],
            'subject' => $validated['subject'],
            'snippet' => $validated['snippet'],
            'timestamp' => $validated['timestamp'],
        ]);

        // Enviar a FCM
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

        // Actualizar sent_at si FCM fue exitoso
        if ($fcmSent) {
            $alert->update(['sent_at' => now()]);
        } else {
            Log::warning("FCM notification not sent for alert {$alert->id}");
        }

        return response()->json([
            'success' => true,
            'alert_id' => $alert->id,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        // Validar Bearer token
        $token = str_replace('Bearer ', '', $request->header('Authorization', ''));
        if ($token !== config('services.alert.secret')) {
            return response()->json(
                ['error' => 'Unauthorized'],
                401
            );
        }

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
                    'timestamp' => $alert->timestamp->toIso8601String(),
                    'sent_at' => $alert->sent_at?->toIso8601String(),
                    'created_at' => $alert->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }
}
