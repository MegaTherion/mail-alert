<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
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

    /**
     * Send notification to FCM topic using HTTP v1 API
     */
    public function sendToTopic(
        string $topic,
        string $title,
        string $body,
        array $data,
        string $priority = 'high'
    ): bool {
        try {
            if (!$this->validateConfig()) {
                Log::error('FCM configuration incomplete');
                return false;
            }

            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                Log::error('Failed to obtain FCM access token');
                return false;
            }

            $message = [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
            ];

            // Configure Android-specific settings based on priority
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
                    'timeout' => 10,
                ]
            );

            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                Log::info("FCM message sent successfully to topic: {$topic}");
                return true;
            } else {
                Log::warning("FCM returned status {$statusCode}: " . $response->getBody());
                return false;
            }

        } catch (GuzzleException $e) {
            Log::error('FCM Guzzle Error: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('FCM Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get OAuth2 access token using service account JWT
     */
    private function getAccessToken(): ?string
    {
        try {
            $serviceAccount = $this->loadServiceAccount();
            if (!$serviceAccount) {
                return null;
            }

            $now = time();
            $payload = [
                'iss' => $serviceAccount['client_email'],
                'sub' => $serviceAccount['client_email'],
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            ];

            // Create JWT manually using openssl_sign with RS256
            $jwt = $this->createJWT($payload, $serviceAccount['private_key']);

            $response = $this->client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ],
                'timeout' => 10,
            ]);

            $body = json_decode($response->getBody(), true);

            if (isset($body['access_token'])) {
                return $body['access_token'];
            }

            Log::error('No access token in OAuth response');
            return null;

        } catch (\Exception $e) {
            Log::error('Failed to get access token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create JWT signed with RS256 algorithm
     */
    private function createJWT(array $payload, string $privateKey): string
    {
        // Encode header and payload as base64url
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $headerEncoded = $this->base64url_encode($header);
        $payloadEncoded = $this->base64url_encode(json_encode($payload));

        // Create signature
        $signatureInput = "{$headerEncoded}.{$payloadEncoded}";
        $signature = '';

        if (!openssl_sign($signatureInput, $signature, $privateKey, 'sha256')) {
            throw new \Exception('Failed to sign JWT');
        }

        $signatureEncoded = $this->base64url_encode($signature);

        return "{$signatureInput}.{$signatureEncoded}";
    }

    /**
     * Base64url encode (RFC 4648 Section 5)
     */
    private function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Load service account JSON
     */
    private function loadServiceAccount(): ?array
    {
        try {
            if (!file_exists($this->serviceAccountPath)) {
                Log::error('Service account file not found: ' . $this->serviceAccountPath);
                return null;
            }

            $content = file_get_contents($this->serviceAccountPath);
            $serviceAccount = json_decode($content, true);

            if (!$serviceAccount) {
                Log::error('Failed to parse service account JSON');
                return null;
            }

            return $serviceAccount;

        } catch (\Exception $e) {
            Log::error('Error loading service account: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate FCM configuration
     */
    private function validateConfig(): bool
    {
        if (empty($this->projectId)) {
            Log::error('FCM_PROJECT_ID not configured');
            return false;
        }

        if (empty($this->serviceAccountPath)) {
            Log::error('FCM_SERVICE_ACCOUNT_JSON not configured');
            return false;
        }

        return true;
    }
}
