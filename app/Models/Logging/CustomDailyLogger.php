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
            // 分離 Log 檔
            $currentUserInfo = 'linux' === config('ocard.server_os_name', 'linux')
                ? posix_getpwuid(posix_geteuid())
                : ['uid' => 0, 'name' => get_current_user()];
            $currentUserId = $currentUserInfo['uid'];
            $currentUser = $currentUserInfo['name'];
            $sapi = php_sapi_name();
            $handler->setFilenameFormat("{filename}-$currentUserId-$currentUser-$sapi-{date}", 'Y-m-d');

            // 內容格式
            $handler->setFormatter(new CustomFormatter());
        }
    }
}
