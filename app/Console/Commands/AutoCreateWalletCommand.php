<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Users\Databases\Services\UserApiService;
use App\Models\Wallets\Databases\Entities\WalletUserEntity;
use Crypt;
use Firebase\JWT\JWT;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AutoCreateWalletCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:auto_create_wallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '排程固定時間自動建立錢包';

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
        $user = app(UserApiService::class)->find($userId);
        $key = config('app.name');
        $payload = [
            'iss' => config('app.url'),
            'aud' => 'https://easysplit.usongrat.tw',
            'iat' => now()->timestamp,
            'exp' => now()->addMonth()->timestamp,
            'nbf' => now()->timestamp,
            'user' => [
                'id' => Crypt::encryptString($user->id),
                'account' => $user->account,
                'name' => $user->name,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');
        // 呼叫 API 建立錢包
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $jwt,
            'Accept' => 'application/json',
        ])->post(route('api.wallet.store'), [
            'user_id' => $userId,
            'code' => 'TWD',
            'title' => Carbon::now()->format('Y.m'),
        ]);
        $walletId = null;
        // 檢查響應並處理
        if ($response->successful()) {
            $walletId = data_get(json_decode($response->body(), 1), 'data.wallet.id');
            $this->info('Wallet created successfully.');
        } else {
            $this->error('Failed to create wallet. Response: ' . $response->body());
        }

        if ($walletId) {
            WalletUserEntity::create([
                'wallet_id' => $walletId,
                'user_id' => 1,
                'name' => 'Roy',
                'token' => Str::random(12)
            ]);
        }
    }
}
