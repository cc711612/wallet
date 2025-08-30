<?php

namespace Tests\Unit\Http\Validators\Apis\Wallets\Details;

use App\Http\Validators\Apis\Wallets\Details\WalletDetailStoreValidator;
use App\Concerns\Databases\Contracts\Request;
use PHPUnit\Framework\TestCase;
use Mockery;

class WalletDetailStoreValidatorTest extends TestCase
{
    protected $mockRequest;
    protected $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRequest = Mockery::mock(Request::class);
        $this->validator = new WalletDetailStoreValidator($this->mockRequest);
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
        
        // 檢查必要的欄位存在
        $expectedFields = [
            'wallets.id',
            'wallet_users.id',
            'wallet_details.category_id',
            'wallet_details.type',
            'wallet_details.symbol_operation_type_id',
            'wallet_details.title',
            'wallet_details.value',
            'wallet_details.select_all',
            'wallet_details.created_by',
        ];
        
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $rules, "欄位 $field 應該存在於規則中");
        }
    }

    public function testRequiredFields()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('rules');
        $method->setAccessible(true);
        
        $rules = $method->invoke($this->validator);
        
        // 檢查必填欄位
        $requiredFields = [
            'wallets.id',
            'wallet_users.id',
            'wallet_details.type',
            'wallet_details.symbol_operation_type_id',
            'wallet_details.title',
            'wallet_details.value',
            'wallet_details.select_all',
            'wallet_details.created_by',
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertContains('required', $rules[$field], "欄位 $field 應該是必填的");
        }
    }

    public function testOptionalFields()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('rules');
        $method->setAccessible(true);
        
        $rules = $method->invoke($this->validator);
        
        // 檢查可選欄位
        $this->assertContains('sometimes', $rules['wallet_details.category_id']);
        $this->assertContains('nullable', $rules['wallet_details.category_id']);
    }

    public function testNumericValidation()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('rules');
        $method->setAccessible(true);
        
        $rules = $method->invoke($this->validator);
        
        // 檢查數值驗證
        $this->assertContains('numeric', $rules['wallet_details.value']);
    }

    public function testExistsValidation()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('rules');
        $method->setAccessible(true);
        
        $rules = $method->invoke($this->validator);
        
        // 檢查 exists 驗證（簡化檢查，因為 Rule::exists 會回傳物件）
        $this->assertContains('exists:wallet_users,id', $rules['wallet_users.id']);
    }

    public function testMessagesMethod()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);
        
        $messages = $method->invoke($this->validator);
        
        $this->assertIsArray($messages);
        
        // 檢查使用者友善的錯誤訊息
        $this->assertEquals('標題 為必填', $messages['wallet_details.title.required']);
        $this->assertEquals('金額 為必填', $messages['wallet_details.value.required']);
        
        // 檢查系統錯誤訊息
        $this->assertStringContainsString('請重新整理', $messages['wallets.id.required']);
        $this->assertStringContainsString('請重新整理', $messages['wallet_users.id.required']);
    }

    public function testSystemErrorMessages()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);
        
        $messages = $method->invoke($this->validator);
        
        // 檢查系統相關欄位的錯誤訊息都包含「請重新整理」或「系統錯誤」
        $systemFields = [
            'wallets.id.required',
            'wallets.id.exists',
            'wallet_users.id.required',
            'wallet_users.id.exists',
            'wallet_details.type.required',
            'wallet_details.type.in',
            'wallet_details.symbol_operation_type_id.required',
            'wallet_details.symbol_operation_type_id.in',
            'wallet_details.select_all.required',
            'wallet_details.select_all.in',
            'wallet_details.created_by.required',
            'wallet_details.created_by.integer',
        ];
        
        foreach ($systemFields as $field) {
            $message = $messages[$field];
            $this->assertTrue(
                strpos($message, '請重新整理') !== false || strpos($message, '系統錯誤') !== false,
                "欄位 $field 的訊息應該包含系統錯誤提示: $message"
            );
        }
    }

    public function testUserFriendlyMessages()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);
        
        $messages = $method->invoke($this->validator);
        
        // 檢查使用者友善的錯誤訊息
        $userFriendlyFields = [
            'wallet_details.title.required' => '標題 為必填',
            'wallet_details.value.required' => '金額 為必填',
        ];
        
        foreach ($userFriendlyFields as $field => $expectedMessage) {
            $this->assertEquals($expectedMessage, $messages[$field]);
        }
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(WalletDetailStoreValidator::class, $this->validator);
        
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

    public function testNestedValidationStructure()
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('rules');
        $method->setAccessible(true);
        
        $rules = $method->invoke($this->validator);
        
        // 檢查巢狀驗證結構
        $expectedPrefixes = ['wallets.', 'wallet_users.', 'wallet_details.'];
        
        foreach (array_keys($rules) as $field) {
            $hasValidPrefix = false;
            foreach ($expectedPrefixes as $prefix) {
                if (strpos($field, $prefix) === 0) {
                    $hasValidPrefix = true;
                    break;
                }
            }
            $this->assertTrue($hasValidPrefix, "欄位 $field 應該有有效的前綴");
        }
    }
}
