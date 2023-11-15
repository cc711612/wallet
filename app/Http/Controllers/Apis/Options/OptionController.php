<?php
/**
 * @Author: Roy
 * @DateTime: 2023/7/25 下午 11:04
 */

namespace App\Http\Controllers\Apis\Options;

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\OptionResource;


class OptionController extends ApiController
{
    public function exchangeRate()
    {
        return $this->response()->success(
            (new OptionResource(json_decode($this->get(config('services.exchangeRate.domain')), 1)))
                ->exchangeRate()
        );
    }

    public function get($url): string
    {
        return Http::get($url)->body();
    }
}
