<?php

namespace App\Observers;

use App\Models\Users\Databases\Services\UserApiService;
use App\Models\Wallets\Databases\Entities\WalletEntity;
use App\Models\Wallets\Databases\Services\WalletApiService;

/**
 * Class WalletObserver
 *
 * @package App\Observers
 * @Author: Roy
 * @DateTime: 2022/7/17 下午 01:25
 */
class WalletObserver
{
    /**
     * @var \App\Models\Wallets\Databases\Services\WalletApiService
     */
    private $wallet_api_service;

    /**
     * @param  \App\Models\Wallets\Databases\Services\WalletApiService  $walletApiService
     */
    public function __construct(WalletApiService $walletApiService)
    {
        $this->walletApiService = $walletApiService;
    }

    /**
     * @param  \App\Models\Wallets\Databases\Entities\WalletEntity  $walletEntity
     *
     * @return bool
     * @Author: Roy
     * @DateTime: 2022/7/17 下午 01:39
     */
    public function created(WalletEntity $walletEntity)
    {
        app(UserApiService::class)->forgetFindCache($walletEntity->user_id);
    }

    /**
     * Handle the WalletEntity "updated" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletEntity  $walletEntity
     *
     * @return void
     */
    public function updated(WalletEntity $walletEntity)
    {
        //
        return $this->walletApiService->forgetDetailCache($walletEntity->id);
    }

    /**
     * Handle the WalletEntity "deleted" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletEntity  $walletEntity
     *
     * @return void
     */
    public function deleted(WalletEntity $walletEntity)
    {
        //
    }

    /**
     * Handle the WalletEntity "restored" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletEntity  $walletEntity
     *
     * @return void
     */
    public function restored(WalletEntity $walletEntity)
    {
        //
    }

    /**
     * Handle the WalletEntity "force deleted" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletEntity  $walletEntity
     *
     * @return void
     */
    public function forceDeleted(WalletEntity $walletEntity)
    {
        //
    }
}
