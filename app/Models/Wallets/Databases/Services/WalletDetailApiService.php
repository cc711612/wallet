<?php

/**
 * @Author: Roy
 * @DateTime: 2022/6/20 下午 03:40
 */

namespace App\Models\Wallets\Databases\Services;

use App\Concerns\Databases\Service;
use App\Models\SymbolOperationTypes\Contracts\Constants\SymbolOperationTypes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Wallets\Databases\Entities\WalletDetailEntity;
use Illuminate\Support\Facades\DB;
use App\Models\Wallets\Contracts\Constants\WalletDetailTypes;
use App\Traits\Caches\CacheTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;

class WalletDetailApiService extends Service
{
    use CacheTrait;

    protected function getEntity(): Model
    {
        // TODO: Implement getEntity() method.
        if (app()->has(WalletDetailEntity::class) === false) {
            app()->singleton(WalletDetailEntity::class);
        }

        return app(WalletDetailEntity::class);
    }

    /**
     * @return mixed|null
     * @Author: Roy
     * @DateTime: 2022/6/25 下午 05:04
     */
    public function updateWalletDetail()
    {
        if (is_null($this->getRequestByKey('walletDetails.id'))) {
            return null;
        }

        return DB::transaction(function () {
            $entity = $this->getEntity()
                ->find($this->getRequestByKey('walletDetails.id'));

            if (is_null($entity) === true) {
                return null;
            }
            # 不等於公帳
            if ($this->getRequestByKey('walletDetails.type') != WalletDetailTypes::WALLET_DETAIL_TYPE_PUBLIC_EXPENSE) {
                $users = $this->getRequestByKey('walletDetailWalletUser');
                # 全選
                if ($this->getRequestByKey('walletDetails.selectAll') == 1) {
                    $users = $entity->wallets()->first()->wallet_users()->get()->pluck('id')->toArray();
                }
                $entity->wallet_users()->sync($users);
            }

            return $entity->update($this->getRequestByKey('walletDetails'));
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed|null
     * @Author: Roy
     * @DateTime: 2022/6/25 下午 05:27
     */
    public function findDetail()
    {
        if (is_null($this->getRequestByKey('walletDetails.id')) === true) {
            return null;
        }
        $result = $this->getEntity()
            ->with([
                'wallet_users',
                'category',
            ])
            ->where('wallet_id', $this->getRequestByKey('wallets.id'))
            ->find($this->getRequestByKey('walletDetails.id'));

        return $result;
    }

    /**
     * @return bool
     * @Author: Roy
     * @DateTime: 2022/7/30 下午 07:17
     */
    public function checkoutWalletDetails(): bool
    {
        $this->forgetDetailCache($this->getRequestByKey('wallets.id'));
        return $this->getEntity()
            ->where('wallet_id', $this->getRequestByKey('wallets.id'))
            ->whereIn('id', $this->getRequestByKey('checkout.ids'))
            ->update($this->getRequestByKey('walletDetails'));
    }

    /**
     * @return bool
     * @Author: Roy
     * @DateTime: 2022/7/30 下午 07:34
     */
    public function unCheckoutWalletDetails(): bool
    {
        $this->forgetDetailCache($this->getRequestByKey('wallets.id'));
        return $this->getEntity()
            ->where('wallet_id', $this->getRequestByKey('wallets.id'))
            ->where('checkout_at', $this->getRequestByKey('checkoutAt'))
            ->update($this->getRequestByKey('walletDetails'));
    }

    /**
     * @param  int  $wallet_id
     *
     * @return \Illuminate\Support\Collection
     * @Author: Roy
     * @DateTime: 2022/9/5 下午 10:28
     */
    public function getPublicDetailByWalletId(int $walletId): Collection
    {
        return $this->getEntity()
            ->select([
                'id', 'wallet_id', 'type', 'symbol_operation_type_id', 'value',
            ])
            ->where('wallet_id', $walletId)
            ->where('type', WalletDetailTypes::WALLET_DETAIL_TYPE_PUBLIC_EXPENSE)
            ->get();
    }

    /**
     * @param  int  $wallet_id
     *
     * @return float
     * @Author: Roy
     * @DateTime: 2023/11/14 下午 09:50
     */
    public function getWalletBalance(int $walletId): float
    {
        $detailGroupBySymbol = $this->getPublicDetailByWalletId($walletId)->groupBy('symbol_operation_type_id');
        return $detailGroupBySymbol->get(
            SymbolOperationTypes::SYMBOL_OPERATION_TYPE_INCREMENT,
            collect([])
        )->sum('value')
            -
            $detailGroupBySymbol->get(SymbolOperationTypes::SYMBOL_OPERATION_TYPE_DECREMENT, collect([]))->sum('value');
    }
}
