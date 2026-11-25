<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect_uri' => env('LINKEDIN_REDIRECT_URI'),
        'scopes' => env('LINKEDIN_SCOPES', 'openid profile w_member_social'),
        'api_version' => env('LINKEDIN_API_VERSION'),
    ],

    'tiktok' => [
        'client_key' => env('TIKTOK_CLIENT_KEY'),
        'client_secret' => env('TIKTOK_CLIENT_SECRET'),
        'redirect_uri' => env('TIKTOK_REDIRECT_URI'),
        'scopes' => env('TIKTOK_SCOPES', 'user.info.basic,video.publish'),
        'media_domain' => env('TIKTOK_MEDIA_DOMAIN'),
        'media_domain_verified' => env('TIKTOK_MEDIA_DOMAIN_VERIFIED', false),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
        'twiml_app_sid' => env('TWILIO_TWIML_APP_SID'),
        'api_key' => env('TWILIO_API_KEY'),
        'api_secret' => env('TWILIO_API_SECRET'),
        'transcription_configuration_id' => env('TWILIO_TRANSCRIPTION_CONFIGURATION_ID'),
        'webhook_base_url' => env('TWILIO_WEBHOOK_BASE_URL'),
    ],

    'whatsapp' => [
        'api_key' => env('WHATSAPP_360DIALOG_API_KEY'),
        'webhook_token' => env('WHATSAPP_WEBHOOK_TOKEN'),
        'recipient' => env('WHATSAPP_360DIALOG_RECIPIENT'),
        'recipient_type' => env('WHATSAPP_360DIALOG_RECIPIENT_TYPE', 'phone'),
        'display_phone' => env('WHATSAPP_DISPLAY_PHONE'),
        'phone_number_ids' => env('WHATSAPP_PHONE_NUMBER_IDS', ''),
        'api_url' => env('WHATSAPP_360DIALOG_API_URL', 'https://waba-sandbox.360dialog.io/v1'),
    ],

];
