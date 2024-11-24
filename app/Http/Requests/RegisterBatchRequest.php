<?php

namespace App\Http\Requests;

use App\Models\Wallets\Databases\Services\WalletApiService;
use Arr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterBatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'code' => [
                'required',
                'string',
                'exists:wallets,code',
            ],
            'name.*' => [
                'required',
                'string',
                Rule::unique('wallet_users', 'name')->where(function ($query) {
                    $query->where('wallet_id', $this->wallet_id);
                }),
            ],
        ];
    }

    public function messages()
    {
        return [
            'code.required' => 'The code field is required.',
            'code.string' => 'The code field must be a string.',
            'code.exists' => 'The provided code does not exist in the wallets table.',
            'name.*.required' => 'The name field is required.',
            'name.*.string' => 'The name field must be a string.',
            'name.*.unique' => 'The name ":input" has already been taken for this wallet.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->code) {
            /**
             * @var WalletApiService $walletApiService
             */
            $walletApiService = app(WalletApiService::class);
            $wallet = $walletApiService->setRequest(['wallets' => ['code' => $this->code]])->getWalletByCode();
            if ($wallet) {
                $this->merge(['wallet_id' => $wallet->id]);
            }
        }
    }
}
