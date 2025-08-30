<?php

namespace Tests\Unit;

use App\Services\GeminiService;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;
use Mockery;

class GeminiServiceTest extends TestCase
{
    protected $geminiService;
    protected $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Log facade
        if (!class_exists('\Illuminate\Support\Facades\Log')) {
            $mockLog = Mockery::mock('alias:Illuminate\Support\Facades\Log');
            $mockLog->shouldReceive('info')->andReturn(true);
            $mockLog->shouldReceive('error')->andReturn(true);
        }
        
        $this->mockClient = Mockery::mock(Client::class);
        $this->geminiService = new GeminiService(
            'fake-api-key',
            'https://fake-url',
            'v1beta',
            'gemini-pro',
            ['harassment' => 'BLOCK_NONE'],
            ['maxOutputTokens' => 10]
        );
        // 注入 mock client
        $reflection = new \ReflectionClass($this->geminiService);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($this->geminiService, $this->mockClient);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGeminiServiceCanBeInstantiated()
    {
        $this->assertInstanceOf(GeminiService::class, $this->geminiService);
    }

    public function testGenerateContentMethodExists()
    {
        $this->assertTrue(method_exists($this->geminiService, 'generateContent'));
    }

    public function testChatMethodExists()
    {
        $this->assertTrue(method_exists($this->geminiService, 'chat'));
    }

    public function testEmbedContentMethodExists()
    {
        $this->assertTrue(method_exists($this->geminiService, 'embedContent'));
    }

    public function testListModelsMethodExists()
    {
        $this->assertTrue(method_exists($this->geminiService, 'listModels'));
    }

    public function testCountTokensMethodExists()
    {
        $this->assertTrue(method_exists($this->geminiService, 'countTokens'));
    }

    public function testFormatSafetySettings()
    {
        $reflection = new \ReflectionClass($this->geminiService);
        $method = $reflection->getMethod('formatSafetySettings');
        $method->setAccessible(true);
        
        $settings = $method->invoke($this->geminiService);
        $this->assertEquals([
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_NONE'
            ]
        ], $settings);
    }

    public function testConstructorSetsProperties()
    {
        $reflection = new \ReflectionClass($this->geminiService);
        
        $apiKeyProperty = $reflection->getProperty('apiKey');
        $apiKeyProperty->setAccessible(true);
        $this->assertEquals('fake-api-key', $apiKeyProperty->getValue($this->geminiService));
        
        $apiUrlProperty = $reflection->getProperty('apiUrl');
        $apiUrlProperty->setAccessible(true);
        $this->assertEquals('https://fake-url', $apiUrlProperty->getValue($this->geminiService));
        
        $defaultModelProperty = $reflection->getProperty('defaultModel');
        $defaultModelProperty->setAccessible(true);
        $this->assertEquals('gemini-pro', $defaultModelProperty->getValue($this->geminiService));
    }

    public function testMakeRequestMethodExists()
    {
        $reflection = new \ReflectionClass($this->geminiService);
        $this->assertTrue($reflection->hasMethod('makeRequest'));
        
        $method = $reflection->getMethod('makeRequest');
        $this->assertTrue($method->isProtected());
    }
}
