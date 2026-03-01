<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini AI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Gemini text generation API used by the AI
    | refinement pipeline. The GeminiClient reads these values.
    |
    */

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        // Stable default. Newer models available: gemini-2.5-flash, gemini-3-flash-preview
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'rate_limit' => (int) env('GEMINI_RATE_LIMIT', 60),
        'temperature' => (float) env('GEMINI_TEMPERATURE', 0.1),
        'timeout' => (int) env('GEMINI_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vertex AI Embedding Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Gemini embedding API used by the
    | GeminiEmbeddingClient for document and query embeddings.
    |
    */

    'vertex' => [
        'api_key' => env('VERTEX_API_KEY'),
        'base_url' => env('VERTEX_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'embedding_model' => env('VERTEX_EMBEDDING_MODEL', 'gemini-embedding-001'),
        'rate_limit' => (int) env('VERTEX_RATE_LIMIT', 300),
        'timeout' => (int) env('VERTEX_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pipeline Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the document processing pipeline behavior.
    |
    */

    'pipeline' => [
        'confidence_threshold' => (int) env('OCR_CONFIDENCE_THRESHOLD', 85),
        'auto_create_students' => (bool) env('AUTO_CREATE_STUDENT_RECORDS', true),
        'auto_classification' => (bool) env('AUTO_CLASSIFICATION', true),
    ],

];
