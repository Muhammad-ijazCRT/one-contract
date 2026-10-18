<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'nowpayments' => [
        'api_key' => env('NOWPAYMENTS_API_KEY'),
        'ipn_secret' => env('NOWPAYMENTS_IPN_SECRET'),
        'sandbox' => env('NOWPAYMENTS_SANDBOX', false),
        'checkout_endpoint' => env('NOWPAYMENTS_CHECKOUT_ENDPOINT', '/v1/invoice'),
        'base_url' => env('NOWPAYMENTS_SANDBOX', false)
            ? env('NOWPAYMENTS_SANDBOX_URL', 'https://api-sandbox.nowpayments.io')
            : env('NOWPAYMENTS_BASE_URL', 'https://api.nowpayments.io'),
        'ipn_callback_url' => env('NOWPAYMENTS_IPN_CALLBACK_URL')
            ?: rtrim((string) env('APP_URL', 'http://localhost'), '/').'/nowpayments/ipn',
        'success_url' => env('NOWPAYMENTS_SUCCESS_URL'),
        'cancel_url' => env('NOWPAYMENTS_CANCEL_URL'),
        'custody_email' => env('NOWPAYMENTS_CUSTODY_EMAIL'),
        'custody_password' => env('NOWPAYMENTS_CUSTODY_PASSWORD'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/auth/google/callback'),
    ],

    'monnify' => [
        'api_key' => env('MONNIFY_API_KEY'),
        'secret_key' => env('MONNIFY_SECRET_KEY'),
        'contract_code' => env('MONNIFY_CONTRACT_CODE'),
        'wallet_account' => env('MONNIFY_WALLET_ACCOUNT'),
    ],

    'dojah' => [
        'public_key' => env('DOJAH_PUBLIC_KEY'),
        'secret_key' => env('DOJAH_SECRET_KEY'),
        'app_id' => env('DOJAH_APP_ID'),
        'ingest_key' => env('DOJAH_INGEST_KEY'),
    ],

];
