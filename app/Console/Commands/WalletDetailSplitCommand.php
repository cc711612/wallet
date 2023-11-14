<?php

namespace App\Console\Commands;

use App\Models\Wallets\Databases\Entities\WalletDetailEntity;
use Illuminate\Support\Arr;
use App\Modules\PredictionRule\Entities\PredictionRule;
use App\Modules\PredictionRule\Entities\PredictionRuleCriteria;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Models\Wallets\Databases\Entities\WalletDetailSplitEntity;

class WalletDetailSplitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:wallet_detail_splits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '執行 補 wallet_detail_splits';

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
        $details = $this->getWalletDetails();
        foreach ($details as $detail) {
            $total = $detail->value;
            $detailUserCount = $detail->wallet_users->count();
            if ($detailUserCount == 0) {
                continue;
            }
            $avgValue = ceil($total / $detailUserCount);
            $this->deleteWalletDetailSplitByWalletDetailId($detail->id);
            foreach ($detail->wallet_users as $wallet_user) {
                app(WalletDetailSplitEntity::class)->updateOrCreate([
                    'wallet_detail_id' => $detail->id,
                    'wallet_user_id'   => $wallet_user->id,
                    'unit'             => $detail->unit,
                    'value'            => $avgValue,
                ], [
                    'wallet_detail_id' => $detail->id,
                    'wallet_user_id'   => $wallet_user->id,
                    'unit'             => $detail->unit,
                    'value'            => $avgValue,
                ]);
            }
        }
    }

    public function getWalletDetails(): Collection
    {
        return app(WalletDetailEntity::class)
            ->with([
                'wallet_users',
            ])
            ->get();
    }

    public function deleteWalletDetailSplitByWalletDetailId($walletDetailId)
    {
        return app(WalletDetailSplitEntity::class)
            ->where('wallet_detail_id', $walletDetailId)
            ->delete();
    }
}
