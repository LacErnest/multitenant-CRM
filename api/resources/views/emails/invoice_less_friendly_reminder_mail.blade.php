@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.front_url')])
{{ config('app.name')}}
@endcomponent
@endslot

<h1>An invoice was due {{$days}} ago</h1>
<br>
<h3>You are receiving this email because invoice {{$invoiceNumber}} with reference '{{$invoiceRef}}' is overdue.</h3>
<p>Please take the necessary steps to pay this invoice.</p>


{{-- Footer --}}
@slot('footer')
@component('mail::footer')
©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
@endcomponent
@endslot

@endcomponent
