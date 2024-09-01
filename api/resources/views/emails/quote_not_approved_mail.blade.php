@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.front_url')])
{{ config('app.name')}}
@endcomponent
@endslot

<h1>Quote {{$quoteNumber}} not yet approved</h1>
<br>
<h3>You are receiving this email because a quote has expired, and needs your attention for approval.</h3>
<p>Please click the button below to edit this quote.

@component('mail::button', ['url' => $url])
    Edit Quote
@endcomponent

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
@endcomponent
@endslot

@endcomponent
