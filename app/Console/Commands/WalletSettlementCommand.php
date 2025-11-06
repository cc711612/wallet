<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallets\Databases\Entities\WalletEntity;
use App\Models\Wallets\Databases\Entities\WalletUserEntity;
use App\Models\Wallets\Databases\Entities\WalletDetailEntity;
use App\Models\Wallets\Databases\Entities\WalletDetailSplitEntity;
use Illuminate\Support\Collection;

class WalletSettlementCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:settlement {wallet_id : 帳本ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '計算帳本結算 - 計算每個人的總分攤金額和總代墊金額';

    /**
     * 帳本主要使用的幣別
     *
     * @var string
     */
    private $primaryUnit = 'TWD';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $walletId = $this->argument('wallet_id');
        // 檢查帳本是否存在
        $wallet = WalletEntity::find($walletId);
        if (!$wallet) {
            $this->error("帳本 ID {$walletId} 不存在！");
            return 1;
        }

        $this->info("開始計算帳本「{$wallet->title}」的結算...");
        $this->newLine();

        // 1. 獲取帳本的所有用戶
        $walletUsers = $this->getWalletUsers($walletId);
        if ($walletUsers->isEmpty()) {
            $this->warn('此帳本沒有任何用戶！');
            return 0;
        }

        // 2. 獲取帳本明細資料（排除個人記帳）
        $walletDetails = $this->getWalletDetails($walletId);
        if ($walletDetails->isEmpty()) {
            $this->warn('此帳本沒有任何非個人記帳的明細！');
            return 0;
        }

        // 3. 計算每個用戶的結算資料
        $settlementData = $this->calculateSettlement($walletUsers, $walletDetails);

        // 4. 顯示結果
        $this->displaySettlement($wallet, $settlementData);

