<?php

namespace App\Http\Requesters\Apis\Wallets\Details;

use App\Concerns\Databases\Request;
use App\Models\SymbolOperationTypes\Contracts\Constants\SymbolOperationTypes;
use App\Models\Wallets\Contracts\Constants\WalletDetailTypes;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class WalletDetailStoreRequest extends Request
{
    /**
     * @return null[]
     * @Author  : Roy
     * @DateTime: 2020/12/15 下午 03:02
     */
    protected function schema(): array
    {
        return [
            'wallets.id' => null,
            'wallet_users.id' => null,
            //            'wallet_details.wallet_id'                => null,
            'wallet_details.type' => WalletDetailTypes::WALLET_DETAIL_TYPE_GENERAL_EXPENSE,
            'wallet_details.payment_wallet_user_id' => null,
            'wallet_details.title' => null,
            'wallet_details.symbol_operation_type_id' => SymbolOperationTypes::SYMBOL_OPERATION_TYPE_DECREMENT,
            'wallet_details.select_all' => 0,
            'wallet_details.is_personal' => 0,
            'wallet_details.value' => 0,
            'wallet_details.date' => Carbon::now()->toDateString(),
            'wallet_details.unit' => 'TWD',
            'wallet_details.rates' => null,
            'wallet_details.splits' => [],
            'wallet_details.note' => null,
            'wallet_details.category_id' => null,
            'wallet_details.created_by' => null,
            'wallet_details.updated_by' => null,
            'wallet_detail_wallet_user' => [],
        ];
    }

    /**
     * @param $row
     *
     * @return array
     * @Author  : Roy
     * @DateTime: 2020/12/15 下午 03:02
     */
    protected function map($row): array
    {
        return [
            'wallets.id' => Arr::get($row, 'wallet'),
            'wallet_users.id' => Arr::get(
                $row,
                sprintf("wallet_user.%s.id", Arr::get($row, 'wallet'))
            ),
            'wallet_details.category_id' => Arr::get($row, 'category_id'),
            'wallet_details.type' => Arr::get($row, 'type'),
            'wallet_details.payment_wallet_user_id' => Arr::get($row, 'payment_wallet_user_id'),
            'wallet_details.title' => Arr::get($row, 'title'),
            'wallet_details.symbol_operation_type_id' => Arr::get($row, 'symbol_operation_type_id'),
            'wallet_details.select_all' => Arr::get($row, 'select_all'),
            'wallet_details.is_personal' => Arr::get($row, 'is_personal'),
            'wallet_details.value' => Arr::get($row, 'value'),
            'wallet_details.unit' => Arr::get($row, 'unit'),
            'wallet_details.date' => Arr::get($row, 'date'),
            'wallet_details.rates' => Arr::get($row, 'rates'),
            'wallet_details.splits' => Arr::get($row, 'splits'),
            'wallet_details.note' => Arr::get($row, 'note'),
            'wallet_details.created_by' => Arr::get(
                $row,
                sprintf("wallet_user.%s.id", Arr::get($row, 'wallet'))
            ),
            'wallet_details.updated_by' => Arr::get(
                $row,
                sprintf("wallet_user.%s.id", Arr::get($row, 'wallet'))
            ),
            'wallet_detail_wallet_user' => Arr::get($row, 'users'),
        ];
    }
}
