<?php

namespace App\Http\Requests;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class LoginRequest extends FortifyLoginRequest
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
            "email" => ["required", "string","email", "max:256"],
            "password" => ["required", "between:8, 20"],
        ];
    }

    public function messages()
    {
        return [
            "email.required" => "メールアドレスを入力してください",
            "email.string" => 'メールアドレスを文字列で入力してください',
            "email.email" => "メールアドレス形式で入力してください",
            "email.max" => "メールアドレスは256文字以下で入力してください",
            "password.required" => "パスワードを入力してください",
            "password.between" => "パスワードは８字以上、２０字以内で入力してください"
        ];
    }
}
