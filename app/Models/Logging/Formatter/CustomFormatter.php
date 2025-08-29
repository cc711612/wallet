<?php

namespace App\Models\Logging\Formatter;

use App\Models\Logging\Services\LoggingService;
use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

class CustomFormatter extends LineFormatter
{
    public const SIMPLE_DATE = "Y-m-d H:i:s";

    protected bool $includeStacktraces = true;

    /**
     * @param string $format The format of the message
     */
    public function __construct($format = null)
    {
        $this->format = $format ?: '[%datetime%] [' . getmypid() . "] [%prefix%] (%index%) %channel%.%level_name%: %message% %context% %extra%\n";

        parent::__construct($this->format, null, true, true);
    }

    public function format(LogRecord $record): string
    {
        $output = parent::format($record);

        static $index = 0;

        $vars = array_merge(
            [
                'index' => ++$index,
            ],
            LoggingService::getLoggerExtend()
        );

        foreach ($vars as $key => $var) {
            $output = str_replace('%' . $key . '%', $var, $output);
        }

        return $output;
    }
}
