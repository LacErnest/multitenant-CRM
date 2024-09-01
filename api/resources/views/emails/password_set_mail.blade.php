@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.front_url')])
{{ config('app.name')}}
@endcomponent
@endslot

<h1>Activate Account Notification</h1>
<br>
<h3>You are receiving this mail because an account has been created for you.</h3>
<p>Please click the button below to activate your account.

@component('mail::button', ['url' => $url])
        Activate
@endcomponent

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
©{{ date('Y') }} {{ config('app.name')}} All rights reserved.
@endcomponent
@endslot

@endcomponent
