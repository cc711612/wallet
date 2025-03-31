<?php

namespace App\Facades;

use App\Services\GeminiService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array generateContent(string $prompt, array $options = [])
 * @method static \Generator streamContent(string $prompt, array $options = [])
 * @method static array chat(array $messages, array $options = [])
 * @method static array embedContent(string $text, array $options = [])
 * @method static array listModels()
 * @method static array countTokens(string $text, array $options = [])
 * 
 * @see \App\Services\GeminiService
 */
class Gemini extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return GeminiService::class;
    }
}