<?php

namespace NettSite\Messenger\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FCMService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function send(string $deviceToken, string $title, string $body, ?string $url = null): void
    {
        $projectId = config('messenger.fcm.project_id');
        $credentialsPath = config('messenger.fcm.credentials');

        if (! $projectId || ! file_exists($credentialsPath)) {
            Log::debug('FCMService: missing project_id or credentials file, skipping send.');

            return;
        }

        $accessToken = $this->fetchAccessToken($credentialsPath);

        if (! $accessToken) {
            return;
        }

        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => 'messenger_messages',
                        'sound' => 'default',
                        'priority' => 'HIGH',
                    ],
                ],
            ],
        ];

        if ($url) {
            $payload['message']['data'] = ['url' => $url];
        }

        $response = Http::withToken($accessToken)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

        if (! $response->successful()) {
            Log::debug('FCMService: send failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    private function fetchAccessToken(string $credentialsPath): ?string
    {
        try {
            $keyData = json_decode(file_get_contents($credentialsPath), associative: true);

            $credentials = new ServiceAccountCredentials(self::SCOPE, $keyData);

            $token = $credentials->fetchAuthToken();

            return $token['access_token'] ?? null;
        } catch (\Throwable $e) {
            Log::debug('FCMService: failed to fetch access token.', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
