<?php

namespace App\Http\Resources;

use App\Models\Wallets\Databases\Services\WalletUserApiService;
use Firebase\JWT\JWT;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;

class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }

    public function login()
    {
        $wallet = $this->resource->wallets()->get()->sortByDesc('updated_at')->first();
        if (is_null($wallet)) {
            $wallet = collect([]);
        }
        $devices = $this->resource
            ->devices()
            ->where('expired_at', '>', now())
            ->get()
            ->sortByDesc('updated_at');

        $walletUsers = $this->resource
            ->wallet_users()
            ->get();

        $user = $this->resource;
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
            ],
        ];

        return [
            'id' => Arr::get($this->resource, 'id'),
            'name' => Arr::get($this->resource, 'name'),
            'member_token' => Arr::get($this->resource, 'token'),
            'jwt' => JWT::encode($payload, $key, 'HS256'),
            'wallet' => [
                'id' => Arr::get($wallet, 'id'),
                'code' => Arr::get($wallet, 'code'),
            ],
            'walletUsers' => WalletUserResource::collection($walletUsers),
            'devices' => DeviceResource::collection($devices),
            'notifies' => app(WalletUserApiService::class)
                ->getWalletUsers($user->id, null),
        ];
    }
}
