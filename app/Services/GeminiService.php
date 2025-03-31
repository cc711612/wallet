<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    /**
     * @var string Gemini API key
     */
    protected $apiKey;

    /**
     * @var string Gemini API base URL
     */
    protected $apiUrl;

    /**
     * @var string Gemini API version
     */
    protected $apiVersion;

    /**
     * @var string Default model to use
     */
    protected $defaultModel;
    
    /**
     * @var array Safety settings for content moderation
     */
    protected $safetySettings;
    
    /**
     * @var array Generation configuration parameters
     */
    protected $generationConfig;
    
    /**
     * @var Client HTTP Client
     */
    protected $httpClient;

    /**
     * GeminiService constructor.
     *
     * @param string $apiKey
     * @param string $apiUrl
     * @param string $apiVersion
     * @param string $defaultModel
     * @param array $safetySettings
     * @param array $generationConfig
     */
    public function __construct(
        $apiKey,
        $apiUrl = 'https://generativelanguage.googleapis.com',
        $apiVersion = 'v1beta',
        $defaultModel = 'gemini-pro',
        $safetySettings = [],
        $generationConfig = []
    ) {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
        $this->apiVersion = $apiVersion;
        $this->defaultModel = $defaultModel;
        $this->safetySettings = $safetySettings;
        $this->generationConfig = $generationConfig;
        
        $this->httpClient = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => 30,
            'http_errors' => false,
        ]);
    }

    /**
     * Generate text content using the Gemini API
     *
     * @param string $prompt The text prompt for generation
     * @param array $options Additional options to override defaults
     * @return array|null The response from the API
     * @throws Exception
     */
    public function generateContent($prompt, $options = [])
    {
        $model = $options['model'] ?? $this->defaultModel;
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ]
        ];
        
        // Add generation configuration if provided
        if (!empty($this->generationConfig) && !isset($options['skipGenerationConfig'])) {
            $generationConfig = $options['generationConfig'] ?? $this->generationConfig;
            $payload['generationConfig'] = $generationConfig;
        }
        
        // Add safety settings if provided
        if (!empty($this->safetySettings) && !isset($options['skipSafetySettings'])) {
            $safetySettings = $options['safetySettings'] ?? $this->formatSafetySettings();
            $payload['safetySettings'] = $safetySettings;
        }
        
        return $this->makeRequest("/$this->apiVersion/models/$model:generateContent", $payload);
    }
    
    /**
     * Generate text content in a streaming fashion
     *
     * @param string $prompt The text prompt for generation
     * @param array $options Additional options to override defaults
     * @return \Generator A generator yielding response chunks
     * @throws Exception
     */
    public function streamContent($prompt, $options = [])
    {
        $model = $options['model'] ?? $this->defaultModel;
        
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ]
        ];
        
        // Add generation configuration if provided
        if (!empty($this->generationConfig) && !isset($options['skipGenerationConfig'])) {
            $generationConfig = $options['generationConfig'] ?? $this->generationConfig;
            $payload['generationConfig'] = $generationConfig;
        }
        
        // Add safety settings if provided
        if (!empty($this->safetySettings) && !isset($options['skipSafetySettings'])) {
            $safetySettings = $options['safetySettings'] ?? $this->formatSafetySettings();
            $payload['safetySettings'] = $safetySettings;
        }
        
        try {
            $response = $this->httpClient->request('POST', "/$this->apiVersion/models/$model:streamGenerateContent", [
                'query' => ['key' => $this->apiKey],
                'json' => $payload,
                'stream' => true,
            ]);
            
            $body = $response->getBody();
            
            while (!$body->eof()) {
                $line = $body->readline();
                if (empty($line)) continue;
                
                // Remove "data: " prefix if present
                if (strpos($line, 'data: ') === 0) {
                    $line = substr($line, 6);
                }
                
                if ($line === '[DONE]') {
                    break;
                }
                
                try {
                    $data = json_decode($line, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        yield $data;
                    }
                } catch (\Exception $e) {
                    // Skip malformed JSON
                    continue;
                }
            }
            
        } catch (GuzzleException $e) {
            throw new Exception("API Stream Request Failed: " . $e->getMessage());
        }
    }
    
    /**
     * Chat with the model using a conversation history
     *
     * @param array $messages Array of messages with 'role' (user/model) and 'content'
     * @param array $options Additional options to override defaults
     * @return array|null The response from the API
     * @throws Exception
     */
    public function chat($messages, $options = [])
    {
        $model = $options['model'] ?? $this->defaultModel;
        
        $contents = [];
        foreach ($messages as $message) {
            $contents[] = [
                'role' => $message['role'],
                'parts' => [
                    [
                        'text' => $message['content']
                    ]
                ]
            ];
        }
        
        $payload = [
            'contents' => $contents
        ];
        
        // Add generation configuration if provided
        if (!empty($this->generationConfig) && !isset($options['skipGenerationConfig'])) {
            $generationConfig = $options['generationConfig'] ?? $this->generationConfig;
            $payload['generationConfig'] = $generationConfig;
        }
        
        // Add safety settings if provided
        if (!empty($this->safetySettings) && !isset($options['skipSafetySettings'])) {
            $safetySettings = $options['safetySettings'] ?? $this->formatSafetySettings();
            $payload['safetySettings'] = $safetySettings;
        }
        
        return $this->makeRequest("/$this->apiVersion/models/$model:generateContent", $payload);
    }

    public function getChatResult($messages, $options = [])
    {
        $response = $this->chat($messages, $options);
        Log::info('Gemini Chat Response', ['response' => json_encode($response)]);
        return data_get($response, 'candidates.0.content.parts.0.text', '');
    }

    /**
     * Embed text using the Gemini embedding model
     * 
     * @param string $text The text to embed
     * @param array $options Additional options
     * @return array|null The embedding vectors
     * @throws Exception
     */
    public function embedContent($text, $options = [])
    {
        $model = $options['model'] ?? 'embedding-001';  // Use embedding-specific model
        
        $payload = [
            'content' => [
                'parts' => [
                    [
                        'text' => $text
                    ]
                ]
            ]
        ];
        
        return $this->makeRequest("/$this->apiVersion/models/$model:embedContent", $payload);
    }
    
    /**
     * List available models
     * 
     * @return array|null The available models
     * @throws Exception
     */
    public function listModels()
    {
        return $this->makeRequest("/$this->apiVersion/models", [], 'GET');
    }
    
    /**
     * Count tokens for a text prompt
     * 
     * @param string $text The text to count tokens for
     * @param array $options Additional options
     * @return array|null The token count information
     * @throws Exception
     */
    public function countTokens($text, $options = [])
    {
        $model = $options['model'] ?? $this->defaultModel;
        
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $text
                        ]
                    ]
                ]
            ]
        ];
        
        return $this->makeRequest("/$this->apiVersion/models/$model:countTokens", $payload);
    }

    /**
     * Make a request to the Gemini API
     *
     * @param string $endpoint API endpoint
     * @param array $payload Request payload
     * @param string $method HTTP method (default: POST)
     * @return array|null The response from the API
     * @throws Exception
     */
    protected function makeRequest($endpoint, $payload = [], $method = 'POST')
    {
        Log::info("Making request to Gemini API: $endpoint", ['payload' => $payload]);
        try {
            $options = [
                'query' => ['key' => $this->apiKey]
            ];
            
            if ($method !== 'GET' && !empty($payload)) {
                $options['json'] = $payload;
            }
            $response = $this->httpClient->request($method, $endpoint, $options);
            
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $responseData = json_decode($body, true);
            
            if ($statusCode < 200 || $statusCode >= 300) {
                Log::error('Gemini API Error', [
                    'status' => $statusCode,
                    'response' => $responseData,
                    'endpoint' => $endpoint
                ]);
                
                throw new Exception("Gemini API Error: " . ($responseData['error']['message'] ?? "Status code: $statusCode"));
            }

            return $responseData;
        } catch (GuzzleException $e) {
            Log::error('Gemini API Request Failed', [
                'message' => $e->getMessage(),
                'endpoint' => $endpoint
            ]);
            
            throw new Exception("Gemini API Request Failed: " . $e->getMessage());
        }
    }
    
    /**
     * Format safety settings for the API request
     *
     * @return array Formatted safety settings array
     */
    protected function formatSafetySettings()
    {
        $formattedSettings = [];
        
        $harmCategories = [
            'harassment' => 'HARM_CATEGORY_HARASSMENT',
            'hate_speech' => 'HARM_CATEGORY_HATE_SPEECH',
            'sexually_explicit' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
            'dangerous_content' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
        ];
        
        foreach ($this->safetySettings as $category => $threshold) {
            if (isset($harmCategories[$category])) {
                $formattedSettings[] = [
                    'category' => $harmCategories[$category],
                    'threshold' => $threshold
                ];
            }
        }
        
        return $formattedSettings;
    }
}