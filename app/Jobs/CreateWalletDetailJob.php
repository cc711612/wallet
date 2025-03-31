<?php

namespace App\Jobs;

use App\Models\SymbolOperationTypes\Contracts\Constants\SymbolOperationTypes;
use App\Models\Users\Databases\Entities\UserEntity;
use App\Models\Users\Databases\Services\UserApiService;
use App\Models\Wallets\Contracts\Constants\WalletDetailTypes;
use App\Notifications\LineNotify;
use Crypt;
use Firebase\JWT\JWT;
use Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateWalletDetailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private $userId,
        private $walletId,
        private $params
        )
    {
        $this->onQueue('handle_register');
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = app(UserApiService::class)->find($this->userId);
        $key = config('app.name');
        $payload = [
            'iss' => config('app.url'),
            'aud' => 'https://easysplit.usongrat.tw',
            'iat' => now()->timestamp,
            'exp' => now()->addYear()->timestamp,
            'nbf' => now()->timestamp,
            'user' => [
                'id' => Crypt::encryptString($user->id),
                'account' => $user->account,
                'name' => $user->name,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ];
        $walletUser = $user->wallet_users->where('wallet_id', $this->walletId)->first();
        $jwt = JWT::encode($payload, $key, 'HS256');
        // 呼叫 API 建立錢包
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $jwt,
            'Accept' => 'application/json',
        ])->post(route('api.wallet.detail.store',[
            'wallet' => $this->walletId,
        ]), [
            'type' => WalletDetailTypes::WALLET_DETAIL_TYPE_GENERAL_EXPENSE,
            'symbol_operation_type_id' => SymbolOperationTypes::SYMBOL_OPERATION_TYPE_DECREMENT,
            'title' => data_get($this->params, 'title'),
            'value' => data_get($this->params, 'amount'),
            'unit' => data_get($this->params, 'unit', 'TWD'),
            'select_all' => data_get($this->params, 'select_all', true),
            'payment_wallet_user_id' => $walletUser->id,
            'date' => data_get($this->params, 'date', now()->format('Y-m-d')),
            'category_id' => data_get($this->params, 'categoryId'),
        ]);
        if ($response->failed()) {
            $response = json_decode($response->body(), true);
            Log::channel('bot')->error(sprintf("%s %s", get_class($this), $response['message'] ?? ''));
        }
    }
}
