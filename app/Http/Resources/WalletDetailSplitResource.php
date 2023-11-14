<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class WalletDetailSplitResource extends JsonResource
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
        return [
            'id'               => Arr::get($this->resource, 'id'),
            'wallet_detail_id' => Arr::get($this->resource, 'wallet_detail_id'),
            'wallet_user_id'   => Arr::get($this->resource, 'wallet_user_id'),
            'unit'             => Arr::get($this->resource, 'unit'),
            'value'            => Arr::get($this->resource, 'value'),
        ];
    }
}
