<?php

namespace App\Http\Controllers;

use App\Facades\Gemini;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class GeminiController extends Controller
{
    /**
     * Generate a text response from Gemini
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateContent(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|max:4000',
            'temperature' => 'nullable|numeric|min:0|max:1',
            'max_tokens' => 'nullable|integer|min:1|max:8192'
        ]);

        try {
            $options = [];
            
            if ($request->has('temperature')) {
                $options['generationConfig']['temperature'] = $request->input('temperature');
            }
            
            if ($request->has('max_tokens')) {
                $options['generationConfig']['max_output_tokens'] = $request->input('max_tokens');
            }
            
            $response = Gemini::generateContent($request->input('prompt'), $options);
            
            // Extract the text from the response
            $text = $this->extractTextFromResponse($response);
            
            return response()->json([
                'success' => true,
                'text' => $text,
                'raw_response' => $response
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Stream a text response from Gemini
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function streamContent(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:4000',
            'temperature' => 'nullable|numeric|min:0|max:1',
            'max_tokens' => 'nullable|integer|min:1|max:8192'
        ]);

        return response()->stream(function () use ($request) {
            try {
                $options = [];
                
                if ($request->has('temperature')) {
                    $options['generationConfig']['temperature'] = $request->input('temperature');
                }
                
                if ($request->has('max_tokens')) {
                    $options['generationConfig']['max_output_tokens'] = $request->input('max_tokens');
                }
                
                $stream = Gemini::streamContent($request->input('prompt'), $options);
                
                foreach ($stream as $chunk) {
                    // Extract text from each chunk
                    $text = $this->extractTextFromStreamChunk($chunk);
                    if (!empty($text)) {
                        echo $text;
                        ob_flush();
                        flush();
                    }
                }
                
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no'
        ]);
    }
    
    /**
     * Chat with Gemini
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|string|in:user,model',
            'messages.*.content' => 'required|string',
            'temperature' => 'nullable|numeric|min:0|max:1',
            'max_tokens' => 'nullable|integer|min:1|max:8192'
        ]);

        try {
            $options = [];
            
            if ($request->has('temperature')) {
                $options['generationConfig']['temperature'] = $request->input('temperature');
            }
            
            if ($request->has('max_tokens')) {
                $options['generationConfig']['max_output_tokens'] = $request->input('max_tokens');
            }
            
            $response = Gemini::chat($request->input('messages'), $options);
            
            // Extract the text from the response
            $text = $this->extractTextFromResponse($response);
            
            return response()->json([
                'success' => true,
                'text' => $text,
                'raw_response' => $response
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * List available Gemini models
     *
     * @return JsonResponse
     */
    public function listModels(): JsonResponse
    {
        try {
            $response = Gemini::listModels();
            
            return response()->json([
                'success' => true,
                'models' => $response['models'] ?? []
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Extract text from the Gemini API response
     *
     * @param array $response The response from Gemini API
     * @return string The extracted text
     */
    private function extractTextFromResponse(array $response): string
    {
        $text = '';
        
        if (isset($response['candidates'][0]['content']['parts'])) {
            foreach ($response['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['text'])) {
                    $text .= $part['text'];
                }
            }
        }
        
        return $text;
    }
    
    /**
     * Extract text from a streaming chunk
     *
     * @param array $chunk The streaming chunk
     * @return string The extracted text
     */
    private function extractTextFromStreamChunk(array $chunk): string
    {
        $text = '';
        
        if (isset($chunk['candidates'][0]['content']['parts'])) {
            foreach ($chunk['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['text'])) {
                    $text .= $part['text'];
                }
            }
        }
        
        return $text;
    }
}