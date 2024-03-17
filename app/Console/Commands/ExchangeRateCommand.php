<?php

namespace App\Console\Commands;

use App\Models\ExchangeRates\Databases\Services\ExchangeRateService;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class WalletDetailSplitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:exchange-rate {--start_date= : 指定 start_date } {--end_date= : 指定 end_date } {--currency= : 指定 currency}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '執行 補 匯率';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info(memory_get_usage());
        $startDate = $this->option('start_date') ?  Carbon::parse($this->option('start_date')) : now();
        $endDate = $this->option('end_date') ?  Carbon::parse($this->option('end_date')) : now();
        $this->info('開始日期: ' . $startDate);
        $this->info('結束日期: ' . $endDate);
        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = $period->toArray();
        if ($currency = $this->option('currency')) {
            $this->info('幣別: ' . $currency);
        } else {
            $currency = 'USD';
            $this->info('幣別: USD');
        }
        /**
         * @var ExchangeRateService $exchangeRateService
         */
        $exchangeRateService = app(ExchangeRateService::class);

        foreach ($dates as $date) {
            $date = $date->format('Y/m/d');
            if ($exchangeRateService->isExistExchangeRateByCurrencyAndDate($currency, $date)) {
                $this->info('已存在匯率 ' . $date);
                continue;
            }
            $this->info('Empty:' . memory_get_usage());
            $historyExchangeRates = $exchangeRateService->getHistoryExchangeRateByCurrencyAndDate($currency, $date);
            if (!empty($historyExchangeRates)) {
                $exchangeRateService->updateHistoryByHistoryResult($historyExchangeRates);
                unset($historyExchangeRates);
                $this->info('更新匯率 ' . $date);
            }
        }
        $this->info('done');
    }
}
