@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.front_url')])
{{ config('app.name')}}
@endcomponent
@endslot

<h1>Purchase order needs approval</h1>
<br>
<h3>You are receiving this email because purchase order {{$purchaseNumber}} needs your approval.</h3>
<p>The purchase order has payment terms of {{$paymentTerms}} days.
<p>Please click the button below to see the purchase order and approve it.

@component('mail::button', ['url' => $url])
    See purchase order
@endcomponent

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
@endcomponent
@endslot

@endcomponent
