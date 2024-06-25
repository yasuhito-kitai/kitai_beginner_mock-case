<?php

use Illuminate\Support\Facades\Auth;

?>

@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/stamp.css') }}">
@endsection

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
@endsection

@section('main_content')
<h2 class="user-message"><?php $user = Auth::user(); ?>{{ $user->name }}さんお疲れ様です！</h2>


<div class="stamping-btn__group">

    <div class="div.stamping-btn__item">
        <form class="stamping-btn__form" action="/clock_in" method="post">
            @csrf
            <div class="stamping-btn__item">
                <button type="submit" class="stamping-btn__btn" {{$disabled_clock_in}}>勤務開始</button>
            </div>
        </form>
    </div>

    <div class="div.stamping-btn__item">
        <form class="stamping-btn__form" action="/clock_out" method="post">
            @method('PATCH')
            @csrf
            <div class="stamping-btn__item">
                <button type="submit" class="stamping-btn__btn" {{$disabled_clock_out}}>勤務終了</button>
            </div>
        </form>
    </div>

    <div class="div.stamping-btn__item">
        <form class="stamping-btn__form" action="/break_start" method="post">
            @csrf
            <div class="stamping-btn__item">
                <button type="submit" class="stamping-btn__btn" {{$disabled_break_start}}>休憩開始</button>
            </div>
        </form>
    </div>

    <div class="div.stamping-btn__item">
        <form class="stamping-btn__form" action="/break_end" method="post">
            @method('PATCH')
            @csrf
            <div class="stamping-btn__item">
                <button type="submit" class="stamping-btn__btn" {{$disabled_break_end}}>休憩終了</button>
            </div>
        </form>
    </div>


</div>
@endsection