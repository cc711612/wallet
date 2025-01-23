<?php

namespace App\Http\Requests\WalletUsers;

use Illuminate\Foundation\Http\FormRequest;

class WalletUserUpdateRequest extends FormRequest
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
            'wallet_user_id' => [
                'required',
                'integer',
                'exists:wallet_users,id',
            ],
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'notify_enable' => [
                'sometimes',
                'boolean',
            ]
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'wallet_user_id' => $this->route('wallet_users_id'),
        ]);
    }
}
