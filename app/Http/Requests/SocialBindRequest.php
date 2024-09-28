<?php

namespace App\Http\Requests;

use App\Models\Socials\Contracts\Constants\SocialTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class SocialBindRequest extends FormRequest
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
            'token' => [
                'string',
                'required',
            ],
            'user' => [
                'required',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->user) {
            $this->merge([
                'user' => $this->user,
            ]);
        }
    }
}
