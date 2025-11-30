<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase Cloud Messaging (FCM) push notifications.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials Path
    |--------------------------------------------------------------------------
    |
    | Path to the Firebase service account JSON file.
    | You can specify either:
    | - Absolute path: /path/to/firebase-credentials.json
    | - Relative path from storage/app: firebase-credentials.json
    | - Or use environment variable FIREBASE_CREDENTIALS_PATH
    |
    */
    'credentials_path' => env('FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase-credentials.json')),

    /*
    |--------------------------------------------------------------------------
    | FCM Android Channel ID
    |--------------------------------------------------------------------------
    |
    | Default Android notification channel ID for notifications.
    | This should match the channel ID configured in your Android app.
    |
    */
    'android_channel_id' => env('FCM_ANDROID_CHANNEL_ID', 'ghumantoo_default_channel'),

    /*
    |--------------------------------------------------------------------------
    | Batch Size
    |--------------------------------------------------------------------------
    |
    | Maximum number of tokens to send in a single batch request.
    | FCM supports up to 500 tokens per batch.
    |
    */
    'batch_size' => env('FCM_BATCH_SIZE', 500),
];

