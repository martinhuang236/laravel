<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name'     => 'required|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
        ];
    }

    public function messages()
    {
        return [
            'name.required'     => '请填写姓名',
            'name.max'          => '姓名字数不能超过255个字',
            'email.required'    => '请填写邮箱',
            'email.email'       => '邮箱格式错误',
            'email.unique'      => '请勿重复注册',
            'password.required' => '请填写密码',
            'password.min'      => '密码位数不能低于6位',
        ];
    }
}
