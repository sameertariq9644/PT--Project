@extends('layouts.admin')

@section('title', $affiliate ? 'Admin | Edit affiliate' : 'Admin | Add affiliate')

@section('content')
    <main class="admin-main">
        <x-flash />

        <div class="admin-head">
            <div class="admin-head__title">
                <div><h1>{{ $affiliate ? 'Edit '.$affiliate->user->name : 'Add affiliate' }}</h1></div>
                <span>{{ $affiliate ? 'Change their details, commission or access.' : 'You set the username and password and hand them over.' }}</span>
            </div>
            <div class="admin-head__tools">
                <a href="{{ $affiliate ? route('admin.affiliates.show', $affiliate) : route('admin.affiliates') }}" class="tool-btn tool-btn--link">Cancel</a>
            </div>
        </div>

        <form method="post" action="{{ $affiliate ? route('admin.affiliates.update', $affiliate) : route('admin.affiliates.store') }}" class="panel form-panel">
            @csrf
            @if ($affiliate)
                @method('PUT')
            @endif

            <div class="form-grid">
                <label class="field">Full name
                    <input type="text" name="name" value="{{ old('name', $affiliate?->user->name) }}" class="input" required>
                </label>
                <label class="field">Email
                    <input type="email" name="email" value="{{ old('email', $affiliate?->user->email) }}" class="input" required>
                </label>
                <label class="field">Username
                    <input type="text" name="username" value="{{ old('username', $affiliate?->user->username) }}" class="input" autocomplete="off" required>
                </label>
                <label class="field">{{ $affiliate ? 'New password (leave blank to keep the current one)' : 'Password' }}
                    <input type="text" name="password" class="input" autocomplete="off" @required(! $affiliate)>
                </label>
                <label class="field">Referral code
                    <input type="text" name="code" value="{{ old('code', $suggestedCode) }}" class="input input--upper" required>
                    <span class="field__hint">Goes in links as <code>?ref=CODE</code>. Changing it breaks links already shared.</span>
                </label>
                <label class="field">Channel (optional)
                    <input type="text" name="channel" value="{{ old('channel', $affiliate?->channel) }}" placeholder="e.g. Instagram" class="input">
                </label>
                <label class="field">Commission rate (%)
                    <input type="number" name="commission_rate" value="{{ old('commission_rate', $affiliate?->commission_rate ?? $defaultRate) }}" min="0" max="100" step="0.01" class="input" required>
                </label>
                <div class="field">Access
                    <label class="check check--box">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $affiliate?->is_active ?? true))>
                        Active, can log in and earns on new referrals
                    </label>
                </div>
            </div>

            <div class="form-panel__actions">
                <button type="submit" class="tool-btn tool-btn--dark">{{ $affiliate ? 'Save changes' : 'Create affiliate' }}</button>
            </div>
        </form>
    </main>
@endsection
