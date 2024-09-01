@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.front_url')])
{{ config('app.name')}}
@endcomponent
@endslot

<h1>Six months since last quote for active customer</h1>
<br>
<h3>You are receiving this email because customer '{{$customerName}}' has not received a quote since six months or more.</h3>
<p>Date of last quote : {{$quoteDate}}. </p>
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
