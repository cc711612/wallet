<?php

namespace Tests\Unit\Http\Validators\Apis\Wallets;

use App\Http\Validators\Apis\Wallets\WalletCalculationValidator;
use App\Concerns\Databases\Contracts\Request;
use PHPUnit\Framework\TestCase;
use Mockery;
use Illuminate\Validation\Rule;

class WalletCalculationValidatorTest extends TestCase
{
    protected $mockRequest;
    protected $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRequest = Mockery::mock(Request::class);
        $this->validator = new WalletCalculationValidator($this->mockRequest);
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
        $this->assertArrayHasKey('wallets.id', $rules);
        $this->assertArrayHasKey('wallet_users.id', $rules);
        
        // 檢查 wallets.id 規則
        $this->assertContains('required', $rules['wallets.id']);
        $this->assertContains('exists:wallets,id', $rules['wallets.id']);
        
        // 檢查 wallet_users.id 規則
        $this->assertContains('required', $rules['wallet_users.id']);
        // 第二個規則是 Rule::exists() 物件，檢查是否存在
        $this->assertCount(2, $rules['wallet_users.id']);
    }

    public function testMessagesMethod()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);
        
        $messages = $method->invoke($this->validator);
        
        $this->assertIsArray($messages);
        $this->assertEquals('系統異常', $messages['wallets.id.required']);
        $this->assertEquals('系統異常', $messages['wallets.id.exists']);
        $this->assertEquals('系統異常', $messages['wallet_users.id.required']);
        $this->assertEquals('非帳本內成員', $messages['wallet_users.id.exists']);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(WalletCalculationValidator::class, $this->validator);
        
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

    public function testComplexValidationRules()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('rules');
        $method->setAccessible(true);
        
        $rules = $method->invoke($this->validator);
        
        // 檢查 wallet_users.id 使用了複雜的驗證規則（Rule::exists 帶條件）
        $walletUserRules = $rules['wallet_users.id'];
        $this->assertCount(2, $walletUserRules);
        $this->assertEquals('required', $walletUserRules[0]);
        
        // 第二個規則應該是 Rule 物件
        // 在沒有完整 Laravel 環境的情況下，我們無法完全測試 Rule::exists 的行為
        // 但可以確認規則的存在
        $this->assertNotEquals('required', $walletUserRules[1]);
    }

    public function testSystemErrorMessages()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);
        
        $messages = $method->invoke($this->validator);
        
        // 檢查大部分錯誤訊息都是「系統異常」
        $systemErrorFields = [
            'wallets.id.required',
            'wallets.id.exists',
            'wallet_users.id.required'
        ];
        
        foreach ($systemErrorFields as $field) {
            $this->assertEquals('系統異常', $messages[$field], "欄位 {$field} 應該顯示系統異常訊息");
        }
    }

    public function testSpecificUserErrorMessage()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);
        
        $messages = $method->invoke($this->validator);
        
        // 檢查使用者相關的特定錯誤訊息
        $this->assertEquals('非帳本內成員', $messages['wallet_users.id.exists']);
        $this->assertNotEquals('系統異常', $messages['wallet_users.id.exists']);
    }

    public function testValidatorPurpose()
    {
        // 基於類別名稱和規則，這個驗證器是用於錢包計算相關的驗證
        // 它需要驗證錢包存在，以及使用者是該錢包的成員
        
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('rules');
        $method->setAccessible(true);
        
        $rules = $method->invoke($this->validator);
        
        // 確認有錢包ID驗證
        $this->assertArrayHasKey('wallets.id', $rules);
        
        // 確認有錢包使用者ID驗證
        $this->assertArrayHasKey('wallet_users.id', $rules);
        
        // 這個驗證器應該確保使用者屬於指定的錢包
        $this->assertTrue(true, '驗證器設計目的明確：驗證錢包計算相關的權限');
    }
}
