<?php

namespace App\Http\Validators\Apis\Wallets\Users;

use App\Concerns\Commons\Abstracts\ValidatorAbstracts;
use Illuminate\Validation\Rule;
use App\Concerns\Databases\Contracts\Request;
use Illuminate\Support\Arr;

/**
 * Class WalletUserUpdateValidator
 *
 * @package App\Http\Validators\Apis\Wallets\Users
 * @Author: Roy
 * @DateTime: 2022/7/4 下午 05:47
 */
class WalletUserUpdateValidator extends ValidatorAbstracts
{
    /**
     * @var \App\Concerns\Databases\Contracts\Request
     */
    protected $request;

    /**
     * @param  \App\Concerns\Databases\Contracts\Request  $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return \string[][]
     * @Author: Roy
     * @DateTime: 2022/6/20 下午 10:35
     */
    protected function rules(): array
    {
        return [
            'wallet_users.name' => [
                'required',
            ],
            'wallet_users.id' => [
                'required',
            ],
            'wallet_users_id'   => [
                'required',
                Rule::exists('wallet_users', 'id')->where(function ($query) {
                    $query->where(function ($whereQuery) {
                        $whereQuery
                            ->where('is_admin', 1)
                            ->orWhere('id', Arr::get($this->request, 'wallet_users.id'));
                    });
                }),
            ],
        ];
    }

    /**
     * @return string[]
     * @Author: Roy
     * @DateTime: 2022/6/20 下午 10:35
     */
    protected function messages(): array
    {
        return [
            'wallet_users.name.required' => '名稱為必填欄位',
            'wallet_users.id.required' => '資訊不正確',
            'wallet_users_id.required'   => '名稱無權限修改',
            'wallet_users_id.exists'   => '名稱無權限修改',
        ];
    }
}
