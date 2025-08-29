<?php

namespace Tests\Unit\Concerns\Databases\Contracts\Constants;

use App\Concerns\Databases\Contracts\Constants\Oauth;
use PHPUnit\Framework\TestCase;

class OauthTest extends TestCase
{
    public function testOauthConstants()
    {
        // 測試所有 OAuth 常數是否正確定義
        $this->assertEquals(1, Oauth::OAUTH_GOOGLE);
        $this->assertEquals(2, Oauth::OAUTH_FACEBOOK);
        $this->assertEquals(3, Oauth::OAUTH_LINKED_IN);
        $this->assertEquals(4, Oauth::OAUTH_TWITTER);
        $this->assertEquals(5, Oauth::OAUTH_LINE);
        $this->assertEquals(6, Oauth::OAUTH_LINE_AT);
        $this->assertEquals(7, Oauth::OAUTH_WECHAT);
        $this->assertEquals(8, Oauth::OAUTH_YAHOO);
    }

    public function testOauthConstantsAreIntegers()
    {
        // 測試所有 OAuth 常數都是整數
        $this->assertIsInt(Oauth::OAUTH_GOOGLE);
        $this->assertIsInt(Oauth::OAUTH_FACEBOOK);
        $this->assertIsInt(Oauth::OAUTH_LINKED_IN);
        $this->assertIsInt(Oauth::OAUTH_TWITTER);
        $this->assertIsInt(Oauth::OAUTH_LINE);
        $this->assertIsInt(Oauth::OAUTH_LINE_AT);
        $this->assertIsInt(Oauth::OAUTH_WECHAT);
        $this->assertIsInt(Oauth::OAUTH_YAHOO);
    }

    public function testOauthConstantsAreUnique()
    {
        // 測試所有 OAuth 常數值是否唯一
        $constants = [
            Oauth::OAUTH_GOOGLE,
            Oauth::OAUTH_FACEBOOK,
            Oauth::OAUTH_LINKED_IN,
            Oauth::OAUTH_TWITTER,
            Oauth::OAUTH_LINE,
            Oauth::OAUTH_LINE_AT,
            Oauth::OAUTH_WECHAT,
            Oauth::OAUTH_YAHOO,
        ];
        
        $uniqueConstants = array_unique($constants);
        $this->assertCount(count($constants), $uniqueConstants, 'OAuth 常數值應該是唯一的');
    }

    public function testGetAllOauthConstants()
    {
        // 測試取得所有 OAuth 常數
        $reflection = new \ReflectionClass(Oauth::class);
        $constants = $reflection->getConstants();
        
        $expectedConstants = [
            'OAUTH_GOOGLE' => 1,
            'OAUTH_FACEBOOK' => 2,
            'OAUTH_LINKED_IN' => 3,
            'OAUTH_TWITTER' => 4,
            'OAUTH_LINE' => 5,
            'OAUTH_LINE_AT' => 6,
            'OAUTH_WECHAT' => 7,
            'OAUTH_YAHOO' => 8,
        ];
        
        $this->assertEquals($expectedConstants, $constants);
    }

    public function testOauthConstantsNaming()
    {
        // 測試常數命名規則
        $reflection = new \ReflectionClass(Oauth::class);
        $constants = $reflection->getConstants();
        
        foreach (array_keys($constants) as $constantName) {
            $this->assertStringStartsWith('OAUTH_', $constantName, '所有 OAuth 常數應該以 OAUTH_ 開頭');
        }
    }

    public function testOauthConstantsArePositive()
    {
        // 測試所有 OAuth 常數都是正數
        $reflection = new \ReflectionClass(Oauth::class);
        $constants = $reflection->getConstants();
        
        foreach ($constants as $constant) {
            $this->assertGreaterThan(0, $constant, 'OAuth 常數值應該是正數');
        }
    }

    public function testOauthConstantsSequential()
    {
        // 測試 OAuth 常數是否連續（從1開始）
        $reflection = new \ReflectionClass(Oauth::class);
        $constants = $reflection->getConstants();
        $values = array_values($constants);
        sort($values);
        
        $expectedSequence = range(1, count($constants));
        $this->assertEquals($expectedSequence, $values, 'OAuth 常數應該是從1開始的連續整數');
    }

    public function testSpecificOauthProviders()
    {
        // 測試特定的 OAuth 提供者
        $socialProviders = [
            'OAUTH_GOOGLE' => Oauth::OAUTH_GOOGLE,
            'OAUTH_FACEBOOK' => Oauth::OAUTH_FACEBOOK,
            'OAUTH_TWITTER' => Oauth::OAUTH_TWITTER,
            'OAUTH_LINKED_IN' => Oauth::OAUTH_LINKED_IN,
        ];
        
        $asianProviders = [
            'OAUTH_LINE' => Oauth::OAUTH_LINE,
            'OAUTH_LINE_AT' => Oauth::OAUTH_LINE_AT,
            'OAUTH_WECHAT' => Oauth::OAUTH_WECHAT,
        ];
        
        // 檢查社群媒體提供者存在
        foreach ($socialProviders as $name => $value) {
            $this->assertNotEmpty($value, "{$name} 應該被定義");
        }
        
        // 檢查亞洲地區提供者存在
        foreach ($asianProviders as $name => $value) {
            $this->assertNotEmpty($value, "{$name} 應該被定義");
        }
    }

    public function testLineProvidersAreDifferent()
    {
        // 測試 LINE 和 LINE_AT 是不同的常數
        $this->assertNotEquals(Oauth::OAUTH_LINE, Oauth::OAUTH_LINE_AT, 'LINE 和 LINE_AT 應該是不同的常數值');
    }

    public function testConstantsCount()
    {
        // 測試常數數量
        $reflection = new \ReflectionClass(Oauth::class);
        $constants = $reflection->getConstants();
        
        $this->assertCount(8, $constants, '應該有8個 OAuth 常數');
    }
}
