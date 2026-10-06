@extends('emails.layout')

{{--
    $html is the admin's Markdown already rendered by TemplateRenderer, with
    raw HTML stripped and every placeholder value escaped. It is printed as
    data here and is never compiled as Blade.
--}}
@section('content')
    {!! $html !!}
@endsection
