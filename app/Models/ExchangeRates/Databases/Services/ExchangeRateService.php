<?php

/**
 * @Author: Roy
 * @DateTime: 2023/7/25 下午 11:44
 */

namespace App\Models\ExchangeRates\Databases\Services;

use App\Concerns\Databases\Service;
use Illuminate\Database\Eloquent\Model;
use App\Models\ExchangeRates\Databases\Entities\ExchangeRateEntity;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class ExchangeRateService extends Service
{
    protected function getEntity(): Model
    {
        // TODO: Implement getEntity() method.
        if (app()->has(ExchangeRateEntity::class) === false) {
            app()->singleton(ExchangeRateEntity::class);
        }

        return app(ExchangeRateEntity::class);
    }

    public function setExchangeRate(): void
    {
        $units = ['TWD', 'USD'];
        foreach ($units as $unit) {
            $result = json_decode($this->get(config('services.exchangeRate.domain') . 'TWD'), 1);
            $exchangeRateEntity = (new ExchangeRateEntity());
            $checkDate = Arr::get($result, 'date', Carbon::now()->toDateString());
            $rates = Arr::get($result, 'rates', []);
            $unit = 'TWD';
            foreach ($rates as $unit => $rate) {
                $exchangeRateEntity::updateOrCreate([
                    'date'          => $checkDate,
                    'from_currency' => $unit,
                    'to_currency'   => $unit,
                    'rate'          => $rate,
                ], [
                    'date'          => $checkDate,
                    'from_currency' => $unit,
                    'to_currency'   => $unit,
                    'rate'          => $rate,
                ]);
            }
        }
    }

    public function get($url): string
    {
        return Http::get($url)->body();
    }

    public function isExistExchangeRateByCurrencyAndDate($fromCurrency, $date): bool
    {
        return $this->getEntity()
            ->where('from_currency', $fromCurrency)
            ->where('date', $date)
            ->exists();
    }

    public function getHistoryExchangeRateByCurrencyAndDate($fromCurrency, $date)
    {
        $baseUrl = config('services.exchangeRate.history');
        $url = str_replace([
            '{APIKEY}',
            '{UNIT}',
            '{DATESTRING}'
        ], [
            config('services.exchangeRate.apiKey'),
            $fromCurrency,
            $date
        ], $baseUrl);

        return json_decode($this->get($url), 1);
    }

    public function updateHistoryByHistoryResult(array $historyResult)
    {
        $exchangeRateEntity = resolve(ExchangeRateEntity::class);
        $currency = Arr::get($historyResult, 'base_code');
        if (Arr::get($historyResult, 'result') != 'success') {
            return;
        }
        $date = Carbon::parse(
            Arr::get($historyResult, 'year') . '/' .
                Arr::get($historyResult, 'month') . '/' .
                Arr::get($historyResult, 'day')
        )->format('Y-m-d');
        $rates = Arr::get($historyResult, 'conversion_rates', []);

        foreach ($rates as $toCurrency => $rate) {
            $exchangeRateEntity::updateOrCreate([
                'date'          => $date,
                'from_currency' => $currency,
                'to_currency'   => $toCurrency,
                'rate' => $rate,
            ], [
                'date'          => $date,
                'from_currency' => $currency,
                'to_currency'   => $toCurrency,
                'rate' => $rate,
            ]);
        }
        unset($rates);
    }

    /**
     * @param array $date
     * @param string $fromCurrency
     * @return Collection
     */
    public function getExchangeRateByCurrencyAndDate($dates, $fromCurrency = 'USD'): Collection
    {
        return $this->getEntity()
            ->where('from_currency', $fromCurrency)
            ->whereIn('date', $dates)
            ->get()
            ->groupBy('date');
    }
}
