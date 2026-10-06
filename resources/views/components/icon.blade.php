@props(['name', 'size' => 24, 'stroke' => 2])

@php
    $solid = in_array($name, ['play', 'quote']);
@endphp

<svg {{ $attributes }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" aria-hidden="true"
    @if ($solid) fill="currentColor" @else fill="none" stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round" @endif>
    @switch($name)
        @case('arrow-right') <path d="M5 12h14"></path><path d="M13 6l6 6-6 6"></path> @break
        @case('arrow-up') <path d="M12 19V5"></path><path d="M6 11l6-6 6 6"></path> @break
        @case('arrow-down') <path d="M12 5v14"></path><path d="M6 13l6 6 6-6"></path> @break
        @case('play') <path d="M8 5v14l11-7z"></path> @break
        @case('quote') <path d="M7 7h4v4c0 3-1.5 5-4 6l-.5-1.2C8 15 8.6 13.8 8.7 12H7zM14 7h4v4c0 3-1.5 5-4 6l-.5-1.2c1.5-.8 2.1-2 2.2-3.8H14z"></path> @break
        @case('check') <path d="M20 6L9 17l-5-5"></path> @break
        @case('x') <path d="M18 6L6 18M6 6l12 12"></path> @break
        @case('plus') <path d="M12 5v14M5 12h14"></path> @break
        @case('minus') <path d="M5 12h14"></path> @break
        @case('user') <circle cx="12" cy="8" r="4"></circle><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"></path> @break
        @case('users') <circle cx="9" cy="8" r="3.5"></circle><path d="M2.5 20c0-3.5 3-6 6.5-6s6.5 2.5 6.5 6"></path><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14c2 .7 3.5 2.8 3.5 6"></path> @break
        @case('shield') <path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z"></path><path d="M9 12l2 2 4-4"></path> @break
        @case('form') <rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="M9 8h6M9 12h6M9 16h3"></path> @break
        @case('mail') <rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 7l9 6 9-6"></path> @break
        @case('list') <path d="M4 7h16M4 12h16M4 17h10"></path> @break
        @case('bank') <path d="M3 10l9-6 9 6"></path><path d="M5 10v8M9.5 10v8M14.5 10v8M19 10v8"></path><path d="M3 20h18"></path> @break
        @case('image') <rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="9" cy="10" r="2"></circle><path d="M21 16l-5-5-9 8"></path> @break
        @case('receipt') <rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="9" cy="9" r="2"></circle><path d="M21 15l-5-5L5 21"></path> @break
        @case('clock') <circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path> @break
        @case('lock') <rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path> @break
        @case('route') <circle cx="6" cy="19" r="2"></circle><circle cx="18" cy="5" r="2"></circle><path d="M8 19h7a3.5 3.5 0 0 0 0-7H9a3.5 3.5 0 0 1 0-7h7"></path> @break
        @case('chat') <path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"></path> @break
        @case('video') <rect x="3" y="5" width="18" height="14" rx="4"></rect><path d="M10 9l5 3-5 3z"></path> @break
        @case('warning') <path d="M12 9v4M12 17h.01"></path><path d="M10.3 3.9L2 18a2 2 0 0 0 1.7 3h16.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"></path> @break
        @case('info') <circle cx="12" cy="12" r="9"></circle><path d="M12 11v5M12 8h.01"></path> @break
        @case('copy') <rect x="9" y="9" width="11" height="11" rx="2"></rect><path d="M5 15V5a2 2 0 0 1 2-2h8"></path> @break
        @case('calendar') <rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18M8 3v4M16 3v4"></path> @break
        @case('home') <path d="M3 11l9-7 9 7"></path><path d="M5 10v10h14V10"></path> @break
        @case('card') <rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 10h18"></path> @break
        @case('link') <path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"></path><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"></path> @break
        @case('settings') <circle cx="12" cy="12" r="3"></circle><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"></path> @break
        @case('search') <circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5"></path> @break
    @endswitch
</svg>
