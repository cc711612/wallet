<?php

namespace Tests\Unit\Traits;

use App\Traits\ApiPaginateTrait;
use PHPUnit\Framework\TestCase;

class ApiPaginateTraitTest extends TestCase
{
    use ApiPaginateTrait;

    public function testApiPaginateTraitExists()
    {
        $this->assertTrue(trait_exists('App\Traits\ApiPaginateTrait'));
    }

    public function testHandleApiPageInfoMethodExists()
    {
        $this->assertTrue(method_exists($this, 'handleApiPageInfo'));
    }

    public function testTraitCanBeUsed()
    {
        // 測試 trait 可以被使用
        $this->assertInstanceOf(ApiPaginateTraitTest::class, $this);
    }

    public function testHandleApiPageInfoMethodIsPublic()
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('handleApiPageInfo');
        
        $this->assertTrue($method->isPublic());
    }

    public function testHandleApiPageInfoParameters()
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('handleApiPageInfo');
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('collection', $parameters[0]->getName());
    }

    public function testHandleApiPageInfoReturnType()
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('handleApiPageInfo');
        
        $this->assertEquals('array', $method->getReturnType()->getName());
    }
}
