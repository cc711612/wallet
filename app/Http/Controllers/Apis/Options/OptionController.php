<?php

/**
 * @Author: Roy
 * @DateTime: 2023/7/25 下午 11:04
 */

namespace App\Http\Controllers\Apis\Options;

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\OptionResource;
use App\Models\Categories\Entities\CategoryEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OptionController extends ApiController
{
    public function exchangeRate(Request $request)
    {
        $options = [
            "TWD",
            "AED",
            "AFN",
            "ALL",
            "AMD",
            "ANG",
            "AOA",
            "ARS",
            "AUD",
            "AWG",
            "AZN",
            "BAM",
            "BBD",
            "BDT",
            "BGN",
            "BHD",
            "BIF",
            "BMD",
            "BND",
            "BOB",
            "BRL",
            "BSD",
            "BTN",
            "BWP",
            "BYN",
            "BZD",
            "CAD",
            "CDF",
            "CHF",
            "CLP",
            "CNY",
            "COP",
            "CRC",
            "CUP",
            "CVE",
            "CZK",
            "DJF",
            "DKK",
            "DOP",
            "DZD",
            "EGP",
            "ERN",
            "ETB",
            "EUR",
            "FJD",
            "FKP",
            "FOK",
            "GBP",
            "GEL",
            "GGP",
            "GHS",
            "GIP",
            "GMD",
            "GNF",
            "GTQ",
            "GYD",
            "HKD",
            "HNL",
            "HRK",
            "HTG",
            "HUF",
            "IDR",
            "ILS",
            "IMP",
            "INR",
            "IQD",
            "IRR",
            "ISK",
            "JEP",
            "JMD",
            "JOD",
            "JPY",
            "KES",
            "KGS",
            "KHR",
            "KID",
            "KMF",
            "KRW",
            "KWD",
            "KYD",
            "KZT",
            "LAK",
            "LBP",
            "LKR",
            "LRD",
            "LSL",
            "LYD",
            "MAD",
            "MDL",
            "MGA",
            "MKD",
            "MMK",
            "MNT",
            "MOP",
            "MRU",
            "MUR",
            "MVR",
            "MWK",
            "MXN",
            "MYR",
            "MZN",
            "NAD",
            "NGN",
            "NIO",
            "NOK",
            "NPR",
            "NZD",
            "OMR",
            "PAB",
            "PEN",
            "PGK",
            "PHP",
            "PKR",
            "PLN",
            "PYG",
            "QAR",
            "RON",
            "RSD",
            "RUB",
            "RWF",
            "SAR",
            "SBD",
            "SCR",
            "SDG",
            "SEK",
            "SGD",
            "SHP",
            "SLE",
            "SLL",
            "SOS",
            "SRD",
            "SSP",
            "STN",
            "SYP",
            "SZL",
            "THB",
            "TJS",
            "TMT",
            "TND",
            "TOP",
            "TRY",
            "TTD",
            "TVD",
            "TZS",
            "UAH",
            "UGX",
            "USD",
            "UYU",
            "UZS",
            "VES",
            "VND",
            "VUV",
            "WST",
            "XAF",
            "XCD",
            "XDR",
            "XOF",
            "XPF",
            "YER",
            "ZAR",
            "ZMW",
            "ZWL"
        ];
        $unit = $request->get('unit', 'TWD');
        if (!in_array($unit, $options)) {
            return $this->response()->errorBadRequest('unit not found', 404);
        }
        $cacheKey = 'exchangeRate_' . now()->format('Ymd') . '_' . $unit;
        if (Cache::has($cacheKey)) {
            return $this->response()->success(
                (new OptionResource(Cache::get($cacheKey)))
                    ->exchangeRate()
            );
        }
        $exchangeRate = json_decode($this->get(config('services.exchangeRate.domain') . $unit), 1);
        Cache::put($cacheKey, $exchangeRate, now()->addHours(1));
        return $this->response()->success(
            (new OptionResource($exchangeRate))
                ->exchangeRate()
        );
    }

    public function get($url): string
    {
        return Http::get($url)->body();
    }

    public function category()
    {
        $cacheKey = 'category';
        if (Cache::has($cacheKey)) {
            return $this->response()->success(Cache::get($cacheKey));
        }
        $category = CategoryEntity::get();
        Cache::put($cacheKey, $category, now()->addHours(1));
        return $this->response()->success($category);
    }
}
