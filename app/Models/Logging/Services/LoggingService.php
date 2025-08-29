<?php

namespace App\Models\Logging\Services;

use Illuminate\Support\Facades\Config;

class LoggingService
{
    public static function setLoggingConfig(?string $prefix = null, ?string $deviceUuid = null, ?string $requestUuid = null)
    {
        self::setPrefix($prefix);
        self::setDeviceUuid($deviceUuid);
        self::setRequestUuid($requestUuid);
    }

    public static function setPrefix(string $prefix = null)
    {
        Config::set('logging.extend.prefix', $prefix);
    }

    public static function setDeviceUuid(string $uuid = null)
    {
        Config::set('logging.extend.device_uuid', $uuid);
    }

    public static function setRequestUuid(string $uuid = null)
    {
        Config::set('logging.extend.request_uuid', $uuid);
    }

    public static function getLoggerExtend(): array
    {
        return [
            'prefix' => config('logging.extend.prefix') ?: 'UNKNOWN',
            'deviceUuid' => config('logging.extend.device_uuid'),
            'requestUuid' => config('logging.extend.request_uuid'),
        ];
    }
}
