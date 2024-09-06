<?php
/**
 * @Author: Roy
 * @DateTime: 2022/6/20 下午 03:40
 */

namespace App\Models\Wallets\Databases\Services;

use App\Concerns\Databases\Service;
use Illuminate\Database\Eloquent\Model;
use App\Models\Wallets\Databases\Entities\WalletEntity;
use App\Models\Wallets\Databases\Entities\WalletDetailEntity;
use Illuminate\Support\Facades\DB;
use App\Models\Wallets\Contracts\Constants\WalletDetailTypes;
use Illuminate\Support\Arr;

class WalletDetailJobService extends Service
{
    protected function getEntity(): Model
    {
        // TODO: Implement getEntity() method.
        if (app()->has(WalletDetailEntity::class) === false) {
            app()->singleton(WalletDetailEntity::class);
        }

        return app(WalletDetailEntity::class);
    }

    /**
     * @param $walletId
     *
     * @return mixed
     * @throws \Throwable
     * @Author: Roy
     * @DateTime: 2022/7/4 下午 11:22
     */
    public function updateAllSelectedWalletDetails($walletId)
    {
        return DB::transaction(function () use ($walletId) {
            $walletUsers = (new WalletUserApiService())
                ->getWalletUserByWalletId($walletId)->pluck('id')->toArray();
            $details = $this->getEntity()
                ->where('wallet_id', $walletId)
                ->where('select_all', 1)
                ->get();
            return $details->map(function (WalletDetailEntity $detail) use ($walletUsers) {
                return $detail->wallet_users()->sync($walletUsers);
            });
        });
    }
}
