@extends('layouts.admin')

@section('title', 'Admin — Email templates')

@section('content')
    <main class="admin-main">
        <x-flash />

        <div class="admin-head">
            <div class="admin-head__title">
                <div><h1>Email templates</h1></div>
                <span>The wording of every email students receive</span>
            </div>
            <div class="admin-head__tools">
                <a href="{{ route('admin.email-templates.create') }}" class="tool-btn tool-btn--dark tool-btn--link">+ New template</a>
            </div>
        </div>

        <div class="table" style="--cols: 1.3fr 2.2fr 0.8fr 0.8fr 0.9fr">
            <div class="table__head">
                <span>Template</span><span>Subject</span><span>Type</span><span>Updated</span><span></span>
            </div>
            @foreach ($templates as $template)
                <div class="table__row">
                    <span class="cell-name">{{ $template->name }}</span>
                    <span>{{ $template->subject }}</span>
                    <span class="status status--sm status--{{ $template->is_system ? 'dark' : 'grey' }}">{{ $template->is_system ? 'System' : 'Custom' }}</span>
                    <span class="muted">{{ \App\Support\SiteTime::format($template->updated_at, 'M j, Y') }}</span>
                    <div class="row-actions">
                        <a href="{{ route('admin.email-templates.edit', $template) }}" class="aff-view">Edit</a>
                        @unless ($template->is_system)
                            <form method="post" action="{{ route('admin.email-templates.destroy', $template) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="link-btn link-btn--danger" data-confirm="Delete the template “{{ $template->name }}”?">Delete</button>
                            </form>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>

        {{ $templates->links('partials.pagination') }}

        <p class="fineprint">System templates are sent automatically (lead video, order received, confirmed, rejected) and cannot be deleted. Custom templates can be sent to a lead from the lead’s page.</p>
    </main>
@endsection
