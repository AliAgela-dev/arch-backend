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

    /*
    |--------------------------------------------------------------------------
    | Document Parsing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the OCR document parsing system.
    | Paths to external binaries should be set in .env file.
    |
    */

    'document_parsing' => [
        'tesseract_path' => env('TESSERACT_PATH', 'C:/Program Files/Tesseract-OCR/tesseract.exe'),
        'pdftotext_path' => env('PDFTOTEXT_PATH', 'C:/xpdf/bin64/pdftotext.exe'),
        'pdftoppm_path' => env('PDFTOPPM_PATH', 'C:/poppler/bin/pdftoppm.exe'),
        'temp_directory' => storage_path('app/ocr_temp'),
    ],

];
