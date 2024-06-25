@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify_email.css') }}">
@endsection

@section('main_content')

<div>ご登録ありがとうございます！<br>
    ご入力いただいたメールアドレスへ認証リンクを送信しましたので、クリックして認証を完了させてください。<br>
    もし、認証メールが届かない場合は再送させていただきます。
</div>

<div>
    <form action="/email/verification-notification" method="post">
        @csrf
        <input class="logout" type="submit" value="認証メールを再送信する">
    </form>
</div>
<!-- フラッシュメッセージ -->
@if (session('message'))
<div class="message">
    {{ session('message') }}
</div>
@endif
<div>
    <form action="/logout" method="post">
        @csrf
        <input class="logout" type="submit" value="ログイン画面へ戻る">
    </form>
</div>


@endsection