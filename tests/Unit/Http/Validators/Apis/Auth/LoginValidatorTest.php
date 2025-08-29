<?php

namespace Tests\Unit\Http\Validators\Apis\Auth;

use App\Http\Validators\Apis\Auth\LoginValidator;
use App\Concerns\Databases\Contracts\Request;
use PHPUnit\Framework\TestCase;
use Mockery;
use Illuminate\Validation\Validator as IlluminateValidator;

class LoginValidatorTest extends TestCase
{
    protected $mockRequest;
    protected $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRequest = Mockery::mock(Request::class);
        $this->validator = new LoginValidator($this->mockRequest);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRulesMethod()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('rules');
        $method->setAccessible(true);
        
        $rules = $method->invoke($this->validator);
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayHasKey('account', $rules);
        
        // 檢查 password 規則
        $this->assertContains('required', $rules['password']);
        $this->assertContains('min:6', $rules['password']);
        $this->assertContains('max:18', $rules['password']);
        
        // 檢查 account 規則
        $this->assertContains('required', $rules['account']);
        $this->assertContains('exists:users,account', $rules['account']);
    }

    public function testMessagesMethod()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);
        
        $messages = $method->invoke($this->validator);
        
        $this->assertIsArray($messages);
        $this->assertEquals('password 為必填', $messages['password.required']);
        $this->assertEquals('password 至多18字元', $messages['password.max']);
        $this->assertEquals('password 至少6字元', $messages['password.min']);
        $this->assertEquals('account 為必填', $messages['account.required']);
        $this->assertEquals('account not exist', $messages['account.exists']);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(LoginValidator::class, $this->validator);
        
        // 檢查 request 屬性是否正確設定
        $reflection = new \ReflectionClass($this->validator);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);
        
        $this->assertSame($this->mockRequest, $property->getValue($this->validator));
    }

    public function testValidateMethodExists()
    {
        // 測試繼承自父類別的 validate 方法
        $this->assertTrue(method_exists($this->validator, 'validate'));
    }

    public function testInheritsFromValidatorAbstracts()
    {
        $this->assertInstanceOf(\App\Concerns\Commons\Abstracts\ValidatorAbstracts::class, $this->validator);
    }
}
