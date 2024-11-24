<?php

namespace App\Console\Commands;

use App\Jobs\LineNotifyJob;
use App\Models\SymbolOperationTypes\Contracts\Constants\SymbolOperationTypes;
use Illuminate\Console\Command;
use App\Models\Wallets\Contracts\Constants\WalletDetailTypes;
use App\Models\Wallets\Databases\Entities\WalletEntity;
use App\Models\Wallets\Databases\Entities\WalletUserEntity;
use Illuminate\Support\Carbon;


class AutoCalculateWalletCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:auto_calculate_wallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '排程固定時間自動結算錢包';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $userId = 58;
        $wallet = WalletEntity::where('user_id', $userId)
            ->where('title', Carbon::now()->subMonth()->format('Y.m'))
            ->with([
                'wallet_details' => function ($query) {
                    $query->where('is_personal', 0);
                },
                'wallet_users' => function ($query) {
                    $query->with([
                        'users'
                    ]);
                }
            ])
            ->first();
        if ($wallet) {
            /**
             * @var Collection
             */
            $walletDetails = $wallet->wallet_details;
            /**
             * @var Collection
             */
            $walletUsers = $wallet->wallet_users;
            $messages[] = "帳本名稱: {$wallet->title}";
            $total = 0;
            $walletGroupByTypes = $walletDetails->groupBy('type');
            $publicWalletDetailTotal = $walletGroupByTypes->get(
                WalletDetailTypes::class::WALLET_DETAIL_TYPE_PUBLIC_EXPENSE,
                collect()
            )->where('symbol_operation_type_id', SymbolOperationTypes::class::SYMBOL_OPERATION_TYPE_DECREMENT)
                ->sum('value');
            $total += $publicWalletDetailTotal;
            $messages[] = "公費總支出金額: {$publicWalletDetailTotal}";
            $privateWalletDetailGroupByPaymentUserId = $walletGroupByTypes->get(
                WalletDetailTypes::class::WALLET_DETAIL_TYPE_GENERAL_EXPENSE,
                collect()
            )->groupBy('payment_wallet_user_id');
            foreach ($walletUsers as $walletUser) {
                $messages[] = "帳本成員: {$walletUser->name}";
                $userPaymentTotal = $privateWalletDetailGroupByPaymentUserId->get($walletUser->id, collect())
                    ->where('symbol_operation_type_id', SymbolOperationTypes::class::SYMBOL_OPERATION_TYPE_DECREMENT)
                    ->sum('value');
                $total += $userPaymentTotal;
                $messages[] = "帳本成員總代墊金額: {$userPaymentTotal}";
            }

            $messages[] = "總支出金額: {$total}";
            $messages[] = "結算時間: " . Carbon::now()->format('Y-m-d H:i:s');
            $walletUsers->each(function (WalletUserEntity $walletUser) use ($messages) {
                if ($walletUser->users && $walletUser->users->notify_token) {
                    $user = $walletUser->users;
                    // notify
                    LineNotifyJob::dispatch($user->id, implode("\r\n", $messages));
                }
            });
        }
    }
}
