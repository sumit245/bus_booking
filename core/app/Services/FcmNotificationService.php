<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class FcmNotificationService
{
    protected $messaging;
    protected $batchSize;

    public function __construct()
    {
        $this->batchSize = config('firebase.batch_size', 500);

        try {
            $credentialsPath = config('firebase.credentials_path');
            $originalPath = $credentialsPath;

            // Normalize path - ensure it's absolute
            // If path doesn't exist and looks relative, try to resolve it
            if (!file_exists($credentialsPath)) {
                // Check if it's a relative path (doesn't start with / or drive letter)
                if (!preg_match('/^(\/|[A-Z]:\\\\|\\\\\\\\)/i', $credentialsPath)) {
                    // Try from storage/app first (most common location)
                    $storagePath = storage_path('app/' . basename($credentialsPath));
                    if (file_exists($storagePath)) {
                        $credentialsPath = $storagePath;
                    } else {
                        // Try from base path
                        $basePath = base_path($credentialsPath);
                        if (file_exists($basePath)) {
                            $credentialsPath = $basePath;
                        }
                    }
                }

                // If still not found, try the default location
                if (!file_exists($credentialsPath)) {
                    $defaultPath = storage_path('app/firebase-credentials.json');
                    if (file_exists($defaultPath)) {
                        $credentialsPath = $defaultPath;
                        Log::info('Using default Firebase credentials path', [
                            'original_path' => $originalPath,
                            'resolved_path' => $credentialsPath
                        ]);
                    }
                }
            }

            // Final check if file exists
            if (!file_exists($credentialsPath)) {
                Log::warning('Firebase credentials file not found', [
                    'original_path' => $originalPath,
                    'resolved_path' => $credentialsPath,
                    'config_path' => config('firebase.credentials_path'),
                    'default_path' => storage_path('app/firebase-credentials.json'),
                    'file_exists_default' => file_exists(storage_path('app/firebase-credentials.json'))
                ]);
                return;
            }

            // Create Firebase factory and messaging instance
            // Note: Improved error handling below will catch ConnectException and TypeError
            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();

            Log::info('Firebase messaging initialized successfully', [
                'credentials_path' => $credentialsPath
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase', [
                'error' => $e->getMessage(),
                'credentials_path' => $credentialsPath ?? config('firebase.credentials_path'),
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
        } catch (\TypeError $e) {
            // Handle Firebase SDK internal TypeError when ConnectException occurs
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'ConnectException') !== false) {
                Log::error('FCM connection error (TypeError wrapper) - single token', [
                    'error' => $errorMessage,
                    'token' => substr($token, 0, 20) . '...',
                    'hint' => 'Network connectivity issue detected'
                ]);
            } else {
                Log::error('FCM TypeError (unexpected) - single token', [
                    'error' => $errorMessage,
                    'token' => substr($token, 0, 20) . '...'
                ]);
            }
            return false;
        } catch (ConnectException $e) {
            Log::error('FCM connection error - single token', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);
            return false;
        } catch (MessagingException $e) {
            $this->handleMessagingException($e, $token);
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send FCM notification', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
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

                try {
                    $results = $this->messaging->sendAll($messages);

                    Log::info('FCM sendAll called', [
                        'messages_count' => count($messages),
                        'results_type' => get_class($results)
                    ]);
                } catch (\TypeError $e) {
                    // Firebase SDK internal TypeError when ConnectException occurs
                    // This happens because the SDK's promise handler doesn't handle ConnectException properly
                    $errorMessage = $e->getMessage();

                    // Check if it's the known ConnectException TypeError
                    if (
                        strpos($errorMessage, 'ConnectException') !== false ||
                        strpos($errorMessage, 'Argument #1') !== false
                    ) {
                        Log::error('FCM connection error (TypeError wrapper)', [
                            'error' => $errorMessage,
                            'error_class' => get_class($e),
                            'batch_size' => count($batch),
                            'hint' => 'Network connectivity issue. The ConnectException was not properly handled by Firebase SDK. Check: 1) Internet connectivity, 2) Firewall settings, 3) DNS resolution, 4) Proxy configuration'
                        ]);
                        $failed += count($batch);
                        continue; // Skip to next batch
                    } else {
                        // Some other TypeError, re-throw or handle differently
                        Log::error('FCM TypeError (unexpected)', [
                            'error' => $errorMessage,
                            'error_class' => get_class($e),
                            'batch_size' => count($batch),
                            'trace' => substr($e->getTraceAsString(), 0, 1000)
                        ]);
                        $failed += count($batch);
                        continue;
                    }
                } catch (ConnectException $e) {
                    // Connection error - cannot reach Firebase servers during API call
                    Log::error('FCM connection error during API call', [
                        'error' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'batch_size' => count($batch),
                        'hint' => 'Connection works but API call fails. Check: 1) Timeout settings, 2) SSL/TLS handshake, 3) Network stability, 4) Firebase API endpoint availability'
                    ]);
                    $failed += count($batch);
                    continue; // Skip to next batch
                } catch (RequestException $e) {
                    // HTTP request error
                    $responseBody = 'no response';
                    if ($e->hasResponse()) {
                        try {
                            $responseBody = substr((string) $e->getResponse()->getBody(), 0, 500);
                        } catch (\Exception $ex) {
                            $responseBody = 'could not read response';
                        }
                    }

                    Log::error('FCM HTTP request error', [
                        'error' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'response_preview' => $responseBody,
                        'batch_size' => count($batch)
                    ]);
                    $failed += count($batch);
                    continue; // Skip to next batch
                } catch (MessagingException $e) {
                    // Firebase messaging-specific error
                    Log::error('FCM messaging exception', [
                        'error' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'batch_size' => count($batch)
                    ]);
                    $failed += count($batch);
                    continue; // Skip to next batch
                } catch (\Exception $e) {
                    // Catch-all for any other exceptions
                    Log::error('FCM unexpected error', [
                        'error' => $e->getMessage(),
                        'error_class' => get_class($e),
                        'error_code' => $e->getCode(),
                        'batch_size' => count($batch),
                        'trace' => substr($e->getTraceAsString(), 0, 1000)
                    ]);
                    $failed += count($batch);
                    continue; // Skip to next batch
                }

                // Process results - sendAll returns MulticastSendReport
                // Use getItems() to access individual SendResponse objects
                $responses = [];
                if (method_exists($results, 'getItems')) {
                    $responses = $results->getItems();
                } elseif (method_exists($results, 'getResults')) {
                    $responses = $results->getResults();
                } else {
                    // Try iterating directly if it's iterable
                    $responses = iterator_to_array($results, false);
                }

                Log::info('FCM responses extracted', [
                    'responses_count' => count($responses),
                    'batch_size' => count($batch)
                ]);

                // Process each response
                foreach ($responses as $index => $response) {
                    try {
                        if ($response->isSuccess()) {
                            $sent++;
                            Log::info('FCM notification sent successfully', [
                                'index' => $index,
                                'token_preview' => isset($batch[$index]) ? substr($batch[$index], 0, 20) . '...' : 'unknown'
                            ]);
                        } else {
                            $failed++;
                            $token = $batch[$index] ?? null;

                            // Get error details
                            $error = null;
                            $errorMessage = 'Unknown error';

                            if (method_exists($response, 'error')) {
                                $error = $response->error();
                            } elseif (method_exists($response, 'getException')) {
                                $error = $response->getException();
                            }

                            if ($error) {
                                if (is_object($error) && method_exists($error, 'getMessage')) {
                                    $errorMessage = $error->getMessage();
                                } elseif (is_string($error)) {
                                    $errorMessage = $error;
                                } else {
                                    $errorMessage = json_encode($error);
                                }
                            }

                            // Log detailed error information for invalid_grant
                            $logContext = [
                                'index' => $index,
                                'token_preview' => $token ? substr($token, 0, 20) . '...' : 'unknown',
                                'error' => $errorMessage,
                                'response_class' => get_class($response)
                            ];

                            // If it's invalid_grant, add more context
                            if (stripos($errorMessage, 'invalid_grant') !== false) {
                                $logContext['system_time'] = date('Y-m-d H:i:s');
                                $logContext['timezone'] = date_default_timezone_get();
                                $logContext['credentials_path'] = config('firebase.credentials_path');
                                $logContext['hint'] = 'This error usually means: 1) Service account key needs regeneration, 2) System time is out of sync, or 3) Service account lacks FCM permissions';
                            }

                            Log::warning('FCM notification failed', $logContext);

                            if ($token && $error && $this->isInvalidTokenError($error)) {
                                $invalidTokens[] = $token;
                            }
                        }
                    } catch (\Exception $e) {
                        $failed++;
                        Log::error('Error processing FCM result', [
                            'index' => $index,
                            'error' => $e->getMessage(),
                            'response_class' => get_class($response ?? 'null'),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }

                Log::info('FCM batch results processed', [
                    'batch_size' => count($batch),
                    'responses_count' => count($responses),
                    'sent' => $sent,
                    'failed' => $failed
                ]);

                // If no responses were found, mark all as failed
                if (count($responses) == 0 && count($messages) > 0) {
                    Log::warning('FCM batch completed but no responses were found', [
                        'messages_count' => count($messages),
                        'results_type' => get_class($results),
                        'available_methods' => get_class_methods($results)
                    ]);
                    $failed = count($batch);
                }

            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                // Connection error - cannot reach Firebase servers
                Log::error('FCM connection error - cannot reach Firebase servers', [
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'batch_size' => count($batch),
                    'hint' => 'Check: 1) Internet connectivity, 2) Firewall blocking Firebase, 3) DNS resolution, 4) Proxy settings'
                ]);
                $failed += count($batch);
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                // HTTP request error
                Log::error('FCM HTTP request error', [
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'response' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'no response',
                    'batch_size' => count($batch)
                ]);
                $failed += count($batch);
            } catch (\Exception $e) {
                Log::error('Failed to send batch FCM notifications', [
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'error_code' => $e->getCode(),
                    'batch_size' => count($batch),
                    'trace' => $e->getTraceAsString()
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

