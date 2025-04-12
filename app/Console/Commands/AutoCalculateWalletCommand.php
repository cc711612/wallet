<?php

namespace App\Console\Commands;

use App\Jobs\SendLineRemindMessage;
use Illuminate\Console\Command;
use App\Models\Wallets\Databases\Entities\WalletEntity;
use App\Models\Wallets\Databases\Services\WalletApiService;
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
    public function __construct(private WalletApiService $walletApiService)
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
            $messages = $this->walletApiService->calculateAndNotifyWalletExpenses($wallet);
            if (!empty($messages)) {
                SendLineRemindMessage::dispatch([
                    'userIds' => ['U1d40789aa8461e74ead62181b1abc442'],
                    'message' => implode("\r\n", $messages)
                ]);
            }
        }
    }
}
