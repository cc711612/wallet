<?php

namespace Tests\Unit\Concerns\Commons\Abstracts;

use App\Concerns\Commons\Abstracts\SupportAbstracts;
use App\Concerns\Databases\Contracts\Constants\Status;
use PHPUnit\Framework\TestCase;

class SupportAbstractsTest extends TestCase
{
    public function testGetEnableDisableRadio()
    {
        // 測試預設狀態 (DISABLE)
        $result = SupportAbstracts::getEnableDisableRadio();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey(Status::STATUS_ENABLE, $result);
        $this->assertArrayHasKey(Status::STATUS_DISABLE, $result);
        $this->assertArrayNotHasKey(Status::STATUS_DRAFT, $result);
        $this->assertArrayNotHasKey(Status::STATUS_CRON, $result);
        $this->assertArrayNotHasKey(null, $result);
        
        // 檢查預設選中的是 DISABLE
        $this->assertEquals('checked', $result[Status::STATUS_DISABLE]['checked']);
        $this->assertEquals('', $result[Status::STATUS_ENABLE]['checked']);
    }

    public function testGetEnableDisableRadioWithEnableStatus()
    {
        // 測試 ENABLE 狀態
        $result = SupportAbstracts::getEnableDisableRadio(Status::STATUS_ENABLE);
        
        $this->assertEquals('checked', $result[Status::STATUS_ENABLE]['checked']);
        $this->assertEquals('', $result[Status::STATUS_DISABLE]['checked']);
    }

    public function testGetEnableDisableRadioWithInvalidStatus()
    {
        // 測試無效狀態
        $result = SupportAbstracts::getEnableDisableRadio(999);
        
        // 應該沒有任何項目被選中
        $this->assertEquals('', $result[Status::STATUS_ENABLE]['checked']);
        $this->assertEquals('', $result[Status::STATUS_DISABLE]['checked']);
    }

    public function testGetEnableDisableDraftRadio()
    {
        $result = SupportAbstracts::getEnableDisableDraftRadio();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey(Status::STATUS_ENABLE, $result);
        $this->assertArrayHasKey(Status::STATUS_DISABLE, $result);
        $this->assertArrayHasKey(Status::STATUS_DRAFT, $result);
        $this->assertArrayNotHasKey(Status::STATUS_CRON, $result);
        $this->assertArrayNotHasKey(null, $result);
    }

    public function testGetEnableDisableDraftRadioWithDraftStatus()
    {
        $result = SupportAbstracts::getEnableDisableDraftRadio(Status::STATUS_DRAFT);
        
        $this->assertEquals('checked', $result[Status::STATUS_DRAFT]['checked']);
        $this->assertEquals('', $result[Status::STATUS_ENABLE]['checked']);
        $this->assertEquals('', $result[Status::STATUS_DISABLE]['checked']);
    }

    public function testGetEnableDisableSelect()
    {
        $result = SupportAbstracts::getEnableDisableSelect();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey(Status::STATUS_ENABLE, $result);
        $this->assertArrayHasKey(Status::STATUS_DISABLE, $result);
        $this->assertArrayHasKey(null, $result); // select 包含 null 選項
        $this->assertArrayNotHasKey(Status::STATUS_DRAFT, $result);
        $this->assertArrayNotHasKey(Status::STATUS_CRON, $result);
    }

    public function testGetEnableDisableSelectWithStatus()
    {
        $result = SupportAbstracts::getEnableDisableSelect(Status::STATUS_ENABLE);
        
        $this->assertEquals('selected', $result[Status::STATUS_ENABLE]['selected']);
        $this->assertEquals('', $result[Status::STATUS_DISABLE]['selected']);
        $this->assertEquals('', $result[null]['selected']);
    }

    public function testGetEnableDisableChecked()
    {
        $result = SupportAbstracts::getEnableDisableChecked(Status::STATUS_ENABLE);
        
        $this->assertIsArray($result);
        $this->assertEquals('checked', $result[Status::STATUS_ENABLE]['checked']);
        $this->assertEquals('selected', $result[Status::STATUS_ENABLE]['selected']);
        $this->assertEquals('', $result[Status::STATUS_DISABLE]['checked']);
    }

    public function testGetEnableDisableCheckedWithDisableStatus()
    {
        $result = SupportAbstracts::getEnableDisableChecked(Status::STATUS_DISABLE);
        
        $this->assertEquals('', $result[Status::STATUS_ENABLE]['checked']);
        $this->assertEquals('', $result[Status::STATUS_ENABLE]['selected']);
        $this->assertEquals('', $result[Status::STATUS_DISABLE]['checked']);
    }

    public function testRadioStructure()
    {
        $result = SupportAbstracts::getEnableDisableRadio();
        
        // 測試每個項目的結構
        foreach ($result as $status => $item) {
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('text', $item);
            $this->assertArrayHasKey('status_text', $item);
            $this->assertArrayHasKey('contact_text', $item);
            $this->assertArrayHasKey('span_class', $item);
            $this->assertArrayHasKey('font_class', $item);
            $this->assertArrayHasKey('checked', $item);
            $this->assertArrayHasKey('selected', $item);
            $this->assertArrayHasKey('icon_class', $item);
            $this->assertArrayHasKey('button_class', $item);
            
            $this->assertEquals('status', $item['name']);
            $this->assertEquals($status, $item['value']);
        }
    }

    public function testStatusTexts()
    {
        $result = SupportAbstracts::getEnableDisableRadio();
        
        $this->assertEquals('上架', $result[Status::STATUS_ENABLE]['text']);
        $this->assertEquals('下架', $result[Status::STATUS_DISABLE]['text']);
        
        $this->assertEquals('上架', $result[Status::STATUS_ENABLE]['status_text']);
        $this->assertEquals('下架', $result[Status::STATUS_DISABLE]['status_text']);
    }

    public function testCssClasses()
    {
        $result = SupportAbstracts::getEnableDisableRadio();
        
        // 測試 ENABLE 的 CSS classes
        $this->assertEquals('label-success', $result[Status::STATUS_ENABLE]['span_class']);
        $this->assertEquals('font-green', $result[Status::STATUS_ENABLE]['font_class']);
        $this->assertEquals('green', $result[Status::STATUS_ENABLE]['button_class']);
        $this->assertEquals('icon-check', $result[Status::STATUS_ENABLE]['icon_class']);
        
        // 測試 DISABLE 的 CSS classes
        $this->assertEquals('label-warning', $result[Status::STATUS_DISABLE]['span_class']);
        $this->assertEquals('font-red', $result[Status::STATUS_DISABLE]['font_class']);
        $this->assertEquals('red', $result[Status::STATUS_DISABLE]['button_class']);
        $this->assertEquals('icon-close', $result[Status::STATUS_DISABLE]['icon_class']);
    }
}
