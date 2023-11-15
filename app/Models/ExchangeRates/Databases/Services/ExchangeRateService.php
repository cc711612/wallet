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
        $result = json_decode($this->get(config('services.exchangeRate.domain')), 1);
        $exchangeRateEntity = (new ExchangeRateEntity());
        $checkDate = Arr::get($result, 'date', Carbon::now()->toDateString());
//        $check = $exchangeRateEntity
//            ->where('date', $checkDate)
//            ->count();
        $rates = Arr::get($result, 'rates', []);
        $baseUnit = 'TWD';
//        if (count($rates) != $check) {
            foreach ($rates as $unit => $rate) {
                $exchangeRateEntity::updateOrCreate([
                    'date'          => $checkDate,
                    'from_currency' => $baseUnit,
                    'to_currency'   => $unit,
                ], [
                    'date'          => $checkDate,
                    'from_currency' => $baseUnit,
                    'to_currency'   => $unit,
                    'rate'          => $rate,
                ]);
            }
//        }
    }

    public function get($url): string
    {
        return Http::get($url)->body();
    }
}
