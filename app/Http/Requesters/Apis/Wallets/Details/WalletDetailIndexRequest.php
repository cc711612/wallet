<?php

namespace App\Http\Requesters\Apis\Wallets\Details;

use App\Concerns\Databases\Request;
use Illuminate\Support\Arr;

class WalletDetailIndexRequest extends Request
{
    /**
     * @return null[]
     * @Author  : Roy
     * @DateTime: 2020/12/15 下午 03:02
     */
    protected function schema(): array
    {
        return [
            'wallets.id'      => null,
            'wallet_users.id' => null,
            'wallet_details.is_personal' => 0,
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
            'wallets.id'      => Arr::get($row, 'wallet'),
            'wallet_users.id' => Arr::get($row, sprintf("wallet_user.%s.id", Arr::get($row, 'wallet'))),
            'wallet_details.is_personal' => Arr::get($row, 'is_personal'),
        ];
    }
}
