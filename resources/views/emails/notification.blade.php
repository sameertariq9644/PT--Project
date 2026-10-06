@extends('emails.layout')

@section('content')
    <h1 style="margin:0 0 12px; font-family:Georgia, 'Times New Roman', serif; font-size:22px; font-weight:normal; color:#0B2530;">{{ $heading }}</h1>

    @if ($intro)
        <p style="margin:0 0 20px;">{{ $intro }}</p>
    @endif

    @if ($rows)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #EEE9DF;">
            @foreach ($rows as $label => $value)
                <tr>
                    <td style="padding:10px 12px 10px 0; border-bottom:1px solid #EEE9DF; font-size:14px; color:#52676C; vertical-align:top; width:38%;">{{ $label }}</td>
                    <td style="padding:10px 0; border-bottom:1px solid #EEE9DF; font-size:15px; color:#0B2530; font-weight:bold;">{!! nl2br(e($value)) !!}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($action)
        <p style="margin:24px 0 0;">
            <a href="{{ $action['url'] }}" style="display:inline-block; padding:12px 20px; border-radius:999px; background:#0B3B3C; color:#FFFFFF; font-size:15px; font-weight:bold; text-decoration:none;">{{ $action['label'] }}</a>
        </p>
    @endif
@endsection
