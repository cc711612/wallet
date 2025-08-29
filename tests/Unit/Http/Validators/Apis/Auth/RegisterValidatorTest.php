<?php

namespace Tests\Unit\Http\Validators\Apis\Auth;

use App\Http\Validators\Apis\Auth\RegisterValidator;
use App\Concerns\Databases\Contracts\Request;
use PHPUnit\Framework\TestCase;
use Mockery;

class RegisterValidatorTest extends TestCase
{
    protected $mockRequest;
    protected $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRequest = Mockery::mock(Request::class);
        $this->validator = new RegisterValidator($this->mockRequest);
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
        $this->assertArrayHasKey('name', $rules);
        
        // 檢查 password 規則
        $this->assertContains('required', $rules['password']);
        $this->assertContains('min:6', $rules['password']);
        $this->assertContains('max:18', $rules['password']);
        
        // 檢查 account 規則 (註冊時要求唯一，與登入時不同)
        $this->assertContains('required', $rules['account']);
        $this->assertContains('unique:users,account', $rules['account']);
        
        // 檢查 name 規則
        $this->assertContains('required', $rules['name']);
    }

    public function testMessagesMethod()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);
        
        $messages = $method->invoke($this->validator);
        
        $this->assertIsArray($messages);
        $this->assertEquals('密碼 為必填', $messages['password.required']);
        $this->assertEquals('密碼 至多18字元', $messages['password.max']);
        $this->assertEquals('密碼 至多6字元', $messages['password.min']);
        $this->assertEquals('帳號 為必填', $messages['account.required']);
        $this->assertEquals('帳號已存在', $messages['account.unique']);
        $this->assertEquals('暱稱 為必填', $messages['name.required']);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(RegisterValidator::class, $this->validator);
        
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

    public function testRegisterAndLoginValidatorDifferences()
    {
        // 比較註冊和登入驗證器的差異
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('rules');
        $method->setAccessible(true);
        
        $rules = $method->invoke($this->validator);
        
        // 註冊時 account 應該是 unique，而非 exists
        $this->assertContains('unique:users,account', $rules['account']);
        $this->assertNotContains('exists:users,account', $rules['account']);
        
        // 註冊時應該有 name 欄位
        $this->assertArrayHasKey('name', $rules);
    }
}
