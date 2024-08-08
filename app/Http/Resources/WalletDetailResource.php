<?php

namespace App\Http\Resources;

use App\Models\SymbolOperationTypes\Contracts\Constants\SymbolOperationTypes;
use App\Models\Wallets\Contracts\Constants\WalletDetailTypes;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class WalletDetailResource extends JsonResource
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

    /**
     * @return array[]
     * @Author: Roy
     * @DateTime: 2022/7/31 下午 11:52
     */
    public function index(): array
    {
        $wallet = $this->resource;
        $walletDetails = $wallet->wallet_details;
        $walletUsers = $wallet->wallet_users->pluck('id');
        $walletDetailGroupBySymbolType = $walletDetails
            ->where('type', 1)
            ->groupBy('symbol_operation_type_id');
        $walletCreatedUser = $wallet->wallet_users->where('is_admin', 1)->first();

        return [
            'wallet' => [
                'id' => Arr::get($wallet, 'id'),
                'code' => Arr::get($wallet, 'code'),
                'title' => Arr::get($wallet, 'title'),
                'status' => Arr::get($wallet, 'status'),
                'unit' => Arr::get($wallet, 'unit'),
                'wallet_user' => $walletCreatedUser,
                'properties' => Arr::get($wallet, 'properties'),
                'created_at' => Arr::get($wallet, 'created_at'),
                'updated_at' => Arr::get($wallet, 'updated_at'),
                'details' => $walletDetails->map(function ($detail) use ($wallet, $walletUsers) {
                    $users = $detail->wallet_users->pluck('id')->toArray();
                    # 公帳
                    if (Arr::get(
                        $detail,
                        'type'
                    ) == WalletDetailTypes::WALLET_DETAIL_TYPE_PUBLIC_EXPENSE && is_null(Arr::get(
                        $detail,
                        'payment_wallet_user_id'
                    )) == true) {
                        $users = $walletUsers;
                    }
                    return [
                        'id' => Arr::get($detail, 'id'),
                        'type' => Arr::get($detail, 'type'),
                        'title' => Arr::get($detail, 'title'),
                        'payment_user_id' => Arr::get($detail, 'payment_wallet_user_id'),
                        'symbol_operation_type_id' => Arr::get($detail, 'symbol_operation_type_id'),
                        'select_all' => Arr::get($detail, 'select_all') ? true : false,
                        'is_personal' => Arr::get($detail, 'is_personal') ? true : false,
                        'value' => Arr::get($detail, 'value', 0),
                        'unit' => Arr::get($detail, 'unit'),
                        'date' => Arr::get($detail, 'date'),
                        'users' => $users,
                        'checkout_by' => Arr::get($detail, 'checkout_by'),
                        'created_by' => Arr::get($detail, 'created_by'),
                        'updated_by' => Arr::get($detail, 'updated_by'),
                        'created_at' => Arr::get($detail, 'created_at')->toDateTimeString(),
                        'updated_at' => Arr::get($detail, 'updated_at')->toDateTimeString(),
                        'checkout_at' => Arr::get($detail, 'checkout_at'),
                        'exchange_rates' => $this->handleExchangeRates(
                            Arr::get($detail, 'exchange_rates', []),
                            Arr::get($detail, 'unit'),
                            Arr::get($wallet, 'unit'),
                        ),
                        'rates' => Arr::get($detail, 'rates') ? (float) Arr::get($detail, 'rates') : null,
                        'splits' => Arr::get($detail, 'splits', []),
                    ];
                })->toArray(),
                'wallet_users' => Arr::get($wallet, 'wallet_users', []),
                'total' => [
                    'income' => $walletDetailGroupBySymbolType->get(
                        SymbolOperationTypes::SYMBOL_OPERATION_TYPE_INCREMENT,
                        collect([])
                    )->sum('value'),
                    'expenses' => $walletDetailGroupBySymbolType->get(
                        SymbolOperationTypes::SYMBOL_OPERATION_TYPE_DECREMENT,
                        collect([])
                    )->sum('value'),
                ],
            ],
        ];
    }

    /**
     * @param $requester
     *
     * @return array[]
     * @Author: Roy
     * @DateTime: 2022/7/31 下午 11:52
     */
    public function show(array $requester): array
    {
        $detail = $this->resource;
        return [
            'wallet' => [
                'id' => Arr::get($requester, 'wallets.id'),
                'wallet_detail' => [
                    'id' => Arr::get($detail, 'id'),
                    'type' => Arr::get($detail, 'type'),
                    'payment_wallet_user_id' => Arr::get($detail, 'payment_wallet_user_id'),
                    'title' => Arr::get($detail, 'title'),
                    'symbol_operation_type_id' => Arr::get($detail, 'symbol_operation_type_id'),
                    'select_all' => Arr::get($detail, 'select_all'),
                    'is_personal' => Arr::get($detail, 'is_personal') ? true : false,
                    'value' => Arr::get($detail, 'value'),
                    'rates' => Arr::get($detail, 'rates') ? (float) Arr::get($detail, 'rates') : null,
                    'splits' => Arr::get($detail, 'splits', []),
                    'note' => Arr::get($detail, 'note'),
                    'created_by' => Arr::get($detail, 'created_by'),
                    'checkout_by' => Arr::get($detail, 'checkout_by'),
                    'updated_by' => Arr::get($detail, 'updated_by'),
                    'updated_at' => Arr::get($detail, 'updated_at')->toDateTimeString(),
                    'checkout_at' => Arr::get($detail, 'checkout_at'),
                    'users' => Arr::get(
                        $detail,
                        'wallet_users',
                        collect([])
                    )->pluck('id')->toArray(),
                ],
            ],
        ];
    }

    // 想根據不同的幣別，將USD的匯率轉換成對應的幣別
    private function handleExchangeRates($exchangeRates, $detailUnit, $walletUnit)
    {
        $baseExchangeRate = $exchangeRates->keyBy('to_currency')->get($walletUnit);
        $exchangeRates = $exchangeRates->map(function ($exchangeRate) use ($baseExchangeRate, $walletUnit) {
            return [
                'from_currency' => $walletUnit,
                'to_currency' => $exchangeRate['to_currency'],
                'rate' => $exchangeRate['rate'] / $baseExchangeRate['rate'],
            ];
        });

        return $exchangeRates->where('to_currency', $detailUnit)->first();
    }
}
