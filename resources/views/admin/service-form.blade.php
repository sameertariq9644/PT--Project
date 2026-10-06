@extends('layouts.admin')

@section('title', 'Admin — Edit '.$plan->name)

@section('content')
    <main class="admin-main">
        <x-flash />

        <div class="admin-head">
            <div class="admin-head__title">
                <div><h1>Edit {{ $plan->name }}</h1></div>
                <span>Changes appear on the public site as soon as you save.</span>
            </div>
            <div class="admin-head__tools">
                <a href="{{ route('admin.services') }}" class="tool-btn tool-btn--link">Cancel</a>
            </div>
        </div>

        <form method="post" action="{{ route('admin.services.update', $plan) }}" class="panel form-panel">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <label class="field">Name
                    <input type="text" name="name" value="{{ old('name', $plan->name) }}" class="input" required>
                </label>
                <label class="field">Kicker (small line above the name)
                    <input type="text" name="kicker" value="{{ old('kicker', $plan->kicker) }}" class="input" required>
                </label>
                <label class="field">Price in USD (leave empty to show “$[—]”)
                    <input type="number" name="price" value="{{ old('price', $plan->price) }}" min="0" step="1" class="input">
                </label>
                <label class="field">Position
                    <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}" min="0" step="1" class="input" required>
                </label>
                <label class="field field--full">Home page text
                    <textarea name="teaser" class="input input--short" required>{{ old('teaser', $plan->teaser) }}</textarea>
                </label>
                <label class="field field--full">Services page text
                    <textarea name="description" class="input input--short" required>{{ old('description', $plan->description) }}</textarea>
                </label>
                <label class="field">Home page highlights (one per line)
                    <textarea name="highlights" class="input">{{ old('highlights', implode("\n", $plan->highlights)) }}</textarea>
                </label>
                <label class="field">Features (one per line)
                    <textarea name="features" class="input">{{ old('features', implode("\n", $plan->features)) }}</textarea>
                </label>
                <div class="field">Badge
                    <label class="check check--box">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $plan->is_featured))>
                        Featured — shows the “Most chosen” badge
                    </label>
                </div>
                <div class="field">Visibility
                    <label class="check check--box">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active))>
                        Active — shown on the public site
                    </label>
                </div>
            </div>

            <div class="form-panel__actions">
                <button type="submit" class="tool-btn tool-btn--dark">Save plan</button>
            </div>
        </form>
    </main>
@endsection
