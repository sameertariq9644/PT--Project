@extends('layouts.base')

@section('title', 'Log in — '.config('site.name'))

@section('body')
    <div class="page login">
        <div class="login__card">
            <a href="{{ route('home') }}" class="brand">
                <div class="brand__mark">{{ config('site.initials') }}</div>
                <div class="brand__text">
                    <span class="brand__name">{{ config('site.name') }}</span>
                    <span class="brand__tag">{{ config('site.tagline') }}</span>
                </div>
            </a>

            <div class="login__head">
                <h1>Log in</h1>
                <p>For the admin panel and the affiliate portal.</p>
            </div>

            <x-flash />

            <form method="post" action="{{ route('login.store') }}" class="login__form">
                @csrf
                <label class="field">Username or email
                    <input type="text" name="login" value="{{ old('login') }}" class="input" autocomplete="username" autofocus required>
                </label>
                <label class="field">Password
                    <input type="password" name="password" class="input" autocomplete="current-password" required>
                </label>
                <label class="check"><input type="checkbox" name="remember" value="1">Keep me logged in on this device</label>
                <button type="submit" class="btn btn--dark login__submit">Log in</button>
            </form>

            <p class="login__help">Forgot your password? Ask the site owner to reset it for you.</p>
        </div>
    </div>
@endsection
