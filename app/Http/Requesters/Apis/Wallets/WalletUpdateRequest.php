<?php

namespace App\Http\Requesters\Apis\Wallets;

use App\Concerns\Databases\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class WalletUpdateRequest extends Request
{
    /**
     * @return null[]
     * @Author  : Roy
     * @DateTime: 2020/12/15 下午 03:02
     */
    protected function schema(): array
    {
        return [
            'users.id'        => null,
            'wallets.id'      => null,
            'wallets.user_id' => null,
            'wallets.title'   => null,
            'wallets.properties.unitConfigurable' => false,
            'wallets.properties.decimalPlaces' => false,
            'wallets.unit'    => 'TWD',
            'wallets.status'  => 1,
        ];
    }

    /**
     * @param $row
     *
     * @return array
     * @Author  : Roy
     * @DateTime: 2020/12/15 下午 03:02
     */
    protected function map($row): array
    {
        return [
            'users.id'        => Arr::get($row, 'user.id'),
            'wallets.id'      => Arr::get($row, 'wallet'),
            'wallets.user_id' => Arr::get($row, 'user.id'),
            'wallets.title'   => Arr::get($row, 'title'),
            'wallets.unit'    => Arr::get($row, 'unit'),
            'wallets.properties.unitConfigurable' => Arr::get($row, 'unitConfigurable'),
            'wallets.properties.decimalPlaces' => Arr::get($row, 'decimalPlaces'),
            'wallets.status'  => Arr::get($row, 'status'),
        ];
    }
}
