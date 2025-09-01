<?php

namespace Tests\Unit\Concerns\Databases\Contracts\Constants;

use App\Concerns\Databases\Contracts\Constants\Status;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    public function testStatusConstants()
    {
        // 測試所有狀態常數是否正確定義
        $this->assertEquals(101, Status::STATUS_ARCHIVE);
        $this->assertEquals(201, Status::STATUS_DISABLE);
        $this->assertEquals(202, Status::STATUS_DRAFT);
        $this->assertEquals(301, Status::STATUS_ENABLE);
        $this->assertEquals(302, Status::STATUS_CRON);
    }

    public function testStatusConstantsAreUnique()
    {
        // 測試所有狀態常數值是否唯一
        $constants = [
            Status::STATUS_ARCHIVE,
            Status::STATUS_DISABLE,
            Status::STATUS_DRAFT,
            Status::STATUS_ENABLE,
            Status::STATUS_CRON,
        ];
        
        $uniqueConstants = array_unique($constants);
        $this->assertCount(count($constants), $uniqueConstants, '狀態常數值應該是唯一的');
    }

    public function testStatusConstantsAreIntegers()
    {
        // 測試所有狀態常數都是整數
        $this->assertIsInt(Status::STATUS_ARCHIVE);
        $this->assertIsInt(Status::STATUS_DISABLE);
        $this->assertIsInt(Status::STATUS_DRAFT);
        $this->assertIsInt(Status::STATUS_ENABLE);
        $this->assertIsInt(Status::STATUS_CRON);
    }

    public function testStatusConstantsGrouping()
    {
        // 測試狀態常數的分組邏輯
        
        // 100-199: 封存相關
        $this->assertTrue(Status::STATUS_ARCHIVE >= 100 && Status::STATUS_ARCHIVE < 200);
        
        // 200-299: 停用/草稿相關
        $this->assertTrue(Status::STATUS_DISABLE >= 200 && Status::STATUS_DISABLE < 300);
        $this->assertTrue(Status::STATUS_DRAFT >= 200 && Status::STATUS_DRAFT < 300);
        
        // 300-399: 啟用相關
        $this->assertTrue(Status::STATUS_ENABLE >= 300 && Status::STATUS_ENABLE < 400);
        $this->assertTrue(Status::STATUS_CRON >= 300 && Status::STATUS_CRON < 400);
    }

    public function testGetAllStatusConstants()
    {
        // 測試取得所有狀態常數
        $reflection = new \ReflectionClass(Status::class);
        $constants = $reflection->getConstants();
        
        $expectedConstants = [
            'STATUS_ARCHIVE' => 101,
            'STATUS_DISABLE' => 201,
            'STATUS_DRAFT' => 202,
            'STATUS_ENABLE' => 301,
            'STATUS_CRON' => 302,
        ];
        
        $this->assertEquals($expectedConstants, $constants);
    }

    public function testStatusConstantsNaming()
    {
        // 測試常數命名規則
        $reflection = new \ReflectionClass(Status::class);
        $constants = $reflection->getConstants();
        
        foreach (array_keys($constants) as $constantName) {
            $this->assertStringStartsWith('STATUS_', $constantName, '所有狀態常數應該以 STATUS_ 開頭');
        }
    }
}
