<?php

namespace NettSite\Messenger\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FCMService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function send(string $deviceToken, string $title, string $body, ?string $url = null): bool
    {
        $credentialsPath = $this->resolveCredentialsPath(config('messenger.fcm.credentials'));

        if (! $credentialsPath) {
            Log::error('Messenger FCMService: credentials file not found.', [
                'tried' => config('messenger.fcm.credentials'),
                'fix' => 'Place your Firebase service account JSON at storage/app/private/fcm-credentials.json, or set MESSENGER_FCM_CREDENTIALS in your .env to an absolute or base-path-relative path.',
            ]);

            return false;
        }

        $keyData = $this->loadCredentials($credentialsPath);

        if (! $keyData) {
            Log::error('Messenger FCMService: credentials file is not valid JSON.', [
                'path' => $credentialsPath,
                'fix' => 'Regenerate the service account key from Firebase Console → Project Settings → Service accounts → Generate new private key.',
            ]);

            return false;
        }

        $projectId = config('messenger.fcm.project_id') ?: ($keyData['project_id'] ?? null);

        if (! $projectId) {
            Log::error('Messenger FCMService: could not determine Firebase project_id.', [
                'fix' => 'Set MESSENGER_FCM_PROJECT_ID in your .env, or ensure your credentials JSON contains a project_id field.',
            ]);

            return false;
        }

        $accessToken = $this->fetchAccessToken($keyData);

        if (! $accessToken) {
            return false;
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
            Log::error('Messenger FCMService: send failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    private function resolveCredentialsPath(string $configured): ?string
    {
        if (file_exists($configured)) {
            return $configured;
        }

        $relative = base_path($configured);

        if (file_exists($relative)) {
            return $relative;
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    private function loadCredentials(string $path): ?array
    {
        $decoded = json_decode(file_get_contents($path), associative: true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @param array<string, mixed> $keyData */
    private function fetchAccessToken(array $keyData): ?string
    {
        try {
            $credentials = new ServiceAccountCredentials(self::SCOPE, $keyData);

            $token = $credentials->fetchAuthToken();

            return $token['access_token'] ?? null;
        } catch (\Throwable $e) {
            Log::error('Messenger FCMService: failed to fetch access token.', [
                'error' => $e->getMessage(),
                'fix' => 'Check that your Firebase service account JSON is valid and has the Firebase Messaging permission.',
            ]);

            return null;
        }
    }
}
