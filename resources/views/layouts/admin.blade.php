@extends('layouts.base')

@php
    $nav = [
        ['label' => 'Leads', 'icon' => 'users', 'route' => 'admin.leads', 'count' => $navCounts['leads']],
        ['label' => 'Orders', 'icon' => 'card', 'route' => 'admin.orders', 'count' => $navCounts['orders']],
        ['label' => 'Affiliates', 'icon' => 'link', 'route' => 'admin.affiliates'],
        ['label' => 'Services', 'icon' => 'list', 'route' => 'admin.services'],
        ['label' => 'Email templates', 'icon' => 'mail', 'route' => 'admin.email-templates'],
        ['label' => 'Settings', 'icon' => 'settings', 'route' => 'admin.settings'],
    ];
@endphp

@section('body')
    <div class="admin">
        <aside class="admin-side">
            <div class="admin-brand">
                <div class="admin-brand__mark">{{ config('site.initials') }}</div>
                <div class="admin-brand__text">
                    <span class="admin-brand__name">Admin</span>
                    <span class="admin-brand__sub">DPT to USA</span>
                </div>
            </div>
            <nav class="admin-nav">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" @class(['admin-nav__link', 'is-active' => request()->routeIs($item['route'], $item['route'].'.*')])>
                        <x-icon :name="$item['icon']" size="20" />
                        <span>{{ $item['label'] }}</span>
                        @if (! empty($item['count']))
                            <span class="admin-nav__count">{{ $item['count'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
            <div class="admin-user">
                <div class="admin-user__avatar"></div>
                <div class="admin-user__text">
                    <span class="admin-user__name">{{ auth()->user()->name }}</span>
                    <span class="admin-user__role">{{ auth()->user()->email }}</span>
                </div>
                <form method="post" action="{{ route('logout') }}" class="admin-user__logout">
                    @csrf
                    <button type="submit">Log out</button>
                </form>
            </div>
        </aside>

        @yield('content')
    </div>
@endsection
