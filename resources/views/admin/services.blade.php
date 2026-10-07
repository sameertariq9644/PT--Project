@extends('layouts.admin')

@section('title', 'Admin | Services')

@section('content')
    <main class="admin-main">
        <x-flash />

        <div class="admin-head">
            <div class="admin-head__title">
                <div><h1>Services</h1></div>
                <span>The plans shown on the home page, the Services page and at checkout</span>
            </div>
        </div>

        <div class="table" style="--cols: 0.5fr 1.6fr 1.4fr 1fr 0.9fr 0.9fr 0.6fr">
            <div class="table__head">
                <span>Order</span><span>Plan</span><span>Kicker</span><span>Price</span><span>Featured</span><span>Status</span><span></span>
            </div>
            @foreach ($plans as $plan)
                <div class="table__row">
                    <span class="muted">{{ $plan->sort_order }}</span>
                    <div class="person">
                        <span class="person__name">{{ $plan->name }}</span>
                        <span class="person__sub">{{ count($plan->features) }} features</span>
                    </div>
                    <span>{{ $plan->kicker }}</span>
                    <span class="strong">{{ $plan->formattedPrice() }}</span>
                    @if ($plan->is_featured)
                        <span class="status status--amber">Most chosen</span>
                    @else
                        <span class="dash">-</span>
                    @endif
                    <span class="status status--{{ $plan->is_active ? 'green' : 'grey' }}">{{ $plan->is_active ? 'Active' : 'Hidden' }}</span>
                    <a href="{{ route('admin.services.edit', $plan) }}" class="aff-view">Edit</a>
                </div>
            @endforeach
        </div>

        <p class="fineprint">A plan without a price shows as “$[-]” and cannot be bought online. The comparison table on the Services page is edited in code for now.</p>
    </main>
@endsection
