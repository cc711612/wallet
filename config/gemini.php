<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Gemini API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the Google Gemini AI API.
    |
    */

    'api_key' => env('GEMINI_API_KEY'),
    'api_url' => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com'),
    'api_version' => env('GEMINI_API_VERSION', 'v1beta'),
    'default_model' => env('GEMINI_MODEL', 'gemini-pro'),
    
    // Configure safety settings
    'safety_settings' => [
        // You can customize safety thresholds for different harm categories
        // Possible values: HARM_BLOCK_THRESHOLD_UNSPECIFIED, BLOCK_LOW_AND_ABOVE, 
        // BLOCK_MEDIUM_AND_ABOVE, BLOCK_ONLY_HIGH, BLOCK_NONE
        'harassment' => env('GEMINI_SAFETY_HARASSMENT', 'BLOCK_MEDIUM_AND_ABOVE'),
        'hate_speech' => env('GEMINI_SAFETY_HATE', 'BLOCK_MEDIUM_AND_ABOVE'),
        'sexually_explicit' => env('GEMINI_SAFETY_SEXUAL', 'BLOCK_MEDIUM_AND_ABOVE'),
        'dangerous_content' => env('GEMINI_SAFETY_DANGEROUS', 'BLOCK_MEDIUM_AND_ABOVE'),
    ],

    // Generation config defaults
    'generation_config' => [
        'temperature' => env('GEMINI_TEMPERATURE', 0.9),
        'top_p' => env('GEMINI_TOP_P', 1.0),
        'top_k' => env('GEMINI_TOP_K', 1),
        'max_output_tokens' => env('GEMINI_MAX_TOKENS', 2048),
    ],
];