<?php

/**
 * @Author: Roy
 * @DateTime: 2022/6/20 下午 03:40
 */

namespace App\Models\Wallets\Databases\Services;

use App\Concerns\Databases\Service;
use Illuminate\Database\Eloquent\Model;
use App\Models\Wallets\Databases\Entities\WalletDetailSplitEntity;

class WalletDetailSplitApiService extends Service
{

    protected function getEntity(): Model
    {
        // TODO: Implement getEntity() method.
        if (app()->has(WalletDetailSplitEntity::class) === false) {
            app()->singleton(WalletDetailSplitEntity::class);
        }

        return app(WalletDetailSplitEntity::class);
    }

    /**
     * @param  int  $walletId
     * @param  array  $attributes
     *
     * @return mixed
     * @Author: Roy
     * @DateTime: 2023/11/14 下午 10:08
     */
    public function updateById(int $walletId, array $attributes)
    {
        return $this->getEntity()
            ->where('id', $walletId)
            ->update($attributes);
    }
}
