@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.front_url')])
{{ config('app.name')}}
@endcomponent
@endslot

<h1>No quote for potential customer</h1>
<br>
<h3>You are receiving this email because a potential customer '{{$customerName}}' has not received a quote yet.</h3>
<p>This is a potential customer since : {{$customerDate}}. </p>
<p>Name of primary contact : {{$contactName}}</p>
<p>Phone number of primary contact : {{$contactPhone}} </p>
<p>Email of primary contact : {{$contactEmail}} </p>


{{-- Footer --}}
@slot('footer')
@component('mail::footer')
©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
@endcomponent
@endslot

@endcomponent
