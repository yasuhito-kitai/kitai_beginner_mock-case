@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/users_list.css') }}">
@stop

@section('header_content')
<nav>
    <ul class="menu">
        <li class="menu-text"><a class="home" href="/stamp">ホーム</a></li>
        <li class="menu-text"><a class="date-list" href="/date">日付一覧</a></li>
        <li class="menu-text"><a class="users-list" href="/user">ユーザー一覧</a></li>
        <li class="menu-text">
            <form action="/logout" method="post">
                @csrf
                <input class="logout" type="submit" value="ログアウト">
            </form>
        </li>
    </ul>
</nav>
@stop

@section('main_content')
<div class="main-title">

</div>


<table class="users-list__table">
    <caption class="title">ユーザー一覧</caption>
    <caption><form class="search-form" action="/user_search" method="get">
        @csrf
        <div class="search-form__item">
            <input class="search-form__item-input" type="text" name="keyword" placeholder="名前 or メールアドレス">
        </div>
        <div class="search-form__button">
            <button class="search-form__button-submit" type="submit">検索</button>
        </div>
    </form>
    </caption>
    <tr class="users-list__row">
        <th class="users-list__header">ID</th>
        <th class="users-list__header">名前</th>
        <th class="users-list__header">メールアドレス</th>
    </tr>


    @foreach($user_records as $user_record)
    <tr class="users-list__row">
        <td class="users-list__data">{{$user_record->id}}</td>
        <td class="users-list__data">{{$user_record->name}}</td>
        <td class="users-list__data">{{$user_record->email}}</td>
        <form class="transition-month" action="/month" method="get">
            @csrf
            <input type="hidden" name="id" value="{{ $user_record->id }}">
            <td class="transition-month__button"><button class="transition-month__button-submit" type="submit">月別勤怠一覧</button></td>
        </form>
    </tr>
    @endforeach

</table>

{{ $user_records->links("vendor.pagination.bootstrap-4") }}

@stop