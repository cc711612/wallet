<?php

namespace App\Observers;

use App\Models\Users\Databases\Services\UserApiService;
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
    private $walletApiService;

    /**
     * @param  \App\Models\Wallets\Databases\Services\WalletApiService  $walletApiService
     */
    public function __construct(WalletApiService $walletApiService)
    {
        $this->walletApiService = $walletApiService;
    }

    /**
     * @param  \App\Models\Wallets\Databases\Entities\WalletUserEntity  $walletUserEntity
     *
     * @Author: Roy
     * @DateTime: 2022/7/16 下午 05:08
     */
    public function created(WalletUserEntity $walletUserEntity)
    {
        $this->walletApiService->forgetDetailCache($walletUserEntity->wallet_id);
        return $this->walletApiService->update(
            $walletUserEntity->wallet_id,
            ['updated_at' => Carbon::now()->toDateTimeString()]
        );
    }

    /**
     * Handle the WalletUserEntity "updated" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletUserEntity  $walletUserEntity
     *
     * @return void
     */
    public function updated(WalletUserEntity $walletUserEntity)
    {
        if ($walletUserEntity->deleted_at) {
            app(WalletUserApiService::class)->forgetCacheByWalletUser($walletUserEntity);
        }

        $this->forgetWalletUsersCache($walletUserEntity);
        $this->walletApiService->forgetDetailCache($walletUserEntity->wallet_id);

        return $this->walletApiService->update(
            $walletUserEntity->wallet_id,
            ['updated_at' => Carbon::now()->toDateTimeString()]
        );
    }

    /**
     * Handle the WalletUserEntity "deleted" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletUserEntity  $walletUserEntity
     *
     * @return void
     */
    public function deleted(WalletUserEntity $walletUserEntity)
    {
        $this->walletApiService->forgetDetailCache($walletUserEntity->wallet_id);
        $this->walletApiService->update(
            $walletUserEntity->wallet_id,
            ['updated_at' => Carbon::now()->toDateTimeString()]
        );
        app(WalletUserApiService::class)->forgetCacheByWalletUser($walletUserEntity);
        $this->forgetWalletUsersCache($walletUserEntity);
    }

    /**
     * Handle the WalletUserEntity "restored" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletUserEntity  $walletUserEntity
     *
     * @return void
     */
    public function restored(WalletUserEntity $walletUserEntity)
    {
        //
    }

    /**
     * Handle the WalletUserEntity "force deleted" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletUserEntity  $walletUserEntity
     *
     * @return void
     */
    public function forceDeleted(WalletUserEntity $walletUserEntity)
    {
        //
    }

    public function forgetWalletUsersCache(WalletUserEntity $walletUserEntity)
    {
        $wallet = $walletUserEntity->wallets()->first();
        if ($wallet) {
            app(WalletApiService::class)->forgetWalletUsersCache($wallet->code);
        }
        app(UserApiService::class)->forgetFindCache($walletUserEntity->user_id);
    }
}
