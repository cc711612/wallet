<?php

namespace Tests\Unit\Http\Validators\Apis\Users;

use App\Http\Validators\Apis\Users\UserStoreValidator;
use App\Concerns\Databases\Contracts\Request;
use PHPUnit\Framework\TestCase;
use Mockery;

class UserStoreValidatorTest extends TestCase
{
    protected $mockRequest;
    protected $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRequest = Mockery::mock(Request::class);
        $this->validator = new UserStoreValidator($this->mockRequest);
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
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayHasKey('email', $rules);
        
        // 檢查 name 規則
        $this->assertContains('required', $rules['name']);
        
        // 檢查 password 規則
        $this->assertContains('required', $rules['password']);
        $this->assertContains('min:6', $rules['password']);
        $this->assertContains('max:18', $rules['password']);
        
        // 檢查 email 規則
        $this->assertContains('required', $rules['email']);
        $this->assertContains('unique:users,email', $rules['email']);
        $this->assertContains('email', $rules['email']);
    }

    public function testMessagesMethod()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);
        
        $messages = $method->invoke($this->validator);
        
        $this->assertIsArray($messages);
        $this->assertEquals('name 為必填', $messages['name.required']);
        $this->assertEquals('password 為必填', $messages['password.required']);
        $this->assertEquals('password 至多18字元', $messages['password.max']);
        $this->assertEquals('password 至多6字元', $messages['password.min']);
        $this->assertEquals('email 為必填', $messages['email.required']);
        $this->assertEquals('email 已存在', $messages['email.unique']);
        $this->assertEquals('email 格式有誤', $messages['email.email']);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(UserStoreValidator::class, $this->validator);
        
        // 檢查 request 屬性是否正確設定
        $reflection = new \ReflectionClass($this->validator);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);
        
        $this->assertSame($this->mockRequest, $property->getValue($this->validator));
    }

    public function testInheritsFromValidatorAbstracts()
    {
        $this->assertInstanceOf(\App\Concerns\Commons\Abstracts\ValidatorAbstracts::class, $this->validator);
    }

    public function testEmailValidationRules()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('rules');
        $method->setAccessible(true);
        
        $rules = $method->invoke($this->validator);
        
        // 確認 email 有三個驗證規則
        $this->assertCount(3, $rules['email']);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
        $this->assertContains('unique:users,email', $rules['email']);
    }

    public function testPasswordValidationRules()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('rules');
        $method->setAccessible(true);
        
        $rules = $method->invoke($this->validator);
        
        // 確認 password 有三個驗證規則
        $this->assertCount(3, $rules['password']);
        $this->assertContains('required', $rules['password']);
        $this->assertContains('min:6', $rules['password']);
        $this->assertContains('max:18', $rules['password']);
    }

    public function testAllFieldsHaveMessages()
    {
        $reflection = new \ReflectionClass($this->validator);
        $rulesMethod = $reflection->getMethod('rules');
        $rulesMethod->setAccessible(true);
        $messagesMethod = $reflection->getMethod('messages');
        $messagesMethod->setAccessible(true);
        
        $rules = $rulesMethod->invoke($this->validator);
        $messages = $messagesMethod->invoke($this->validator);
        
        // 檢查每個欄位的每個規則都有對應的錯誤訊息
        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule) {
                $ruleKey = strpos($rule, ':') !== false ? explode(':', $rule)[0] : $rule;
                $messageKey = "$field.$ruleKey";
                $this->assertArrayHasKey($messageKey, $messages, "Missing message for: $messageKey");
            }
        }
    }
}
