<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceStoreRequest extends FormRequest
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
            // user_id or wallet_user_id 擇一
            'user_id' => 'required_without:wallet_user_id|integer',
            'wallet_user_id' => 'required_without:user_id|integer',
            'platform' => 'required|string',
            'device_name' => 'required|string',
            'device_type' => 'required|string',
            'fcm_token' => 'required|string',
            'expired_at' => 'required|date',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->wallet_user->count() > 1) {
            $this->merge([
                'user_id' => $this->wallet_user->first()->user_id
            ]);
        } else {
            $this->merge([
                'wallet_user_id' => $this->wallet_user->first()->id
            ]);
        }
    }
}
