{!! $heading !!}

@if ($intro)
{!! $intro !!}

@endif
@foreach ($rows as $label => $value)
{!! $label !!}: {!! $value !!}
@endforeach
@if ($action)

{!! $action['label'] !!}: {!! $action['url'] !!}
@endif

--
{!! config('site.name') !!}
