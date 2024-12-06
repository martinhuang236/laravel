<?php

namespace App\Http\Requests\Link;

use Illuminate\Foundation\Http\FormRequest;

class LinkRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'original_url' => 'required|url'
        ];
    }


    public function messages()
    {
        return [
            'original_url.required' => '请填写链接',
            'original_url.url'      => '请填写正确的链接',
        ];
    }
}
