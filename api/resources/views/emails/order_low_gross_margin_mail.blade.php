@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.front_url')])
{{ config('app.name')}}
@endcomponent
@endslot

<h1>Gross margin is below {{$lowPoint}}%</h1>
<br>
<h3>You are receiving this email because the gross margin of order {{$orderNumber}} dropped below {{$lowPoint}}%.</h3>
<p>The gross margin is now at {{$markUp}}%</p>
<p>Please click the button below to see the order in detail.

@component('mail::button', ['url' => $url])
    See order
@endcomponent

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
@endcomponent
@endslot

@endcomponent
