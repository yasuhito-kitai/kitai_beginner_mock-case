@extends('layouts.app')

@section('main_content')
<div class="auth-form">
    <h2 class="section-title">ログイン</h2>
    <div class="auth-form__group">
        <form class="login-form__form" action="/login" method="post">
            @csrf
            <div class="auth-form__item">
                <input class="auth-form__input" type="email" name="email" placeholder="メールアドレス" value="{{ old('email') }}">
                <p class="error-message">
                    @error('email')
                    {{ $message }}
                    @enderror
                </p>
            </div>

            <div class="auth-form__item">
                <input class="auth-form__input" type="password" name="password" placeholder="パスワード">
                <p class="error-message">
                    @error('password')
                    {{ $message }}
                    @enderror
                </p>
            </div>

            <input class="auth-form__btn" type="submit" value="ログイン">
        </form>
    </div>
</div>

<div class="guidance">
    <p class="guidance-text">アカウントをお持ちでない方はこちらから</p>
    <a class="guidance-link" href="/register">会員登録</a>
</div>
@endsection