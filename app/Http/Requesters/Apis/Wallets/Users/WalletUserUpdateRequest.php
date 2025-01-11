<?php

namespace App\Http\Requesters\Apis\Wallets\Users;

use App\Concerns\Databases\Request;
use Illuminate\Support\Arr;

class WalletUserUpdateRequest extends Request
{
    /**
     * @return null[]
     * @Author  : Roy
     * @DateTime: 2020/12/15 下午 03:02
     */
    protected function schema(): array
    {
        return [
            'wallet_users_id'   => null,
            'wallet_users.name' => null,
            'wallet_users.id' => null,
            'wallet_users.notify_enable' => false,
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
            'wallet_users_id' => Arr::get($row, sprintf("wallet_user.%s.id", Arr::get($row, 'wallet_id'))),
            'wallet_users.name' => Arr::get($row, 'name'),
            'wallet_users.id' => Arr::get($row, 'wallet_users_id'),
            'wallet_users.notify_enable' => (bool) Arr::get($row, 'notify_enable'),
        ];
    }
}