        return 0;
    }

    /**
     * 獲取帳本的所有用戶
     *
     * @param int $walletId
     * @return Collection
     */
    private function getWalletUsers(int $walletId): Collection
    {
        return WalletUserEntity::where('wallet_id', $walletId)
            ->whereNull('deleted_at')
            ->get();
    }

    /**
     * 獲取帳本明細資料（排除個人記帳）
     *
     * @param int $walletId
     * @return Collection
     */
    private function getWalletDetails(int $walletId): Collection
    {
        return WalletDetailEntity::with(['wallet_users', 'payment_user', 'wallet_detail_splits'])
            ->where('wallet_id', $walletId)
            ->where('is_personal', false) // 排除個人記帳
            ->whereNull('deleted_at')
            ->get();
    }

    /**
     * 計算每個用戶的結算資料
     *
     * @param Collection $walletUsers
     * @param Collection $walletDetails
     * @return array
     */
    private function calculateSettlement(Collection $walletUsers, Collection $walletDetails): array
    {
        $settlement = [];
        
        // 檢查帳本主要使用的幣別
        $this->primaryUnit = $this->getPrimaryUnit($walletDetails);
        
        // 初始化每個用戶的資料
        foreach ($walletUsers as $user) {
            $settlement[$user->id] = [
                'user' => $user,
                'total_split_amount' => 0,    // 總分攤金額
                'total_payment_amount' => 0,  // 總代墊金額
                'net_amount' => 0,            // 淨額（代墊 - 分攤）
                'split_details' => [],        // 分攤明細
                'payment_details' => [],      // 代墊明細
            ];
        }

        // 計算每筆明細的分攤和代墊
        foreach ($walletDetails as $detail) {
            $this->processWalletDetail($detail, $settlement);
        }

        // 計算淨額
        foreach ($settlement as $userId => &$userSettlement) {
            $userSettlement['net_amount'] = $userSettlement['total_payment_amount'] - $userSettlement['total_split_amount'];
        }

        return $settlement;
    }

    /**
     * 處理單筆帳本明細
     *
     * @param WalletDetailEntity $detail
     * @param array &$settlement
     */
    private function processWalletDetail(WalletDetailEntity $detail, array &$settlement): void
    {
        // 處理代墊金額
        if ($detail->payment_wallet_user_id && isset($settlement[$detail->payment_wallet_user_id])) {
            $paymentAmount = $this->formatAmountByUnit($detail->value, $detail->unit);
            $settlement[$detail->payment_wallet_user_id]['total_payment_amount'] += $paymentAmount;
            $settlement[$detail->payment_wallet_user_id]['payment_details'][] = [
                'title' => $detail->title,
                'amount' => $paymentAmount,
                'date' => $detail->date,
                'unit' => $detail->unit,
            ];
        }

        // 處理分攤金額
        // 優先使用 wallet_detail_splits 表的資料，如果沒有則平分
        if ($detail->wallet_detail_splits->isNotEmpty()) {
            // 使用分帳明細表的資料
            foreach ($detail->wallet_detail_splits as $split) {
                if (isset($settlement[$split->wallet_user_id])) {
                    $splitAmount = $this->formatAmountByUnit($split->value, $split->unit);
                    $settlement[$split->wallet_user_id]['total_split_amount'] += $splitAmount;
                    $settlement[$split->wallet_user_id]['split_details'][] = [
                        'title' => $detail->title,
                        'amount' => $splitAmount,
                        'date' => $detail->date,
                        'unit' => $split->unit,
                    ];
                }
            }
        } else {
            // 沒有分帳明細，使用關聯的 wallet_users 平分
            $participantUsers = $detail->wallet_users;
            if ($participantUsers->isNotEmpty()) {
                $rawSplitAmount = $detail->value / $participantUsers->count();
                $splitAmount = $this->formatAmountByUnit($rawSplitAmount, $detail->unit, true);
                
                foreach ($participantUsers as $user) {
                    if (isset($settlement[$user->id])) {
                        $settlement[$user->id]['total_split_amount'] += $splitAmount;
                        $settlement[$user->id]['split_details'][] = [
                            'title' => $detail->title,
                            'amount' => $splitAmount,
                            'date' => $detail->date,
                            'unit' => $detail->unit,
                        ];
                    }
                }
            }
        }
    }

    /**
     * 顯示結算結果
     *
     * @param WalletEntity $wallet
     * @param array $settlementData
     */
    private function displaySettlement(WalletEntity $wallet, array $settlementData): void
    {
        $this->info("帳本「{$wallet->title}」結算結果：");
        $this->line(str_repeat('=', 80));

        $headers = ['用戶名稱', '總分攤金額', '總代墊金額', '淨額', '狀態'];
        $rows = [];

        foreach ($settlementData as $data) {
            $user = $data['user'];
            $netAmount = $data['net_amount'];
            
            // 判斷狀態
            if ($netAmount > 0) {
                $status = '應收取';
            } elseif ($netAmount < 0) {
                $status = '應支付';
            } else {
                $status = '已結清';
            }

            $rows[] = [
                $user->name,
                $this->formatDisplayAmount($data['total_split_amount']),
                $this->formatDisplayAmount($data['total_payment_amount']),
                $this->formatDisplayAmount($netAmount),
                $status,
            ];
        }

        $this->table($headers, $rows);

        // 顯示詳細資訊選項
        if ($this->confirm('是否要顯示詳細的分攤和代墊明細？', false)) {
            $this->displayDetailedSettlement($settlementData);
        }
    }

    /**
     * 顯示詳細的結算明細
     *
     * @param array $settlementData
     */
    private function displayDetailedSettlement(array $settlementData): void
    {
        foreach ($settlementData as $data) {
            $user = $data['user'];
            
            $this->newLine();
            $this->info("用戶：{$user->name}");
            $this->line(str_repeat('-', 60));

            // 顯示代墊明細
            if (!empty($data['payment_details'])) {
                $this->warn('代墊明細：');
                foreach ($data['payment_details'] as $payment) {
                    $this->line("  - {$payment['title']}: {$payment['amount']} {$payment['unit']} ({$payment['date']})");
                }
            }

            // 顯示分攤明細
            if (!empty($data['split_details'])) {
                $this->comment('分攤明細：');
                foreach ($data['split_details'] as $split) {
                    $this->line("  - {$split['title']}: {$split['amount']} {$split['unit']} ({$split['date']})");
                }
            }

            $this->line("總代墊：{$data['total_payment_amount']}");
            $this->line("總分攤：{$data['total_split_amount']}");
            $this->line("淨額：{$data['net_amount']}");
        }
    }

    /**
     * 根據幣別格式化金額
     *
     * @param float $amount
     * @param string $unit
     * @param bool $useCeil 是否使用向上取整（用於平分時）
     * @return float|int
     */
    private function formatAmountByUnit($amount, $unit, $useCeil = false)
    {
        // 使用幣別判斷，TWD 台幣轉換為整數
        $targetUnit = $unit ?? $this->primaryUnit;
        
        if (strtoupper($targetUnit) === 'TWD') {
            return $useCeil ? (int) ceil($amount) : (int) round($amount);
        }
        
        // 其他幣別保持原始精度
        return $useCeil ? ceil($amount) : $amount;
    }

    /**
     * 格式化顯示金額
     *
     * @param float|int $amount
     * @return string
     */
    private function formatDisplayAmount($amount)
    {
        // 如果是整數或者小數部分為0，顯示為整數格式
        if (is_int($amount) || fmod($amount, 1) == 0) {
            return number_format($amount, 0);
        }
        
        // 否則顯示兩位小數
        return number_format($amount, 2);
    }

    /**
     * 獲取帳本主要使用的幣別
     *
     * @param Collection $walletDetails
     * @return string
     */
    private function getPrimaryUnit(Collection $walletDetails): string
    {
        if ($walletDetails->isEmpty()) {
            return 'TWD';
        }

        // 統計各幣別出現的次數
        $unitCounts = $walletDetails->groupBy('unit')->map->count();
        
        // 返回出現次數最多的幣別
        return $unitCounts->keys()->sortByDesc(function ($unit) use ($unitCounts) {
            return $unitCounts[$unit];
        })->first() ?? 'TWD';
    }
}
