<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use App\Models\Wallets\Contracts\Constants\WalletDetailTypes;
use App\Models\SymbolOperationTypes\Contracts\Constants\SymbolOperationTypes;
use Illuminate\Support\Carbon;

class OptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }

    /**
     * @return array[]
     * @Author: Roy
     * @DateTime: 2022/7/31 下午 11:52
     */
    public function exchangeRate(): array
    {
        $option = $this->resource;
        return [
            'option'     => collect(Arr::get($option, 'rates', []))->keys(),
            'rates'       => Arr::get($option, 'rates', []),
            'updated_at' => Arr::get($option, 'date', Carbon::now()->toDateString()),
        ];
    }
}
