@extends('layouts.base')

@section('body')
    <div class="page">
        <header class="site-header portal-header">
            <div class="wrap portal-header__inner">
                <div class="portal-header__left">
                    <a href="{{ route('affiliate.portal') }}" class="brand">
                        <div class="brand__mark">{{ config('site.initials') }}</div>
                        <span class="brand__name">{{ config('site.name') }}</span>
                    </a>
                    <span class="portal-header__tag">AFFILIATE PORTAL</span>
                </div>
                <div class="portal-header__right">
                    <a href="{{ route('contact') }}" class="portal-header__help">Help</a>
                    <a href="{{ route('affiliate.profile') }}" class="portal-user">
                        <div class="portal-user__avatar">{{ auth()->user()->initials() }}</div>
                        <span>{{ auth()->user()->name }}</span>
                    </a>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="portal-header__logout link-btn">Log out</button>
                    </form>
                </div>
            </div>
        </header>

        @yield('content')
    </div>
@endsection
