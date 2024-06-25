<?php

namespace App\Http\Requests;

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
     * @return array
     */
    public function rules()
    {
        return [
            "name" => ["required", "string", "max:30"],
            "email" => ["required", "string", "email", "max:256", "unique:users,email"],
            "password" => ["required", "between:8, 20"],
            "password_confirmation" =>["required"]
        ];
    }

    public function messages()
    {
        return [
            "name.required" => "名前を入力してください",
            "name.string" => "名前を文字列で入力してください",
            "name.max" => "名前を30文字以下で入力してください",
            "email.required" => "メールアドレスを入力してください",
            "email.string" => 'メールアドレスを文字列で入力してください',
            "email.email" => "メールアドレス形式で入力してください",
            "email.max" => "メールアドレスは256文字以下で入力してください",
            "password.required" => "パスワードを入力してください",
            "password.between" => "パスワードは8字以上、20字以内で入力してください",
            "password_confirmation.required" => "確認用パスワードを入力してください"
        ];
    }
}
