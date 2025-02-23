<?php

namespace App\Jobs;

use App\Models\Devices\Databases\Services\DeviceService;
use App\Models\Users\Databases\Entities\UserEntity;
use App\Models\Wallets\Databases\Entities\WalletUserEntity;
use App\Models\Wallets\Databases\Services\WalletApiService;
use App\Models\Wallets\Databases\Services\WalletUserApiService;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificationFCM implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $walletUserId;
    private $message;
    private $walletDetailId;

    public function __construct($walletDetailId, $walletUserId, $message)
    {
        //
        $this
            ->onQueue('send_message');
        $this->walletUserId = $walletUserId;
        $this->message = $message;
        $this->walletDetailId = $walletDetailId;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $walletUser = WalletUserEntity::find($this->walletUserId);
        $devices = app(DeviceService::class)
            ->getActiveDeviceByUserId($walletUser->user_id, $walletUser->id);

        if ($devices->isEmpty()) {
            return;
        }

        $notificationUrl = config('services.notification.url');
        $requestBody = [
            'platform' => 'FCM',
            'targetId' => $this->walletDetailId,
            'platformBotId' => 'Easysplit-App',
            'platformParameters' => json_decode(
                file_get_contents(storage_path('easysplit-firebase-key.json')),
                true
            ),
            'webhookUrl' => null,
            'users' => $devices->map(function ($device) use ($walletUser) {
                return [
                    'userId' => $device->wallet_user_id,
                    'userName' => $walletUser->name,
                    'notificationId' => $device->fcm_token,
                    'messages' => [
                        [
                            'title' => 'Easysplit',
                            'body' => $this->message,
                            'icon' => 'https://easysplit.usongrat.tw/images/logo.png',
                            'click_action' => 'https://easysplit.usongrat.tw/',
                        ]
                    ]
                ];
            })->toArray(),
        ];

        try {
            $client = new Client([
                'base_uri' => $notificationUrl,
                'timeout' => 5.0,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-API-KEY' => config('services.notification.key'),
                ]
            ]);

            // 記錄請求 URL 和 BODY
            Log::info('Sending notification', [
                'url' => $notificationUrl . '/v1/firebase/batch',
                'request_body' => $requestBody,
            ]);

            $response = $client->post('/v1/firebase/batch', ['json' => $requestBody]);

            // 記錄 Response
            Log::info('Notification response', [
                'status_code' => $response->getStatusCode(),
                'response_body' => $response->getBody()->getContents(),
            ]);
        } catch (\Exception $e) {
            Log::error('Notification request failed', [
                'error_message' => $e->getMessage(),
                'url' => $notificationUrl . '/v1/firebase/batch',
                'request_body' => $requestBody,
            ]);
        }
    }
}
