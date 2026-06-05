<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Log;

class FCMService
{
    private Client $client;
    private string $projectId;
    private string $serviceAccountPath;

    public function __construct()
    {
        $this->projectId = config('services.fcm.project_id');
        $this->serviceAccountPath = config('services.fcm.service_account_json');
        $this->client = new Client();
    }

    public function sendToTopic(
        string $topic,
        string $title,
        string $body,
        array $data,
        string $priority = 'high'
    ): bool {
        try {
            $accessToken = $this->getAccessToken();

            $message = [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
            ];

            // Configuración específica por prioridad
            if ($priority === 'emergency') {
                $message['android'] = [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'emergency_channel',
                    ],
                ];
            } else {
                $message['android'] = [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'alert_channel',
                    ],
                ];
            }

            $response = $this->client->post(
                "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                [
                    'headers' => [
                        'Authorization' => "Bearer {$accessToken}",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => ['message' => $message],
                ]
            );

            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            Log::error('FCM Error: ' . $e->getMessage());
            return false;
        }
    }

    private function getAccessToken(): string
    {
        $serviceAccount = json_decode(
            file_get_contents($this->serviceAccountPath),
            true
        );

        $now = time();
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'sub' => $serviceAccount['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $jwt = JWT::encode(
            $payload,
            $serviceAccount['private_key'],
            'RS256'
        );

        $response = $this->client->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ],
        ]);

        $body = json_decode($response->getBody(), true);
        return $body['access_token'];
    }
}
