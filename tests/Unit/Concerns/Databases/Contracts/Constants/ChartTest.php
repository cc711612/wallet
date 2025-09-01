<?php

namespace Tests\Unit\Concerns\Databases\Contracts\Constants;

use App\Concerns\Databases\Contracts\Constants\Chart;
use PHPUnit\Framework\TestCase;

class ChartTest extends TestCase
{
    public function testChartConstants()
    {
        // 測試所有圖表常數是否正確定義
        $this->assertEquals('book', Chart::CHART_BOOK);
        $this->assertEquals('course', Chart::CHART_COURSE);
        $this->assertEquals('continue', Chart::CHART_CONTINUE);
        $this->assertEquals('complete', Chart::CHART_COMPLETE);
        $this->assertEquals('knowledge', Chart::CHART_KNOWLEDGE);
        $this->assertEquals('view', Chart::CHART_VIEW);
        $this->assertEquals('login', Chart::CHART_LOGIN);
        $this->assertEquals('status', Chart::CHART_STATUS);
        $this->assertEquals('progress', Chart::CHART_PROGRESS);
        $this->assertEquals('department', Chart::CHART_DEPARTMENT);
        $this->assertEquals('member', Chart::CHART_MEMBER);
    }

    public function testChartConstantsAreStrings()
    {
        // 測試所有圖表常數都是字串
        $this->assertIsString(Chart::CHART_BOOK);
        $this->assertIsString(Chart::CHART_COURSE);
        $this->assertIsString(Chart::CHART_CONTINUE);
        $this->assertIsString(Chart::CHART_COMPLETE);
        $this->assertIsString(Chart::CHART_KNOWLEDGE);
        $this->assertIsString(Chart::CHART_VIEW);
        $this->assertIsString(Chart::CHART_LOGIN);
        $this->assertIsString(Chart::CHART_STATUS);
        $this->assertIsString(Chart::CHART_PROGRESS);
        $this->assertIsString(Chart::CHART_DEPARTMENT);
        $this->assertIsString(Chart::CHART_MEMBER);
    }

    public function testChartConstantsAreUnique()
    {
        // 測試所有圖表常數值是否唯一
        $constants = [
            Chart::CHART_BOOK,
            Chart::CHART_COURSE,
            Chart::CHART_CONTINUE,
            Chart::CHART_COMPLETE,
            Chart::CHART_KNOWLEDGE,
            Chart::CHART_VIEW,
            Chart::CHART_LOGIN,
            Chart::CHART_STATUS,
            Chart::CHART_PROGRESS,
            Chart::CHART_DEPARTMENT,
            Chart::CHART_MEMBER,
        ];
        
        $uniqueConstants = array_unique($constants);
        $this->assertCount(count($constants), $uniqueConstants, '圖表常數值應該是唯一的');
    }

    public function testGetAllChartConstants()
    {
        // 測試取得所有圖表常數
        $reflection = new \ReflectionClass(Chart::class);
        $constants = $reflection->getConstants();
        
        $expectedConstants = [
            'CHART_BOOK' => 'book',
            'CHART_COURSE' => 'course',
            'CHART_CONTINUE' => 'continue',
            'CHART_COMPLETE' => 'complete',
            'CHART_KNOWLEDGE' => 'knowledge',
            'CHART_VIEW' => 'view',
            'CHART_LOGIN' => 'login',
            'CHART_STATUS' => 'status',
            'CHART_PROGRESS' => 'progress',
            'CHART_DEPARTMENT' => 'department',
            'CHART_MEMBER' => 'member',
        ];
        
        $this->assertEquals($expectedConstants, $constants);
    }

    public function testChartConstantsNaming()
    {
        // 測試常數命名規則
        $reflection = new \ReflectionClass(Chart::class);
        $constants = $reflection->getConstants();
        
        foreach (array_keys($constants) as $constantName) {
            $this->assertStringStartsWith('CHART_', $constantName, '所有圖表常數應該以 CHART_ 開頭');
        }
    }

    public function testChartConstantsNotEmpty()
    {
        // 測試圖表常數值不為空
        $reflection = new \ReflectionClass(Chart::class);
        $constants = $reflection->getConstants();
        
        foreach ($constants as $constant) {
            $this->assertNotEmpty($constant, '圖表常數值不應為空');
        }
    }

    public function testSpecificChartTypes()
    {
        // 測試特定圖表類型的分組
        $analysisCharts = [
            Chart::CHART_CONTINUE,
            Chart::CHART_COMPLETE,
            Chart::CHART_KNOWLEDGE,
            Chart::CHART_VIEW,
            Chart::CHART_LOGIN,
            Chart::CHART_STATUS,
            Chart::CHART_PROGRESS,
        ];
        
        $entityCharts = [
            Chart::CHART_BOOK,
            Chart::CHART_COURSE,
            Chart::CHART_DEPARTMENT,
            Chart::CHART_MEMBER,
        ];
        
        // 檢查分析型圖表的存在
        foreach ($analysisCharts as $chart) {
            $this->assertNotEmpty($chart);
        }
        
        // 檢查實體型圖表的存在
        foreach ($entityCharts as $chart) {
            $this->assertNotEmpty($chart);
        }
    }
}
