<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use App\Models\Wallets\Contracts\Constants\WalletDetailTypes;
use App\Models\SymbolOperationTypes\Contracts\Constants\SymbolOperationTypes;

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
        $Wallet = $this->resource;
        $WalletDetails = $Wallet->wallet_details;
        $WalletUsers = $Wallet->wallet_users->pluck('id');
        $WalletDetailGroupBySymbolType = $WalletDetails
            ->where('type', 1)
            ->groupBy('symbol_operation_type_id');

        return [
            'wallet' => [
                'id'      => Arr::get($Wallet, 'id'),
                'code'    => Arr::get($Wallet, 'code'),
                'title'   => Arr::get($Wallet, 'title'),
                'status'   => Arr::get($Wallet, 'status'),
                'unit'   => Arr::get($Wallet, 'unit'),
                'wallet_user'    => Arr::get($Wallet, 'wallet_user_created')->first(),
                'properties'   => Arr::get($Wallet, 'properties'),
                'created_at'   => Arr::get($Wallet, 'created_at'),
                'updated_at'   => Arr::get($Wallet, 'updated_at'),
                'details' => $WalletDetails->map(function ($Detail) use ($Wallet, $WalletUsers) {
                    $Users = $Detail->wallet_users->pluck('id')->toArray();
                    # 公帳
                    if (Arr::get(
                        $Detail,
                        'type'
                    ) == WalletDetailTypes::WALLET_DETAIL_TYPE_PUBLIC_EXPENSE && is_null(Arr::get(
                        $Detail,
                        'payment_wallet_user_id'
                    )) == true) {
                        $Users = $WalletUsers;
                    }
                    return [
                        'id'                       => Arr::get($Detail, 'id'),
                        'type'                     => Arr::get($Detail, 'type'),
                        'title'                    => Arr::get($Detail, 'title'),
                        'payment_user_id'          => Arr::get($Detail, 'payment_wallet_user_id'),
                        'symbol_operation_type_id' => Arr::get($Detail, 'symbol_operation_type_id'),
                        'select_all'               => Arr::get($Detail, 'select_all') ? true : false,
                        'is_personal'               => Arr::get($Detail, 'is_personal') ? true : false,
                        'value'                    => Arr::get($Detail, 'value', 0),
                        'unit'                     => Arr::get($Detail, 'unit'),
                        'users'                    => $Users,
                        'checkout_by'              => Arr::get($Detail, 'checkout_by'),
                        'created_by'               => Arr::get($Detail, 'created_by'),
                        'updated_by'               => Arr::get($Detail, 'updated_by'),
                        'created_at'               => Arr::get($Detail, 'created_at')->toDateTimeString(),
                        'updated_at'               => Arr::get($Detail, 'updated_at')->toDateTimeString(),
                        'checkout_at'              => Arr::get($Detail, 'checkout_at'),
                        'exchange_rates' => $this->handleExchangeRates(
                            Arr::get($Detail, 'exchange_rates', []),
                            Arr::get($Detail, 'unit'),
                            Arr::get($Wallet, 'unit'),
                        ),
                        'rates'                    => Arr::get($Detail, 'rates') ? (float) Arr::get($Detail, 'rates') : null,
                        'splits'                   => Arr::get($Detail, 'splits', []),
                    ];
                })->toArray(),
                'wallet_users'   => Arr::get($Wallet, 'wallet_users', []),
                'total'   => [
                    'income'   => $WalletDetailGroupBySymbolType->get(
                        SymbolOperationTypes::SYMBOL_OPERATION_TYPE_INCREMENT,
                        collect([])
                    )->sum('value'),
                    'expenses' => $WalletDetailGroupBySymbolType->get(
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
        $Detail = $this->resource;
        return [
            'wallet' => [
                'id'            => Arr::get($requester, 'wallets.id'),
                'wallet_detail' => [
                    'id'                       => Arr::get($Detail, 'id'),
                    'type'                     => Arr::get($Detail, 'type'),
                    'payment_wallet_user_id'   => Arr::get($Detail, 'payment_wallet_user_id'),
                    'title'                    => Arr::get($Detail, 'title'),
                    'symbol_operation_type_id' => Arr::get($Detail, 'symbol_operation_type_id'),
                    'select_all'               => Arr::get($Detail, 'select_all'),
                    'is_personal'               => Arr::get($Detail, 'is_personal') ? true : false,
                    'value'                    => Arr::get($Detail, 'value'),
                    'rates'                    => Arr::get($Detail, 'rates') ? (float) Arr::get($Detail, 'rates') : null,
                    'splits'                   => Arr::get($Detail, 'splits', []),
                    'note'                     => Arr::get($Detail, 'note'),
                    'created_by'               => Arr::get($Detail, 'created_by'),
                    'checkout_by'              => Arr::get($Detail, 'checkout_by'),
                    'updated_by'               => Arr::get($Detail, 'updated_by'),
                    'updated_at'               => Arr::get($Detail, 'updated_at')->toDateTimeString(),
                    'checkout_at'              => Arr::get($Detail, 'checkout_at'),
                    'users'                    => Arr::get(
                        $Detail,
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
                'rate'        => $exchangeRate['rate'] / $baseExchangeRate['rate'],
            ];
        });

        return $exchangeRates->where('to_currency', $detailUnit)->first();
    }
}
