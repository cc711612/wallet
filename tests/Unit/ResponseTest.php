<?php

namespace Tests\Unit;

use App\Support\Response;
use Illuminate\Http\Response as HttpResponse;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    protected $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->response = new Response();
    }

    public function testResponseClassExists()
    {
        $this->assertInstanceOf(Response::class, $this->response);
    }

    public function testAcceptedMethodExists()
    {
        $this->assertTrue(method_exists($this->response, 'accepted'));
    }

    public function testCreatedMethodExists()
    {
        $this->assertTrue(method_exists($this->response, 'created'));
    }

    public function testErrorBadRequestMethodExists()
    {
        $this->assertTrue(method_exists($this->response, 'errorBadRequest'));
    }

    public function testNoContentMethodExists()
    {
        $this->assertTrue(method_exists($this->response, 'noContent'));
    }

    public function testSuccessMethodExists()
    {
        $this->assertTrue(method_exists($this->response, 'success'));
    }

    public function testFormatDataMethod()
    {
        $reflection = new \ReflectionClass($this->response);
        $method = $reflection->getMethod('formatData');
        $method->setAccessible(true);
        
        $code = 200;
        $result = $method->invoke($this->response, ['test'], 'Success', $code);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testFormatDataWithClientError()
    {
        $reflection = new \ReflectionClass($this->response);
        $method = $reflection->getMethod('formatData');
        $method->setAccessible(true);
        
        $code = 404;
        $result = $method->invoke($this->response, ['test'], 'Not found', $code);
        
        $this->assertFalse($result['status']);
        $this->assertEquals(404, $result['code']);
        $this->assertEquals('Not found', $result['message']);
        $this->assertEquals(['test'], $result['data']);
    }

    public function testFormatDataWithServerError()
    {
        $reflection = new \ReflectionClass($this->response);
        $method = $reflection->getMethod('formatData');
        $method->setAccessible(true);
        
        $code = 500;
        $result = $method->invoke($this->response, null, 'Server error', $code);
        
        $this->assertFalse($result['status']);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Server error', $result['message']);
    }

    public function testFormatDataWithSuccessCode()
    {
        $reflection = new \ReflectionClass($this->response);
        $method = $reflection->getMethod('formatData');
        $method->setAccessible(true);
        
        $code = 200;
        $result = $method->invoke($this->response, ['data'], 'Success', $code);
        
        $this->assertTrue($result['status']);
        $this->assertEquals(200, $result['code']);
        $this->assertEquals('Success', $result['message']);
        $this->assertEquals(['data'], $result['data']);
    }

    public function testFormatDataWithNullData()
    {
        $reflection = new \ReflectionClass($this->response);
        $method = $reflection->getMethod('formatData');
        $method->setAccessible(true);
        
        $code = 200;
        $result = $method->invoke($this->response, null, 'Success', $code);
        
        $this->assertTrue($result['status']);
        $this->assertEquals((object)[], $result['data']); // null 會被轉成空物件
    }
}
