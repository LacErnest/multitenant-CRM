@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.front_url')])
{{ config('app.name')}}
@endcomponent
@endslot

<h1>A purchase order has been paid</h1>
<br>
<h3>Dear {{$name}}, </h3>
<p>you are receiving this email because purchase order {{$poNumber}} with an amount of {{$amount}} has been paid.

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
@endcomponent
@endslot

@endcomponent
