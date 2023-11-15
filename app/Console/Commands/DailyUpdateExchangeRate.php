<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExchangeRates\Databases\Services\ExchangeRateService;

class DailyUpdateExchangeRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:daily_update_exchange_rate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '執行 每小時更新匯率';

    /**
     * @var \App\Models\ExchangeRates\Databases\Services\ExchangeRateService
     */
    protected $exchangeRateService;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
        parent::__construct();
    }

    public function handle()
    {
        $this->exchangeRateService->setExchangeRate();
    }
}
