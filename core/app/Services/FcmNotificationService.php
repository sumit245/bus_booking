<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Illuminate\Support\Facades\Log;

class FcmNotificationService
{
    protected $messaging;
    protected $batchSize;

    public function __construct()
    {
        $this->batchSize = config('firebase.batch_size', 500);
        
        try {
            $credentialsPath = config('firebase.credentials_path');
            
            if (!file_exists($credentialsPath)) {
                Log::warning('Firebase credentials file not found', [
                    'path' => $credentialsPath
                ]);
                return;
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send notification to a single FCM token
     *
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        if (!$this->messaging) {
            Log::error('Firebase messaging not initialized');
            return false;
        }

        try {
            $notification = Notification::create($title, $body);
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);
            
            Log::info('FCM notification sent to token', [
                'token' => substr($token, 0, 20) . '...',
                'title' => $title
            ]);

            return true;
        } catch (MessagingException $e) {
            $this->handleMessagingException($e, $token);
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send FCM notification', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);
            return false;
        }
    }

    /**
     * Send notification to multiple FCM tokens (batch)
     *
     * @param array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array ['sent' => int, 'failed' => int, 'invalid_tokens' => array]
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        if (!$this->messaging) {
            Log::error('Firebase messaging not initialized');
            return ['sent' => 0, 'failed' => count($tokens), 'invalid_tokens' => []];
        }

        if (empty($tokens)) {
            return ['sent' => 0, 'failed' => 0, 'invalid_tokens' => []];
        }

        // Remove duplicates
        $tokens = array_unique($tokens);
        $totalTokens = count($tokens);
        $sent = 0;
        $failed = 0;
        $invalidTokens = [];

        // Split into batches of 500 (FCM limit)
        $batches = array_chunk($tokens, $this->batchSize);

        foreach ($batches as $batch) {
            try {
                $notification = Notification::create($title, $body);
                $messages = [];

                foreach ($batch as $token) {
                    $messages[] = CloudMessage::withTarget('token', $token)
                        ->withNotification($notification)
                        ->withData($data);
                }

                $results = $this->messaging->sendAll($messages);

                // Process results
                foreach ($results as $index => $result) {
                    if ($result->isSuccess()) {
                        $sent++;
                    } else {
                        $failed++;
                        $token = $batch[$index];
                        
                        // Check if token is invalid
                        $error = $result->error();
                        if ($error && $this->isInvalidTokenError($error)) {
                            $invalidTokens[] = $token;
                        }
                    }
                }

            } catch (\Exception $e) {
                Log::error('Failed to send batch FCM notifications', [
                    'error' => $e->getMessage(),
                    'batch_size' => count($batch)
                ]);
                $failed += count($batch);
            }
        }

        // Remove invalid tokens from database
        if (!empty($invalidTokens)) {
            $this->handleInvalidTokens($invalidTokens);
        }

        Log::info('FCM batch notification completed', [
            'total' => $totalTokens,
            'sent' => $sent,
            'failed' => $failed,
            'invalid_tokens' => count($invalidTokens)
        ]);

        return [
            'sent' => $sent,
            'failed' => $failed,
            'invalid_tokens' => $invalidTokens
        ];
    }

    /**
     * Send notification to a user by user_id
     *
     * @param int $userId
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): bool
    {
        $tokens = FcmToken::where('user_id', $userId)
            ->pluck('fcm_token')
            ->toArray();

        if (empty($tokens)) {
            Log::info('No FCM tokens found for user', ['user_id' => $userId]);
            return false;
        }

        $results = $this->sendToTokens($tokens, $title, $body, $data);
        return $results['sent'] > 0;
    }

    /**
     * Send notification to multiple users by user_ids
     *
     * @param array $userIds
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): array
    {
        $tokens = FcmToken::whereIn('user_id', $userIds)
            ->pluck('fcm_token')
            ->toArray();

        if (empty($tokens)) {
            Log::info('No FCM tokens found for users', ['user_ids' => $userIds]);
            return ['sent' => 0, 'failed' => 0, 'invalid_tokens' => []];
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send notification to all users (broadcast)
     *
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendToAll(string $title, string $body, array $data = []): array
    {
        // Use chunking to avoid loading all tokens into memory
        $totalSent = 0;
        $totalFailed = 0;
        $allInvalidTokens = [];

        FcmToken::chunk(1000, function ($tokens) use ($title, $body, $data, &$totalSent, &$totalFailed, &$allInvalidTokens) {
            $tokenArray = $tokens->pluck('fcm_token')->toArray();
            $results = $this->sendToTokens($tokenArray, $title, $body, $data);
            
            $totalSent += $results['sent'];
            $totalFailed += $results['failed'];
            $allInvalidTokens = array_merge($allInvalidTokens, $results['invalid_tokens']);
        });

        return [
            'sent' => $totalSent,
            'failed' => $totalFailed,
            'invalid_tokens' => $allInvalidTokens
        ];
    }

    /**
     * Build notification payload according to FCM spec
     *
     * @param string $title
     * @param string $body
     * @param array $data
     * @param array $options
     * @return array
     */
    public function buildNotificationPayload(string $title, string $body, array $data = [], array $options = []): array
    {
        $androidChannelId = config('firebase.android_channel_id', 'ghumantoo_default_channel');
        $priority = $options['priority'] ?? 'high';

        $payload = [
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'android_channel_id' => $androidChannelId,
            ],
            'data' => array_merge([
                'type' => $data['type'] ?? 'general',
                'notification_type' => $data['type'] ?? 'general',
            ], $data),
            'android' => [
                'priority' => $priority,
                'notification' => [
                    'channel_id' => $androidChannelId,
                    'sound' => 'default',
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => $options['badge'] ?? 1,
                    ],
                ],
            ],
        ];

        // Add image URL if provided
        if (isset($options['image_url'])) {
            $payload['notification']['image'] = $options['image_url'];
        }

        return $payload;
    }

    /**
     * Handle invalid FCM tokens - remove from database
     *
     * @param array $invalidTokens
     * @return void
     */
    public function handleInvalidTokens(array $invalidTokens): void
    {
        if (empty($invalidTokens)) {
            return;
        }

        $deleted = FcmToken::whereIn('fcm_token', $invalidTokens)->delete();

        Log::info('Removed invalid FCM tokens from database', [
            'count' => $deleted,
            'tokens' => count($invalidTokens)
        ]);
    }

    /**
     * Check if messaging exception indicates invalid token
     *
     * @param \Exception $error
     * @param string|null $token
     * @return bool
     */
    protected function isInvalidTokenError($error): bool
    {
        if (!$error) {
            return false;
        }

        $message = $error->getMessage();
        
        // Check for common invalid token error messages
        $invalidTokenMessages = [
            'not found',
            'invalid registration token',
            'registration token not registered',
            'invalid argument',
            'unregistered',
        ];

        foreach ($invalidTokenMessages as $keyword) {
            if (stripos($message, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle messaging exception
     *
     * @param MessagingException $e
     * @param string|null $token
     * @return void
     */
    protected function handleMessagingException(MessagingException $e, ?string $token = null): void
    {
        $tokenPreview = $token ? substr($token, 0, 20) . '...' : 'unknown';
        
        if ($this->isInvalidTokenError($e)) {
            Log::warning('Invalid FCM token detected', [
                'token' => $tokenPreview,
                'error' => $e->getMessage()
            ]);

            if ($token) {
                $this->handleInvalidTokens([$token]);
            }
        } else {
            Log::error('FCM messaging exception', [
                'token' => $tokenPreview,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }
}

