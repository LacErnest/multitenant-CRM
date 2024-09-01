@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.front_url')])
{{ config('app.name')}}
@endcomponent
@endslot
@if(empty($tempalteBody))
<h1>Invoice submitted</h1>
<br>
<h3>You are receiving this email because invoice {{$invoiceNumber}} has been submitted.</h3>
<p>Please click the button below to see the invoice and submit it.
@else
{!! $tempalteBody !!}
@endif

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
@if(empty($templateFooter))
©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
@else
{!! $templateFooter !!}
@endif
@endcomponent
@endslot

@endcomponent
