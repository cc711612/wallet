<?php

namespace App\Models\Logging;

use App\Models\Logging\Formatter\CustomFormatter;
use Illuminate\Log\Logger;

class CustomDailyLogger
{
    /**
     * Customize the given logger instance.
     */
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            try {
                // 分離 Log 檔
                $currentUserInfo = 'linux' === config('ocard.server_os_name', 'linux')
                    ? posix_getpwuid(posix_geteuid())
                    : ['uid' => 0, 'name' => get_current_user()];
                
                if (!$currentUserInfo) {
                    // 如果無法取得使用者資訊，使用預設格式
                    $currentUserInfo = ['uid' => 0, 'name' => 'unknown'];
                }
                
                $currentUserId = $currentUserInfo['uid'];
                $currentUser = $currentUserInfo['name'];
                $sapi = php_sapi_name();
                
                // 嘗試設定自訂檔名格式
                if (method_exists($handler, 'setFilenameFormat')) {
                    $handler->setFilenameFormat("{filename}-$currentUserId-$currentUser-$sapi-{date}", 'Y-m-d');
                }

                // 內容格式
                $handler->setFormatter(new CustomFormatter());
            } catch (\Exception $e) {
                // 如果發生錯誤，記錄錯誤但不中斷應用程式
                error_log("CustomDailyLogger error: " . $e->getMessage());
                
                // 至少設定自訂格式化器
                try {
                    $handler->setFormatter(new CustomFormatter());
                } catch (\Exception $formatterError) {
                    // 如果連格式化器都失敗，就使用預設的
                    error_log("CustomFormatter error: " . $formatterError->getMessage());
                }
            }
        }
    }
}
