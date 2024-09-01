@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.front_url')])
{{ config('app.name')}}
@endcomponent
@endslot

<h1>An invoice is due in 2 days</h1>
<br>
<h3>You are receiving this email as a friendly reminder that invoice {{$invoiceNumber}} with reference '{{$invoiceRef}}' is due in 2 days.</h3>
<p>Please take the necessary steps to pay this invoice. </p>


{{-- Footer --}}
@slot('footer')
@component('mail::footer')
©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
@endcomponent
@endslot

@endcomponent
