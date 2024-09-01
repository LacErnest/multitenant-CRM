@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.front_url')])
{{ config('app.name')}}
@endcomponent
@endslot

<h1>New Purchase Order has been created for you</h1>
<br>
<h3>You are receiving this email because purchase order {{$poNumber}} has been created for you.</h3>
<p>Please click the button below to see all your purchase orders on your profile.

@component('mail::button', ['url' => $url])
    Go to my profile
@endcomponent

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
@endcomponent
@endslot

@endcomponent
