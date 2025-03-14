<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceDestroyRequest extends FormRequest
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
            'id' => 'required|integer|exists:devices,id',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->wallet_user->count() > 1) {
            $merge['user_id'] = $this->wallet_user->first()->user_id;
        } else {
            $merge['wallet_user_id'] = $this->wallet_user->first()->id;
        }
        $merge['id'] = $this->route('device');
        $this->merge($merge);
    }
}
