<?php

namespace App\Observers;

use App\Jobs\LineNotifyJob;
use App\Models\Wallets\Databases\Entities\WalletDetailEntity;
use App\Models\Wallets\Databases\Entities\WalletEntity;
use App\Models\Wallets\Databases\Entities\WalletUserEntity;
use App\Models\Wallets\Databases\Services\WalletApiService;
use App\Notifications\LineNotify;
use Illuminate\Support\Carbon;

/**
 * Class WalletDetailObserver
 *
 * @package App\Observers
 * @Author: Roy
 * @DateTime: 2022/7/17 下午 01:25
 */
class WalletDetailObserver
{
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
     * @param  \App\Models\Wallets\Databases\Entities\WalletDetailEntity  $walletDetailEntity
     *
     * @return bool
     * @Author: Roy
     * @DateTime: 2022/7/17 下午 01:39
     */
    public function created(WalletDetailEntity $walletDetailEntity)
    {
        $walletId = $walletDetailEntity->wallet_id;
        $wallet = WalletEntity::find($walletId);

        $this->wallet_api_service->update(
            $walletDetailEntity->wallet_id,
            ['updated_at' => Carbon::now()->toDateTimeString()]
        );

        $walletUsers = WalletUserEntity::where('wallet_id', $walletId)
            ->with(['users'])
            ->get();

        $walletUsers->each(function (WalletUserEntity $walletUser) use ($wallet, $walletDetailEntity) {
            if ($walletUser->users && $walletUser->users->notify_token) {
                $user = $walletUser->users;
                $contents = [
                    '有一筆新的記帳資料',
                    '帳本名稱：' . $wallet->title,
                    '記帳日期：' . $walletDetailEntity->date,
                    '記帳名稱：' . $walletDetailEntity->title,
                    '記帳金額：' . number_format($walletDetailEntity->value),
                ];
                // notify
                LineNotifyJob::dispatch($user->id, implode("\r\n", $contents));
            }
        });

        return $this->wallet_api_service->forgetDetailCache($walletDetailEntity->wallet_id);
    }

    /**
     * Handle the WalletDetailEntity "updated" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletDetailEntity  $walletDetailEntity
     *
     * @return void
     */
    public function updated(WalletDetailEntity $walletDetailEntity)
    {
        //
        $this->wallet_api_service->update(
            $walletDetailEntity->wallet_id,
            ['updated_at' => Carbon::now()->toDateTimeString()]
        );
        return $this->wallet_api_service->forgetDetailCache($walletDetailEntity->wallet_id);
    }

    /**
     * Handle the WalletDetailEntity "deleted" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletDetailEntity  $walletDetailEntity
     *
     * @return void
     */
    public function deleted(WalletDetailEntity $walletDetailEntity)
    {
        //
    }

    /**
     * Handle the WalletDetailEntity "restored" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletDetailEntity  $walletDetailEntity
     *
     * @return void
     */
    public function restored(WalletDetailEntity $walletDetailEntity)
    {
        //
    }

    /**
     * Handle the WalletDetailEntity "force deleted" event.
     *
     * @param  \App\Models\Wallets\Databases\Entities\WalletDetailEntity  $walletDetailEntity
     *
     * @return void
     */
    public function forceDeleted(WalletDetailEntity $walletDetailEntity)
    {
        //
    }
}
