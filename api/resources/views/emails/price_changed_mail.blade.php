@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.front_url')])
{{ config('app.name')}}
@endcomponent
@endslot

<h1>Price of service manually changed</h1>
<br>
<h3>You are receiving this email because user {{$user}} has changed the price of a service.</h3>
<p>Service : {{$serviceName}} </p>
<p>Price : {{$servicePrice}} </p>
<p>New price set by {{$user}} : {{$newPrice}} </p>


{{-- Footer --}}
@slot('footer')
@component('mail::footer')
©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
@endcomponent
@endslot

@endcomponent
