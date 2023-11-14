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
        if (is_null($this->getRequestByKey('wallet_details.id'))) {
            return null;
        }

        return DB::transaction(function () {
            $Entity = $this->getEntity()
                ->find($this->getRequestByKey('wallet_details.id'));

            if (is_null($Entity) === true) {
                return null;
            }
            # 不等於公帳
            if ($this->getRequestByKey('wallet_details.type') != WalletDetailTypes::WALLET_DETAIL_TYPE_PUBLIC_EXPENSE) {
                $Users = $this->getRequestByKey('wallet_detail_wallet_user');
                # 全選
                if ($this->getRequestByKey('wallet_details.select_all') == 1) {
                    $Users = $Entity->wallets()->first()->wallet_users()->get()->pluck('id')->toArray();
                }
                $Entity->wallet_users()->sync($Users);
                $walletDetailSplitApiService = app(WalletDetailSplitApiService::class);
                # 自定義分帳
                foreach ($this->getRequestByKey('wallet_detail_splits') as $walletDetailSplit) {
                    $walletDetailSplitApiService->updateById(
                        $walletDetailSplit['id'],
                        Arr::except($walletDetailSplit,['id'])
                    );
                }
            }
            return $Entity->update($this->getRequestByKey('wallet_details'));
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed|null
     * @Author: Roy
     * @DateTime: 2022/6/25 下午 05:27
     */
    public function findDetail()
    {
        if (is_null($this->getRequestByKey('wallet_details.id')) === true) {
            return null;
        }
        return $this->getEntity()
            ->with([
                'wallet_users',
                'wallet_detail_splits',
            ])
            ->where('wallet_id', $this->getRequestByKey('wallets.id'))
            ->find($this->getRequestByKey('wallet_details.id'));
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
            ->update($this->getRequestByKey('wallet_details'));
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
            ->where('checkout_at', $this->getRequestByKey('checkout_at'))
            ->update($this->getRequestByKey('wallet_details'));
    }

    /**
     * @param  int  $wallet_id
     *
     * @return \Illuminate\Support\Collection
     * @Author: Roy
     * @DateTime: 2022/9/5 下午 10:28
     */
    public function getPublicDetailByWalletId(int $wallet_id): Collection
    {
        return $this->getEntity()
            ->select([
                'id', 'wallet_id', 'type', 'symbol_operation_type_id', 'value',
            ])
            ->where('wallet_id', $wallet_id)
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
    public function getWalletBalance(int $wallet_id): float
    {
        $DetailGroupBySymbol = $this->getPublicDetailByWalletId($wallet_id)->groupBy('symbol_operation_type_id');
        return $DetailGroupBySymbol->get(SymbolOperationTypes::SYMBOL_OPERATION_TYPE_INCREMENT,
                collect([]))->sum('value')
            -
            $DetailGroupBySymbol->get(SymbolOperationTypes::SYMBOL_OPERATION_TYPE_DECREMENT, collect([]))->sum('value');
    }
}
