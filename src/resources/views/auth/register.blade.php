@extends('layouts.app')

@section('main_content')
<div class="auth-form">
    <h2 class="section-title">会員登録</h2>
    <div class="auth-form__group">
        <form class="auth-form__form" action="/register" method="post">
            @csrf
            <div class="auth-form__item">
                <input class="auth-form__input" type="text" name="name" placeholder="名前" value="{{ old('name') }}">
                <p class="error-message">
                    @error('name')
                    {{ $message }}
                    @enderror
                </p>
            </div>

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

            <div class="auth-form__item">
                <input class="auth-form__input" type="password" name="password_confirmation" placeholder="確認用パスワード">
                <p class="error-message">
                    @error('password_confirmation')
                    {{ $message }}
                    @enderror
                </p>
            </div>

            <input class="auth-form__btn" type="submit" value="会員登録">
        </form>
    </div>
</div>

<div class="guidance">
    <p class="guidance-text">アカウントをお持ちの方はこちらから</p>
    <a class="guidance-link" href="/login">ログイン</a>
</div>
@endsection