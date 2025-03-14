<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class WalletUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }

    /**
     * @param $request
     *
     * @Author: Roy
     * @DateTime: 2022/7/31 下午 01:49
     */
    public function index()
    {
        return [
            'wallet' => [
                'users' => $this->resource->wallet_users->map(function ($user) {
                    return [
                        'id'       => Arr::get($user, 'id'),
                        'name'     => Arr::get($user, 'name'),
                        'user_id'  => Arr::get($user, 'user_id'),
                        'is_admin' => Arr::get($user, 'is_admin') ? true : false,
                        'notify_enable' => Arr::get($user, 'notify_enable') ? true : false,
                    ];
                }),
            ],
        ];
    }
}
