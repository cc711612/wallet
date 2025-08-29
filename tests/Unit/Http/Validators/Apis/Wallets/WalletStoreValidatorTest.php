<?php

namespace Tests\Unit\Http\Validators\Apis\Wallets;

use App\Http\Validators\Apis\Wallets\WalletStoreValidator;
use App\Concerns\Databases\Contracts\Request;
use PHPUnit\Framework\TestCase;
use Mockery;

class WalletStoreValidatorTest extends TestCase
{
    protected $mockRequest;
    protected $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRequest = Mockery::mock(Request::class);
        $this->validator = new WalletStoreValidator($this->mockRequest);
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
        $this->assertArrayHasKey('wallets.user_id', $rules);
        $this->assertArrayHasKey('wallets.title', $rules);
        $this->assertArrayHasKey('wallets.code', $rules);
        
        // 檢查 wallets.user_id 規則
        $this->assertContains('required', $rules['wallets.user_id']);
        $this->assertContains('exists:users,id', $rules['wallets.user_id']);
        
        // 檢查 wallets.title 規則
        $this->assertContains('required', $rules['wallets.title']);
        
        // 檢查 wallets.code 規則
        $this->assertContains('required', $rules['wallets.code']);
    }

    public function testMessagesMethod()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);
        
        $messages = $method->invoke($this->validator);
        
        $this->assertIsArray($messages);
        $this->assertEquals('系統異常', $messages['wallets.user_id.required']);
        $this->assertEquals('系統異常', $messages['wallets.user_id.exists']);
        $this->assertEquals('帳簿名稱為必填', $messages['wallets.title.required']);
        $this->assertEquals('系統異常', $messages['wallets.code.required']);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(WalletStoreValidator::class, $this->validator);
        
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

    public function testNestedValidationRules()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('rules');
        $method->setAccessible(true);
        
        $rules = $method->invoke($this->validator);
        
        // 確認使用了巢狀驗證（wallets.* 格式）
        foreach (array_keys($rules) as $field) {
            $this->assertStringStartsWith('wallets.', $field, '所有欄位都應該在 wallets 巢狀結構下');
        }
    }

    public function testSystemErrorMessages()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);
        
        $messages = $method->invoke($this->validator);
        
        // 檢查系統相關欄位的錯誤訊息都是「系統異常」
        $systemFields = [
            'wallets.user_id.required',
            'wallets.user_id.exists',
            'wallets.code.required'
        ];
        
        foreach ($systemFields as $field) {
            $this->assertEquals('系統異常', $messages[$field], "欄位 {$field} 應該顯示系統異常訊息");
        }
    }

    public function testUserFacingMessages()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);
        
        $messages = $method->invoke($this->validator);
        
        // 檢查使用者面向的錯誤訊息
        $this->assertEquals('帳簿名稱為必填', $messages['wallets.title.required']);
        $this->assertNotEquals('系統異常', $messages['wallets.title.required']);
    }
}
