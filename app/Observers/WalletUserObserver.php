<?php

namespace App\Observers;

use App\Models\Wallets\Databases\Entities\WalletUserEntity;
use App\Models\Wallets\Databases\Services\WalletApiService;
use App\Models\Wallets\Databases\Services\WalletUserApiService;
use App\Traits\Wallets\Auth\WalletUserAuthLoginTrait;
use Illuminate\Support\Carbon;

/**
 * Class WalletUserObserver
 *
 * @package App\Observers
 * @Author: Roy
 * @DateTime: 2022/7/16 下午 10:40
 */
class WalletUserObserver
{
    use WalletUserAuthLoginTrait;
    /**
     * @var \App\Models\Wallets\Databases\Services\WalletApiService
     */
    private $wallet_api_service;

    /**
     * @param  \App\Models\Wallets\Databases\Services\WalletApiService  $WalletApiService
     */
    public function __construct(WalletApiService $WalletApiService)
    {
        $this->wallet_api_service = $WalletApiService;
    }

    /**
     * @param  \App\Models\Wallets\Databases\Entities\WalletUserEntity  $WalletUserEntity
     *
     * @Author: Roy
     * @DateTime: 2022/7/16 下午 05:08
     */
    public function created(WalletUserEntity $WalletUserEntity)
    {
        //
        return $this->wallet_api_service->update(
            $WalletUserEntity->wallet_id,
            ['updated_at' => Carbon::now()->toDateTimeString()]
        );
    }

    /**
     * Handle the WalletUserEntity "updated" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletUserEntity  $WalletUserEntity
     *
     * @return void
     */
    public function updated(WalletUserEntity $WalletUserEntity)
    {
        //
        return $this->wallet_api_service->update(
            $WalletUserEntity->wallet_id,
            ['updated_at' => Carbon::now()->toDateTimeString()]
        );
    }

    /**
     * Handle the WalletUserEntity "deleted" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletUserEntity  $WalletUserEntity
     *
     * @return void
     */
    public function deleted(WalletUserEntity $WalletUserEntity)
    {
        //
        $this->wallet_api_service->update(
            $WalletUserEntity->wallet_id,
            ['updated_at' => Carbon::now()->toDateTimeString()]
        );
        app(WalletUserApiService::class)->forgetCacheByWalletUser($WalletUserEntity);
    }

    /**
     * Handle the WalletUserEntity "restored" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletUserEntity  $WalletUserEntity
     *
     * @return void
     */
    public function restored(WalletUserEntity $WalletUserEntity)
    {
        //
    }

    /**
     * Handle the WalletUserEntity "force deleted" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletUserEntity  $WalletUserEntity
     *
     * @return void
     */
    public function forceDeleted(WalletUserEntity $WalletUserEntity)
    {
        //
    }
}
