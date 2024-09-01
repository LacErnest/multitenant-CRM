@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.front_url')])
{{ config('app.name')}}
@endcomponent
@endslot

<h1>Invoice ready to be submitted</h1>
<br>
<h3>You are receiving this email because invoice {{$invoiceNumber}} has been approved, and can now be submitted.</h3>
<p>Please click the button below to see the invoice and submit it.

@component('mail::button', ['url' => $url])
    See Invoice
@endcomponent

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
@endcomponent
@endslot

@endcomponent
