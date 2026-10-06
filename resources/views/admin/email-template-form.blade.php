@extends('layouts.admin')

@section('title', $template->exists ? 'Admin — Edit template' : 'Admin — New template')

@section('content')
    <main class="admin-main">
        <x-flash />

        <div class="admin-head">
            <div class="admin-head__title">
                <div>
                    <h1>{{ $template->exists ? 'Edit “'.$template->name.'”' : 'New email template' }}</h1>
                    @if ($template->is_system)
                        <span class="status status--dark">System</span>
                    @endif
                </div>
                <span>Write in Markdown. Click a placeholder to insert it — it is replaced with the real value when the email is sent.</span>
            </div>
            <div class="admin-head__tools">
                <a href="{{ route('admin.email-templates') }}" class="tool-btn tool-btn--link">Cancel</a>
            </div>
        </div>

        <div class="editor-layout" data-template-editor data-preview-url="{{ route('admin.email-templates.preview') }}" data-token="{{ csrf_token() }}">
            <form method="post" action="{{ $template->exists ? route('admin.email-templates.update', $template) : route('admin.email-templates.store') }}" class="panel form-panel">
                @csrf
                @if ($template->exists)
                    @method('PUT')
                @endif

                <label class="field">Template name (only you see this)
                    <input type="text" name="name" value="{{ old('name', $template->name) }}" class="input" required>
                </label>
                <label class="field">Subject
                    <input type="text" name="subject" value="{{ old('subject', $template->subject) }}" class="input" data-el="subject" required>
                </label>
                <div class="field">Placeholders
                    <div class="chips">
                        @foreach ($placeholders as $token => $description)
                            <button type="button" class="chip" data-placeholder="{{ $token }}" title="{{ $description }}">{{ $token }}</button>
                        @endforeach
                    </div>
                </div>
                <label class="field">Body (Markdown)
                    <textarea name="body" class="input input--tall" data-el="body" required>{{ old('body', $template->body) }}</textarea>
                    <span class="field__hint">**bold**, *italic*, [link text](https://example.com), blank line for a new paragraph.</span>
                </label>

                <div class="form-panel__actions">
                    <button type="submit" class="tool-btn tool-btn--dark">Save template</button>
                </div>
            </form>

            <div class="panel">
                <div class="panel__title"><h2>Preview</h2><span data-el="preview-subject">Shown with example values</span></div>
                <iframe class="email-preview" title="Email preview" sandbox data-el="preview"></iframe>
            </div>
        </div>
    </main>
@endsection
