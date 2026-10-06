{{-- Success message and validation errors for the session-backed (admin / portal) pages. --}}
@if (session('status'))
    <div class="flash flash--ok" role="status">
        <x-icon name="check" size="18" stroke="2.4" />
        <span>{{ session('status') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="flash flash--error" role="alert">
        <x-icon name="warning" size="18" />
        <div>
            @foreach ($errors->all() as $error)
                <span>{{ $error }}</span>
            @endforeach
        </div>
    </div>
@endif
